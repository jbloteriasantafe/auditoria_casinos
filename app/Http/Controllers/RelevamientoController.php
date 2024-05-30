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
use App\Formula;
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
use ProgresivoController;
use App\Http\Controllers\ProducidoController;
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

  //Verifica que todos los sectores esten validados para un relevamiento en una fecha,
  //En principio no podria darse la situacion que esten todos los sectores esten en estado "4" (Visado)
  //Porque automaticamente los pasa a 7 (Rel Visado), pero si se fuese a dar lo consideramos igualmente de correcto
  //Si esta todo bien, retorna un arreglo vacio.
  public function estaValidado($fecha, $id_casino,$tipo_moneda){
    $relevamientos_validados=Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
                                ->where([
                                  ['fecha' , '=' , $fecha] ,
                                  ['sector.id_casino' , '=' , $id_casino],
                                  ['backup','=',0]
                                ])
                                ->whereIn('id_estado_relevamiento',[4,7])
                                ->get();
    $errores=array();

    //No estoy seguro pq hace esto aca, pero verifica que tenga contadores importados tambien.
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

    if($sectorescount != $relevamientos_validados->count()){
      $errores[]= 'No todos los sectores estan relevados o validados';
      $sin_validar  = Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
      ->where([
        ['fecha' , '=' , $fecha] ,
        ['sector.id_casino' , '=' , $id_casino],
        ['backup','=',0]
      ])
      ->whereNotIn('id_estado_relevamiento',[4,7])
      ->get();
      foreach ($sin_validar as $sv) {
        $errores[]=[$sv->id_relevamiento,$sv->sector->descripcion];
      }
    }

    return $errores;
  }
  
  private function find_columns($table,$count_str){
    $arr   = \Schema::getColumnListing($table);
    $regex = '/^'.$count_str.'\d+$/';
    return collect($arr)->filter(function($s) use ($regex){
      return preg_match($regex,$s);
    })->keyBy(function($s) use ($count_str){
      return intval(substr($s,strlen($count_str)));
    })->sortBy(function($s,$k){
      return $k;
    });
  }
  
  private function contadores(){
    return $this->find_columns((new DetalleRelevamiento)->getTableName(),'cont');
  }
  private function contadores_formula(){
    return $this->find_columns((new Formula)->getTableName(),'cont');
  }
  private function operadores_formula(){
    return $this->find_columns((new Formula)->getTableName(),'operador');
  }
  
  public function __construct(){
    $CONTADORES = $this->contadores();
    $CONTADORES_F = $this->contadores_formula();
    $OPERADORES_F = $this->operadores_formula();
    assert(count($CONTADORES) == count($CONTADORES_F));
    assert(count($CONTADORES) == (count($OPERADORES_F)-1));
    for($k=1;$k<=count($CONTADORES);$k++){
      assert($CONTADORES->has($k));
      assert($CONTADORES_F->has($k));
      if($k != count($CONTADORES)){
        assert($OPERADORES_f->has($k));
      }
    }
  }

  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $estados = EstadoRelevamiento::all();

    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Contadores', 'relevamientos');
        
    return view('Relevamientos/index', [
      'casinos' => $usuario->casinos ,
      'estados' => $estados ,
      'tipos_cantidad' => TipoCantidadMaquinasPorRelevamiento::all(),
      'tipos_causa_no_toma' => TipoCausaNoToma::all(),
      'CONTADORES' => $this->contadores()->count(),
      'TRUNCAMIENTO' => ProducidoController::getInstancia()->truncamiento()
    ]);
  }

  // buscarRelevamientos busca relevamientos de acuerdo a los filtros
  public function buscarRelevamientos(Request $request){
    $reglas = [];
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']
    ->casinos->map(function($c){ return $c->id_casino; })->toArray();
    
    if(isset($request->fecha)){
      $reglas[]=['relevamiento.fecha', '=', $request->fecha];
    }
    if(!empty($request->casino)){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if(!empty($request->sector)){
      $reglas[]=['sector.id_sector', '=', $request->sector];
    }
    if(!empty($request->estadoRelevamiento)){
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

    return $resultados;
  }
  // obtenerRelevamiento
  public function obtenerRelevamiento($id_relevamiento){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $relevamiento = Relevamiento::find($id_relevamiento);
    
    if(!$usuario_actual->casinos->pluck('id_casino')->contains($relevamiento->sector->id_casino)){
      return [];
    }
    
    $detalles = $relevamiento->detalles->map(function($det){//POR CADA MAQUINA EN EL DETALLE BUSCO FORMULA Y UNIDAD DE MEDIDA , Y CALCULO PRODUCIDO
      $d = new \stdClass();
      $d->detalle = $det;
      
      $m = $det->maquina()->withTrashed()->first();
      if(is_null($m)) return null;
      
      $d->formula       = $m->formula;
      $d->unidad_medida = $m->unidad_medida;
      $d->denominacion  = $m->denominacion;
      $d->maquina       = $m->nro_admin;
      $d->producido     = $det->producido_importado;
      $d->tipo_causa_no_toma = is_null($det->tipo_causa_no_toma)? null : $det->tipo_causa_no_toma->id_tipo_causa_no_toma;
      return $d;
    })->filter(function($det){return !is_null($det);});

    return [
      'relevamiento' => $relevamiento,
      'casino' => $relevamiento->sector->casino->nombre,
      'id_casino' => $relevamiento->sector->casino->id_casino,
      'sector' => $relevamiento->sector->descripcion,
      'detalles' => $detalles,
      'usuario_cargador' => $relevamiento->usuario_cargador,
      'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador,
      'usuario_actual' => $usuario_actual
    ];
  }

  // existeRelevamiento retorna una bandera indicando si existe relevamiento que no se a backup
  // si esta con estado generado retorna 1 , sino existe retorna 0 y 2 para los demas estados
  public function existeRelevamiento($id_sector){
    Validator::make(['id_sector' => $id_sector],[
        'id_sector' => 'required|exists:sector,id_sector'
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $fecha_hoy = date("Y-m-d");
    $rel_sobreescribiles = Relevamiento::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'=' ,1]])->count() > 0;
    $rel_no_sobreescribiles = Relevamiento::where([['fecha','=',$fecha_hoy],['id_sector','=',$id_sector],['backup','=',0] , ['id_estado_relevamiento' ,'<>' ,1]])->count() > 0;
    return $rel_no_sobreescribiles? 'CARGADO' : ($rel_sobreescribiles? 'GENERADO' : 'SIN_GENERAR');
  }
  // crearRelevamiento crea un nuevo relevamiento
  // limpia los existentes , se considera que se puede regenerar
  // considera las maquinas para un sector y las maquinas a pedido
  // genera para el relevamiento, segun la cantidad de maquinas, los detalles relevamientos
  // genera los backup para la carga sin sistema
  // genera las planillas , comprime las de backup y se descargan
  public function crearRelevamiento(Request $request){
    $fecha_hoy = date("Y-m-d"); // fecha de hoy
    
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'cantidad_fiscalizadores' => 'nullable|integer|between:1,10',
        'seed' => 'nullable|integer',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor es invalido',
      'integer' => 'El valor tiene que ser un número entero',
      'between' => 'El valor tiene que ser entre 1-10',
    ], self::$atributos)->after(function($validator) use ($fecha_hoy){
      if($validator->errors()->any()) return;
      $id_sector = $validator->getData()['id_sector'];
      $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $sector = Sector::find($id_sector);
      if(!$u->es_superusuario){
        if(is_null($sector) || $u->casinos->pluck('id_casino')->search($sector->id_casino) === false){
          return $validator->errors()->add('id_sector','No puede acceder a ese sector');
        }
      }
      
      $estados_rechazados = [2,3,4];
      $relevamientos = Relevamiento::where([['fecha',$fecha_hoy],['id_sector',$id_sector],['backup',0]])->whereIn('id_estado_relevamiento',$estados_rechazados);
      if($relevamientos->count() > 0){
        $validator->errors()->add('relevamiento_en_carga','El Relevamiento para esa fecha ya está en carga y no se puede reemplazar.');
      }
      
      $cantidad_fiscalizadores = $validator->getData()['cantidad_fiscalizadores'];
      if($cantidad_fiscalizadores > RelevamientoController::getInstancia()->obtenerCantidadMaquinasRelevamiento($id_sector)){
        $validator->errors()->add('cantidad_maquinas','La cantidad de maquinas debe ser mayor o igual a la cantidad de fiscalizadores.');
      }
      $seed = $validator->getData()['seed'] ?? null;
      if(!empty($seed) && !UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario){
        $validator->errors()->add('seed','El usuario no puede realizar esa acción');
      }
    })->validate();

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


    //@WARNING: Seeding el generador de numeros aleatorios solo funciona con MySQL en esta version de Laravel
    $seed = ((new DateTime())->getTimestamp() % 999999) + 1;//Por las moscas, no permito $seed = 0 para evitar problemas con nulos (lol php)
    if(!empty($request->seed)){
      $seed = $request->seed;
    }
    $maquinas = Maquina::whereIn('id_isla',$islas)->whereNotIn('id_maquina',$arregloMaquinaAPedido)
                       ->whereHas('estado_maquina',function($q){$q->where('descripcion','Ingreso')->orWhere('descripcion','ReIngreso');})
                       ->inRandomOrder($seed)->take($cantidad_maquinas)->get();

    $maquinas_total = $maquinas->merge($maquinas_a_pedido);
    if($id_casino == 3){ // si es rosario ordeno por el ordne de los islotes
      $maquinas_total = $maquinas_total->sortBy(function($maquina,$key){
         $maq=Isla::find($maquina->id_isla);
         return [$maq->orden, $maq->nro_isla];
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
        $relevamientos->seed = $seed;
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
          $detalle->producido_importado = $this->calcularProducidoImportado($fecha_hoy,$maq);
          $detalle->save();
        }

        $arregloRutas[] = $this->guardarPlanilla($relevamientos->id_relevamiento);
    }
    else{
        $cant_por_planilla = ceil($maquinas_total->count()/$request->cantidad_fiscalizadores);
        $start = 0;
        $offset = 1 + $request->cantidad_fiscalizadores - (($cant_por_planilla*$request->cantidad_fiscalizadores) - $maquinas_total->count());
        for($i = 1; $i <= $request->cantidad_fiscalizadores; $i++){
          $relevamiento = new Relevamiento;
          $relevamiento->nro_relevamiento = DB::table('relevamiento')->max('nro_relevamiento') + 1;
          $relevamiento->fecha = $fecha_hoy;
          $relevamiento->fecha_generacion = date('Y-m-d h:i:s', time());
          $relevamiento->seed = $seed;
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
            $detalle->producido_importado = $this->calcularProducidoImportado($fecha_hoy,$maq);
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
      $relevamiento_backup->seed = $seed+$i;
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
                         ->inRandomOrder($seed+$i)->take($cantidad_maquinas)->get();

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

  // cargarRelevamiento se guardan los detalles relevamientos de la toma de los fisca
  public function cargarRelevamiento(Request $request){
    Validator::make($request->all(), [
        'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
        'id_usuario_fiscalizador' => 'required_if:estado,3|nullable|exists:usuario,id_usuario',
        'tecnico' => 'nullable|max:45',
        'observacion_carga' => 'nullable|max:2000',
        'estado' => 'required|numeric|between:2,3',
        'hora_ejecucion' => 'required_if:estado,3|regex:/^\d\d:\d\d(:\d\d)?/',
        'detalles' => 'required',
        'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
        'detalles.*.cont1' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont2' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont3' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont4' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont5' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont6' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont7' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.cont8' => ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
        'detalles.*.producido_calculado_relevado' => ['required','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'],
        'detalles.*.id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida',
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      //@TODO validar casinos
    })->validate();
    
    return DB::transaction(function() use ($request){
      $request->detalles != 0 ? $detalles = $request->detalles : $detalles = array();
      $relevamiento = Relevamiento::find($request->id_relevamiento);
      if($request->id_usuario_fiscalizador != null){
        $relevamiento->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      }else {
        $relevamiento->usuario_fiscalizador()->dissociate();
      }
      if($relevamiento->id_usuario_cargador == null){
        $relevamiento->usuario_cargador()->associate(UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario);
      }
      if($request->estado == 2){
        $relevamiento->estado_relevamiento()->associate(2);
      }else if($request->estado == 3){
        $relevamiento->estado_relevamiento()->associate(3);
        // si cierra el relevamiento (estado == 3) me fijo en la bd y elimino todos los backup que habia para esa fecha
        $this->eliminarRelevamientosBackUp($relevamiento);
      }

      $cantidad_habilitadas = $this->calcularMTMsHabilitadas($relevamiento->sector->id_casino);
      $sin_isla = $this->calcular_sin_isla($relevamiento->sector->id_casino);

      $relevamiento->fecha_ejecucion = $request->hora_ejecucion;
      $relevamiento->fecha_carga = date('Y-m-d h:i:s', time());
      $relevamiento->tecnico = $request->tecnico;
      $relevamiento->observacion_carga = $request->observacion_carga;
      $relevamiento->truncadas = $request->truncadas;
      $relevamiento->mtms_habilitadas_hoy = $cantidad_habilitadas;
      $relevamiento->mtms_sin_isla = $sin_isla;
      $relevamiento->save();

      $CONTADORES = $this->contadores();
      foreach($detalles as $det){
        $detalle = DetalleRelevamiento::find($det['id_detalle_relevamiento']);
        foreach($CONTADORES as $c){
          $detalle->{$c} = $det[$c] ?? null;
        }
        $detalle->id_tipo_causa_no_toma = $det['id_tipo_causa_no_toma'];
        $detalle->producido_calculado_relevado = $det['producido_calculado_relevado'];

        if($detalle->producido_importado != null && $detalle->producido_calculado_relevado != null){
          $detalle->diferencia = $detalle->producido_calculado_relevado - $detalle->producido_importado;
        }

        $detalle->id_unidad_medida = $det['id_unidad_medida'];
        $detalle->denominacion = $det['denominacion'];
        $detalle->save();
      }

      return 1;
    });
  }
  
  // eliminarRelevamientosBackUp funcion de utilidad, borra los relevamientos backup, se la llama
  // cuando se cierra el relevamiento
  private function eliminarRelevamientosBackUp($relevamiento){
    $relevamientos = Relevamiento::where([['id_sector',$relevamiento->sector->id_sector],['fecha',$relevamiento->fecha],['backup',1]])->get();
    foreach($relevamientos as $rel){
      foreach($rel->detalles as $det){
        $det->delete();
      }
      $rel->delete();
    }
  }
  // validarRelevamiento valida un relevamiento, le cambia el estado a visado
  // tambien verifica si todos los relevamientos para ese sector estan validados, en ese caso
  // se cambia el estado al 7 "rel visado", es decir, todos los relevamientos generados, estan visados
  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
        'observacion_validacion' => 'nullable|max:2000',
        'truncadas' => 'required|integer|min:0',
        'detalles' => 'required',///trae datos para guardar en el detalle relevamiento
        'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
        'detalles.*.denominacion' => 'nullable|numeric',
        'detalles.*.diferencia' => 'nullable|numeric',
        'detalles.*.importado' => 'nullable|numeric',
        'detalles.*.a_pedido' => 'nullable|integer|min:0',
    ], array(), self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      
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
    foreach ($request['detalles'] as $dat) {
      $dett = DetalleRelevamiento::find($dat['id_detalle_relevamiento']);
      //dd($dat['id_detalle_relevamiento']);
      if(isset($dat['denominacion'])){
        $dett->denominacion = $dat['denominacion'];
      }

      if(isset($dat['diferencia'])){
        $dett->diferencia = $dat['diferencia'];
      }

      if(isset($dat['importado'])){
        $dett->producido_importado = $dat['importado'];
      }

      $dett->save();
      
      if(isset($dat['a_pedido'])){
        $mtm_controller->crearPedidoEn($dett->id_maquina,$dat['a_pedido'],$request->id_relevamiento);
      }
    }

    return ['relevamiento' => $relevamiento,
            'casino' => $relevamiento->sector->casino->nombre,
            'sector' => $relevamiento->sector->descripcion,
            'estado' => $relevamiento->estado_relevamiento->descripcion,
            'detalles' => $relevamiento->detalles];
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
  // crearPlanilla crea y guarda la planilla de relevamiento
  public function crearPlanilla($id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    $rel= new \stdClass();
    $rel->nro_relevamiento = $relevamiento->nro_relevamiento;
    $rel->casinoCod = $relevamiento->sector->casino->codigo;
    $rel->casinoNom = $relevamiento->sector->casino->nombre;
    $rel->sector = $relevamiento->sector->descripcion;
    $rel->fecha = $relevamiento->fecha;
    $rel->fecha_ejecucion = $relevamiento->fecha_ejecucion;
    $rel->fecha_generacion = $relevamiento->fecha_generacion;
    $rel->seed = is_null($relevamiento->seed)? '' : $relevamiento->seed;

    $año = substr($rel->fecha,0,4);
    $mes = substr($rel->fecha,5,2);
    $dia = substr($rel->fecha,8,2);
    $rel->fecha = $dia."-".$mes."-".$año;

    $añoG = substr($rel->fecha_generacion,0,4);
    $mesG = substr($rel->fecha_generacion,5,2);
    $diaG = substr($rel->fecha_generacion,8,2);
    $rel->fecha_generacion = $diaG."-".$mesG."-".$añoG;
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

    $view = View::make('planillaRelevamientos2018', compact('detalles','rel'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("Helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 565, 
      (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->seed.'/'.$rel->casinoCod
      ."/".$rel->sector."/".$rel->fecha."/Generado:".$rel->fecha_generacion
    , $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;
  }

  public function crearPlanillaValidado($id_relevamiento){
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
    $truncadas=0;
    $detallesOK = 0;
    $no_tomadas = 0;
    $habilitadas_en_tal_fecha=0;
    $sin_isla = 0;
    $sin_contadorImportado_relevada=0;
    $errores = 0;

    foreach ($relevamientos as $unRelevamiento){
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
      foreach ($unRelevamiento->detalles as $idx => $detalle){
        $detalle_contador_horario = null;

        if($contador_horario_ARS != null){
          $detalle_contador_horario = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_ARS->id_contador_horario], ['id_maquina','=',$detalle->id_maquina]])->first();
        }

        if($detalle_contador_horario == null && $contador_horario_USD != null ){
          $detalle_contador_horario = DetalleContadorHorario::where([['id_contador_horario','=',$contador_horario_USD->id_contador_horario], ['id_maquina','=',$detalle->id_maquina]])->first();
        }

        //el contador horario puede ser null porque la mtm puede estar apagada en ese momento
        if($detalle_contador_horario == null){
          $det = new \stdClass();
          $det->producido_calculado_relevado = round($detalle->producido_calculado_relevado,2);
          $maquina = $detalle->maquina()->withTrashed()->get()->first();
          $det->nro_admin = $maquina->nro_admin;
          if($maquina->isla!=null){
            $det->isla = $maquina->isla->nro_isla;
            $det->sector= $maquina->isla->sector->descripcion;
          }else{
            $det->isla ="-";
            $det->sector= "-";
          }
          $det->producido = 0;
          if($detalle->tipo_causa_no_toma != null){
              $det->no_toma = $detalle->tipo_causa_no_toma->descripcion;
              $no_tomadas++;
          }else{
              //sino se importaron contadores pero si se relevaron los contadores de la maquina
              $sin_contadorImportado_relevada+=1;
              $det->no_toma = 'FALTA DE IMPORTACIÓN';
          }
          $check = $this->chequearMTMpedida($detalle->id_maquina, $detalle->id_relevamiento);
          if($check != null){
            $det->observacion = 'No se importaron contadores. Se pidió para el '.$check->fecha.'.';
          }else{
            $det->observacion = 'No se importaron contadores.';
          }
          $detalles[] = $det;
        }else{
            //@HACK por algun motivo, el producido no se esta seteando, p
            //por eso lo recalculamos a pata
            //Ya en la BD hay muchas filas con el producido sin setear...
            //Asi que ya lo calculamos en el momento.
            //$producido = $detalle->producido;

            $producido = round($detalle_contador_horario->coinin
            - $detalle_contador_horario->coinout
            - $detalle_contador_horario->jackpot
            - $detalle_contador_horario->progresivo,2);//APLICO FORMULA

            $diferencia = round(abs(round($detalle->producido_calculado_relevado,2) - $producido), 2);

            if($diferencia != 0){

              $det = new \stdClass();
              $det->producido_calculado_relevado = round($detalle->producido_calculado_relevado,2);
              
              $maquina = $detalle->maquina()->withTrashed()->get()->first();
              $det->nro_admin = $maquina->nro_admin;
              if ($maquina->isla!=null){
                $det->isla = $maquina->isla->nro_isla;
                $det->sector= $maquina->isla->sector->descripcion;
              }else{
                $det->isla ="-";
                $det->sector= "-";
              }

              $det->producido = $producido;
              if($detalle->tipo_causa_no_toma != null){
                  $det->no_toma = $detalle->tipo_causa_no_toma->descripcion;
                  $no_tomadas++;
              }else{
                // se tomo, pero da diferencia, en este punto se evalua si es truncada
                if(fmod($diferencia,ProducidoController::getInstancia()->truncamiento()) == 0){//@HACK?: ver ProducidoController::probarAjusteAutomatico
                  $det->no_toma = 'TRUNCAMIENTO';
                  $truncadas++;
                }else{
                  $det->no_toma = 'ERROR GENERAL';
                  $errores++;
                }
              }

              $check = $this->chequearMTMpedida($detalle->id_maquina, $detalle->id_relevamiento);
              if($check != null){
                $det->observacion = ' Se pidió para el '.$check->fecha.'.';
              }else{
                $det->observacion = '';
              }

              $detalles[] = $det;
            }
            else{
              //No se ve el caso diferencia = 0 y con causa de no toma... creo que esta bien...
              $detallesOK++;
            }
        }
      }
    }

    $rel->observaciones = $observaciones;

    $rel->referencias = TipoCausaNoToma::all();

    if(!empty($detalles)){
      $rel->detalles = $detalles;
    }

    //?? wrong!?
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

    /*
    los conceptos del resumen cambiaron por los siguientes:
    relevadas: la totalidad de maquinas del relevamiento
    verificadas: todas las maquinas a las que se le tomaron contadores, sin importar los errores (relevadas-no tomas)
    errores generales: aquellas que tiene la X, es decir la que dio diferencia sin considerar el truncammiento, tampoco se consideran aquellas que dieron error por falta de improtar contadores
    sin toma: persiste el concepto, todos los tipos de no toma
    la isla ya no es necesario en este informe
    */

    $rel->truncadas = $truncadas;
    $rel->verificadas = $relevadas - $no_tomadas;
    $rel->sin_relevar = $no_tomadas;
    $rel->errores_generales = $errores;
    $rel->sin_contadorImportado_relevada=$sin_contadorImportado_relevada;

    $view = View::make('planillaRelevamientosValidados', compact('rel'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4','portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica","regular");
    $dompdf->getCanvas()->page_text(515, 815,"Página {PAGE_NUM} de {PAGE_COUNT}",$font,10,array(0,0,0));
    return $dompdf;
  }

  // calcularMTMsHabilitadas
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
  // calcular_sin_isla retorna la cantidad de maquinas sin isla en un casino
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
        'fecha_generacion' => 'required|date|before:today',
        'fecha' => 'required|date|after_or_equal:fecha_generacion',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor no existe',
      'date' => 'El valor tiene que ser una fecha en formato YYYY-MM-DD',
      'before' => 'La fecha tiene que ser anterior a hoy',
      'after_or_equal' => 'La fecha tiene que ser posterior a la fecha de generación',
    ], self::$atributos)->after(function($validator){//@TODO: chequear que tenga acceso al casino
    })->validate();

    $relevamientos = Relevamiento::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',0]])->whereIn('id_estado_relevamiento',[1,2])->get();
    if($relevamientos != null){
      foreach($relevamientos as $relevamiento){
        $relevamiento->backup = 1;
        $relevamiento->save();
      }
    }

    $rel_backup = Relevamiento::where([['id_sector',$request->id_sector],['fecha',$request->fecha],['backup',1]])->whereDate('fecha_generacion','=',$request->fecha_generacion)->first();
    $fecha = $rel_backup->fecha;
    $id_casino = $rel_backup->sector->casino->id_casino;
    foreach($rel_backup->detalles as $detalle){
      $detalle->producido_importado = $this->calcularProducidoImportado($fecha,$detalle->maquina()->withTrashed()->first());
      $detalle->save();
    }
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
        'id_casino' => 'nullable|numeric|exists:casino,id_casino',
        'id_sector' => 'required|numeric',
        'nro_isla' => 'nullable|numeric',
        'fecha_desde' => 'nullable|date',
        'fecha_hasta' => 'nullable|date'
    ], array(), self::$atributos)->after(function($validator){
      $id_casino = $validator->getData()['id_casino'];
      $id_sector = $validator->getData()['id_sector'];
      $nro_isla = $validator->getData()['nro_isla'];

      $userc = UsuarioController::getInstancia();
      $usuario = $userc->quienSoy()['usuario'];
      if(!is_null($id_casino)){
        if(!$usuario->usuarioTieneCasino($id_casino)){
          $validator->errors()->add('id_casino','El usuario no puede acceder a ese casino');
        }
      }else{
        if(!$usuario->es_superusuario){
          $validator->errors()->add('id_casino','Solo un superusuario puede buscar en todos los casinos');
        }
      }
    })->validate();

    $reglas_maquinas = array();
    $reglas_relevamientos = array();
    $reglas_relevamientos[] = ['relevamiento.backup','=','0'];

    if(!is_null($request->id_casino)){
        $reglas_maquinas[] = ['casino.id_casino','=',$request->id_casino];
        $reglas_relevamientos[] = ['casino.id_casino','=',$request->id_casino];
    }

    if($request->id_sector != 0){
        $reglas_maquinas[] = ['sector.id_sector','=',$request->id_sector];
        $reglas_relevamientos[] = ['sector.id_sector','=',$request->id_sector];
    }

    if(!is_null($request->nro_isla)){
        $reglas_maquinas[] = ['isla.nro_isla','=',$request->nro_isla];
    }

    if(!is_null($request->fecha_desde)){
        $reglas_relevamientos[] = ['relevamiento.fecha','>=',$request->fecha_desde];
    }

    if(!is_null($request->fecha_hasta)){
        $reglas_relevamientos[] = ['relevamiento.fecha','<=',$request->fecha_hasta];
    }

    $reglas[] = ['relevamiento.backup','=',0];

    $sort_by = $request->sort_by;

    //Buscamos todas las maquinas CON relevamientos
    $maq_con_rel = DB::table('detalle_relevamiento')
    ->select('detalle_relevamiento.id_maquina as id_maquina')
    ->join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')
    ->join('sector','relevamiento.id_sector','=','sector.id_sector')
    ->join('casino','sector.id_casino','=','casino.id_casino')
    ->where($reglas_relevamientos)
    ->distinct()
    ->get();

    $maq_con_rel_arr = array();
    foreach($maq_con_rel as $m){
      $maq_con_rel_arr[]=$m->id_maquina;
    }

    //Ahora buscamos la SIN relevamientos.
    $resultados = DB::table('maquina')
    ->select(
      'maquina.id_maquina as id_maquina',
      'maquina.nro_admin as maquina',
      'casino.nombre as casino',
      'sector.descripcion as sector',
      'isla.nro_isla as isla'
    )
    ->join('isla','maquina.id_isla','=','isla.id_isla')
    ->join('sector','isla.id_sector','=','sector.id_sector')
    ->join('casino','sector.id_casino','=','casino.id_casino')
    ->whereNull('maquina.deleted_at')
    ->where($reglas_maquinas)
    ->whereNotIn('maquina.id_maquina',$maq_con_rel_arr)
    ->when($sort_by,function($q) use ($sort_by){return $q->orderBy($sort_by['columna'],$sort_by['orden']);})
    ->paginate($request->page_size);

    return $resultados;
  }

  public function obtenerUltimosRelevamientosPorMaquina(Request $request){
    Validator::make($request->all(),[
        'id_maquina' => 'required|numeric|exists:maquina,id_maquina',
        'cantidad_relevamientos' => 'required|numeric|min:1',
        'tomado' => 'nullable|string',
        'diferencia' => 'nullable|string',
    ], array(), self::$atributos)->after(function($validator){
      $data = $validator->getData();
      $maq = Maquina::find($data['id_maquina']);
      //No deberia pasar porque se chequea en el validator.
      if($maq === null) $validator->errors()->add('id_maquina','No existe esa maquina');
      else{
        $userc = UsuarioController::getInstancia();
        $user = $userc->quienSoy()['usuario'];
        $casino = $maq->casino;
        if(!$userc->usuarioTieneCasinoCorrespondiente($user->id_usuario,$casino->id_casino)){
          $validator->errors()->add('id_casino','El usuario no tiene acceso a ese casino');
        }
      }
      if(array_key_exists('tomado',$data)){
        $tomado = $data['tomado'];
        if(!is_null($tomado) 
        && $tomado != 'SI' 
        && $tomado != 'NO'){
          $validator->errors()->add('tomado','Tomado invalido');
        }
      }
      if(array_key_exists('diferencia',$data)){
        $diferencia = $data['diferencia'];
        if(!is_null($diferencia)
        && $diferencia != 'SI' 
        && $diferencia != 'NO'){
          $validator->errors()->add('diferencia','Diferencia invalido');
        }
      }
    })->validate();

    $maq = Maquina::find($request->id_maquina);

    $ret = new \stdClass();
    $ret->casino = $maq->casino->nombre;
    if(!is_null($maq->isla)){
      $ret->sector = $maq->isla->sector->descripcion;
      $ret->isla = $maq->isla->nro_isla;
    }

    $ret->nro_admin = $maq->nro_admin;

    $testString = array("SI" => True, "NO" => False, null => null);
    $tomado = $testString[$request->tomado];
    $diferencia = $testString[$request->diferencia];
    $queryFunction = function($query) use ($diferencia,$tomado){
      if(!is_null($tomado)){
        if($tomado){
         $query->whereNull('detalle_relevamiento.id_tipo_causa_no_toma');
        }
        else{
          $query->whereNotNull('detalle_relevamiento.id_tipo_causa_no_toma');
        }
      }
      if(!is_null($diferencia)){
        $query->whereNotNull('detalle_relevamiento.diferencia');
        if($diferencia){
          $query->where('detalle_relevamiento.diferencia','<>','0');
        }
        else{
          $query->where('detalle_relevamiento.diferencia','=','0');
        }
      }
    };

    $detalles = DB::table('detalle_relevamiento')
                    ->select('relevamiento.fecha','usuario.nombre','tipo_causa_no_toma.descripcion as tipos_causa_no_toma','detalle_relevamiento.id_detalle_relevamiento',
                            'detalle_relevamiento.cont1','detalle_relevamiento.cont2','detalle_relevamiento.cont3','detalle_relevamiento.cont4',
                            'detalle_relevamiento.cont5','detalle_relevamiento.cont6','detalle_relevamiento.cont7','detalle_relevamiento.cont8',
                            'detalle_relevamiento.producido_calculado_relevado','detalle_relevamiento.producido_importado','detalle_relevamiento.diferencia',
                            'detalle_contador_horario.coinin','detalle_contador_horario.coinout','detalle_contador_horario.jackpot','detalle_contador_horario.progresivo'
                           )
                           ->join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')
                           ->join('maquina','maquina.id_maquina','=','detalle_relevamiento.id_maquina')
                           ->join('sector','relevamiento.id_sector','=','sector.id_sector')
                           ->leftJoin('contador_horario',function ($leftJoin){
                                       $leftJoin->on('contador_horario.fecha','=','relevamiento.fecha');
                                       $leftJoin->on('contador_horario.id_casino','=','sector.id_casino');
                                     })
                           ->leftJoin('detalle_contador_horario','detalle_contador_horario.id_contador_horario','=','contador_horario.id_contador_horario')
                           ->leftJoin('tipo_causa_no_toma','tipo_causa_no_toma.id_tipo_causa_no_toma','=','detalle_relevamiento.id_tipo_causa_no_toma')
                           ->leftJoin('usuario','usuario.id_usuario','=','relevamiento.id_usuario_cargador')
                           ->where('maquina.id_maquina',$maq->id_maquina)
                           ->where('detalle_relevamiento.id_maquina',$maq->id_maquina)
                           ->where('detalle_contador_horario.id_maquina',$maq->id_maquina)
                           ->where($queryFunction)
                           //->groupby()
                           ->distinct('relevamiento.id_relevamiento',
                                     'detalle_relevamiento.id_detalle_relevamiento',
                                     'usuario.id_usuario',
                                     'detalle_contador_horario.id_detalle_contador_horario')
                           ->orderBy('relevamiento.fecha','desc')
                           ->take($request->cantidad_relevamientos)->get();
    return ['maquina' => $ret,
            'detalles' => $detalles];
  }

  public function obtenerUltimosRelevamientosPorMaquinaNroAdmin(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|numeric|exists:casino,id_casino',
        'nro_admin' => 'required|numeric|exists:maquina,nro_admin',
        'cantidad_relevamientos' => 'required|numeric|min:1'
    ], array(), self::$atributos)->after(function($validator){
      $id_casino = $validator->getData()['id_casino'];
      $nro_admin = $validator->getData()['nro_admin'];
      $maq = Maquina::where('nro_admin',$nro_admin)
      ->where('id_casino',$id_casino)
      ->first();
      if($maq === null) $validator->errors()->add('id_maquina','No existe esa maquina');
      else{
        $userc = UsuarioController::getInstancia();
        $user = $userc->quienSoy()['usuario'];
        $casino = $maq->casino;
        if(!$userc->usuarioTieneCasinoCorrespondiente($user->id_usuario,$casino->id_casino)){
          $validator->errors()->add('id_casino','El usuario no tiene acceso a ese casino');
        }
      }
    })->validate();
    $maq = Maquina::where('nro_admin',$request->nro_admin)
    ->where('id_casino',$request->id_casino)->first();
    $request->merge(['id_maquina'=>$maq->id_maquina]);
    return $this->obtenerUltimosRelevamientosPorMaquina($request);
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
  
  public function crearCantidadMaquinasPorRelevamiento(Request $request){
    Validator::make($request->all(),[
      'id_sector' => 'required|exists:sector,id_sector',
      'id_tipo_cantidad_maquinas_por_relevamiento' => 'required|exists:tipo_cantidad_maquinas_por_relevamiento,id_tipo_cantidad_maquinas_por_relevamiento',
      'cantidad_maquinas' => 'required|max:1000|integer|min:1',
      'fecha_desde' => 'required_if:id_tipo_cantidad_maquinas_por_relevamiento,2|date|after_or_equal:today',
      'fecha_hasta' => 'required_if:id_tipo_cantidad_maquinas_por_relevamiento,2|date|after_or_equal:fecha_desde',
      'forzar' => 'required|boolean',
    ],[
      'required' => 'El valor es requerido',
      'required_if' => 'El valor es requerido',
      'exists' => 'El valor no existe',
      'max' => 'El valor supera el limite',
      'date' => 'El valor tiene que ser una fecha en formato YYYY-MM-DD',
      'min' => 'El valor es inferior al limite',
      'after_or_equal' => 'El valor es inferior al limite',
    ], self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      //@TODO: validar usuario
      $data = $validator->getData();
      if($data['id_tipo_cantidad_maquinas_por_relevamiento'] != 2) return;
      
      $fecha_desde = $data['fecha_desde'];
      $fecha_hasta = $data['fecha_hasta'];
      $id_sector   = $data['id_sector'];
      
      $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      $sector = Sector::find($id_sector);
      if(!$u->es_superusuario){
        if(is_null($sector) || $u->casinos->pluck('id_casino')->search($sector->id_casino) === false){
          return $validator->errors()->add('id_sector','No puede acceder a ese sector');
        }
      }
      
      $ya_existe = !$data['forzar'] && DB::table('cantidad_maquinas_por_relevamiento')
      ->where([['id_sector',$id_sector],['id_tipo_cantidad_maquinas_por_relevamiento',2]])
      ->where(function($q)use($fecha_desde,$fecha_hasta){
        $q->where([['fecha_desde','>=',$fecha_desde],['fecha_desde','<=',$fecha_hasta]])
        ->orWhere([['fecha_hasta','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]])
        ->orWhere([['fecha_desde','<=',$fecha_desde],['fecha_hasta','>=',$fecha_hasta]])
        ->orWhere([['fecha_desde','>=',$fecha_desde],['fecha_hasta','<=',$fecha_hasta]]);
      })->count() > 0;
      if($ya_existe){
        $validator->errors()->add('fecha_desde','Ya existe una cantidad para este intervalo');
        $validator->errors()->add('fecha_hasta','Ya existe una cantidad para este intervalo');
        return $validator->errors()->add('ya_existe','1');
      }
    })->validate();

    $modify_date = function($date,$modif){
      $dt = new DateTime($date);
      $dt->modify($modif);
      return $dt->format('Y-m-d');
    };

    return DB::transaction(function() use ($request,$modify_date){
      $cantidades_superpuestas = CantidadMaquinasPorRelevamiento::where([
        ['id_sector',$request->id_sector],
        ['id_tipo_cantidad_maquinas_por_relevamiento',$request->id_tipo_cantidad_maquinas_por_relevamiento]
      ])
      ->where(function($q) use ($request){
        if($request->id_tipo_cantidad_maquinas_por_relevamiento == 1)
          return $q;
        return $q                                                                                         //    d                   h
        ->where  ([['fecha_desde','>=',$request->fecha_desde],['fecha_desde','<=',$request->fecha_hasta]])//    |       D---...     |    Comienza adentro
        ->orWhere([['fecha_hasta','>=',$request->fecha_desde],['fecha_hasta','<=',$request->fecha_hasta]])//    | ...---H           |    Termina adentro
        ->orWhere([['fecha_desde','<=',$request->fecha_desde],['fecha_hasta','>=',$request->fecha_hasta]])//  D-|-------------------|-H  Empieza antes, termina despues
        ->orWhere([['fecha_desde','>=',$request->fecha_desde],['fecha_hasta','<=',$request->fecha_hasta]]);//   |  D---------H      |    Empieza adentro, termina adentro (innecesario pero lo pongo por compleción)
      })->get();

      
      foreach($cantidades_superpuestas as $c){
        if($request->id_tipo_cantidad_maquinas_por_relevamiento == 1){//Si es tipo DEFAULT, borro siempre
          $c->delete();
          continue;
        }
        //No deberia pasar pero lo chequeo por las dudas
        //Termina antes de entrar, o empieza despues
        if($c->fecha_hasta < $request->fecha_desde || $c->fecha_desde > $request->fecha_hasta){
          continue;
        }
        //Hay una interseccion
        $termina_en_el_medio_o_igual = $c->fecha_hasta <= $request->fecha_hasta;
        
        if($c->fecha_desde < $request->fecha_desde){//empieza_antes
          if($termina_en_el_medio_o_igual){
            $c->fecha_hasta = $modify_date($request->fecha_desde,'-1 day');
            $c->save();
          }
          else{//termina_despues
            $c2 = $c->replicate();
            $c->fecha_hasta = $modify_date($request->fecha_desde,'-1 day');
            $c->save();
            
            $c2->fecha_desde = $modify_date($request->fecha_hasta,'+1 day');
            $c2->save();
          }
        }
        else{//empieza_en_el_medio_o_igual
          if($termina_en_el_medio_o_igual){
            $c->delete();
          }
          else{//termina_despues
            $c->fecha_desde = $modify_date($request->fecha_hasta,'+1 day');
            $c->save();
          }
        }
      }
      //guardo el nuevo
      $cantidad = new CantidadMaquinasPorRelevamiento;
      $cantidad->id_sector   = $request->id_sector;
      $cantidad->cantidad    = $request->cantidad_maquinas;
      $cantidad->fecha_desde = $request->fecha_desde;
      $cantidad->fecha_hasta = $request->fecha_hasta;
      $cantidad->id_tipo_cantidad_maquinas_por_relevamiento = 2;
      $cantidad->save();
      return 1;
    });
  }

  public function obtenerCantidadMaquinasRelevamiento($id_sector,$fecha = null){
    $fecha = is_null($fecha) ? date("Y-m-d") : $fecha;
    $temporal = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',2],['id_sector',$id_sector],['fecha_desde','<=',$fecha],['fecha_hasta','>=',$fecha]])
                                                ->orderBy('fecha_hasta','desc')->first();
    if($temporal != null){//Si hay temporal lo retorno
      return $temporal->cantidad;
    }
    //Defecto
    $cantidad = CantidadMaquinasPorRelevamiento::where([['id_tipo_cantidad_maquinas_por_relevamiento',1],['id_sector',$id_sector]])->first();
    return is_null($cantidad)? 10 : $cantidad->cantidad;//No hay por defecto, retorno 10
  }

  public function eliminarCantidadMaquinasPorRelevamiento(Request $request){
    return CantidadMaquinasPorRelevamiento::destroy($request->id_cantidad_maquinas_por_relevamiento);
  }

  public function modificarDenominacionYUnidad(Request $request){
    Validator::make($request->all(),[
      'id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'denominacion' => ['nullable','regex:/^\d\d?([,|.]\d\d?\d?)?$/'],
      'id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida'
    ], array(), self::$atributos)->after(function($validator){})->validate();
    
    return DB::transaction(function() use ($request){
      $d = DetalleRelevamiento::find($request->id_detalle_relevamiento);
      $m = MTMController::getInstancia()->modificarDenominacionYUnidad($request->id_unidad_medida,$request->denominacion,$d->id_maquina);
      
      $d->producido_calculado_relevado = $this->calcularProducidoRelevado_detalle($d,$d->toArray());
      $d->id_unidad_medida = $m->id_unidad_medida;//guardo con que unidad de medida hice elcalculo para ese detalle, porque puede cambiar
      $d->diferencia = $d->producido_calculado_relevado - $d->producido_importado;
      $d->save();
      
      return ['producido_calculado_relevado' => $d->producido_calculado_relevado];
    });
  }
  
  private function calcularProducidoRelevado_array(array $contadores,array $signos,float $denominacion){
    $producido = 0.0;
    $size = min(count($contadores),count($signos));
    for($idx = 0;$idx<$size;$idx++){
      $c = $contadores[$idx];
      $s = $signos[$idx];
      $sign = 0;
      if     ($s == '+'){ $sign =  1; }
      else if($s == '-'){ $sign = -1; }
      $producido += $sign*$c;
    }
    return round($producido*$denominacion,2);
  }
  
  private function calcularProducidoRelevado_detalle($d,$conts){
    $conts_arr = [];
    foreach($this->contadores() as $cidx => $c){
      $conts_arr[] = empty($conts[$c])? 0.0 : floatval($conts[$c]);//Redondeo?
    }
    $ops_arr = ['+'];
    $m = $d->maquina()->withTrashed()->first();
    $formula = $m->formula;
    foreach($this->operadores_formula() as $oidx => $o){
      $ops_arr[] = $formula->{$o} ?? null;
    }
    
    /*
      c1  c2  c3  c4  c5  c6  c7  c8
     /    /   /   /   /   /   /   /
    +    o1  o2  o3  o4  o5  o6  o7
    Si o_n es nulo -> + y c_n+1 = 0
    */

    foreach($ops_arr as $oidx => $o){
      if(empty($o)){
        $ops_arr[$oidx]    = '+';
        $cont_arr[$oidx+1] = 0;//No necesito verificar limites porque ya esta assert() en el constructor
      }
    }
    
    $deno = $m->id_unidad_medida == 2? 1.0 : floatval($m->denominacion);
    return $this->calcularProducidoRelevado_array($conts_arr, $ops_arr, $deno);
  }
  
  public function calcularEstadoDetalleRelevamiento(Request $request){
    $validation_arr = [
      'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'detalles.*.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
    ];
    foreach($this->contadores() as $cidx => $c){
      $validation_arr["detalles.*.$c"] = ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d\d?)?$/'];
    }
    
    $detalles = collect([]);
    Validator::make($request->all(),$validation_arr, array(), self::$atributos)->after(function($validator) use (&$detalles){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $detalles = DetalleRelevamiento::whereIn(
        'id_detalle_relevamiento',
        array_map(function($d){return $d['id_detalle_relevamiento'];},$data['detalles'] ?? [])
      )->get();
      
      $id_relevamiento = null;
      foreach($detalles as $d){
        if($d->id_relevamiento != $id_relevamiento && !is_null($id_relevamiento)){
          return $validator->errors()->add('id_relevamiento','No coinciden los detalles');
        }
        $id_relevamiento = $d->id_relevamiento;
      }
      
      if(!is_null($id_relevamiento)){
        $r = Relevamiento::find($id_relevamiento);
        $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $id_casino = $r->sector()->withTrashed()->first()->casino->id_casino;
        if(!$u->casinos->pluck('id_casino')->contains($id_casino)){
          return $validator->errors()->add('id_relevamiento','No puede acceder a ese relevamiento');
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$detalles){
      $ret = [];
      $recibido = collect($request['detalles'])->keyBy('id_detalle_relevamiento');
      $fecha_rel = null;
      $calcular = null;//Solo recalculo si esta relevando, sino uso directamente del detalle
      foreach($detalles as $d){
        $fecha_rel = $fecha_rel ?? $d->relevamiento->fecha;
        $calcular = $calcular ?? in_array($d->relevamiento->id_estado_relevamiento,[1,2]);
        $conts = $recibido[$d->id_detalle_relevamiento];
        $m = $d->maquina()->withTrashed()->first();
        
        $relevado   = $calcular? $this->calcularProducidoRelevado_detalle($d,$conts) : $d->producido_calculado_relevado;
        $importado  = $calcular? $this->calcularProducidoImportado($d->relevamiento->fecha,$m) : $d->producido_importado;
        $diferencia = $calcular? round($relevado - $importado,2) : $d->diferencia;
        $id_tipo_causa_no_toma = $calcular? ($conts['id_tipo_causa_no_toma'] ?? null) : $d->id_tipo_causa_no_toma;
        
        $hay_contadores = $calcular? false : true;
        foreach($this->contadores() as $cidx => $c){
          if($hay_contadores) break;
          $hay_contadores = $hay_contadores || (($conts[$c] ?? null) !== null);
        }
        $estado     = $this->obtenerEstadoDetalleRelevamiento($hay_contadores,$importado,$diferencia,$id_tipo_causa_no_toma);
        
        //@HACK: Usar id_unidad_medida y denominacion usado el calcularProducidoRelevado?
        $id_unidad_medida = $calcular? ($m->id_unidad_medida ?? $d->id_unidad_medida) : ($d->id_unidad_medida ?? $m->id_unidad_medida);
        $id_unidad_medida = $id_unidad_medida ?? 2;
        
        $denominacion = $calcular? 
          ($m->id_unidad_medida == 2? 1.0 : floatval($m->denominacion))
        : ($d->id_unidad_medida == 2? 1.0 : floatval($d->denominacion));
        
        $denominacion = $denominacion ?? 1.0;
        
        $ret[$d->id_detalle_relevamiento] = compact('relevado','importado','diferencia','estado','id_unidad_medida','denominacion');
      }
      return $ret;
    });
  }
  //No modificar sin cambiar el template del modal de carga... usa estos valores de retorno
  private function obtenerEstadoDetalleRelevamiento($hay_contadores,$importado,$diferencia,$id_tipo_causa_no_toma){
    if(!is_null($id_tipo_causa_no_toma)) return 'NO_TOMA';
    if(is_null($importado)) return 'SIN_IMPORTAR';
    if($diferencia != 0){
      if(fmod($diferencia,ProducidoController::getInstancia()->truncamiento()) == 0){
        return 'TRUNCAMIENTO';
      }
      return 'DIFERENCIA';
    }
    return 'CORRECTO';
  }
  
  public function calcularProducidoImportado($fecha,$maquina){
    if($fecha == null || $maquina == null){
      return null;
    }
    return ProducidoController::getInstancia()->calcularProducidoAcumulado($fecha,$maquina);
  }

  public function estaRelevadoMaquina($fecha,$id_maquina){
    //si tiene un detalle relevamiento para cierta fecha es porque salio sorteado
    $detalle = DetalleRelevamiento::join('relevamiento' , 'detalle_relevamiento.id_relevamiento' , '=' , 'relevamiento.id_relevamiento')
     ->where([['detalle_relevamiento.id_maquina', $id_maquina],['relevamiento.fecha', $fecha]])
     ->first();
    
    $validado = 0;//La función originalmente retornaba ints, por eso lo mantengo asi
    $relevado = 0;
    if(!is_null($detalle)){
      $estado_relevamiento = $detalle->relevamiento->estado_relevamiento->descripcion;
      $validado   += $estado_relevamiento == "Validado";
      $finalizado  = $estado_relevamiento == 'Finalizado';
      $relevado   += $validado || $finalizado;
    }

    return compact('relevado','validado','detalle');
  }

  public function existeRelVisado($fecha, $id_casino){
    return Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
    ->where([['fecha' , '=' , $fecha] ,['sector.id_casino' , '=' , $id_casino] ,['id_estado_relevamiento','=',4]])
    ->whereIn('id_estado_relevamiento',[4,7])->count() > 0;
  }

  public function buscarMaquinasPorCasino(Request $request,$id_casino){
    if($id_casino === null) return [];
    
    $query = 
    "select maq.id_maquina as id_maquina, maq.nro_admin as nro_admin, maq.id_casino as id_casino, cas.codigo as codigo
    from maquina as maq
    join casino cas on (maq.id_casino = cas.id_casino)
    where maq.deleted_at is NULL";
    
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($id_casino == 0){
      if($user->es_superusuario) return DB::select(DB::raw($query),[]);
      else return [];
    }

    if(!$user->usuarioTieneCasino($id_casino) || is_null(Casino::find($id_casino))){
      return [];
    }
    
    return DB::select(DB::raw($query),['id_casino' => $id_casino]);
  }
}
