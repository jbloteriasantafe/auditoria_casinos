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
    ]);
  }

  // buscarRelevamientos busca relevamientos de acuerdo a los filtros
  public function buscarRelevamientos(Request $request){
    $reglas = [];
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']
    ->casinos->pluck('id_casino');
    
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
    
    return DB::table('relevamiento')
    ->select('relevamiento.*'  , 'sector.descripcion as sector' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('sector' ,'sector.id_sector' , '=' , 'relevamiento.id_sector')
    ->join('casino' , 'sector.id_casino' , '=' , 'casino.id_casino')
    ->join('estado_relevamiento' , 'relevamiento.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->where($reglas)
    ->whereIn('casino.id_casino' , $casinos)
    ->where('backup','=',0)->paginate($request->page_size);
  }
  
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
      'relevamiento'         => $relevamiento,
      'casino'               => $relevamiento->sector->casino->nombre,
      'id_casino'            => $relevamiento->sector->casino->id_casino,
      'sector'               => $relevamiento->sector->descripcion,
      'detalles'             => $detalles,
      'usuario_cargador'     => $relevamiento->usuario_cargador,
      'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador,
      'usuario_actual'       => $usuario_actual
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

    return DB::transaction(function() use ($request,$fecha_hoy){
      $fecha_generacion = explode(' ',$fecha_hoy)[0];
      $fechas = [$fecha_hoy];
      $fecha_backup = $fecha_hoy;
      for($i = 1; $i <= self::$cant_dias_backup_relevamiento; $i++){
        $fecha_backup = strftime("%Y-%m-%d", strtotime("$fecha_backup +1 day"));
        $fechas[] = $fecha_backup;
      }
      
      $seed = null;
      if(!empty($request->seed)){
        $seed = $request->seed;
      }
      else{//Por las moscas, no permito $seed = 0 para evitar problemas con nulos (lol php)
        $seed = ((new DateTime())->getTimestamp() % 999999) + 1;
      }
      
      $sector = Sector::find($request->id_sector);
            
      $maquinas_sector = Maquina::whereIn('id_isla',$sector->islas->pluck('id_isla'))
      ->whereHas('estado_maquina',function($q){$q->whereIn('descripcion',['Ingreso','Reingreso']);});
      
      $maquinas_sorter = $sector->id_casino == 3?
        function($m,$key) {
           $i = Isla::find($m->id_isla);
           return [$i->orden, $i->nro_isla];
        }
      :
        function($m,$key){
          return Isla::find($m->id_isla)->nro_isla;
        }
      ;
      
      $archivos = [];
      foreach($fechas as $idx => $f){
        $es_backup = $idx != 0;
        
        {//Elimino los relevamientos viejos
          $relevamientos_viejos = Relevamiento::where([
            ['fecha',$f],['id_sector',$request->id_sector],['id_estado_relevamiento',1],['backup',$es_backup? 1 : 0]
          ]);
          
          if($es_backup){
            $relevamientos_viejos = $relevamientos_viejos->where('backup',1)->whereDate('fecha_generacion',$fecha_generacion);
          }
          else{
            $relevamientos_viejos = $relevamientos_viejos->where('backup',0);
          }
          
          foreach($relevamientos_viejos->get() as $r){
            foreach($r->detalles as $d){
              $d->delete();
            }
            $r->delete();
          }
        }
        
        $f_seed = $seed+$idx;
        $cantidad_maquinas = $this->obtenerCantidadMaquinasRelevamiento($request->id_sector,$f);
        $maquinas_total = null;
        {//Obtengo las maquinas a relevar
          $maquinas_a_pedido = (clone $maquinas_sector)
          ->whereHas('maquinas_a_pedido',function($q) use ($f){$q->where('fecha',$f);})
          ->get();

          $maquinas = (clone $maquinas_sector)->whereNotIn('id_maquina',$maquinas_a_pedido->pluck('id_maquina'))
          ->inRandomOrder($f_seed)->take($cantidad_maquinas)->get();
          
          $maquinas_total = $maquinas->merge($maquinas_a_pedido)->sortBy($maquinas_sorter);
        }
      
        
        $archivos_subrelevamientos = [];
        {//Creo los subrelevamientos
          $inicio            = 0;
          $restantes         = $maquinas_total->count();
          $cant_por_planilla = intdiv($maquinas_total->count(),$request->cantidad_fiscalizadores);
          $sumar_uno = $maquinas_total->count() % $request->cantidad_fiscalizadores;
          $subrelevamiento = 1;
          while($restantes > 0){
            $r = new Relevamiento;
            $r->nro_relevamiento = DB::table('relevamiento')->max('nro_relevamiento') + 1;
            $r->fecha            = $f;
            $r->fecha_generacion = $fecha_generacion;
            $r->backup           = $es_backup;
            $r->seed             = $f_seed;
            $r->subrelevamiento = $cant_por_planilla==$maquinas_total->count()? null : $subrelevamiento;
            $subrelevamiento+=1;
            $r->sector()->associate($sector->id_sector);
            $r->estado_relevamiento()->associate(1);
            $r->save();

            $cantidad   = $cant_por_planilla + ($sumar_uno>0? 1 : 0);
            $sumar_uno -= 1;
            
            foreach($maquinas_total->slice($inicio,$cantidad) as $maq){
              $dr = new DetalleRelevamiento;
              $dr->id_maquina          = $maq->id_maquina;
              $dr->id_relevamiento     = $r->id_relevamiento;
              $dr->producido_importado = $this->calcularProducidoImportado($f,$maq);
              $dr->save();
            }
            
            $archivos_subrelevamientos[] = $this->guardarPlanilla($r->id_relevamiento);
            
            $inicio    += $cantidad;
            $restantes -= $cantidad;
          }
        }
        
        $archivos = array_merge($archivos,$archivos_subrelevamientos);
      }
      
      $nombreZip = "Planillas-{$sector->casino->codigo}-{$sector->descripcion}-{$fechas[0]} al {$fechas[count($fechas)-1]}.zip";

      Zipper::make($nombreZip)->add($archivos)->close();
      File::delete($archivos);

      return ['url_zip' => 'relevamientos/descargarZip/'.$nombreZip];
    });
  }

  public function descargarZip($nombre){
    $file = public_path() . "/" . $nombre;
    $headers = array('Content-Type' => 'application/octet-stream',);

    return response()->download($file,$nombre,$headers)->deleteFileAfterSend(true);
  }

  // cargarRelevamiento se guardan los detalles relevamientos de la toma de los fisca
  public function cargarRelevamiento(Request $request){
    $detalles = $this->validarDetalles($request);
    
    Validator::make($request->all(), [
        'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
        'id_usuario_fiscalizador' => 'required_if:estado,3|nullable|exists:usuario,id_usuario,deleted_at,NULL',
        'tecnico' => 'nullable|max:45',
        'observacion_carga' => 'nullable|max:2000',
        'estado' => 'required|numeric|between:2,3',
        'hora_ejecucion' => 'required_if:estado,3|regex:/^\d\d:\d\d(:\d\d)?/',
    ], [], self::$atributos)->after(function($validator) use ($detalles){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      foreach($detalles as $d){
        if($d->id_relevamiento != $data['id_relevamiento']){
          return $validator->errors()->add('id_relevamiento','Error de mismatch entre detalles y relevamiento');
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$detalles){
      $r = Relevamiento::find($request->id_relevamiento);
      
      if($request->id_usuario_fiscalizador != null){
        $r->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      }else {
        $r->usuario_fiscalizador()->dissociate();
      }
      
      if($r->id_usuario_cargador == null){
        $r->usuario_cargador()->associate(UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario);
      }
      
      $r->estado_relevamiento()->associate($request->estado);
      
      if($request->estado == 3){// si cierra el relevamiento me fijo en la bd y elimino todos los backup que habia para esa fecha
        $this->eliminarRelevamientosBackUp($r);
      }

      $r->fecha_ejecucion = $request->hora_ejecucion;
      $r->fecha_carga = date('Y-m-d h:i:s', time());
      $r->tecnico = $request->tecnico;
      $r->observacion_carga = $request->observacion_carga;
      //No deberia usar el sector???
      $r->mtms_habilitadas_hoy = $this->calcularMTMsHabilitadas($r->sector->id_casino);
      //No deberia usar el sector???
      $r->mtms_sin_isla = $this->calcular_sin_isla($r->sector->id_casino);
      $r->truncadas = 0;
      $r->save();

      $CONTADORES = $this->contadores();
      $request_detalles = collect($request->detalles ?? [])->keyBy('id_detalle_relevamiento');
      
      foreach($detalles as $d){
        $r_d = $request_detalles[$d->id_detalle_relevamiento];
        $d->id_tipo_causa_no_toma  = $r_d['id_tipo_causa_no_toma'] ?? null;
        
        foreach($CONTADORES as $c){
          $d->{$c} = is_null($d->id_tipo_causa_no_toma)? null : ($r_d[$c] ?? null);
        }
        
        $m = $d->maquina;
        $d->producido_calculado_relevado = is_null($d->id_tipo_causa_no_toma)? null : $this->calcularProducidoRelevado_detalle($d,$r_d);
        $d->producido_importado          = $this->calcularProducidoImportado($r->fecha,$m);
        $d->diferencia                   = $d->producido_calculado_relevado - $d->producido_importado;
        $d->id_unidad_medida             = $m->id_unidad_medida;
        $d->denominacion                 = $m->denominacion;
        $d->save();
        $estado = $this->obtenerEstadoDetalleRelevamiento($d->producido_importado,$d->diferencia,$d->id_tipo_causa_no_toma);
        
        $r->truncadas += $estado == 'TRUNCAMIENTO'? 1 : 0;
      }

      $r->save();
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

  private function calcularMTMsHabilitadas($id_casino){
    $estados_habilitados = EstadoMaquina::where('descripcion' , 'Ingreso')
    ->orWhere('descripcion' , 'Reingreso')
    ->orWhere('descripcion' , 'Eventualidad Observada')
    ->get()->pluck('id_estado_maquina');
    
    return DB::table('maquina')
    ->join('isla','isla.id_isla','=','maquina.id_isla')
    ->where('maquina.id_casino',$id_casino)
    ->whereIn('maquina.id_estado_maquina',$estados_habilitados)
    ->whereNull('maquina.deleted_at')
    ->whereNull('isla.deleted_at')
    ->count();
  }
  // calcular_sin_isla retorna la cantidad de maquinas sin isla en un casino
  private function calcular_sin_isla($id_casino){
    return Maquina::where('id_casino',$id_casino)->whereNull('id_isla')->count();
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
    $rel_backup = null;
    $reglas = null;
    Validator::make($request->all(),[
      'id_sector' => 'required|exists:sector,id_sector,deleted_at,NULL',
      'fecha_generacion' => 'required|date|before:today',
      'fecha' => 'required|date|after_or_equal:fecha_generacion',
    ], [
      'required' => 'El valor es requerido',
      'exists' => 'El valor no existe',
      'date' => 'El valor tiene que ser una fecha en formato YYYY-MM-DD',
      'before' => 'La fecha tiene que ser anterior a hoy',
      'after_or_equal' => 'La fecha tiene que ser posterior a la fecha de generación',
    ], self::$atributos)->after(function($validator) use (&$rel_backup,&$reglas){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $id_casino = Sector::find($data['id_sector'])->id_casino;
      $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$u->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_sector','No puede acceder a ese sector');
      }
      
      $data = $validator->getData();
      $reglas = [['id_sector',$data['id_sector']],['fecha',$data['fecha']]];
      $rel_backup = Relevamiento::where($reglas)
      ->where('backup',1)->whereDate('fecha_generacion','=',$data['fecha_generacion'])->first();
      if(is_null($rel_backup)){
        $validator->errors()->add('id_sector','No existe un relevamiento con esos parametros');
        $validator->errors()->add('fecha_generacion','No existe un relevamiento con esos parametros');
        $validator->errors()->add('fecha','No existe un relevamiento con esos parametros');
        return;
      }
    })->validate();

    return DB::transaction(function() use ($request,&$reglas,&$rel_backup){
      $relevamientos_activos = Relevamiento::where($reglas)
      ->where('backup',0)->whereIn('id_estado_relevamiento',[1,2])->get();
      foreach($relevamientos_activos as $r){
        $r->backup = 1;
        $r->save();
      }

      foreach($rel_backup->detalles as $detalle){
        $m = $detalle->maquina()->withTrashed()->first();
        $detalle->producido_calculado_relevado = null;
        $detalle->producido_importado = $this->calcularProducidoImportado($rel_backup->fecha,$m);
        $detalle->diferencia = -$detalle->producido_importado;
        $detalle->save();
      }
      
      $rel_backup->backup = 0;
      $rel_backup->save();
      
      return 1;
    });
  }

  public function buscarMaquinasSinRelevamientos(Request $request){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos_validos = ($u->es_superusuario? Casino::all() : $u->casinos)->pluck('id_casino');

    $reglas_maquinas      = [];
    $reglas_relevamientos = [['relevamiento.backup','=','0']];

    if(!is_null($request->id_casino)){
      $reglas_maquinas[]      = ['casino.id_casino','=',$request->id_casino];
      $reglas_relevamientos[] = ['casino.id_casino','=',$request->id_casino];
    }

    if(!is_null($request->id_sector)){
      $reglas_maquinas[]      = ['sector.id_sector','=',$request->id_sector];
      $reglas_relevamientos[] = ['sector.id_sector','=',$request->id_sector];
    }

    if(!is_null($request->nro_isla)){
      $reglas_maquinas[]      = ['isla.nro_isla','=',$request->nro_isla];
    }

    if(!is_null($request->fecha_desde)){
      $reglas_relevamientos[] = ['relevamiento.fecha','>=',$request->fecha_desde];
    }

    if(!is_null($request->fecha_hasta)){
      $reglas_relevamientos[] = ['relevamiento.fecha','<=',$request->fecha_hasta];
    }
    
    //Buscamos todas las maquinas CON relevamientos
    $maq_con_rel = DB::table('detalle_relevamiento')
    ->select('detalle_relevamiento.id_maquina')
    ->join('relevamiento','detalle_relevamiento.id_relevamiento','=','relevamiento.id_relevamiento')
    ->join('sector','relevamiento.id_sector','=','sector.id_sector')
    ->join('casino','sector.id_casino','=','casino.id_casino')
    ->where($reglas_relevamientos)
    ->whereIn('casino.id_casino',$casinos_validos)
    ->distinct()->get()->pluck('id_maquina');

    $sort_by = $request->sort_by;
    //Ahora buscamos la SIN relevamientos.
    return DB::table('maquina')
    ->select(
      'maquina.id_maquina',
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
    ->whereIn('casino.id_casino',$casinos_validos)
    ->whereNotIn('maquina.id_maquina',$maq_con_rel)
    ->when($sort_by,function($q) use ($sort_by){return $q->orderBy($sort_by['columna'],$sort_by['orden']);})
    ->paginate($request->page_size);
  }

  public function obtenerUltimosRelevamientosPorMaquina(Request $request){
    $maq = null;
    
    Validator::make($request->all(),[
        'id_maquina' => 'required|numeric|exists:maquina,id_maquina',
        'cantidad_relevamientos' => 'required|numeric|min:1',
        'tomado' => 'nullable|string|in:SI,NO',
        'diferencia' => 'nullable|string|in:SI,NO',
    ], [], self::$atributos)->after(function($validator) use (&$maq){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $maq = Maquina::find($data['id_maquina']);
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$user->usuarioTieneCasino($maq->id_casino)){
        return $validator->errors()->add('id_casino','El usuario no tiene acceso a ese casino');
      }
    })->validate();

    $maquina = new \stdClass();
    $maquina->casino = $maq->casino->nombre;
    if(!is_null($maq->isla)){
      $maquina->sector = $maq->isla->sector->descripcion;
      $maquina->isla = $maq->isla->nro_isla;
    }
    $maquina->nro_admin = $maq->nro_admin;

    $tomado     = ['SI' => 'dr.id_tipo_causa_no_toma IS NULL', 'NO' => 'dr.id_tipo_causa_no_toma IS NOT NULL', null => '1'][$request->tomado];
    $diferencia = ['SI' => 'dr.diferencia <> 0'              , 'NO' => 'dr.diferencia = 0'                   , null => '1'][$request->diferencia];
    
    $detalles = DB::table('detalle_relevamiento as dr')
    ->select('r.fecha','u.nombre','t.descripcion as tipos_causa_no_toma','dr.id_detalle_relevamiento',
            'dr.cont1','dr.cont2','dr.cont3','dr.cont4',
            'dr.cont5','dr.cont6','dr.cont7','dr.cont8',
            'dr.producido_calculado_relevado','dr.producido_importado','dr.diferencia',
            'dch.coinin','dch.coinout','dch.jackpot','dch.progresivo'
    )
    ->join('relevamiento as r','dr.id_relevamiento','=','r.id_relevamiento')
    ->join('maquina as m','m.id_maquina','=','dr.id_maquina')
    ->join('sector as s','s.id_sector','=','s.id_sector')
    ->leftJoin('contador_horario as ch',function ($q){
       $q->on('ch.fecha','=','r.fecha')->on('ch.id_casino','=','s.id_casino');
    })
    ->leftJoin('detalle_contador_horario as dch','dch.id_contador_horario','=','ch.id_contador_horario')
    ->leftJoin('tipo_causa_no_toma as t','t.id_tipo_causa_no_toma','=','dr.id_tipo_causa_no_toma')
    ->leftJoin('usuario as u','u.id_usuario','=','r.id_usuario_cargador')
    ->where('m.id_maquina',$maq->id_maquina)
    ->where('dr.id_maquina',$maq->id_maquina)
    ->where('dch.id_maquina',$maq->id_maquina)
    ->whereRaw($tomado)
    ->whereRaw($diferencia)
    ->distinct('r.id_relevamiento','dr.id_detalle_relevamiento','u.id_usuario','dch.id_detalle_contador_horario')
    ->orderBy('r.fecha','desc')
    ->take($request->cantidad_relevamientos)->get();
    
    return compact('maquina','detalles');
  }

  public function obtenerUltimosRelevamientosPorMaquinaNroAdmin(Request $request){
    Validator::make($request->all(),[
        'id_casino' => 'required|numeric|exists:casino,id_casino,deleted_at,NULL',
        'nro_admin' => 'required|numeric|exists:maquina,nro_admin,deleted_at,NULL',
        'cantidad_relevamientos' => 'required|numeric|min:1'
    ],[], self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$u->usuarioTieneCasino($validator->getData()['id_casino'])){
        return $validator->errors()->add('id_casino','El usuario no tiene acceso a ese casino');
      }
    })->validate();
    
    $maq = Maquina::where('nro_admin',$request->nro_admin)
    ->where('id_casino',$request->id_casino)->first();
    
    if(!is_null($maq))
      $request->merge(['id_maquina'=>$maq->id_maquina]);
      
    return $this->obtenerUltimosRelevamientosPorMaquina($request);
  }

  public function obtenerCantidadMaquinasPorRelevamiento($id_sector){
    Validator::make(
      ['id_sector' => $id_sector],
      ['id_sector' => 'required|exists:sector,id_sector'],
      [],
      self::$atributos
    )->validate();

    return DB::table('cantidad_maquinas_por_relevamiento as cmr')
    ->join('tipo_cantidad_maquinas_por_relevamiento as tcmr','cmr.id_tipo_cantidad_maquinas_por_relevamiento','=','tcmr.id_tipo_cantidad_maquinas_por_relevamiento')
    ->where('cmr.id_sector','=',$id_sector)
    ->orderBy('cmr.fecha_hasta','asc')
    ->get();
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
  
  private function validarDetalles(Request $request){
    $validation_arr = [
      'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'detalles.*.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
    ];
    foreach($this->contadores() as $cidx => $c){
      $validation_arr["detalles.*.$c"] = ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d?\d?)?$/'];
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
    
    return $detalles;
  }
  
  public function calcularEstadoDetalleRelevamiento(Request $request){
    $detalles = $this->validarDetalles($request);
    
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
        $estado = $this->obtenerEstadoDetalleRelevamiento($importado,$diferencia,$id_tipo_causa_no_toma);
        
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
  private function obtenerEstadoDetalleRelevamiento($importado,$diferencia,$id_tipo_causa_no_toma){
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
    
    $q = DB::table('maquina as m')
    ->select('m.id_maquina','m.nro_admin','m.id_casino','c.codigo')
    ->join('casino as c','c.id_casino','=','m.id_casino')
    ->whereNull('m.deleted_at');
    
    $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if($id_casino == 0){
      if($user->es_superusuario) return $q->get();
      else return [];
    }
    
    return $q->whereIn('m.id_casino',$user->casinos->pluck('id_casino'))
    ->where('m.id_casino',$id_casino)->get();
  }
}
