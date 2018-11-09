<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Relevamiento;
use App\Sector;
use App\Casino;
use App\DetalleRelevamiento;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use App\Maquina;
use App\MaquinaAPedido;
use App\Isla;
use App\EstadoRelevamiento;
use App\Producido;
use App\ContadorHorario;
use App\EstadoMaquina;
use App\DetalleContadorHorario;
use App\TipoCausaNoToma;
use App\DetalleProducido;
use Zipper;
use File;
use DateTime;
use App\TipoCantidadMaquinasPorRelevamiento;
use App\CantidadMaquinasPorRelevamiento;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class RelevamientoController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  private static $cant_dias_backup_relevamiento = 6;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new RelevamientoController();
    }
    return self::$instance;
  }

  //devuelve los sectores sin validar, si está vacia, esta validado
  //evalua que todos los sectores sean relevados y que los mismos esten visados
  public function estaValidado($fecha, $id_casino,$tipo_moneda){
    $relevamientos=Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
                                ->where([['fecha' , '=' , $fecha] ,['sector.id_casino' , '=' , $id_casino] ])
                                ->get();
    $errores=array();

    $cant_contador_horario = ContadorHorario::where([['fecha' , '=' , $fecha] ,
                                                    ['id_casino' , '=' , $id_casino] ,
                                                    ['id_tipo_moneda' , '=' , $tipo_moneda->id_tipo_moneda]])
                                                    ->count();
    if($cant_contador_horario != 1){
      $errores[]= 'Cantidad incorrecta de contador';
    }
    //haya relevamiento en todos los sectores
    $casino=Casino::find($id_casino);
    $sectorescount = 0;
    if($casino->id_casino == 2){
      $sec = Sector::where('id_casino','=',2)->get();
      $sectorescount = count($sec);
    }else{
      $sectorescount = $casino->sectores->count();
    }
    if($sectorescount != $relevamientos->count()){
      $errores[]= 'No todos los sectores estan relevados';

      foreach ($relevamientos as $relevamientoSector) {
        //si todos los relevamientos estan relevados y visados, entonces el estado es rel. visado = 7
          if($relevamientoSector->estado_relevamiento->id_estado_relevamiento!=7){//todos los relevamientos validados para el día. ID 4 -> validado
            $errores[]=$relevamientoSector->sector->descripcion;
          }
      }
    }
    return $errores;
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $estados = EstadoRelevamiento::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Contadores', 'relevamientos');
    return view('seccionRelevamientos', ['casinos' => $usuario->casinos ,'estados' => $estados ,'tipos_cantidad' => TipoCantidadMaquinasPorRelevamiento::all()]);
  }

  public function buscarRelevamientos(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }
    if(isset($request->fecha)){
      $reglas[]=['relevamiento.fecha', '=', $request->fecha];
    }
    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if($request->sector != 0){
      $reglas[]=['sector.id_sector', '=', $request->sector];
    }
    if($request->estadoRelevamiento != 0){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->estadoRelevamiento];
    }
    $sort_by = $request->sort_by;
    $resultados=DB::table('relevamiento')
    ->select('relevamiento.*'  , 'sector.descripcion as sector' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('sector' ,'sector.id_sector' , '=' , 'relevamiento.id_sector')
    ->join('casino' , 'sector.id_casino' , '=' , 'casino.id_casino')
    ->join('estado_relevamiento' , 'relevamiento.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->when($sort_by,function($query) use ($sort_by){
                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                })
    ->where($reglas)
    ->whereIn('casino.id_casino' , $casinos)
    ->where('backup' , '=', 0)->paginate($request->page_size);

    // foreach ($resultados as $resultado) {
    //   $resultado->fecha = strftime("%d %b %Y", strtotime($resultado->fecha));
    // }

    return $resultados;
  }

  public function obtenerRelevamiento($id_relevamiento){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy();
    $relevamiento = Relevamiento::find($id_relevamiento);

    $contador_horario_ARS = ContadorHorario::where([['fecha','=',$relevamiento->fecha],
                                                    ['id_casino','=',$relevamiento->sector->casino->id_casino],
                                                    ['id_tipo_moneda','=',1]
                                                    ])->first();
    $contador_horario_USD = ContadorHorario::where([['fecha','=',$relevamiento->fecha],
                                                  ['id_casino','=',$relevamiento->sector->casino->id_casino],
                                                  ['id_tipo_moneda','=',2]
                                                  ])->first();

    // $relevamiento->fecha = date("d-M-Y", strtotime($relevamiento->fecha));

    $detalles = Array();
    foreach($relevamiento->detalles as $det){//POR CADA MAQUINA EN EL DETALLE BUSCO FORMULA Y UNIDAD DE MEDIDA , Y CALCULO PRODUCIDO

      $posicion = new \stdClass();

      $posicion->detalle = $det;

      if($det->maquina != null){
        $posicion->formula = $det->maquina->formula;
      }
      else{
        $posicion->formula = null;
      }

      $posicion->unidad_medida = $det->maquina->unidad_medida;
      $posicion->denominacion = $det->maquina->denominacion;

      if($contador_horario_USD != null || $contador_horario_ARS != null){
        if ($contador_horario_ARS != null){//ars
          $detalle = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_ARS->id_contador_horario], ['id_maquina','=',$det->id_maquina]])->first();
          if($detalle == null && $contador_horario_USD != null){//es 2 entonces es dolares
            $detalle = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_USD->id_contador_horario], ['id_maquina','=',$det->id_maquina]])->first();
          }
        }

        if($detalle != null){
          $posicion->producido = $detalle->coinin - $detalle->coinout - $detalle->jackpot - $detalle->progresivo;//APLICO FORMULA
        }else{
          $posicion->producido = null;
        }
      }
      else{
        $posicion->producido = null;
      }

      $posicion->maquina = $det->maquina->nro_admin;
      if($det->tipo_causa_no_toma != null){
        $posicion->tipo_causa_no_toma = $det->tipo_causa_no_toma->id_tipo_causa_no_toma;
      }else{
        $posicion->tipo_causa_no_toma = null;
      }
      $detalles[] = $posicion;
    }

    return ['relevamiento' => $relevamiento,
            'casino' => $relevamiento->sector->casino->nombre,
            'id_casino' => $relevamiento->sector->casino->id_casino,
            'sector' => $relevamiento->sector->descripcion,
            'detalles' => $detalles,
            'usuario_cargador' => $relevamiento->usuario_cargador,
            'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador,
            'tipos_causa_no_toma' => TipoCausaNoToma::all(),
            'usuario_actual' => $usuario_actual];
  }

  public function existeRelevamiento($id_sector){
    Validator::make(['id_sector' => $id_sector],[
        'id_sector' => 'required|exists:sector,id_sector'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $fecha_hoy = date("Y-m-d");
    $rel_sobresescribir = Relevamiento::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'=' ,1]])->count();
    $resultados = $rel_sobresescribir > 0 ? 1 : 0;
    $rel_no_sobrescribir = Relevamiento::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'<>' ,1]])->count();
    $resultados = $rel_no_sobrescribir > 0 ? 2 : $resultados;

    return $resultados;
  }

  public function crearRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'cantidad_fiscalizadores' => 'nullable|numeric|between:1,10'
    ], array(), self::$atributos)->after(function($validator){
      $estados_rechazados = [2,3,4];
      $relevamientos = Relevamiento::where([['fecha',date("Y-m-d")],['id_sector',$validator->getData()['id_sector']],['backup',0]])->whereIn('id_estado_relevamiento' ,$estados_rechazados )->count();
      if($relevamientos > 0){
        $validator->errors()->add('relevamiento_en_carga','El Relevamiento para esa fecha ya está en carga y no se puede reemplazar.');
      }
      if ($validator->getData()['id_sector'] != 0) {
        if($validator->getData()['cantidad_fiscalizadores'] > RelevamientoController::getInstancia()->obtenerCantidadMaquinasRelevamiento($validator->getData()['id_sector'])){
          $validator->errors()->add('cantidad_maquinas','La cantidad de maquinas debe ser mayor o igual a la cantidad de fiscalizadores.');
        }
      }
    })->validate();

    $fecha_hoy = date("Y-m-d"); // fecha de hoy

    //me fijo si ya habia generados relevamientos para el dia de hoy que no sean back up, si hay los borro
    $relevamientos_viejos = Relevamiento::where([['fecha',$fecha_hoy],['id_sector',$request->id_sector],['backup',0],['id_estado_relevamiento',1]])->get();
    $id_relevamientos_viejo= array();
    foreach($relevamientos_viejos as $relevamiento){
      foreach($relevamiento->detalles as $detalle){
        $detalle->delete();
      }
      $id_relevamientos_viejo[]=$relevamiento->id_relevamiento;
      $relevamiento->delete();
    }

    $sector = Sector::find($request->id_sector);//busco las islas del sector para saber que maquinas se pueden usar
    $id_casino = $sector->casino->id_casino;
    $islas = array();
    foreach($sector->islas as $isla){
      $islas [] = $isla->id_isla;
    }

    $maquinas_a_pedido = Maquina::whereIn('id_isla',$islas)
                                ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','Reingreso');})
                                ->whereHas('maquinas_a_pedido',function($q){$q->where('fecha',date("Y-m-d"));})
                                ->get();

    $arregloMaquinaAPedido = array();
    foreach($maquinas_a_pedido as $maq){
      $arregloMaquinaAPedido[]=$maq->id_maquina;
    }
    $cantidad_maquinas = $this->obtenerCantidadMaquinasRelevamiento($request->id_sector);

    $maquinas = Maquina::whereIn('id_isla',$islas)->whereNotIn('id_maquina',$arregloMaquinaAPedido)
                       ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                       ->inRandomOrder()->take($cantidad_maquinas)->get();

    $maquinas_total = $maquinas->merge($maquinas_a_pedido);
    if($id_casino == 3){ // si es rosario ordeno por el ordne de los islotes
      $maquinas_total = $maquinas_total->sortBy(function($maquina,$key){
        //return Isla::find($maquina->id_isla)->nro_isla;
         //return Isla::find($maquina->id_isla)->orden; se quito el orden de islote, se orderana por islote y nro de isla
         $maq=Isla::find($maquina->id_isla);
         return [$maq->orden, $maq->nro_isla];
       //});
      });


    }else{
      $maquinas_total = $maquinas_total->sortBy(function($maquina,$key){
        return Isla::find($maquina->id_isla)->nro_isla;
      });
    }

    $arregloRutas = array();

    if($request->cantidad_fiscalizadores == 1){
        $relevamientos = new Relevamiento;
        $relevamientos->nro_relevamiento = DB::table('relevamiento')->max('nro_relevamiento') + 1;
        $relevamientos->fecha = $fecha_hoy;
        $relevamientos->fecha_generacion = date('Y-m-d h:i:s', time());
        $relevamientos->backup = 0;
        $fecha_generacion= $relevamientos->fecha_generacion ;
        $relevamientos->sector()->associate($sector->id_sector);
        $relevamientos->estado_relevamiento()->associate(1);
        $relevamientos->save();

        //datos para planilla backup
        $desc_sector=$relevamientos->sector->descripcion;
        $codigo_casino=$relevamientos->sector->casino->codigo;


        foreach($maquinas_total as $maq){
          $detalle = new DetalleRelevamiento;
          $detalle->id_maquina = $maq->id_maquina;
          $detalle->id_relevamiento = $relevamientos->id_relevamiento;
          $detalle->save();
        }

        $arregloRutas[] = $this->guardarPlanilla($relevamientos->id_relevamiento);
    }
    else{
        $cant_por_planilla = ceil($maquinas_total->count()/$request->cantidad_fiscalizadores);///$request->cantidad_fiscalizadores);
        $start = 0;
        $offset = 1 + $request->cantidad_fiscalizadores - (($cant_por_planilla*$request->cantidad_fiscalizadores) - $maquinas_total->count());
        for($i = 1; $i <= $request->cantidad_fiscalizadores; $i++){
          $relevamiento = new Relevamiento;
          $relevamiento->nro_relevamiento = DB::table('relevamiento')->max('nro_relevamiento') + 1;
          $relevamiento->fecha = $fecha_hoy;
          $relevamiento->fecha_generacion = date('Y-m-d h:i:s', time());
          $relevamiento->sector()->associate($sector->id_sector);
          $relevamiento->estado_relevamiento()->associate(1);
          $relevamiento->subrelevamiento = $i;
          $fecha_generacion= $relevamiento->fecha_generacion;
          $relevamiento->save();

          //datos para planilla backup
          $relevamientos[]=$relevamiento;
          $desc_sector=$relevamiento->sector->descripcion;
          $codigo_casino=$relevamiento->sector->casino->codigo;

          if($offset == $i){
            $cant_por_planilla -= 1;
          }
          $maquinas = $maquinas_total->slice($start,$cant_por_planilla);
          $start += $cant_por_planilla;

          foreach($maquinas as $maq){
            $detalle = new DetalleRelevamiento;
            $detalle->id_maquina = $maq->id_maquina;
            $detalle->id_relevamiento = $relevamiento->id_relevamiento;
            $detalle->save();
          }
          $arregloRutas[] = $this->guardarPlanilla($relevamiento->id_relevamiento);
        }
    }

    $fecha_backup = $fecha_hoy; // Armamos los relevamientos para backup
    for($i = 1; $i <= self::$cant_dias_backup_relevamiento; $i++){
      $fecha_backup = strftime("%Y-%m-%d", strtotime("$fecha_backup +1 day"));

      //me fijo si ya habia generados relevamientos backup para ese dia, si hay los borro
      $relevamientos_back = Relevamiento::where([['fecha',$fecha_backup],
                                            ['id_sector',$request->id_sector],
                                            ['backup',1],
                                            ['id_estado_relevamiento',1],
                                            ['fecha_generacion',$fecha_hoy]])->get();
      foreach($relevamientos_back as $relevamiento){
        foreach($relevamiento->detalles as $detalle){
          $detalle->delete();
        }
        $relevamiento->delete();
      }

      $relevamiento_backup = new Relevamiento;
      $relevamiento_backup->fecha = $fecha_backup;
      $relevamiento_backup->fecha_generacion = $fecha_generacion;
      $relevamiento_backup->backup = 1;
      $relevamiento_backup->sector()->associate($sector->id_sector);
      $relevamiento_backup->estado_relevamiento()->associate(1);
      $relevamiento_backup->save();

      $maquinas_a_pedido = Maquina::whereIn('id_isla',$islas)
                                  ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','Reingreso');})
                                  ->whereHas('maquinas_a_pedido',function($q) use ($fecha_backup){$q->where('fecha',$fecha_backup);})
                                  ->get();

      $arregloMaquinaAPedido = array();

      foreach($maquinas_a_pedido as $maq){
        $arregloMaquinaAPedido[]=$maq->id_maquina;
      }

      $cantidad_maquinas = $this->obtenerCantidadMaquinasRelevamiento($request->id_sector,$fecha_backup);

      $maquinas = Maquina::whereIn('id_isla',$islas)->whereNotIn('id_maquina',$arregloMaquinaAPedido)
                         ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                         ->inRandomOrder()->take($cantidad_maquinas)->get();

      $maquinas_total = $maquinas->union($maquinas_a_pedido);
      $maquinas_total = $maquinas_total->sortBy(function($maquina,$key){
        return Isla::find($maquina->id_isla)->nro_isla;
      });

      foreach($maquinas_total as $maq){
        $detalle = new DetalleRelevamiento;
        $detalle->id_maquina = $maq->id_maquina;
        $detalle->id_relevamiento = $relevamiento_backup->id_relevamiento;
        $detalle->save();
      }

      $arregloRutas[] = $this->guardarPlanilla($relevamiento_backup->id_relevamiento);
    }

    $nombreZip = 'Planillas-'.$codigo_casino
                .'-'.$desc_sector
                .'-'.$fecha_hoy.' al '.strftime("%Y-%m-%d", strtotime("$fecha_hoy +".self::$cant_dias_backup_relevamiento." day"))
                .'.zip';

    Zipper::make($nombreZip)->add($arregloRutas)->close();
    File::delete($arregloRutas);

    return ['relevamientos' => $relevamientos, // si son varios devuelve arreglo, si es uno devuelve instancia
            'cantidad_relevamientos' => $request->cantidad_fiscalizadores,
            'fecha' => strftime("%d %b %Y", strtotime($fecha_hoy)),
            'casino' => $sector->casino->nombre,
            'sector' => $sector->descripcion,
            'estado' => 'Generado',
            'id_relevamientos_viejo' => $id_relevamientos_viejo ,
            'url_zip' => 'relevamientos/descargarZip/'.$nombreZip];
  }

  public function descargarZip($nombre){

    $file = public_path() . "/" . $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);
  }

  public function cargarRelevamiento(Request $request){
    Validator::make($request->all(), [
        'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
        'id_usuario_fiscalizador' => 'required_if:estado,3|nullable|exists:usuario,id_usuario',
        'tecnico' => 'nullable|max:45',
        'observacion_carga' => 'nullable|max:200',
        'estado' => 'required|numeric|between:2,3',
        'hora_ejecucion' => 'required_if:estado,3',
        'detalles' => 'required',
        'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
        'detalles.*.cont1' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont2' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont3' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont4' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont5' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont6' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont7' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont8' => ['nullable','regex:/^\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
        'detalles.*.producido_calculado_relevado' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/']
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['estado'] == 3){
        if($validator->getData()['detalles'] != 0){
          foreach($validator->getData()['detalles'] as $index => $un_detalle) {
            if($un_detalle['id_tipo_causa_no_toma'] == null){
              if($un_detalle['producido_calculado_relevado'] == null){
                $validator->errors()->add('detalles.['.$index.'].producido_calculado_relevado','El Producido Calculado Relevado debe estar presente si el estado es 3.');
              }
            }
          }
        }
      }
    })->validate();
    $request->detalles != 0 ? $detalles = $request->detalles : $detalles = array();
    $relevamiento = Relevamiento::find($request->id_relevamiento);
    if($request->id_usuario_fiscalizador != null){
      $relevamiento->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
    }else {
      $relevamiento->usuario_fiscalizador()->dissociate();
    }
    if($relevamiento->id_usuario_cargador == null){
      $relevamiento->usuario_cargador()->associate(UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario);
    }
    if($request->estado == 2){
      $relevamiento->estado_relevamiento()->associate(2);
    }else if($request->estado == 3){
      $relevamiento->estado_relevamiento()->associate(3);
      // si cierra el relevamiento (estado == 3) me fijo en la bd y elimino todos los backup que habia para esa fecha
      $this->eliminarRelevamientosBackUp($relevamiento);
    }
    // dd(date_format($request->hora_ejecucion , 'hh:ii:ss'));
    //
    // $fecha = DateTime::createFromFormat('HH:ii', '');

    $cantidad_habilitadas = $this->calcularMTMsHabilitadas($relevamiento->sector->id_casino);
    $sin_isla = $this->calcular_sin_isla($relevamiento->sector->id_casino);

    $relevamiento->fecha_ejecucion = $request->hora_ejecucion ;
    $relevamiento->fecha_carga = date('Y-m-d h:i:s', time());
    $relevamiento->tecnico = $request->tecnico;
    $relevamiento->observacion_carga = $request->observacion_carga;
    $relevamiento->truncadas = $request->truncadas;
    $relevamiento->mtms_habilitadas_hoy = $cantidad_habilitadas;
    $relevamiento->mtms_sin_isla = $sin_isla;
    $relevamiento->save();

    foreach($detalles as $det){
      $detalle = DetalleRelevamiento::find($det['id_detalle_relevamiento']);
      $detalle->cont1 = $det['cont1'];
      $detalle->cont2 = $det['cont2'];
      $detalle->cont3 = $det['cont3'];
      $detalle->cont4 = $det['cont4'];
      $detalle->cont5 = $det['cont5'];
      $detalle->cont6 = $det['cont6'];
      $detalle->cont7 = $det['cont7'];
      $detalle->cont8 = $det['cont8'];
      $detalle->id_tipo_causa_no_toma = $det['id_tipo_causa_no_toma'];
      $detalle->producido_calculado_relevado = $det['producido_calculado_relevado'];
      $detalle->id_unidad_medida = $det['id_unidad_medida'];
      $detalle->denominacion = $det['denominacion'];
      $detalle->save();
    }

    return ['relevamiento' => $relevamiento,
            'casino' => $relevamiento->sector->casino->nombre,
            'sector' => $relevamiento->sector->descripcion,
            'estado' => $relevamiento->estado_relevamiento->descripcion,
            'detalles' => $relevamiento->detalles ,
            'esAdministrador' => AuthenticationController::getInstancia()->usuarioTienePermiso(session('id_usuario'),'relevamiento_validar')];
  }

  private function eliminarRelevamientosBackUp($relevamiento){
    $relevamientos = Relevamiento::where([['id_sector',$relevamiento->sector->id_sector],['fecha',$relevamiento->fecha],['backup',1]])->get();
    foreach($relevamientos as $rel){
      foreach($rel->detalles as $det){
        $det->delete();
      }
      $rel->delete();
    }
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
        'observacion_validacion' => 'nullable|max:200',
        'data' => 'required',///trae datos para guardar en el detalle relevamiento
    ], array(), self::$atributos)->after(function($validator){
      $rel = Relevamiento::find($validator->getData()['id_relevamiento']);
      $count = ContadorHorario::where([['id_casino',$rel->sector->id_casino],['fecha',$rel->fecha]])->count();
      //dd(ContadorHorario::where([['id_casino',$rel->sector->id_casino],['fecha',$rel->fecha]])->count());
      switch ($rel->sector->id_casino) {
        //se ignora el caso de rosario porque el tipo es "responsable"
        // case 3: // rosario
        //   if($count < 2){ // son 2 contadores, 1 por tipo de moneda
        //     $validator->errors()->add('faltan_contadores','No se puede validar el relevamiento debido a que faltan importar contadores para dicha fecha.');
        //   }
        //   break;
        case 2: // sfe - mel
          if($count < 1){ // 1 solo contador ya que no usan dolares por ahora
            $validator->errors()->add('faltan_contadores','No se puede validar el relevamiento debido a que faltan importar los contadores para dicha fecha.');
          }
          break;
      }
    })->validate();

    $relevamiento = Relevamiento::find($request->id_relevamiento);
    $relevamiento->observacion_validacion = $request->observacion_validacion;
    $relevamiento->truncadas=$request->truncadas;
    $relevamiento->estado_relevamiento()->associate(4);
    $relevamiento->save();

    ///controlo que todos esten visados para habilitar el informe
    $casino = $relevamiento->sector->casino;
    foreach($casino->sectores as $sector){
      $sectores[] = $sector->id_sector;
    }
    $fecha = $relevamiento->fecha;
    $todes = Relevamiento::where([['fecha', $fecha],['backup',0]])->whereIn('id_sector',$sectores)->count();
    $visades = Relevamiento::where([['fecha', $fecha],['backup',0],['id_estado_relevamiento',4]])->whereIn('id_sector',$sectores)->get();

    if($todes  == count($visades)){
      foreach ($visades as $vis) {
        $vis->estado_relevamiento()->associate(7);
        $vis->save();
      }
    }


    $mtm_controller = MaquinaAPedidoController::getInstancia();
    if(!empty($request->maquinas_a_pedido)){
      foreach ($request->maquinas_a_pedido as $maquina_a_pedido) {
        $mtm_controller->crearPedidoEn($maquina_a_pedido['id'] , $maquina_a_pedido['en_dias'],$request->id_relevamiento);
      }
    }


    foreach ($request['data'] as $dat) {
      $dett = DetalleRelevamiento::find($dat['id_detalle_relevamiento']);
      //dd($dat['id_detalle_relevamiento']);
      $dett->denominacion = $dat['denominacion'];
      $dett->diferencia = $dat['diferencia'];
      if(!empty($dat['importado'])){
        $dett->producido_importado = $dat['importado'];
      }
      $dett->save();
    }

    return ['relevamiento' => $relevamiento,
            'casino' => $relevamiento->sector->casino->nombre,
            'sector' => $relevamiento->sector->descripcion,
            'estado' => $relevamiento->estado_relevamiento->descripcion,
            'detalles' => $relevamiento->detalles];
  }

  public function obtenerRelevamientoVisado($id_relevamiento){

    $relevamiento = Relevamiento::find($id_relevamiento);

    ///controlo que todos esten visados para habilitar el informe
    $casino = $relevamiento->sector->casino;
    foreach($casino->sectores as $sector){
      $sectores[] = $sector->id_sector;
    }
    $detalles = array();
    foreach ($relevamiento->detalles as $det) {
      $mtm_a_pedido = $this->chequearMTMpedida($det->id_maquina, $relevamiento->id_relevamiento);

      $mtmm = Maquina::find($det->id_maquina);
      if($mtm_a_pedido != null){
        if(!empty($det->tipo_causa_no_toma)){
          $cosix = $det->tipo_causa_no_toma->descripcion;
        }else{
          $cosix = '';
        }
        $detalles[] = ['detalle' => $det,
                       'nro_admin' => $mtmm->nro_admin,
                       'mtm_a_pedido' => $mtm_a_pedido,
                       'denominacion' => $det->denominacion,
                       'tipo_no_toma' => $cosix,
                       'producido_importado' => $det->producido_importado,
                       'diferencia' => $det->diferencia,
                     ];
      }else{
        if(!empty($det->producido_importado)){
          $importt = $det->producido_importado;
        }else {
          $importt = null;
        }
        if(!empty($det->tipo_causa_no_toma)){
          $tc = $det->tipo_causa_no_toma->descripcion;
        }else {
          $tc = null;
        }
        $detalles[] = ['detalle' => $det,
                       'nro_admin' => $mtmm->nro_admin,
                       'mtm_a_pedido' => null,
                       'denominacion' => $det->denominacion,
                       'tipo_no_toma' => $tc,
                       'producido_importado' => $importt,
                       'diferencia' => $det->diferencia,];
      }
    }


    //buscar mtm que fueron pedidas

    return ['relevamiento' => $relevamiento,
            'detalles' => $detalles,
            'casino' => $relevamiento->sector->casino->nombre,
            'sector' => $relevamiento->sector->descripcion,
            'estado' => $relevamiento->estado_relevamiento->descripcion,
            'fiscalizador' => $relevamiento->usuario_fiscalizador->user_name,
            'cargador' => $relevamiento->usuario_cargador->user_name,
            ];
  }

  private function chequearMTMpedida($id_mtm, $id_relevamiento){
    return  MaquinaAPedido::where([['id_relevamiento','=', $id_relevamiento],
                                            ['id_maquina','=',$id_mtm]])->first();
  }


  private function guardarPlanilla($id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    $dompdf = $this->crearPlanilla($id_relevamiento);

    $output = $dompdf->output();

    if($relevamiento->subrelevamiento != null){
      $ruta = "Relevamiento-".$relevamiento->sector->casino->codigo."-".$relevamiento->sector->descripcion."-".$relevamiento->fecha."(".$relevamiento->subrelevamiento.")".".pdf";
    }else{
      $ruta = "Relevamiento-".$relevamiento->sector->casino->codigo."-".$relevamiento->sector->descripcion."-".$relevamiento->fecha.".pdf";
    }

    file_put_contents($ruta, $output);

    return $ruta;
  }

  public function crearPlanilla($id_relevamiento){// CREAR Y GUARDAR RELEVAMIENTO
    $relevamiento = Relevamiento::find($id_relevamiento);
    $rel= new \stdClass();
    $rel->nro_relevamiento = $relevamiento->nro_relevamiento;
    $rel->casinoCod = $relevamiento->sector->casino->codigo;
    $rel->casinoNom = $relevamiento->sector->casino->nombre;
    $rel->sector = $relevamiento->sector->descripcion;
    $rel->fecha = $relevamiento->fecha;
    $rel->fecha_ejecucion = $relevamiento->fecha_ejecucion;
    $rel->fecha_generacion = $relevamiento->fecha_generacion;

    $año = substr($rel->fecha,0,4);
    $mes = substr($rel->fecha,5,2);
    $dia = substr($rel->fecha,8,2);
    $rel->fecha = $dia."-".$mes."-".$año;

    $añoG = substr($rel->fecha_generacion,0,4);
    $mesG = substr($rel->fecha_generacion,5,2);
    $diaG = substr($rel->fecha_generacion,8,2);
    //$horaG = substr($rel->fecha_generacion,11,2).":".substr($rel->fecha_generacion,14,2).":".substr($rel->fecha_generacion,17,2);;
    $rel->fecha_generacion = $diaG."-".$mesG."-".$añoG;//." ".$horaG;
    $rel->causas_no_toma = TipoCausaNoToma::all();
    $detalles = array();
    foreach($relevamiento->detalles as $detalle){
      $det = new \stdClass();
      $det->maquina = $detalle->maquina->nro_admin;
      $det->isla = $detalle->maquina->isla->nro_isla;
      $det->sector= $detalle->maquina->isla->sector->descripcion;
      $det->marca = $detalle->maquina->marca_juego;
      $det->unidad_medida = $detalle->maquina->unidad_medida->descripcion;
      $det->formula = $detalle->maquina->formula;//abreviar nombre de contadores de formula
      if($detalle->tipo_causa_no_toma != null){
          $det->no_toma = $detalle->tipo_causa_no_toma->codigo;
      }else{
          $det->no_toma = null;
      }

      $det->cont1 = ($detalle->cont1 != null) ? number_format($detalle->cont1, 2, ",", ".") : "";
      $det->cont2 = ($detalle->cont2 != null) ? number_format($detalle->cont2, 2, ",", ".") : "";
      $det->cont3 = ($detalle->cont3 != null) ? number_format($detalle->cont3, 2, ",", ".") : "";
      $det->cont4 = ($detalle->cont4 != null) ? number_format($detalle->cont4, 2, ",", ".") : "";
      $det->cont5 = ($detalle->cont5 != null) ? number_format($detalle->cont5, 2, ",", ".") : "";
      $det->cont6 = ($detalle->cont6 != null) ? number_format($detalle->cont6, 2, ",", ".") : "";
      $det->cont7 = ($detalle->cont7 != null) ? number_format($detalle->cont7, 2, ",", ".") : "";
      $det->cont8 = ($detalle->cont8 != null) ? number_format($detalle->cont8, 2, ",", ".") : "";
      $detalles[] = $det;
    };

    // $view = View::make('planillaRelevamientosEdit', compact('detalles','rel'));

    $view = View::make('planillaRelevamientos2018', compact('detalles','rel'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("Helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 565, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha."/Generado:".$rel->fecha_generacion, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;
  }

  public function crearPlanillaValidado($id_relevamiento){// CREAR Y GUARDAR RELEVAMIENTO
    $relevamiento = Relevamiento::find($id_relevamiento);
    $casino = $relevamiento->sector->casino;
    foreach($casino->sectores as $sector){
      $sectores[] = $sector->id_sector;
    }
    $fecha = $relevamiento->fecha;
    $relevamientos = Relevamiento::where([['fecha', $fecha],['backup',0]])->whereIn('id_sector',$sectores)->get();
    $rel= new \stdClass();
    //la fecha que encesita la interfaz es la de produccion, el dia previo a la de la fecha del relevamiento
    //$rel->fecha = $relevamiento->fecha;
    $rel->fecha=date('Y-m-d',strtotime("$relevamiento->fecha -1 day"));
    $rel->casinoCod = $casino->codigo;
    $rel->casinoNom = $casino->nombre;

    $detalles = array();
    $relevadas = 0;
    $observaciones = array();
    $sumatruncadas=0;
    $detallesOK = 0;
    $no_tomadas = 0;
    $habilitadas_en_tal_fecha=0;
    $sin_isla = 0;
    $sin_contadorImportado_relevada=0;
    foreach ($relevamientos as $unRelevamiento){
      if($unRelevamiento->truncadas != null)  $sumatruncadas += $unRelevamiento->truncadas;
      $detallesOK +=  $unRelevamiento->detalles->where('diferencia','=','0.00')->count();
      $relevadas = $relevadas + $unRelevamiento->detalles->count();
      if($unRelevamiento->mtms_habilitadas_hoy != null) $habilitadas_en_tal_fecha = $unRelevamiento->mtms_habilitadas_hoy;
      if($unRelevamiento->mtm_sin_isla != null) $sin_isla = $unRelevamiento->mtm_sin_isla;

      $contador_horario_ARS = ContadorHorario::where([['fecha','=',$unRelevamiento->fecha],
                                                      ['id_casino','=',$unRelevamiento->sector->casino->id_casino],
                                                      ['id_tipo_moneda','=',1]
                                                      ])->first();

      $contador_horario_USD = ContadorHorario::where([['fecha','=',$unRelevamiento->fecha],
                                                    ['id_casino','=',$unRelevamiento->sector->casino->id_casino],
                                                    ['id_tipo_moneda','=',2]
                                                    ])->first();




      if($unRelevamiento->observacion_validacion != null){
        $observaciones[] = ['zona' => $unRelevamiento->sector->descripcion, 'observacion' =>  $unRelevamiento->observacion_validacion ];
      }
      foreach ($unRelevamiento->detalles as $detalle){
        // $producido = DetalleProducido::join('producido' , 'producido.id_producido' , '=' , 'detalle_producido.id_producido')
        //                                ->where([['detalle_producido.id_maquina' , $detalle->id_maquina] , ['fecha' , $fecha]])
        //                                ->first();
        $detalle_contador_horario = null;
        //ars
        if($contador_horario_ARS != null){
          $detalle_contador_horario = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_ARS->id_contador_horario], ['id_maquina','=',$detalle->id_maquina]])->first();
        }

          if($detalle_contador_horario == null && $contador_horario_USD != null ){
            //es 2 entonces es dolares
            $detalle_contador_horario = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_USD->id_contador_horario], ['id_maquina','=',$detalle->id_maquina]])->first();
          }
          //el contador horario puede ser null porque la mtm puede estar apagada en ese momento
          if($detalle_contador_horario == null){
            $det = new \stdClass();
            $det->producido_calculado_relevado = $detalle->producido_calculado_relevado;
            $det->nro_admin = $detalle->maquina->nro_admin;
            $det->isla = $detalle->maquina->isla->nro_isla;
            $det->sector= $detalle->maquina->isla->sector->descripcion;
            $det->producido = 0;
            if($detalle->tipo_causa_no_toma != null){
                $det->no_toma = $detalle->tipo_causa_no_toma->descripcion;
                $no_tomadas++;
            }else{
                //sino se importaron contadores pero si se relevaron los contadores de la maquina
                $sin_contadorImportado_relevada+=1;
                $det->no_toma = '---';
            }
            $check = $this->chequearMTMpedida($detalle->id_maquina, $detalle->id_relevamiento);
            if($check != null){
              $det->observacion = 'No se importaron contadores. Se pidió para el '.$check->fecha.'.';
            }else{
              $det->observacion = 'No se importaron contadores.';
            }

            $detalles[] = $det;

          }else{
            //esta recalculandolo, pero ya lo deberia tener calculado

            $producido = $detalle_contador_horario->coinin - $detalle_contador_horario->coinout - $detalle_contador_horario->jackpot - $detalle_contador_horario->progresivo;//APLICO FORMULA

            //$producido = $detalle->producido;

            $diferencia = round($detalle->producido_calculado_relevado - $producido, 2);

            if($diferencia != 0){
              $det = new \stdClass();
              $det->producido_calculado_relevado = $detalle->producido_calculado_relevado;
              $det->nro_admin = $detalle->maquina->nro_admin;
              $det->isla = $detalle->maquina->isla->nro_isla;
              $det->sector= $detalle->maquina->isla->sector->descripcion;
              $det->producido = $producido;
              if($detalle->tipo_causa_no_toma != null){
                  $det->no_toma = $detalle->tipo_causa_no_toma->descripcion;
                  $no_tomadas++;
              }else{
                  $det->no_toma = '---';
              }
              //chequearMTMpedida
              $check = $this->chequearMTMpedida($detalle->id_maquina, $detalle->id_relevamiento);
              if($check != null){
                $det->observacion = ' Se pidió para el '.$check->fecha.'.';
              }else{
                $det->observacion = '';
              }

              $detalles[] = $det;
            }
          }
      }
    }

    $rel->observaciones = $observaciones;

    $rel->referencias = TipoCausaNoToma::all();

    if(!empty($detalles)){
      $rel->detalles = $detalles;
    }
    $rel->cantidad_con_diferencia = count($detalles);
    $rel->cantidad_relevadas = $relevadas;

    $estados_habilitados = EstadoMaquina::where('descripcion' , 'Ingreso')
                                          ->orWhere('descripcion' , 'Reingreso')
                                          ->orWhere('descripcion' , 'Eventualidad Observada')
                                          ->get();
    foreach ($estados_habilitados as $key => $estado){
      $estados_habilitados[$key] = $estado->id_estado_maquina;
    }

    //dentro de un año este if va a ser innecesario, pasa que hoy 19/09 estamos agregando campos, y pueden ser nullable
    //y quizas siempre pase jaja
    if($habilitadas_en_tal_fecha == 0){
        $rel->cantidad_habilitadas = $this->calcularMTMsHabilitadas($casino->id_casino);
    }else{
      $rel->cantidad_habilitadas = $habilitadas_en_tal_fecha;
    }

    if($sin_isla == 0){
        $rel->sin_isla = $this->calcular_sin_isla($casino->id_casino);
    }else{
      $rel->sin_isla = $sin_isla;
    }

    /*los conceptos del resumen cambiaron por los siguientes:
    relevadas: la totalidad de maquinas del relevamiento
    verificadas: todas las maquinas a las que se le tomaron contadores, sin importar los errores (relevadas-no tomas)
    errores generales: aquellas que tiene la X, es decir la que dio diferencia sin considerar el truncammiento, tampoco se consideran aquellas que dieron error por falta de improtar contadores
    sin toma: persiste el concepto, todos los tipos de no toma
    la isla ya no es necesario en este informe

    */
    /*resultados antes del cambio
    $rel->truncadas = $sumatruncadas;
    $rel->verificadas = $detallesOK;
    $rel->sin_relevar = $no_tomadas;
    $rel->errores_generales = $relevadas - $sumatruncadas - $detallesOK - $no_tomadas;
    */
    $rel->truncadas = $sumatruncadas;
    $rel->verificadas = $relevadas- $no_tomadas;
    $rel->sin_relevar = $no_tomadas;
    $rel->errores_generales = $relevadas - $sumatruncadas - $detallesOK - $no_tomadas - $sin_contadorImportado_relevada;
    $rel->sin_contadorImportado_relevada=$sin_contadorImportado_relevada;
    //$rel->errores_generales = $detallesOK ;

    $view = View::make('planillaRelevamientosValidados', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4','portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica","regular");
    $dompdf->getCanvas()->page_text(515, 815,"Página {PAGE_NUM} de {PAGE_COUNT}",$font,10,array(0,0,0));
    return $dompdf;
  }

  private function calcularMTMsHabilitadas($id_casino){
    $estados_habilitados = EstadoMaquina::where('descripcion' , 'Ingreso')
                                          ->orWhere('descripcion' , 'Reingreso')
                                          ->orWhere('descripcion' , 'Eventualidad Observada')
                                          ->get();
    foreach ($estados_habilitados as $key => $estado){
      $estados_habilitados[$key] = $estado->id_estado_maquina;
    }
    return DB::table('maquina')
              ->select(DB::raw('COUNT(id_maquina) as cantidad'))
              ->join('isla','isla.id_isla','=','maquina.id_isla')
              ->where('maquina.id_casino',$id_casino)
              ->whereIn('maquina.id_estado_maquina',$estados_habilitados)
              ->whereNull('maquina.deleted_at')
              ->whereNull('isla.deleted_at')
              ->first()->cantidad;

  }

  private function calcular_sin_isla($id_casino){
    return DB::table('maquina')
              ->select(DB::raw('COUNT(id_maquina) as cantidad'))
              ->where('maquina.id_casino',$id_casino)
              ->whereNull('maquina.deleted_at')
              ->whereNull('maquina.id_isla')
              ->first()->cantidad;
  }
  public function generarPlanilla($id_relevamiento){

    $dompdf = $this->crearPlanilla($id_relevamiento);

    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  public function generarPlanillaValidado($id_relevamiento){
      $dompdf = $this->crearPlanillaValidado($id_relevamiento);

      return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));

  }

  public function usarRelevamientoBackUp(Request $request){
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'fecha' => 'required|date',
        'fecha_generacion' => 'required|date'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $relevamientos = Relevamiento::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',0]])->whereIn('id_estado_relevamiento',[1,2])->get();
    if($relevamientos != null){
      foreach($relevamientos as $relevamiento){
        $relevamiento->backup = 1;
        $relevamiento->save();
      }
    }

    $rel_backup = Relevamiento::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',1]])->whereDate('fecha_generacion','=',$request->fecha_generacion)->first();
    $rel_backup->backup = 0;
    $rel_backup->save();

    return ['id_relevamiento' => $rel_backup->id_relevamiento,
            'fecha' => $rel_backup->fecha,
            'casino' => $rel_backup->sector->casino->nombre,
            'sector' => $rel_backup->sector->descripcion,
            'estado' => $rel_backup->estado_relevamiento->descripcion];
  }

  public function buscarMaquinasSinRelevamientos(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required_with:id_sector,nro_isla|exists:casino,id_casino',
        'id_sector' => 'nullable|exists:sector,id_sector',
        'nro_isla' => 'nullable|numeric',
        'fecha_desde' => 'required|date',
        'fecha_hasta' => 'nullable|date'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['nro_isla'] != null){
        if($validator->getData()['id_sector'] != null){
          $islas = Isla::where([['id_sector',$validator->getData()['id_sector']],['nro_isla',$validator->getData()['nro_isla']]])->count();
        }else{
          $islas = Isla::where([['id_casino',$validator->getData()['id_casino']],['nro_isla',$validator->getData()['nro_isla']]])->count();
        }
        if($islas < 1){
          $validator->errors()->add('isla_no existente','No existe una isla con ese nro.');
        }
      }
    })->validate();

    $reglas = Array();
    $reglas2 = Array();

    if($request->id_casino != null){
        $reglas[] = ['casino.id_casino','=',$request->id_casino];
        $reglas2[] = ['casino.id_casino','=',$request->id_casino];
    }
    if($request->id_sector != null){
        $reglas[] = ['sector.id_sector','=',$request->id_sector];
        $reglas2[] = ['sector.id_sector','=',$request->id_sector];
    }
    if($request->nro_isla != null){
        $reglas[] = ['isla.nro_isla','=',$request->nro_isla];
        $reglas2[] = ['isla.nro_isla','=',$request->nro_isla];
    }
    if($request->fecha_desde != null){
        $reglas[] = ['relevamiento.fecha','>=',$request->fecha_desde];
    }
    if($request->fecha_hasta != null){
        $reglas[] = ['relevamiento.fecha','<=',$request->fecha_hasta];
    }
    $reglas[] = ['relevamiento.backup','=',0];

    $sort_by = $request->sort_by;

    $resultados = DB::table('maquina')->select('maquina.id_maquina as id_maquina','maquina.nro_admin as maquina','casino.nombre as casino',
                                               'sector.descripcion as sector','isla.nro_isla as isla')
                                      ->join('isla','maquina.id_isla','=','isla.id_isla')
                                      ->join('sector','isla.id_sector','=','sector.id_sector')
                                      ->join('casino','sector.id_casino','=','casino.id_casino')
                                      ->where($reglas2)
                                      ->whereNull('maquina.deleted_at')
                                      ->whereNotIn('maquina.id_maquina',function($q) use ($reglas){
                                                  $q->select('maquina.id_maquina')
                                                    ->from('maquina')
                                                    ->join('isla','maquina.id_isla','=','isla.id_isla')
                                                    ->join('sector','isla.id_sector','=','sector.id_sector')
                                                    ->join('casino','sector.id_casino','=','casino.id_casino')
                                                    ->join('detalle_relevamiento','detalle_relevamiento.id_maquina','=','maquina.id_maquina')
                                                    ->join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')
                                                    ->where($reglas);})
                                      ->when($sort_by,function($q) use ($sort_by){return $q->orderBy($sort_by['columna'],$sort_by['orden']);})
                                      ->paginate($request->page_size);

    return $resultados;
  }

  public function obtenerUltimosRelevamientosPorMaquina(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'nro_admin' => 'required|numeric',
        'cantidad_relevamientos' => 'required|numeric'
    ], array(), self::$atributos)->after(function($validator){
      $maquinas = Maquina::where([['nro_admin',$validator->getData()['nro_admin']],['id_casino',$validator->getData()['id_casino']]])->count();
      if($maquinas < 1){
        $validator->errors()->add('nro_admin','No existe una máquina con ese nro admin para ese casino.');
      }
    })->validate();

    $maquina = Maquina::where([['nro_admin',$request->nro_admin],['id_casino',$request->id_casino]])->first();
    $formula = $maquina->formula;

    $maq = new \stdClass();
    $maq->casino = $maquina->casino->nombre;
    $maq->sector = $maquina->isla->sector->descripcion;
    $maq->isla = $maquina->isla->nro_isla;
    $maq->nro_admin = $maquina->nro_admin;

    $detalles = DB::table('detalle_relevamiento')
                    ->select('relevamiento.fecha','usuario.nombre','tipo_causa_no_toma.descripcion as tipos_causa_no_toma','detalle_relevamiento.id_detalle_relevamiento',
                            'detalle_relevamiento.cont1','detalle_relevamiento.cont2','detalle_relevamiento.cont3','detalle_relevamiento.cont4',
                            'detalle_relevamiento.cont5','detalle_relevamiento.cont6','detalle_relevamiento.cont7','detalle_relevamiento.cont8',
                            'detalle_relevamiento.producido_calculado_relevado',
                            'detalle_contador_horario.coinin','detalle_contador_horario.coinout','detalle_contador_horario.jackpot','detalle_contador_horario.progresivo')
                     ->join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')
                     ->join('maquina','maquina.id_maquina','=','detalle_relevamiento.id_maquina')
                     ->join('sector','relevamiento.id_sector','=','sector.id_sector')
                     ->leftJoin('contador_horario',function ($leftJoin){
                                 $leftJoin->on('contador_horario.fecha','=','relevamiento.fecha');
                                 $leftJoin->on('contador_horario.id_casino','=','sector.id_casino');
                               })
                     ->leftJoin('detalle_contador_horario','detalle_contador_horario.id_contador_horario','=','contador_horario.id_contador_horario')
                     ->leftJoin('tipo_causa_no_toma','tipo_causa_no_toma.id_tipo_causa_no_toma','=','detalle_relevamiento.id_tipo_causa_no_toma')
                     ->join('usuario','usuario.id_usuario','=','relevamiento.id_usuario_cargador')
                     ->where('maquina.id_maquina',$maquina->id_maquina)
                     ->where('detalle_relevamiento.id_maquina',$maquina->id_maquina)
                     ->where('detalle_contador_horario.id_maquina',$maquina->id_maquina)
                     //->groupby()
                     ->distinct('relevamiento.id_relevamiento',
                               'detalle_relevamiento.id_detalle_relevamiento',
                               'usuario.id_usuario',
                               'detalle_contador_horario.id_detalle_contador_horario')
                     ->orderBy('relevamiento.fecha','desc')
                     ->take(5)->get();
    return ['maquina' => $maq,
            'formula' => $formula,
            'detalles' => $detalles];
  }

  public function obtenerCantidadMaquinasPorRelevamiento($id_sector){
    Validator::make(['id_sector' => $id_sector],
                    ['id_sector' => 'required|exists:sector,id_sector']
                    , array(), self::$atributos)->after(function($validator){
                  })->validate();

    $resultados = DB::table('cantidad_maquinas_por_relevamiento')
                      ->join('tipo_cantidad_maquinas_por_relevamiento','cantidad_maquinas_por_relevamiento.id_tipo_cantidad_maquinas_por_relevamiento'
                            ,'=','tipo_cantidad_maquinas_por_relevamiento.id_tipo_cantidad_maquinas_por_relevamiento')
                      ->where('cantidad_maquinas_por_relevamiento.id_sector','=',$id_sector)
                      ->orderBy('cantidad_maquinas_por_relevamiento.fecha_hasta','asc')
                      ->get();

    return $resultados;
  }

  public function existeCantidadTemporalMaquinas($id_sector,$fecha_desde,$fecha_hasta){
    Validator::make(['id_sector' => $id_sector,'fecha_desde' => $fecha_desde,'fecha_hasta' => $fecha_hasta],
                    ['id_sector' => 'required|exists:sector,id_sector',
                     'fecha_desde' => 'required|date|after_or_equal:today',
                     'fecha_hasta' => 'required|date|after_or_equal:fecha_desde']
                    , array(), self::$atributos)->after(function($validator){
                  })->validate();

    $cant = DB::table('cantidad_maquinas_por_relevamiento')
              ->where([['id_sector',$id_sector],['id_tipo_cantidad_maquinas_por_relevamiento',2]])
              ->where(function($q)use($fecha_desde,$fecha_hasta){
                      $q->where([['fecha_desde','>=',$fecha_desde],['fecha_desde','<=',$fecha_hasta]])
                        ->orWhere([['fecha_hasta','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]])
                        ->orWhere([['fecha_desde','<=',$fecha_desde],['fecha_hasta','>=',$fecha_hasta]])
                        ->orWhere([['fecha_desde','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]]);
              })
              ->count();

    return ['existe' => $cant > 0];
  }

  public function crearCantidadMaquinasPorRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'id_tipo_cantidad_maquinas_por_relevamiento' => 'required|exists:tipo_cantidad_maquinas_por_relevamiento,id_tipo_cantidad_maquinas_por_relevamiento',
        'cantidad_maquinas' => 'required|max:1000',
        'fecha_desde' => 'nullable',
        'fecha_hasta' => 'nullable'
    ], array(), self::$atributos)->sometimes('fecha_desde','date|after_or_equal:today', function($data){
        return $data->id_tipo_cantidad_maquinas_por_relevamiento == 2;
    })->sometimes('fecha_hasta','date|after_or_equal:fecha_desde', function($data){
        return $data->id_tipo_cantidad_maquinas_por_relevamiento == 2;
    })->after(function($validator){
    })->validate();

    if($request->id_tipo_cantidad_maquinas_por_relevamiento == 1){
      $cantidades = CantidadMaquinasPorRelevamiento::where([['id_sector',$request->id_sector],['id_tipo_cantidad_maquinas_por_relevamiento',1]])->get();
      foreach($cantidades as $cantidad){ // elimino el default que habia antes si es que habia
        $cantidad->delete();
      }
      $cantidad = new CantidadMaquinasPorRelevamiento; // creo y guardo el nuevo default
      $cantidad->id_sector = $request->id_sector;
      $cantidad->id_tipo_cantidad_maquinas_por_relevamiento = 1;
      $cantidad->cantidad = $request->cantidad_maquinas;
      $cantidad->save();
    }
    else{
      $fecha_desde = DateTime::createFromFormat('Y-m-d', $request->fecha_desde);
      $fecha_hasta = DateTime::createFromFormat('Y-m-d', $request->fecha_hasta);
      $cantidades = CantidadMaquinasPorRelevamiento::where([['id_sector',$request->id_sector],['id_tipo_cantidad_maquinas_por_relevamiento',2]])
                                                   ->where(function($q)use($fecha_desde,$fecha_hasta){
                                                              $q->where([['fecha_desde','>=',$fecha_desde],['fecha_desde','<=',$fecha_hasta]])
                                                                ->orWhere([['fecha_hasta','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]])
                                                                ->orWhere([['fecha_desde','<=',$fecha_desde],['fecha_hasta','>=',$fecha_hasta]])
                                                                ->orWhere([['fecha_desde','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]]);
                                                 })->get();

      foreach ($cantidades as $cantidad) { // modifico los que se superponen al nuevo
        if(($cantidad->fecha_desde >= $fecha_desde) && ($cantidad->fecha_hasta <= $fecha_hasta)){ // 4
          $cantidad->delete();
        }elseif(($cantidad->fecha_hasta >= $fecha_desde) && ($cantidad->fecha_hasta <= $fecha_hasta)){ // 2
          $new_date = date('Y-m-d', strtotime($cantidad->fecha_hasta));
          $new_date = date('Y-m-d', strtotime('-1 day', $new_date));
          $cantidad->fecha_hasta = $new_date;
          $cantidad->save();
        }elseif(($cantidad->fecha_desde <= $fecha_desde) && ($cantidad->fecha_hasta >= $fecha_hasta)){ // 3
          $cantidad2 = new CantidadMaquinasPorRelevamiento;
          $cantidad2->id_sector = $cantidad->id_sector;
          $cantidad2->id_tipo_cantidad_maquinas_por_relevamiento = 2;
          $cantidad2->cantidad = $cantidad->cantidad_maquinas;
          $cantidad2->fecha_hasta = $cantidad->fecha_hasta;
          $new_date = date('Y-m-d', strtotime($fecha_hasta));
          $new_date = date('Y-m-d', strtotime('+1 day', $new_date));
          $cantidad2->fecha_desde = $new_date;
          $cantidad2->save();
          $new_date = date('Y-m-d', strtotime($fecha_desde));
          $new_date = date('Y-m-d', strtotime('-1 day', $new_date));
          $cantidad->fecha_hasta = $new_date;
          $cantidad->save();
        }elseif(($cantidad->fecha_desde >= $fecha_desde) && ($cantidad->fecha_desde <= $fecha_hasta)){ // 1
          $new_date = date('Y-m-d', strtotime($fecha_hasta));
          $new_date = date('Y-m-d', strtotime($new_date + 86400 , $fecha_hasta));

          $cantidad->fecha_desde = $new_date;
          $cantidad->save();
        }
      }
      //guardo el nuevo
      $cantidad = new CantidadMaquinasPorRelevamiento;
      $cantidad->id_sector = $request->id_sector;
      $cantidad->id_tipo_cantidad_maquinas_por_relevamiento = 2;
      $cantidad->cantidad = $request->cantidad_maquinas;
      $cantidad->fecha_desde = $request->fecha_desde;
      $cantidad->fecha_hasta = $request->fecha_hasta;
      $cantidad->save();
    }

    $resultados = DB::table('cantidad_maquinas_por_relevamiento')
                      ->join('tipo_cantidad_maquinas_por_relevamiento','cantidad_maquinas_por_relevamiento.id_tipo_cantidad_maquinas_por_relevamiento'
                            ,'=','tipo_cantidad_maquinas_por_relevamiento.id_tipo_cantidad_maquinas_por_relevamiento')
                      ->where('cantidad_maquinas_por_relevamiento.id_sector','=',$request->id_sector)
                      ->orderBy('cantidad_maquinas_por_relevamiento.fecha_hasta','asc')
                      ->get();

    return $resultados;
  }

  public function obtenerCantidadMaquinasRelevamientoHoy($id_sector){
    $fecha_hoy = date("Y-m-d");
    $temporal = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',2],['id_sector',$id_sector],['fecha_desde','<=',$fecha_hoy],['fecha_hasta','>=',$fecha_hoy]])
                                                ->orderBy('fecha_hasta','desc')->first();
    if($temporal != null){
      return $temporal->cantidad;
    }else{
      $cantidad = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',1],['id_sector',$id_sector]])->first();
      return $cantidad->cantidad;
    }
  }

  public function obtenerCantidadMaquinasRelevamiento($id_sector,$fecha = null){
    $fecha = is_null($fecha) ? date("Y-m-d") : $fecha;
    $temporal = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',2],['id_sector',$id_sector],['fecha_desde','<=',$fecha],['fecha_hasta','>=',$fecha]])
                                                ->orderBy('fecha_hasta','desc')->first();
    if($temporal != null){
      return $temporal->cantidad;
    }else{
      $cantidad = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',1],['id_sector',$id_sector]])->first();
      return $cantidad->cantidad;
    }
  }

  public function obtenerCantidadMaquinasRelevamientoPorFecha($sector,$fecha){
    $temporal = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',2],['id_sector',$sector],['fecha_desde','<=',$fecha],['fecha_hasta','>=',$fecha]])
                                                ->orderBy('fecha_hasta','desc')->first();
    if($temporal != null){
      return $temporal->cantidad;
    }else{
      $cantidad = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',1],['id_sector',$sector]])->first();
      return $cantidad->cantidad;
    }
  }

  public function eliminarCantidadMaquinasPorRelevamiento(Request $request){
    return CantidadMaquinasPorRelevamiento::destroy($request->id_cantidad_maquinas_por_relevamiento);
  }

  public function modificarDenominacionYUnidad(Request $request){
    Validator::make($request->all(),[
        'id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
        'denominacidon' => ['nullable','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
        'id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida'
    ], array(), self::$atributos)->after(function($validator){

    })->validate();

    $detalle_relevamiento = DetalleRelevamiento::find($request->id_detalle_relevamiento);
    $maquina = MTMController::getInstancia()->modificarDenominacionYUnidad($request->id_unidad_medida ,$request->denominacion , $detalle_relevamiento->id_maquina);
    $fecha = $detalle_relevamiento->relevamiento->fecha;
    $id_maquina = $maquina->id_maquina;

    $detalles = DetalleRelevamiento::join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')->where([['relevamiento.fecha' , '>=' , $fecha] , ['detalle_relevamiento.id_maquina' , '=' , $id_maquina]])->whereNotNull('detalle_relevamiento.producido_calculado_relevado')->get();

    $formula = $maquina->formula;

    if($maquina->id_unidad_medida == 1){

      foreach ($detalles  as $un_detalle) {
          $calculado = $this->calcularDiferencia($un_detalle, $formula);
          $un_detalle->producido_calculado_relevado = round($calculado*$maquina->denominacion , 2);
          $un_detalle->id_unidad_medida = 1;//guardo con que unidad de medida hice elcalculo para ese detalle, porque puede cambiar
          $un_detalle->save();
      }
    }else{
      foreach ($detalles  as $un_detalle) {
          $un_detalle->producido_calculado_relevado = $this->calcularDiferencia($un_detalle, $formula);
          $un_detalle->id_unidad_medida = 2;
          $un_detalle->save();
      }

    }

    $detalle_relevamiento = DetalleRelevamiento::find($request->id_detalle_relevamiento);//re busco para obtener valor actualizado

    return ['producido_calculado_relevado' => $detalle_relevamiento->producido_calculado_relevado];

  }

  public function calcularDiferencia($detalle_relevamiento, $formula){ //aplica formula a contadores relevados, ya sean creidots o pesos, esta tranformacion deberia hacerse despues del calculo
    $arreglo = array();
    $i = 1;
    $calculado = $detalle_relevamiento->cont1;
    while($i <=  8) {
      if($formula['operador' . $i] != null){
        switch ($formula['operador'.$i]) {
          case '-':
            if($detalle_relevamiento['cont' . ($i + 1)] != null){
              $contador = $detalle_relevamiento['cont' . ($i + 1)];
            }else {
              $contador=0;
            }
            $calculado = $calculado - $contador;
            break;
          case '+':
            if($detalle_relevamiento['cont' . ($i + 1)] != null){
              $contador = $detalle_relevamiento['cont' . ($i + 1)];
            }else {
              $contador=0;
            }
            $calculado = $calculado + $contador;
            break;
          default:
            break;
        }
        $i++;
      }else{
        $i=10;
      }
    }

    return $calculado;
  }

  public function estaRelevadoMaquina($fecha,$id_maquina){
    //OBTENGO EL ESTADO DE RELEVAMIENTO DE UNA MAQUINA
    //si tiene un detalle relevamiento para cierta fecha es porque salio sorteado
    $detalles = DetalleRelevamiento::join('relevamiento' , 'detalle_relevamiento.id_relevamiento' , '=' , 'relevamiento.id_relevamiento')
                     ->where([['detalle_relevamiento.id_maquina', $id_maquina],['relevamiento.fecha', $fecha]])
                     ->get();
    if($detalles->count() == 1){
          $validado = $detalles[0]->relevamiento->estado_relevamiento->descripcion == "Validado";
          $relevado = in_array($detalles[0]->relevamiento->estado_relevamiento->descripcion , array('Validado','Finalizado')) ;//in_array es sensible a mayus y min
          $detalle = $detalles[0];
    }else {
            $validado = 0;
            $relevado = 0;
            $detalle= null;
    }

    return ['relevado' => $relevado, 'validado' => $validado, 'detalle' => $detalle];
  }

  public function agregarTresPuntosFormula($formula){ // abrevia los nombres de las formulas para que se vean mejor en la planilla
    $formula_nueva = new \stdClass();
    $formula_nueva->cont1  =  strlen($formula->cont1) > 33 ? substr($formula->cont1,0, 33) . '...' :  $formula->cont1;
    $formula_nueva->cont2  = strlen($formula->cont2) > 33 ? substr($formula->cont2,0, 33) . '...' :  $formula->cont2;
    $formula_nueva->cont3  = strlen($formula->cont3) > 33 ? substr($formula->cont3,0, 33) . '...' :  $formula->cont3;
    $formula_nueva->cont4  = strlen($formula->cont4) > 33 ? substr($formula->cont4,0, 33) . '...' :  $formula->cont4;
    $formula_nueva->cont5  = strlen($formula->cont5) > 33 ? substr($formula->cont5,0, 33) . '...' :  $formula->cont5;
    $formula_nueva->cont6  = strlen($formula->cont6) > 33 ? substr($formula->cont6,0, 33) . '...' :  $formula->cont6;
    return $formula_nueva;
  }

  public function existeRelVisado($fecha, $id_casino){

    $relevamientoVisado=Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
    ->where([['fecha' , '=' , $fecha] ,['sector.id_casino' , '=' , $id_casino] ,['id_estado_relevamiento','=',4]])
    ->orwhere([['fecha' , '=' , $fecha] ,['sector.id_casino' , '=' , $id_casino] ,['id_estado_relevamiento','=',7]])
    ->get();

    if(count($relevamientoVisado)>0){
      return true;

    }
    return false;
  }

}
