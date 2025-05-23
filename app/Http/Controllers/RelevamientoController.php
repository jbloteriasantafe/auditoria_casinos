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
  private static $atributos = [];
  private static $mensajesErrores = [
    'in'          => 'El valor tiene que ser estar dentro del conjunto valido',
    'required'    => 'El valor es requerido',
    'required_if' => 'El valor es requerido',
    'exists'      => 'El valor es inexistente',
    'integer'     => 'El valor tiene que ser un número entero',
    'boolean'     => 'El valor solo puede ser 1 o 0',
    'numeric'     => 'El valor tiene que ser numerico',
    'date'        => 'El valor tiene que ser una fecha en formato YYYY-MM-DD',
    'array'       => 'El valor tiene que ser un arreglo',
    'between'     => 'El valor esta por encima o por debajo del limite',
    'min'         => 'El valor es menor al limite',
    'max'         => 'El valor es mayor al limite',
    'regex'       => 'El formato es incorrecto',
    'before'      => 'La fecha tiene que ser anterior',
    'after'       => 'La fecha tiene que ser posterior',
    'after_or_equal' => 'La fecha tiene que ser posterior',
  ];
  private static $instance;

  private static $cant_dias_backup_relevamiento = 6;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new RelevamientoController();
    }
    return self::$instance;
  }
  
  private function find_columns($table,$count_str){
    return collect(\Schema::getColumnListing($table))
    ->filter(function($s) use ($count_str){
      return preg_match('/^'.$count_str.'\d+$/',$s);
    })->keyBy(function($s) use ($count_str){
      return intval(substr($s,strlen($count_str)));
    })->sortBy(function($s,$k){
      return $k;
    });
  }
  
  public function contadores(){//Tambien usado en MaquinaAPedidoController
    static $ret = null;
    $ret = $ret ?? $this->find_columns((new DetalleRelevamiento)->getTableName(),'cont');
    return $ret;
  }
  private function contadores_formula(){
    static $ret = null;
    $ret = $ret ?? $this->find_columns((new Formula)->getTableName(),'cont');
    return $ret;
  }
  private function operadores_formula(){
    static $ret = null;
    $ret = $ret ?? $this->find_columns((new Formula)->getTableName(),'operador');
    return $ret;
  }
  
  public function __construct(){//Asegurar que tengan sentido las columnas de contadores y operadores
    $CONTADORES = $this->contadores();
    $CONTADORES_F = $this->contadores_formula();
    $OPERADORES_F = $this->operadores_formula();
    assert(count($CONTADORES) == count($CONTADORES_F));//igual cantidad de contadores para los detalles y las formulas
    assert(count($CONTADORES) == (count($OPERADORES_F)-1));//una menos para los operadores
    for($k=1;$k<=count($CONTADORES);$k++){//verificar que la numeración sea correcta
      assert($CONTADORES->has($k));
      assert($CONTADORES_F->has($k));
      if($k != count($CONTADORES)){
        assert($OPERADORES_f->has($k));
      }
    }
  }

  public function buscarTodo(){
    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Contadores', 'relevamientos');
    return view('Relevamientos/index', [
      'casinos' => UsuarioController::getInstancia()->quienSoy()['usuario']->casinos,
      'estados' => EstadoRelevamiento::all(),
      'tipos_cantidad' => TipoCantidadMaquinasPorRelevamiento::all(),
      'tipos_causa_no_toma' => TipoCausaNoToma::all(),
      'CONTADORES' => $this->contadores()->count(),
    ]);
  }

  public function buscarRelevamientos(Request $request){
    $reglas = [['backup','=',0]];
    
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
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos->pluck('id_casino');
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
    ->paginate($request->page_size);
  }
  
  public function obtenerRelevamiento($id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    if($this->validarSector($relevamiento->id_sector) === false){
      return [];
    }
    $this->recalcularRelevamiento($relevamiento);
    $detalles = $relevamiento->detalles->map(function($det){//POR CADA MAQUINA EN EL DETALLE BUSCO FORMULA Y UNIDAD DE MEDIDA , Y CALCULO PRODUCIDO
      $d = new \stdClass();
      $d->detalle = $this->sacarProducidosSegunPrivilegios($det);
      
      $m = $det->maquina()->withTrashed()->first();
      if(is_null($m)) return null;
      
      $d->formula = $m->formula;
      $d->maquina = $m->nro_admin;
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
      'usuario_actual'       => UsuarioController::getInstancia()->quienSoy()['usuario']
    ];
  }

  // existeRelevamiento retorna una bandera indicando si existe relevamiento que no se a backup
  // si esta con estado generado retorna 1 , sino existe retorna 0 y 2 para los demas estados
  public function existeRelevamiento($id_sector){
    Validator::make(['id_sector' => $id_sector],[
        'id_sector' => 'required|exists:sector,id_sector'
    ], self::$mensajesErrores, self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      if($this->validarSector($validator->getData()['id_sector']) === false){
        return $validator->errors()->add('id_sector','No tiene los privilegios');
      }
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
  
  private function crearRelevamiento_crear($es_backup,$sector,$f,$fecha_generacion,$cantidad_fiscalizadores,$seed){
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
      
    //Elimino los relevamientos viejos solo en estado GENERADOS
    foreach($this->crearRelevamiento_buscar($es_backup,$sector,$f,$fecha_generacion,1) as $r){
      foreach($r->detalles as $d){
        $d->delete();
      }
      $r->delete();
    }
    
    $cantidad_maquinas = $this->obtenerCantidadMaquinasRelevamiento($sector->id_sector,$f);
    $maquinas_total = null;
    {//Obtengo las maquinas a relevar
      $maquinas_sector = Maquina::whereIn('id_isla',$sector->islas->pluck('id_isla'))
      ->whereHas('estado_maquina',function($q){$q->whereIn('descripcion',['Ingreso','Reingreso']);});
      
      $maquinas_a_pedido = (clone $maquinas_sector)
      ->whereHas('maquinas_a_pedido',function($q) use ($f){$q->where('fecha',$f);})
      ->get();

      $maquinas = (clone $maquinas_sector)->whereNotIn('id_maquina',$maquinas_a_pedido->pluck('id_maquina'))
      ->inRandomOrder($seed)->take($cantidad_maquinas)->get();
      
      $maquinas_total = $maquinas->merge($maquinas_a_pedido)->sortBy($maquinas_sorter);
    }
        
    $relevamientos = collect([]);
    {//Creo los subrelevamientos
      $inicio            = 0;
      $restantes         = $maquinas_total->count();
      $cant_por_planilla = intdiv($maquinas_total->count(),$cantidad_fiscalizadores);
      $sumar_uno = $maquinas_total->count() % $cantidad_fiscalizadores;
      $subrelevamiento = 1;
      do{
        $r = new Relevamiento;
        $r->nro_relevamiento = DB::table('relevamiento')->max('nro_relevamiento') + 1;
        $r->fecha            = $f;
        $r->fecha_generacion = $fecha_generacion;
        $r->backup           = $es_backup;
        $r->seed             = $seed;
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
        
        $relevamientos->push($r);
        
        $inicio    += $cantidad;
        $restantes -= $cantidad;
      } while($restantes > 0);
    }
    
    return $relevamientos;
  }
  
  private function crearRelevamiento_buscar($es_backup,$sector,$f,$fecha_generacion,$id_estado_relevamiento){
    $relevamientos_viejos = Relevamiento::where([
      ['fecha',$f],['id_sector',$sector->id_sector],['backup',$es_backup? 1 : 0]
    ]);
    
    if(!is_null($id_estado_relevamiento)){
      $relevamientos_viejos = $relevamientos_viejos->where('id_estado_relevamiento',$id_estado_relevamiento);
    }
    
    if($es_backup){
      $relevamientos_viejos = $relevamientos_viejos->where('backup',1)->whereDate('fecha_generacion',$fecha_generacion);
    }
    else{
      $relevamientos_viejos = $relevamientos_viejos->where('backup',0);
    }
    
    return $relevamientos_viejos->get();
  }
  
  public function crearRelevamiento(Request $request,$crear_relevamientos = true){
    $fecha_hoy = date("Y-m-d"); // fecha de hoy
    
    Validator::make($request->all(),[
      'id_sector' => 'required|exists:sector,id_sector',
      'cantidad_fiscalizadores' => 'nullable|integer|between:1,10',
      'seed' => 'nullable|integer',
    ], 
      array_merge(self::$mensajesErrores,['cantidad_fiscalizadores.between' => 'El valor tiene que ser entre 1-10']),
      self::$atributos
    )->after(function($validator) use ($fecha_hoy,$crear_relevamientos){
      if($validator->errors()->any()) return;
      $id_sector = $validator->getData()['id_sector'];
      if($this->validarSector($id_sector) === false){
        return $validator->errors()->add('id_sector','No puede acceder a ese sector');
      }
      
      $seed = $validator->getData()['seed'] ?? null;
      if(!empty($seed) && !UsuarioController::getInstancia()->quienSoy()['usuario']->es_superusuario){
        $validator->errors()->add('seed','El usuario no puede realizar esa acción');
      }
      
      if(!$crear_relevamientos) return;
      
      $relevamientos_en_carga = Relevamiento::where([['fecha',$fecha_hoy],['id_sector',$id_sector],['backup',0]])
      ->get()->filter(function($d){return !$this->estaValidado($r);})->count() > 0;
      if($relevamientos_en_carga){
        $validator->errors()->add('relevamiento_en_carga','El Relevamiento para esa fecha ya está en carga y no se puede reemplazar.');
      }
      
      $cantidad_fiscalizadores = $validator->getData()['cantidad_fiscalizadores'];
      if($cantidad_fiscalizadores > RelevamientoController::getInstancia()->obtenerCantidadMaquinasRelevamiento($id_sector)){
        $validator->errors()->add('cantidad_maquinas','La cantidad de maquinas debe ser mayor o igual a la cantidad de fiscalizadores.');
      }
    })->validate();

    return DB::transaction(function() use ($request,$fecha_hoy,$crear_relevamientos){
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
        
      $relevamientos = collect([]);
      foreach($fechas as $idx => $f){
        $es_backup = $idx != 0;
        
        $rels_f = $crear_relevamientos?
          $this->crearRelevamiento_crear($es_backup,$sector,$f,$fecha_generacion,$request->cantidad_fiscalizadores,$seed+$idx)
        : $this->crearRelevamiento_buscar($es_backup,$sector,$f,$fecha_generacion,null);
        
        $relevamientos = $relevamientos->merge($rels_f->values());
      }
      
      $casino = $sector->casino;
      $archivos = [];
      foreach($relevamientos as $r){
        $ruta  = "Relevamiento-{$casino->codigo}-{$sector->descripcion}-{$r->fecha}";
        $ruta .= $r->subrelevamiento !== null? "({$r->subrelevamiento}).pdf" : '.pdf';
        file_put_contents($ruta, $this->crearPlanilla($r)->output());
        $archivos[] = $ruta;
      }
      
      $nombreZip = "Planillas-{$sector->casino->codigo}-{$sector->descripcion}-{$fechas[0]} al {$fechas[count($fechas)-1]}.zip";

      Zipper::make($nombreZip)->add($archivos)->close();
      File::delete($archivos);

      return ['url_zip' => 'relevamientos/descargarZip/'.$nombreZip];
    });
  }
  
  public function descargarRelevamiento(Request $request){
    return $this->crearRelevamiento($request,false);
  }

  public function descargarZip($nombre){
    return response()->download(public_path($nombre),$nombre,['Content-Type' => 'application/octet-stream'])->deleteFileAfterSend(true);
  }

  // cargarRelevamiento se guardan los detalles relevamientos de la toma de los fisca
  public function cargarRelevamiento(Request $request){
    $detalles = $this->validarDetalles($request,($request->estado ?? null) == '3');
    
    Validator::make($request->all(), [
      'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
      'id_usuario_fiscalizador' => 'required_if:estado,3|nullable|exists:usuario,id_usuario,deleted_at,NULL',
      'tecnico' => 'nullable|max:45',
      'observacion_carga' => 'nullable|max:2000',
      'estado' => 'required|numeric|between:2,3',
      'hora_ejecucion' => 'nullable|required_if:estado,3|regex:/^\d\d:\d\d(:\d\d)?/',
    ],self::$mensajesErrores, self::$atributos)->after(function($validator) use ($detalles){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $d = count($detalles)? $detalles[0] : null;
      if(is_null($d)) return;
      if($d->id_relevamiento != $data['id_relevamiento']){//Solo necesito chequear el primero, los demas se chequean en validarDetalles
        return $validator->errors()->add('id_relevamiento','Error de mismatch entre detalles y relevamiento');
      }
      $r = $d->relevamiento;
      if($this->validarSector($r->id_sector) === false){
        return $validator->errors()->add('id_relevamiento','No tiene acceso');
      }
      if($this->estaValidado($r)){
        return $validator->errors()->add('id_relevamiento','El relevamiento ya esta validado');
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
        $relevamientos = Relevamiento::where([['id_sector',$r->id_sector],['fecha',$r->fecha],['backup',1]])->get();
        foreach($relevamientos as $rel){
          foreach($rel->detalles as $det){
            $det->delete();
          }
          $rel->delete();
        }
      }

      $r->fecha_ejecucion   = $request->hora_ejecucion;
      $r->fecha_carga       = date('Y-m-d h:i:s', time());
      $r->tecnico           = $request->tecnico;
      $r->observacion_carga = $request->observacion_carga;
      $r->save();

      $nuevos_contadores = collect($request->detalles ?? [])->keyBy('id_detalle_relevamiento');
      $this->actualizarMTMsRelevamiento($r);
      $this->recalcularRelevamiento($r,$nuevos_contadores);
      return 1;
    });
  }
  
  public function recalcularDetalles($detalles,$contadores){
    $ret = [];
    {
      $rel = null;
      foreach($detalles as $d){
        $m = $d->maquina()->withTrashed()->first();
        $rel = $rel ?? $d->relevamiento;
        $conts = $contadores[$d->id_detalle_relevamiento];
        
        $producido_importado = $this->calcularProducidoImportado($rel->fecha,$m);
        $id_tipo_causa_no_toma = $conts['id_tipo_causa_no_toma'] ?? null;
        
        $producido_calculado_relevado = is_null($id_tipo_causa_no_toma)? $this->calcularProducidoRelevado($d,$conts) : null;
        $diferencia = round($producido_calculado_relevado - $producido_importado,2);
        $estado     = $this->obtenerEstadoDetalleRelevamiento($producido_importado,$diferencia,$id_tipo_causa_no_toma);
        
        $id_unidad_medida = $m->id_unidad_medida;
        $denominacion     = ($m->id_unidad_medida == 2? 1.0 : floatval($m->denominacion)) ?? 1.0;
        
        $ret[$d->id_detalle_relevamiento] = compact('id_detalle_relevamiento','producido_calculado_relevado','producido_importado','diferencia','estado','id_unidad_medida','denominacion','id_tipo_causa_no_toma');
        
        foreach($this->contadores() as $cidx => $c){
          $ret[$d->id_detalle_relevamiento][$c] = is_null($id_tipo_causa_no_toma)? ($conts[$c] ?? null) : null;
        }
      }
    }
    
    return $ret;
  }
  
  public function calcularEstadoDetalleRelevamiento(Request $request){
    $detalles = $this->validarDetalles($request,false);
    
    return collect(DB::transaction(function() use ($request,$detalles){
      if(count($detalles) <= 0) return [];
      //Solo recalculo si esta relevando, sino uso directamente del detalle
      $calcular = in_array($detalles[0]->relevamiento->id_estado_relevamiento,[1,2]);
            
      if($calcular){
        $contadores = collect($request['detalles'])->keyBy('id_detalle_relevamiento');
        return $this->recalcularDetalles($detalles,$contadores);
      }
      
      return $detalles->map(function($d){
        $nd = (object) $d->toArray();
        $nd->estado = $this->obtenerEstadoDetalleRelevamiento($nd->producido_importado,$nd->diferencia,$nd->id_tipo_causa_no_toma);
        $nd->denominacion = ($nd->id_unidad_medida == 2? 1.0 : floatval($nd->denominacion)) ?? 1.0;
        return $nd;
      })->keyBy('id_detalle_relevamiento');
    }))->map(function($d){
      return $this->sacarProducidosSegunPrivilegios($d);
    });
  }
  
  private function actualizarMTMsRelevamiento($r){
    if($this->estaValidado($r)) return;//Solo recalculo los no validados?
    
    $id_casino = $r->sector->id_casino;
    $r->mtms_habilitadas_hoy = $this->calcularMTMsHabilitadas($id_casino);//No deberia usar el sector???
    $r->mtms_sin_isla = Maquina::where('id_casino',$id_casino)->whereNull('id_isla')->count();//No deberia usar el sector???
  }
  
  public function recalcularRelevamiento($r,$nuevos_contadores=null){
    if($this->estaValidado($r)) return;//Solo recalculo los no validados?
      
    $nuevos_contadores = $nuevos_contadores ?? $r->detalles->keyBy('id_detalle_relevamiento')->toArray();
    $nuevos_detalles   = $this->recalcularDetalles($r->detalles,$nuevos_contadores);
    $r->truncadas = 0;
    foreach($r->detalles as $d){
      $n_d = $nuevos_detalles[$d->id_detalle_relevamiento] ?? null;
      if($n_d === null) continue;
      
      $d->id_tipo_causa_no_toma = $n_d['id_tipo_causa_no_toma'];
      $d->producido_calculado_relevado = $n_d['producido_calculado_relevado'];
      $d->producido_importado = $n_d['producido_importado'];
      $d->diferencia = $n_d['diferencia'];
      $d->id_unidad_medida = $n_d['id_unidad_medida'];
      $d->denominacion = $n_d['denominacion'];
      foreach($this->contadores() as $c){
        $d->{$c} = $n_d[$c];
      }
      $d->save();
      
      $r->truncadas += $n_d['estado'] == 'TRUNCAMIENTO'? 1 : 0;
    }
    $r->save();
  }
  
  public function validarRelevamiento(Request $request){
    $detalles = $this->validarDetalles($request,false);
    Validator::make($request->all(),[
      'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
      'observacion_validacion' => 'nullable|max:2000',
      'detalles' => 'nullable|array',
      'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'detalles.*.a_pedido' => 'nullable|integer|min:0',
    ],self::$mensajesErrores, self::$atributos)->after(function($validator) use ($detalles,&$r){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if(count($detalles) > 0 && $detalles[0]->id_relevamiento != $data['id_relevamiento']){
        return $validator->errors()->add('id_relevamiento','Error de mismatch entre el relevamiento y los detalles');
      }
      $rel = Relevamiento::find($data['id_relevamiento']);
      if($this->validarSector($rel->id_sector) === false){
        return $validator->errors()->add('id_relevamiento','No tiene acceso');
      }
      $id_casino = $rel->sector->id_casino;
      $sin_contadores = $id_casino != 3 && ContadorHorario::where([['id_casino',$id_casino],['fecha',$rel->fecha]])->count() == 0;
      //se ignora el caso de rosario porque el tipo es "responsable"
      if($sin_contadores){
        return $validator->errors()->add('faltan_contadores','No se puede validar el relevamiento debido a que faltan importar los contadores para dicha fecha.');
      }
      if($this->estaValidado($rel)){
        return $validator->errors()->add('id_relevamiento','Ya esta validado');
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$detalles){
      $r = Relevamiento::find($request->id_relevamiento);
      $this->actualizarMTMsRelevamiento($r);
      $this->recalcularRelevamiento($r);
      $r->observacion_validacion = $request->observacion_validacion;
      $r->estado_relevamiento()->associate(4);
      $r->save();

      $sectores = $r->sector->casino->sectores->pluck('id_sector');
      $rels = Relevamiento::where([['fecha', $r->fecha],['backup',0]])->whereIn('id_sector',$sectores)->get();
      $todos_validados = true;
      foreach($rels as $r){
        $todos_validados = $todos_validados && $this->estaValidado($r);
        if(!$todos_validados) break;
      }
      
      if($todos_validados) foreach ($rels as $r) {
        $r->estado_relevamiento()->associate(7);
        $r->save();
      }

      foreach (($request['detalles'] ?? []) as $dat){
        $d = DetalleRelevamiento::find($dat['id_detalle_relevamiento']);    
        if(isset($dat['a_pedido'])){
          MaquinaAPedidoController::getInstancia()->crearPedidoEn($d->id_maquina,$dat['a_pedido'],$d->id_relevamiento);
        }
      }

      return 1;
    });
  }
  
  private function crearPlanilla($relevamiento){
    $rel= new \stdClass();
    $sector = $relevamiento->sector;
    $casino = $sector->casino;
    
    $rel->casinoCod = $casino->codigo;
    $rel->casinoNom = $casino->nombre;
    $rel->sector    = $sector->descripcion;
    
    $rel->nro_relevamiento = $relevamiento->nro_relevamiento;
    $rel->seed = is_null($relevamiento->seed)? '' : $relevamiento->seed;
    $rel->fecha_ejecucion  = $relevamiento->fecha_ejecucion;
    
    $rel->fecha_generacion = implode('-',array_reverse(explode('-',$relevamiento->fecha_generacion)));
    $rel->fecha = implode('-',array_reverse(explode('-',$relevamiento->fecha)));

    $rel->causas_no_toma = TipoCausaNoToma::all();
    $detalles = $relevamiento->detalles->map(function($d){
      $det = new \stdClass();
      $m = $d->maquina;
      $i = $m->isla;
      $tcnt = $d->tipo_causa_no_toma;
      
      $det->maquina       = $m->nro_admin;
      $det->isla          = $i->nro_isla;
      $det->sector        = $i->sector->descripcion;
      $det->marca         = $m->marca_juego;
      $det->unidad_medida = $m->unidad_medida->descripcion;
      $det->formula = $m->formula;//abreviar nombre de contadores de formula
      $det->no_toma = ($tcnt != null)? $tcnt->codigo : null;
      
      foreach($this->contadores() as $cidx => $c){
        $det->{$c} = ($d->{$c} != null) ? number_format($d->{$c}, 2, ",", ".") : "";
      }
      
      return $det;
    })->toArray();

    $view = View::make('planillaRelevamientos2018', compact('detalles','rel'));

    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();

    $font = $dompdf->getFontMetrics()->get_font("Helvetica", "regular");
    $codigo  = ($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX";
    $codigo .= "/{$rel->seed}/{$rel->casinoCod}/{$rel->sector}/{$rel->fecha}/Generado:{$rel->fecha_generacion}";
    
    $dompdf->getCanvas()->page_text(20, 565, $codigo, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(750, 565, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    return $dompdf;
  }

  public function generarPlanillaValidado($id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    $casino = $relevamiento->sector->casino;
    
    $viewrel = new \stdClass();
    $viewrel->fecha = date('Y-m-d',strtotime("{$relevamiento->fecha} -1 day"));//la fecha que encesita la interfaz es la de produccion, el dia previo a la de la fecha del relevamiento
    $viewrel->casinoCod = $casino->codigo;
    $viewrel->casinoNom = $casino->nombre;

    $estados_contador   = [];
    $viewrel->detalles      = collect([]);
    $viewrel->observaciones = [];
    $viewrel->cantidad_habilitadas    = null;
        
    $relevamientos = Relevamiento::where([['fecha', $relevamiento->fecha],['backup',0]])->whereIn('id_sector',$casino->sectores->pluck('id_sector'))->get();
    foreach($relevamientos as $r){
      $this->recalcularRelevamiento($r);
      
      $viewrel->cantidad_habilitadas = $viewrel->cantidad_habilitadas ?? $r->mtms_habilitadas_hoy;
      
      if($r->observacion_validacion !== null)
        $viewrel->observaciones[] = ['zona' => $r->sector->descripcion, 'observacion' =>  $r->observacion_validacion];
      
      $detalles = $r->detalles;
      
      $viewrel->detalles = $viewrel->detalles->merge($detalles->map(function($d) use (&$estados_contador,$r){
        $m = $d->maquina()->withTrashed()->first();
        $i = $m->isla()->withTrashed()->first();
        
        $aux = new \stdClass();
        $aux->nro_admin   = $m->nro_admin;
        $aux->isla        = '-';
        $aux->sector      = '-';
        $aux->observacion = '';
        $aux->no_toma     = '';
        $aux->producido_calculado_relevado = $d->producido_calculado_relevado ?? 0;
        $aux->producido = $d->producido_importado ?? 0;
        
        if($i !== null){
          $aux->isla   = $i->nro_isla;
          $aux->sector = $i->sector->descripcion;
        }
        
        $check =  MaquinaAPedido::where([['id_relevamiento','=', $d->id_relevamiento],['id_maquina','=',$d->id_maquina]])->first();
        if($check != null){
          $aux->observacion = "Se pidió para el {$check->fecha}.";
        }
        
        $estado = $this->obtenerEstadoDetalleRelevamiento($d->producido_importado,$d->diferencia,$d->id_tipo_causa_no_toma);
        $estados_contador[$estado] = ($estados_contador[$estado] ?? 0)+1;
        if($estado == 'CORRECTO'){
          return null;
        }
        else if($estado == 'SIN_IMPORTAR'){
          $aux->no_toma = 'FALTA DE IMPORTACIÓN';
        }
        else if($estado == 'TRUNCAMIENTO'){
          $aux->no_toma = 'TRUNCAMIENTO';
        }
        else if($estado == 'DIFERENCIA'){
          $aux->no_toma = 'ERROR GENERAL';
        }
        else if($estado == 'NO_TOMA'){
          $aux->no_toma = $d->tipo_causa_no_toma->descripcion;
        }
        
        return $aux;
      })->values());
    }
    
    //Si por algun motivo los relevamietnos no los tienen seteados, los calculo para la fecha actual
    $viewrel->cantidad_habilitadas = $viewrel->cantidad_habilitadas ?? $this->calcularMTMsHabilitadas($casino->id_casino);

    /*
    los conceptos del resumen cambiaron por los siguientes:
    relevadas: la totalidad de maquinas del relevamiento
    verificadas: todas las maquinas a las que se le tomaron contadores, sin importar los errores (relevadas-no tomas)
    errores generales: aquellas que tiene la X, es decir la que dio diferencia sin considerar el truncammiento, tampoco se consideran aquellas que dieron error por falta de improtar contadores
    sin toma: persiste el concepto, todos los tipos de no toma
    la isla ya no es necesario en este informe
    */
    
    $viewrel->cantidad_relevadas = count($viewrel->detalles);
    $viewrel->detalles = $viewrel->detalles->filter(function($d){return !is_null($d);})->toArray();
    $viewrel->truncadas = $estados_contador['TRUNCAMIENTO'] ?? 0;
    $viewrel->sin_relevar = ($estados_contador['NO_TOMA'] ?? 0)+($estados_contador['SIN_IMPORTAR'] ?? 0);
    $viewrel->verificadas = $viewrel->cantidad_relevadas - $viewrel->sin_relevar;
    $viewrel->errores_generales = $estados_contador['DIFERENCIA'] ?? 0;
    $viewrel->sin_contadorImportado_relevada = $estados_contador['SIN_IMPORTAR'] ?? 0;
    $view = View::make('planillaRelevamientosValidados',['rel' => $viewrel]);
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4','portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica","regular");
    $dompdf->getCanvas()->page_text(515, 815,"Página {PAGE_NUM} de {PAGE_COUNT}",$font,10,array(0,0,0));
    return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
  }

  private function calcularMTMsHabilitadas($id_casino){
    $estados_habilitados = EstadoMaquina::whereIn('descripcion' ,['Ingreso','Reingreso', 'Eventualidad Observada'])
    ->get()->pluck('id_estado_maquina');
    
    return DB::table('maquina')
    ->join('isla','isla.id_isla','=','maquina.id_isla')
    ->where('maquina.id_casino',$id_casino)
    ->whereIn('maquina.id_estado_maquina',$estados_habilitados)
    ->whereNull('maquina.deleted_at')
    ->whereNull('isla.deleted_at')
    ->count();
  }
  
  public function generarPlanilla($id_relevamiento){
    $r = Relevamiento::find($id_relevamiento);
    if(is_null($r)) return '';
    return $this->crearPlanilla($r)->stream('planilla.pdf', Array('Attachment'=>0));
  }
  
  public function usarRelevamientoBackUp(Request $request){
    $rel_backup = null;
    $reglas = null;
    Validator::make($request->all(),[
      'id_sector' => 'required|exists:sector,id_sector,deleted_at,NULL',
      'fecha_generacion' => 'required|date|before:today',
      'fecha' => 'required|date|after_or_equal:fecha_generacion'
    ],
    array_merge(self::$mensajesErrores,['fecha.after_or_equal' => 'La fecha tiene que ser igual o posterior a la fecha de generación','fecha_generacion.before' => 'La fecha de generación tiene que ser anterior a hoy']),
    self::$atributos)->after(function($validator) use (&$rel_backup,&$reglas){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      if($this->validarSector($data['id_sector']) === false){
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
      
      $this->actualizarMTMsRelevamiento($rel_backup);
      $this->recalcularRelevamiento($rel_backup);
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
      'maquina.id_casino',
      'maquina.id_maquina',
      'maquina.nro_admin as nro_admin',
      'casino.nombre as casino',
      'sector.descripcion as sector',
      'isla.nro_isla as nro_isla'
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
    ], self::$mensajesErrores, self::$atributos)->after(function($validator) use (&$maq){
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
    $id_casino = $maq->id_casino;

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
    ->leftJoin('tipo_causa_no_toma as t','t.id_tipo_causa_no_toma','=','dr.id_tipo_causa_no_toma')
    ->leftJoin('usuario as u','u.id_usuario','=','r.id_usuario_cargador')
    ->leftJoin('contador_horario as ch',function ($q) use($id_casino){
       $q->on('ch.fecha','=','r.fecha')->where('ch.id_casino','=',$id_casino);
    })
    ->leftJoin('detalle_contador_horario as dch','dch.id_contador_horario','=','ch.id_contador_horario')
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
    ],self::$mensajesErrores, self::$atributos)->after(function($validator){
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
      self::$mensajesErrores,self::$atributos)
    ->after(function($validator){
      if($validator->errors()->any()) return;
      if($this->validarSector($validator->getData()['id_sector']) === false){
        return $validator->errors()->add('id_sector','No tiene los privilegios');
      }
    })->validate();

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
    ],
    array_merge(self::$mensajesErrores,['fecha_desde.after_or_equal' => 'Tiene que ser posterior o igual a HOY', 'fecha_hasta.after_or_equal' => 'Tiene que ser posterior o igual a la fecha de inicio']),
    self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      
      $data = $validator->getData();
      $id_sector   = $data['id_sector'];
      
      if($this->validarSector($id_sector) === false){
        return $validator->errors()->add('id_sector','No tiene los privilegios');
      }
      
      if($data['id_tipo_cantidad_maquinas_por_relevamiento'] != 2) return;
      
      $fecha_desde = $data['fecha_desde'];
      $fecha_hasta = $data['fecha_hasta'];
      
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
      'id_unidad_medida' => 'required|exists:unidad_medida,id_unidad_medida'
    ], self::$mensajesErrores, self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $d = DetalleRelevamiento::find($validator->getData()['id_detalle_relevamiento']);
      if($this->estaValidado($d->relevamiento)){
        return $validator->errors()->add('id_detalle_relevamiento','Ya esta validado');
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $d = DetalleRelevamiento::find($request->id_detalle_relevamiento);
      $m = MTMController::getInstancia()->modificarDenominacionYUnidad($request->id_unidad_medida,$d->id_maquina);
      $r = $d->relevamiento;
      $this->actualizarMTMsRelevamiento($r);
      $this->recalcularRelevamiento($r);      
      return ['producido_calculado_relevado' => DetalleRelevamiento::find($request->id_detalle_relevamiento)->producido_calculado_relevado];
    });
  }
    
  private function calcularProducidoRelevado($d,$conts){
    /* Cambio de operaciones a signos
      c1  c2  c3  c4  c5  c6  c7  c8
      |   |   |   |   |   |   |   |
      +   o1  o2  o3  o4  o5  o6  o7
    */
    
    $conts_arr = [];
    foreach($this->contadores() as $cidx => $c){
      $conts_arr[] = empty($conts[$c])? 0.0 : floatval($conts[$c]);//Redondeo?
    }
    
    $m = $d->maquina()->withTrashed()->first();
    $ops_arr = ['+'];
    {
      $formula = $m->formula;
      foreach($this->operadores_formula() as $oidx => $o){
        $ops_arr[] = $formula->{$o} ?? null;
      }
    }
    
    $producido = 0.0;
    {
      $size = count($conts_arr);
      assert($size == count($ops_arr));
      for($idx = 0;$idx<$size;$idx++){
        $c = $conts_arr[$idx];
        $s = $ops_arr[$idx];
        $sign;
        if     ($s == '+'){ $sign =  1; }
        else if($s == '-'){ $sign = -1; }
        else              { $sign =  0; }
        $producido += $sign*$c;
      }
    }
        
    $deno = $m->id_unidad_medida == 2? 1.0 : floatval($m->denominacion);
    return round($producido*$deno,2);
  }
  
  private function validarDetalles(Request $request,$validar_que_haya_un_contador){
    $validation_arr = [
      'detalles.*.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'detalles.*.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
    ];
    foreach($this->contadores() as $cidx => $c){
      $validation_arr["detalles.*.$c"] = ['nullable','regex:/^-?\d\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?\d?([,|.]\d?\d?)?$/'];
    }
    
    $detalles = collect([]);
    Validator::make($request->all(),$validation_arr, self::$mensajesErrores, self::$atributos)->after(function($validator) use (&$detalles,$validar_que_haya_un_contador){
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
      
      if($validar_que_haya_un_contador) foreach($data['detalles'] as $didx => $d){
        $con_contadores = ($d['id_tipo_causa_no_toma'] ?? null) !== null;
        foreach($this->contadores() as $cont){
          if($con_contadores) break;
          $con_contadores = $con_contadores || ($d[$cont] ?? null) !== null;
        }
        if(!$con_contadores) foreach($this->contadores() as $cont){
          $validator->errors()->add("detalles.$didx.$cont",'El valor es requerido');
        }
      }
    })->validate();
    
    return $detalles;
  }
  
  //No modificar sin cambiar el template del modal de carga... usa estos valores de retorno
  private function obtenerEstadoDetalleRelevamiento($importado,$diferencia,$id_tipo_causa_no_toma){
    if(!is_null($id_tipo_causa_no_toma)) return 'NO_TOMA';
    if(is_null($importado)) return 'SIN_IMPORTAR';
    if($diferencia != 0){
      if(ProducidoController::getInstancia()->truncamiento($diferencia)){
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
  
  private function validarSector($id_sector){
    $sector = Sector::find($id_sector);
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    if(!$u->es_superusuario){
      if(is_null($sector) || $u->casinos->pluck('id_casino')->search($sector->id_casino) === false){
        return false;
      }
    }
    return $sector;
  }
  
  private function sacarProducidosSegunPrivilegios($detalle){
    static $u = null;
    $u = $u ?? UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    static $tiene_privilegios = null;
    $tiene_privilegios = $tiene_privilegios ?? $u->tieneAlgunPermiso(['relevamiento_ver','relevamiento_validar']);
    
    static $sacar = null;
    $sacar = $sacar ?? ($tiene_privilegios? [] : ['producido_importado','producido_calculado_relevado','diferencia']);
    
    $d;
    if(is_array($detalle)){
      $d = (object) $detalle;
    }
    else if ($detalle instanceof \Illuminate\Database\Eloquent\Model){
      $d = (object) $detalle->toArray();
    }
    else{
      $d = $detalle;
    }
      
    foreach($sacar as $s) unset($d->{$s});
    return $d;
  }
  
  private function estaValidado($relevamiento){
    return in_array($relevamiento->id_estado_relevamiento,[4,7]);
  }
  public function existeRelVisado($fecha, $id_casino){
    return Relevamiento::join('sector' , 'sector.id_sector' , '=' , 'relevamiento.id_sector')
    ->where([['fecha','=',$fecha],['id_casino','=',$id_casino]])
    ->whereIn('id_estado_relevamiento',[4,7])->count() > 0;
  }
}
