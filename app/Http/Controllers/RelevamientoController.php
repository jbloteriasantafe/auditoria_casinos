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
use App\UnidadMedida;
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
      self::$instance = new self();
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
  
  private static function formatear_numero_ingles($s){
    return str_replace(',','.',preg_replace('/(\.|\s)/','',$s));
  }
  
  private static function formatear_numero_español($s){
    $s = (string) $s;
    $negativo = ($s[0] ?? null) == '-'? '-' : '';
    $abs = strlen($negativo)? substr($s,1) : $s;
    
    $partes = explode('.',$abs);
    $entero = $partes[0] ?? '';
    $decimal = ($partes[1] ?? null) !== null? $partes[1] : null;
    
    $entero_separado = [];
    for($i=0;$i<strlen($entero);$i++){//De atras para adelante voy agregando numeros en baldes
      $bucket = intdiv($i,3);
      $entero_separado[$bucket] = $entero_separado[$bucket] ?? '';
      $c = $entero[strlen($entero)-1-$i];
      $entero_separado[$bucket] = $c.$entero_separado[$bucket];    
    }
    //Puede quedar vacio el ultimo por eso chequeo
    if(count($entero_separado) && strlen($entero_separado[count($entero_separado)-1]) == 0){
      unset($entero_separado[count($entero_separado)-1]);
    }
    $ret = $negativo.implode('.',array_reverse(array_values($entero_separado)));
    if($decimal !== null){
      $ret.=','.$decimal;
    }
    return $ret;
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
  
  private function __pasarDetalleContador($d,$func){
    $newdet = new \stdClass();
    $newdet->detalle = $d->detalle ?? null;
    $newdet->maquina = $d->maquina ?? null;
    $newdet->formula = $d->formula ?? null;
    $newdet->isla    = $d->isla ?? null;
    if($newdet->detalle !== null){
      foreach($this->contadores() as $c){
        if(!isset($newdet->detalle->{$c})) continue;
        $v = $newdet->detalle->{$c};
        $newdet->detalle->{$c} = $v !== null? self::{$func}($v) : null;
      }
      foreach(['producido_importado','producido_calculado_relevado','diferencia','denominacion'] as $attr){
        $v = $newdet->detalle->{$attr};
        $newdet->detalle->{$attr} = $v !== null?
          self::{$func}($v)
        : null;
      }
    }
    if($newdet->maquina !== null){
      $d = ''.$newdet->maquina->denominacion;
      $newdet->maquina->denominacion = $v !== null?
        self::{$func}($d)
      : null;
    }
    return $newdet;
  }
  private function __pasarContadores($detalles,$func){
    $newdets = collect([]);
    foreach($detalles as $didx => $d){      
      $newdets[$didx] = $this->__pasarDetalleContador($d,$func);
    }
    return $newdets;
  }
  
  private function pasarContadoresAIngles($detalles){
    return $this->__pasarContadores($detalles,'formatear_numero_ingles');
  }
  private function pasarContadoresAEspañol($detalles){
    return $this->__pasarContadores($detalles,'formatear_numero_español');
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
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $denominaciones = DB::table('maquina')
    ->select('denominacion',DB::raw('COUNT(*) as cantidad'))
    ->whereNull('deleted_at')
    ->whereIn('id_casino',$casinos->pluck('id_casino'))
    ->where('id_unidad_medida',1)
    ->where('denominacion','<>',1.0)
    ->groupBy('denominacion')
    ->orderBy('cantidad','desc')
    ->get()->pluck('denominacion')
    ->map(function($d){
      return self::formatear_numero_español($d);
    });
    
    return view('Relevamientos/index', [
      'casinos' => $casinos,
      'estados' => EstadoRelevamiento::all(),
      'tipos_cantidad' => TipoCantidadMaquinasPorRelevamiento::all(),
      'tipos_causa_no_toma' => TipoCausaNoToma::all(),
      'CONTADORES' => $this->contadores()->count(),
      'denominaciones' => $denominaciones
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
  
  private function obtenerDetalle($id_detalle_relevamiento){
    $detalle = DetalleRelevamiento::find($id_detalle_relevamiento);
    if(is_null($detalle)) return null;
    $maquina = $detalle->maquina()->withTrashed()->first();
    if(is_null($maquina)) return null;
    $formula = $maquina->formula;
    if(is_null($formula)) return null;
    $isla    = $maquina->isla()->withTrashed()->first();
    if(is_null($isla)) return null;
    
    $d = new \stdClass();
    $d->detalle = (object)($detalle->toArray());
    $d->formula = (object)($formula->toArray());
    $d->maquina = (object)($maquina->toArray());
    $d->isla = (object)($isla->toArray());
    
    $d->detalle->estado = $this->obtenerEstadoDetalleRelevamiento(
      $d->detalle->producido_importado,
      $d->detalle->diferencia,
      $d->detalle->id_tipo_causa_no_toma
    );
    $d->detalle = $this->sacarProducidosSegunPrivilegios($d->detalle);
    
    $d->maquina->denominacion = $maquina->id_unidad_medida == 2? 1 : $maquina->denominacion;
    $d->detalle->denominacion = $detalle->id_unidad_medida == 2? 1 : $detalle->denominacion;
    $d->maquina->id_unidad_medida = $maquina->id_unidad_medida;
    $d->detalle->id_unidad_medida = $detalle->id_unidad_medida;
    return $d;
  }
  
  public function obtenerRelevamiento($id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    if($relevamiento === null) return [];
    if($this->validarSector($relevamiento->id_sector) === false){
      return [];
    }
    $this->recalcularRelevamiento($relevamiento);
    $detalles = $this->pasarContadoresAEspañol(
      $relevamiento->detalles()->select('id_detalle_relevamiento')->get()->keyBy('id_detalle_relevamiento')
      ->map(function($det){//POR CADA MAQUINA EN EL DETALLE BUSCO FORMULA Y UNIDAD DE MEDIDA , Y CALCULO PRODUCIDO
        return $this->obtenerDetalle($det->id_detalle_relevamiento);
      })
      ->filter(function($det){return !is_null($det);})
    );
    
    $sector = $relevamiento->sector;
    $casino = $sector->casino;

    return [
      'relevamiento'         => $relevamiento,
      'casino'               => $casino->nombre,
      'casino_cod'           => $casino->codigo,
      'id_casino'            => $casino->id_casino,
      'sector'               => $sector->descripcion,
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
          $dr->id_unidad_medida    = $maq->id_unidad_medida;
          $dr->denominacion        = $maq->denominacion;
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
      
      $relevamientos_en_carga = Relevamiento::where([
        ['fecha',$fecha_hoy],
        ['id_sector',$id_sector],
        ['backup',0],
        ['id_estado_relevamiento','<>',1]
      ])
      ->get()->count() > 0;
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
        file_put_contents($ruta, $this->crearPlanilla(
          $this->obtenerRelevamiento($r->id_relevamiento)
        )->output());
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
    $detalles = $this->validateDetalles($request,($request->estado ?? null) == '3');
    $r = $this->validateRelevamiento($request,$detalles);
    
    Validator::make($request->all(), [
      'id_usuario_fiscalizador' => 'required_if:estado,3|nullable|exists:usuario,id_usuario,deleted_at,NULL',
      'tecnico' => 'nullable|max:45',
      'observacion_carga' => 'nullable|max:2000',
      'estado' => 'required|numeric|between:2,3',
      'hora_ejecucion' => 'nullable|required_if:estado,3|regex:/^\d\d:\d\d(:\d\d)?/',
    ],self::$mensajesErrores, self::$atributos)->validate();
    
    return DB::transaction(function() use ($request,$detalles,$r){
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
      
      $this->actualizarMTMsRelevamiento($r);
      $this->recalcularRelevamiento($r,$detalles);
      
      return 1;
    });
  }
  
  public function recalcularDetalles($rel,$detalles){
    return $detalles->map(function($d) use ($rel){
      $id_detalle_relevamiento = $d->detalle->id_detalle_relevamiento;
      $producido_importado = $this->calcularProducidoImportado($rel->fecha,$d->maquina);
      $id_tipo_causa_no_toma = $d->detalle->id_tipo_causa_no_toma;
      
      $producido_calculado_relevado = is_null($id_tipo_causa_no_toma)? 
        $this->calcularProducidoRelevado($d) 
      : null;
      
      $diferencia = round($producido_calculado_relevado - $producido_importado,2);
      $estado     = $this->obtenerEstadoDetalleRelevamiento(
        $producido_importado,
        $diferencia,
        $id_tipo_causa_no_toma
      );
      
      $id_unidad_medida = $d->detalle->id_unidad_medida;
      $denominacion     = $id_unidad_medida == 2?
        1.0
      : $d->detalle->denominacion;
      
      $nd = (object) compact('id_detalle_relevamiento','producido_calculado_relevado','producido_importado','diferencia','estado','id_unidad_medida','denominacion','id_tipo_causa_no_toma');
      
      foreach($this->contadores() as $cidx => $c){
        $nd->{$c} = is_null($id_tipo_causa_no_toma)? ($d->detalle->{$c} ?? null) : null;
      }
      
      return (object)[
        'detalle' => $nd,
        'maquina' => $d->maquina,
        'isla' => $d->isla,
        'formula' => $d->formula,
      ];
    });
  }
  
  private function obtenerDetallesDeRequest($request){
    $dets = collect([]);
    $drbd_attrs = null;
    foreach(($request->detalles ?? []) as $didx => $d){      
      $iddr = $d['detalle']['id_detalle_relevamiento'];
      $drbd = DetalleRelevamiento::find($iddr);
      $drbd_attrs = $drbd_attrs ?? array_keys($drbd->toArray());
      
      $nd = new \stdClass();
      $nd->detalle = (object) $d['detalle'];
      $nd = $this->__pasarDetalleContador($nd,'formatear_numero_ingles');//El detalle viene en español
      
      $nd->a_pedido = $d['a_pedido'] ?? null;
      
      foreach($drbd_attrs as $attr){
        if(isset($nd->detalle->{$attr})) continue;
        $nd->detalle->{$attr} = $drbd->{$attr};
      }
      
      $nd->detalle->id_unidad_medida = $nd->detalle->denominacion == '1'? 2 : 1;
            
      $nd->maquina = (object) Maquina::find($d['maquina']['id_maquina'])->toArray();
      $nd->maquina->denominacion = self::formatear_numero_ingles($d['maquina']['denominacion']);
      $nd->maquina->id_unidad_medida = $nd->maquina->denominacion == '1'? 2 : 1;
      
      $nd->isla = (object) Isla::find($d['isla']['id_isla'])->toArray();
      $nd->formula = (object) Formula::find($d['formula']['id_formula'])->toArray();
      $dets[$iddr] = $nd;
    }
    return $dets;
  }
  
  public function calcularEstadoDetalleRelevamiento(Request $request){
    $newdets = $this->validateDetalles($request,false);
    $olddets = collect([]);
    $relevamiento = null;
    
    foreach($newdets as $didx => $d){
      $iddr = $d->detalle->id_detalle_relevamiento;
      $relevamiento = $relevamiento ?? DetalleRelevamiento::find($iddr)->relevamiento;
      $olddets[$iddr] = $this->obtenerDetalle($iddr);
    }
    
    return DB::transaction(function() use ($request,$relevamiento,$newdets,$olddets){
      if(count($newdets) <= 0) return [];
      //Solo recalculo si esta relevando, sino uso directamente del detalle
      $calcular = in_array($relevamiento->id_estado_relevamiento,[1,2]);
            
      if($calcular){
        $newdets = $this->recalcularDetalles($relevamiento,$newdets);
      }
      else {
        $newdets = $olddets;
      }
      return $this->pasarContadoresAEspañol($newdets)->map(function($d){
        return $this->sacarProducidosSegunPrivilegios($d);
      });
    });
  }
  
  private function actualizarMTMsRelevamiento($r){
    if($this->estaValidado($r)) return;//Solo recalculo los no validados?
    
    $id_casino = $r->sector->id_casino;
    $r->mtms_habilitadas_hoy = $this->calcularMTMsHabilitadas($id_casino);//No deberia usar el sector???
    $r->mtms_sin_isla = Maquina::where('id_casino',$id_casino)->whereNull('id_isla')->count();//No deberia usar el sector???
  }
  
  public function recalcularRelevamiento($r,$detalles=null){
    if($this->estaValidado($r)) return;//Solo recalculo los no validados?
    
    if($detalles === null){
      $detalles = $r->detalles->keyBy('id_detalle_relevamiento')->map(function($d){
        $nd = new \stdClass();
        $nd->detalle = (object) $d->toArray();
        $nd->maquina = (object) $d->maquina->toArray();
        $nd->isla = (object) $d->maquina->isla->toArray();
        $nd->formula = (object) $d->maquina->formula->toArray();
        return $nd;
      });
    }
    $detalles = $this->recalcularDetalles($r,$detalles);
    $r->truncadas = 0;
    foreach($r->detalles as &$d){
      $n_d = $detalles[$d->id_detalle_relevamiento] ?? null;
      if($n_d === null) continue;

      foreach($n_d->detalle as $attr => $val){
        if(array_key_exists($attr,$d->toArray())){//No puedo usar isset porque $d->{$attr} puede ser nulo yretorna falso...
          $d->{$attr} = $n_d->detalle->{$attr};
        }
      }
      
      $d->save();
      
      $r->truncadas += $n_d->detalle->estado == 'TRUNCAMIENTO'? 1 : 0;
    }
    
    $r->save();
  }
  
  public function validarRelevamiento(Request $request){
    $detalles = $this->validateDetalles($request,false);
    $r = $this->validateRelevamiento($request,$detalles);
    
    Validator::make($request->all(),[
      'observacion_validacion' => 'nullable|max:2000',
      'detalles.*.a_pedido' => 'nullable|integer|min:0',
    ],self::$mensajesErrores, self::$atributos)->after(function($validator) use ($r){
      if($validator->errors()->any()) return;
      
      $id_casino = $r->sector->id_casino;
      $sin_contadores = $id_casino != 3 && ContadorHorario::where([
        ['id_casino','=',$id_casino],
        ['fecha','=',$r->fecha]
      ])->count() == 0;
      //se ignora el caso de rosario porque el tipo es "responsable"
      if($sin_contadores){
        return $validator->errors()->add('faltan_contadores','No se puede validar el relevamiento debido a que faltan importar los contadores para dicha fecha.');
      }
    })->validate();
        
    return DB::transaction(function() use ($request,$detalles,$r){
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

      foreach ($detalles as $d){
        $a_pedido = $d->a_pedido ?? null;
        if(!empty($a_pedido)){
          //Esto genera una MTM a pedido a N dias desde HOY
          //No se si es lo correcto... supongo que si van validando por dia
          //va a dar lo esperado pero si validan un relevamiento viejo?
          MaquinaAPedidoController::getInstancia()->crearPedidoEn(
            $d->maquina->id_maquina,
            $a_pedido,
            $r->id_relevamiento
          );
        }
      }

      return 1;
    });
  }
  
  private function crearPlanilla($data){
    $rel= new \stdClass();
    
    $rel->casinoCod = $data['casino_cod'];
    $rel->casinoNom = $data['casino'];
    $rel->sector    = $data['sector'];
    
    $rel->nro_relevamiento = $data['relevamiento']->nro_relevamiento;
    $rel->seed = $data['relevamiento']->seed ?? '';
    $rel->fecha_ejecucion  = $data['relevamiento']->fecha_ejecucion;
    
    $rel->fecha_generacion = implode('-',array_reverse(explode('-',$data['relevamiento']->fecha_generacion)));
    $rel->fecha = implode('-',array_reverse(explode('-',$data['relevamiento']->fecha)));

    $rel->causas_no_toma = TipoCausaNoToma::all()->keyBy('id_tipo_causa_no_toma');
    $unidades_medida = UnidadMedida::all()->keyBy('id_unidad_medida');
    
    $detalles = $data['detalles']->map(function($d) use ($rel,$unidades_medida){
      $det = new \stdClass();            
      $det->maquina = $d->maquina->nro_admin;
      $det->marca   = $d->maquina->marca_juego;
      $det->sector  = $rel->sector;
      $det->isla    = $d->isla->nro_isla;
      $det->formula = $d->formula;//abreviar nombre de contadores de formula
      
      $det->unidad_medida = $unidades_medida[$d->detalle->id_unidad_medida] ?? null;
      $det->unidad_medida = $det->unidad_medida? $det->unidad_medida->descripcion : null;
      $det->no_toma = $rel->causas_no_toma[$d->detalle->id_tipo_causa_no_toma] ?? null;
      $det->no_toma = $det->no_toma? $det->no_toma->codigo : null;
      
      foreach($this->contadores() as $cidx => $c){
        $det->{$c} = $d->detalle->{$c} ?? '';
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
        
    $relevamientos = Relevamiento::where([
      ['fecha', $relevamiento->fecha],
      ['backup',0]
    ])->whereIn('id_sector',$casino->sectores->pluck('id_sector'))
    ->select('id_relevamiento')->get()->pluck('id_relevamiento');
    
    $tipos_causa_no_toma = TipoCausaNoToma::all()->keyBy('id_tipo_causa_no_toma');
    
    foreach($relevamientos as $id_relevamiento){
      $data = $this->obtenerRelevamiento($id_relevamiento);
      
      $viewrel->cantidad_habilitadas = $viewrel->cantidad_habilitadas ?? $data['relevamiento']->mtms_habilitadas_hoy;
      
      if($data['relevamiento']->observacion_validacion !== null){
        $viewrel->observaciones[] = [
          'zona' => $data['sector'],
          'observacion' => $data['relevamiento']->observacion_validacion
        ];
      }
            
      $viewrel->detalles = $viewrel->detalles->merge($data['detalles']->map(function($d) use (&$estados_contador,$data,$tipos_causa_no_toma){
        $aux = new \stdClass();
        $aux->nro_admin   = $d->maquina->nro_admin;
        $aux->isla        = $d->isla->nro_isla;
        $aux->sector      = $data['sector'];
        $aux->producido_calculado_relevado = $d->detalle->producido_calculado_relevado ?? 0;
        $aux->producido = $d->detalle->producido_importado ?? 0;
        
        $map = MaquinaAPedido::where([
          ['id_relevamiento','=', $d->detalle->id_relevamiento],
          ['id_maquina','=',$d->detalle->id_maquina]
        ])->first();
        $aux->observacion = $map !== null? "Se pidió para el {$map->fecha}." : '';
        
        $estado = $this->obtenerEstadoDetalleRelevamiento(
          $d->detalle->producido_importado,
          $d->detalle->diferencia,
          $d->detalle->id_tipo_causa_no_toma
        );
        $estados_contador[$estado] = ($estados_contador[$estado] ?? 0)+1;
        $aux->no_toma = '';
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
          $aux->no_toma = $tipos_causa_no_toma[$d->detalle->id_tipo_causa_no_toma] ?? null;
          $aux->no_toma = $aux->no_toma !== null? $aux->no_toma->descripcion : '¡NO_TOMA_BORRADA!';
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
    $rel = $this->obtenerRelevamiento($id_relevamiento);
    if(empty($rel)) return '';
    
    return $this->crearPlanilla($rel)->stream('planilla.pdf', Array('Attachment'=>0));
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
  
  private function __modificarDenominacion(Request $request,$dfunc){
    $detalles = $this->validateDetalles($request,false);
    $request->merge([
      'id_relevamiento' => count($detalles)? 
        $detalles[$detalles->keys()[0]]->detalle->id_relevamiento
      : null
    ]);
    $r = $this->validateRelevamiento($request,$detalles);
    
    return DB::transaction(function() use ($request,$detalles,$r,$dfunc){
      foreach($detalles as $d){
        $dfunc($d,$r,$request);
      }
      
      if($r !== null){
        $this->actualizarMTMsRelevamiento($r);
        $this->recalcularRelevamiento($r);    
      }
      
      return $this->pasarContadoresAEspañol(
        $r->detalles()->whereIn('id_detalle_relevamiento',$detalles->keys())
        ->select('id_detalle_relevamiento')
        ->get()->keyBy('id_detalle_relevamiento')
        ->map(function($det){//POR CADA MAQUINA EN EL DETALLE BUSCO FORMULA Y UNIDAD DE MEDIDA , Y CALCULO PRODUCIDO
          return $this->obtenerDetalle($det->id_detalle_relevamiento);
        })
        ->filter(function($det){return !is_null($det);})
      );
    });
  }
  
  public function modificarDenominacionYUnidadMTM(Request $request){
    return $this->__modificarDenominacion($request,function($d){
      $m = Maquina::find($d->maquina->id_maquina);
      if($m === null) return;
      MTMController::getInstancia()->modificarDenominacionYUnidad(
        $d->maquina->id_unidad_medida,
        $d->maquina->denominacion,
        $m->id_maquina
      );
    });
  }
  
  public function modificarDenominacionYUnidadDetalle(Request $request){
    return $this->__modificarDenominacion($request,function($d){
      $dbd = DetalleRelevamiento::find($d->detalle->id_detalle_relevamiento);
      $dbd->id_unidad_medida = $d->detalle->id_unidad_medida;
      $dbd->denominacion = $d->detalle->denominacion;
      $dbd->save();
    });
  }
    
  private function calcularProducidoRelevado($d){
    /* Cambio de operaciones a signos
      c1  c2  c3  c4  c5  c6  c7  c8
      |   |   |   |   |   |   |   |
      +   o1  o2  o3  o4  o5  o6  o7
    */
    
    $conts_arr = [];
    foreach($this->contadores() as $cidx => $c){
      $conts_arr[] = empty($d->detalle->{$c})? 0.0 : floatval($d->detalle->{$c});//Redondeo?
    }
    
    $ops_arr = ['+'];
    foreach($this->operadores_formula() as $oidx => $o){
      $ops_arr[] = $d->formula->{$o} ?? null;
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
        
    $deno = $d->detalle->id_unidad_medida == 2? 1.0 : floatval($d->detalle->denominacion);
    return round($producido*$deno,2);
  }
   
  private function validateDetalles(Request $request,$validar_que_haya_un_contador){
    $validation_arr = [
      'detalles.*.detalle.id_detalle_relevamiento' => 'required|exists:detalle_relevamiento,id_detalle_relevamiento',
      'detalles.*.detalle.denominacion' => ['required','regex:/^-?((\.|\s)*\d){1,8}(,\d?\d?)?$/'],
      'detalles.*.detalle.id_tipo_causa_no_toma' => 'nullable|exists:tipo_causa_no_toma,id_tipo_causa_no_toma',
      'detalles.*.maquina.id_maquina' => 'required|exists:maquina,id_maquina,deleted_at,NULL',
      'detalles.*.maquina.denominacion' => ['required','regex:/^-?((\.|\s)*\d){1,8}(,\d?\d?)?$/'],
    ];
    foreach($this->contadores() as $cidx => $c){
      $validation_arr["detalles.*.detalle.$c"] = ['nullable','regex:/^-?((\.|\s)*\d){1,12}(,\d?\d?)?$/'];
    }
    
    Validator::make($request->all(),$validation_arr, self::$mensajesErrores, self::$atributos)->after(function($validator) use ($validar_que_haya_un_contador){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $detalles = DetalleRelevamiento::whereIn(
        'id_detalle_relevamiento',
        array_map(function($d){return $d['detalle']['id_detalle_relevamiento'];},$data['detalles'] ?? [])
      )->get()->keyBy('id_detalle_relevamiento');
      
      $id_relevamiento = null;
      foreach($detalles as $d){
        if($d->id_relevamiento != $id_relevamiento && !is_null($id_relevamiento)){
          return $validator->errors()->add('id_relevamiento','No coinciden los detalles');
        }
        $id_relevamiento = $id_relevamiento ?? $d->id_relevamiento;
      }
      
      if($validator->errors()->any()) return;
      
      if(!is_null($id_relevamiento)){
        $r = Relevamiento::find($id_relevamiento);
        $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
        $id_casino = $r->sector()->withTrashed()->first()->casino->id_casino;
        if(!$u->casinos->pluck('id_casino')->contains($id_casino)){
          return $validator->errors()->add('id_relevamiento','No puede acceder a ese relevamiento');
        }
      }
      
      if($validar_que_haya_un_contador) foreach($data['detalles'] as $didx => $d){
        $con_contadores = ($d['detalle']['id_tipo_causa_no_toma'] ?? null) !== null;
        foreach($this->contadores() as $cont){
          if($con_contadores) break;
          $con_contadores = $con_contadores || ($d['detalle'][$cont] ?? null) !== null;
        }
        if(!$con_contadores) foreach($this->contadores() as $cont){
          $validator->errors()->add("detalles.$didx.detalle.$cont",'El valor es requerido');
        }
      }
    })->validate();
    
    return $this->obtenerDetallesDeRequest($request);
  }
  
  private function validateRelevamiento(Request $request,$detalles){
    $r = null;
    
    Validator::make($request->all(),[
      'id_relevamiento' => 'required|exists:relevamiento,id_relevamiento',
    ],self::$mensajesErrores, self::$atributos)->after(function($validator) use ($detalles,&$r){
      if($validator->errors()->any()) return;

      $id_relevamiento = $validator->getData()['id_relevamiento'];
      $d = count($detalles)? $detalles[$detalles->keys()[0]] : null;
      if(is_null($d)) return;
      if($d->detalle->id_relevamiento != $id_relevamiento){//Solo necesito chequear el primero, los demas se chequean en validateDetalles
        return $validator->errors()->add('id_relevamiento','Error de mismatch entre detalles y relevamiento');
      }

      $r = Relevamiento::find($id_relevamiento);
      if($this->validarSector($r->id_sector) === false){
        return $validator->errors()->add('id_relevamiento','No tiene acceso');
      }
      if($this->estaValidado($r)){
        return $validator->errors()->add('id_relevamiento','El relevamiento ya esta validado');
      }
    })->validate();
    
    return $r;
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
