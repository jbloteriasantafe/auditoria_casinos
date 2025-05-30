<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Usuario;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\DB;
use App\RelevamientoProgresivo;
use App\DetalleRelevamientoProgresivo;
use Validator;
use View;
use Dompdf\Dompdf;
use PDF;

use Zipper;
use File;

use App\Sector;
use App\Pozo;
use App\Casino;
use App\TipoMoneda;
use App\DetalleLayoutParcial;
use App\LayoutParcial;
use App\EstadoRelevamiento;
use App\Progresivo;
use App\NivelProgresivo;
use App\CampoConDiferencia;
use App\Maquina;
use App\LayoutTotal;
use App\DetalleLayoutTotal;
use App\MaquinaAPedido;
use App\Isla;
use App\TipoCausaNoTomaProgresivo;


class RelevamientoProgresivoController extends Controller
{
  private static $atributos = [
  ];
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new RelevamientoProgresivoController();
    }
    return self::$instance;
  }
  
  private static function SIMPLIFY_INT_RANGES(Array $list){
    if(count($list) == 0) return '';
    $init_range = $list[0];
    $end_range  = $init_range;
    $list = array_slice($list,1);
    $newlist = [];
    foreach($list as $i){
      if($i == $end_range) continue;
      if($i != ($end_range+1)){
        $newlist[] = [$init_range,$end_range];
        $init_range = $i;
      }
      $end_range = $i;
    }
    $newlist[] = [$init_range,$end_range];
    $newlist = array_map(function($r){
      $sep = $r[1] != ($r[0]+1)? '-' : ',';
      return $r[0]!=$r[1]? "$r[0]$sep$r[1]" : $r[0];
    },$newlist);
    return implode(",",$newlist);
  }
  
  private function relevamientoCompleto($id_relevamiento_progresivo){
    $relevamiento = RelevamientoProgresivo::findOrFail($id_relevamiento_progresivo);
    $detalles = [];
    $MAX_LVL = (new DetalleRelevamientoProgresivo)->max_lvl;
    foreach ($relevamiento->detalles as $drel) {
      $pozo = Pozo::withTrashed()->find($drel->id_pozo);
      if($pozo == null) continue;
      $progresivo = $pozo->progresivo()->withTrashed()->get()->first();
      
      $nro_admins  = $progresivo->maquinas()
      ->select('maquina.nro_admin')->distinct()
      ->orderBy('maquina.nro_admin','asc')
      ->get()->map(function($m){return intval($m->nro_admin);})->toArray();
      
      $nro_islas = $progresivo->maquinas()
      ->join('isla','isla.id_isla','=','maquina.id_isla')
      ->selectRaw('DISTINCT isla.nro_isla, isla.orden')
      ->orderBy('isla.nro_isla','asc')
      ->orderBy('isla.orden','asc')
      ->get();
      
      $d = new \stdClass;
      $d->id_detalle_relevamiento_progresivo = $drel->id_detalle_relevamiento_progresivo;
      $d->nro_islas_arr  = $nro_islas->map(function($i){return intval($i->nro_isla);})->toArray();
      $d->ordenes_arr    = $nro_islas->map(function($i){return intval($i->orden);})->toArray();
      $d->nro_admins_arr = $nro_admins;
      sort($d->ordenes_arr);//nro_islas_arr y nro_admins_arr ya estan ordenados
      
      $d->nro_islas     = self::SIMPLIFY_INT_RANGES($d->nro_islas_arr);
      $d->nro_admins    = self::SIMPLIFY_INT_RANGES($nro_admins);
      $d->pozo_unico    = count($progresivo->pozos) == 1;//@HACK: ver como hacer cuando esta elimiando el pozo
      $d->descripcion_pozo   = $pozo->descripcion;
      $d->id_pozo       = $pozo->id_pozo;
      $d->nombre_progresivo = $progresivo->nombre;
      $d->es_individual = $progresivo->es_individual;//@HACK: ver como hacer cuando esta elimiando el pozo
      $d->niveles       = $pozo->niveles()->orderBy('nivel_progresivo.nro_nivel','asc')->get()
      ->map(function($n) use ($drel,$MAX_LVL){
        if($n->nro_nivel < 1 || $n->nro_nivel > $MAX_LVL) return null;
        $ret = new \stdClass;
        $ret->base         = $n->base;
        $ret->nombre_nivel = $n->nombre_nivel;
        $ret->nro_nivel    = $n->nro_nivel;
        $ret->valor        = $drel->{"nivel$n->nro_nivel"} ?? '';
        $ret->id_nivel_progresivo = $n->id_nivel_progresivo;
        return $ret;
      })
      ->reject(function($n){return is_null($n);})->toArray();
      
      $d->id_tipo_causa_no_toma_progresivo   = $drel->id_tipo_causa_no_toma_progresivo;
      $d->causa_no_toma_progresivo = is_null($drel->tipo_causa_no_toma)? null : $drel->tipo_causa_no_toma->descripcion;
      $detalles[]=$d;
    }
    {
      $ROS = $relevamiento->sector->id_casino == 3;
      $sort_int_arrs = function($a,$b){//Usado para devolver el "mas chico" de los arreglos
        $count_a = count($a);
        $count_b = count($b);
        for($idx=0;$idx<min($count_a,$count_b);$idx++){
          $val_a = $a[$idx];
          $val_b = $b[$idx];
          if($val_a > $val_b) return 1;
          if($val_a < $val_b) return -1;
        }
        if($count_a > $count_b) return 1;
        if($count_a < $count_b) return -1;
        return 0;//Son iguales
      };
      usort($detalles,function($a,$b) use ($ROS,$sort_int_arrs){
        if($ROS){
          $s = $sort_int_arrs($a->ordenes_arr,$b->ordenes_arr);
          if($s != 0) return $s > 0;
        }
        $s = $sort_int_arrs($a->nro_islas_arr,$b->nro_islas_arr);
        if($s != 0) return $s > 0;
        $s = $sort_int_arrs($a->nro_admins_arr,$b->nro_admins_arr);
        return $s > 0;
      });
    }
    return $detalles;
  }

  public function obtenerRelevamiento($id_relevamiento_progresivo){
    $relevamiento = RelevamientoProgresivo::findOrFail($id_relevamiento_progresivo);
    $detalles = $this->relevamientoCompleto($id_relevamiento_progresivo);
    return [
      'relevamiento' => $relevamiento,
      'detalles' => $detalles, 
      'sector' => $relevamiento->sector,
      'casino' => $relevamiento->sector->casino,
      'usuario_cargador' => $relevamiento->usuario_cargador,
      'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador
    ];
  }
  
  public function generarPlanillaProgresivos(int $id_relevamiento_progresivo,bool $sin = false){
    $rel = $this->obtenerRelevamiento($id_relevamiento_progresivo);
    if($sin){
      foreach(($rel['detalles'] ?? []) as &$d){
        foreach($d->niveles as &$n) 
          $n->valor = '';
        $d->id_tipo_causa_no_toma = null;
        $d->causa_no_toma_progresivo = '';
      }
      $rel['relevamiento']->observacion_validacion = null;
      $rel['relevamiento']->observacion_carga      = null;
      $rel['relevamiento']->fecha_ejecucion        = null;
      $rel['usuario_fiscalizador']                 = null;
    }
    else{
      $RC = \App\Http\Controllers\RelevamientoController::getInstancia();
      foreach(($rel['detalles'] ?? []) as &$d){
        foreach($d->niveles as &$n) 
          $n->valor = $RC::formatear_numero_español($n->valor);
      }
    }
    $html = false;
    $dompdf = $this->crearPlanillaProgresivos($rel,$html);//poner en true si se quiere ver como html (DEBUG)

    if($html) return $dompdf;
    return $dompdf->stream("Relevamiento_Progresivo_". $rel['sector']->descripcion ."_".date('Y-m-d').".pdf", ['Attachment'=>0]);
  }
  
  public function crearPlanillaProgresivos($rel,$html = false){
    $rel['detalles_linkeados']    = [];
    $rel['detalles_individuales'] = [];
    foreach($rel['detalles'] as $d){
      $rel[$d->es_individual? 'detalles_individuales' : 'detalles_linkeados'][] = $d;
    }
    $rel['MAX_LVL'] = (new DetalleRelevamientoProgresivo)->max_lvl;
    $view = View::make('planillaRelevamientosProgresivo',compact('rel'));
    if(!$html){
      $dompdf  = new Dompdf();
      $font    = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $nro_rel = $rel['relevamiento']->nro_relevamiento_progresivo;
      $CAS     = $rel['casino']->codigo;
      $sector  = $rel['sector']->descripcion;
      $dompdf->getCanvas()->page_text(20, 575, "$nro_rel/$CAS/$sector", $font, 10, [0,0,0]);
      $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, [0,0,0]);
      $dompdf->set_paper('A4', 'landscape');
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      return $dompdf;
    }
    return $view;
  }
  
  public function buscarTodo(){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = $usuario->casinos;
    UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Progresivo' , 'relevamientosProgresivo');
    return view('seccionRelevamientoProgresivo',[
      'casinos'        => $casinos,
      'tipo_monedas'   => TipoMoneda::all(),
      'estados'        => EstadoRelevamiento::all(),
      'fiscalizadores' => $this->obtenerFiscalizadores($casinos,$usuario),
      'causasNoToma'   => TipoCausaNoTomaProgresivo::all(),
      'puede_fiscalizar' => $usuario->es_fiscalizador || $usuario->es_superusuario,
      'puede_validar' => $usuario->es_administrador || $usuario->es_superusuario || $usuario->es_control,
      'puede_eliminar' => $usuario->es_administrador || $usuario->es_superusuario,
      'puede_modificar_valores' => $usuario->es_administrador || $usuario->es_superusuario,
      'niveles' => (new DetalleRelevamientoProgresivo)->max_lvl
    ])->render();
  }

  public function buscarRelevamientosProgresivos(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->fecha_generacion)){
      $fecha_desde = $request->fecha_generacion . ' 00:00:00';
      $fecha_hasta = $request->fecha_generacion . ' 23:59:59';
      $reglas[]=['relevamiento_progresivo.fecha_generacion','>=',$fecha_desde];
      $reglas[]=['relevamiento_progresivo.fecha_generacion','<=',$fecha_hasta];
    }

    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if($request->sector != 0){
      $reglas[]=['sector.id_sector', '=', $request->sector];
    }
    if(!empty($request->estadoRelevamiento)){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->estadoRelevamiento];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('relevamiento_progresivo')
    ->select('relevamiento_progresivo.*'  , 'sector.descripcion as sector' , 'casino.nombre as casino' , 'estado_relevamiento.descripcion as estado')
    ->join('sector' ,'sector.id_sector' , '=' , 'relevamiento_progresivo.id_sector')
    ->join('casino' , 'sector.id_casino' , '=' , 'casino.id_casino')
    ->join('estado_relevamiento' , 'relevamiento_progresivo.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
    ->when($sort_by,function($query) use ($sort_by){
      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
    })
    ->where($reglas)
    ->whereIn('casino.id_casino' , $casinos)
    ->where('backup','=',0)->paginate($request->page_size);

    return $resultados;
  }

  private function obtenerFiscalizadores($casinos,$user){
    $UC = UsuarioController::getInstancia();
    $fiscalizadores = [];
    foreach($casinos as $c){
      $fs = $UC->obtenerFiscalizadores($c->id_casino,$user->id_usuario);
      $cas = [];
      foreach($fs as $f){
        $cas[] = ['id_usuario' => $f->id_usuario,'nombre' => $f->nombre];
      }
      $fiscalizadores[$c->id_casino] = $cas;
    }
    return $fiscalizadores;
  }
  
  public function crearRelevamientoProgresivos(Request $request){
    $fiscalizador = UsuarioController::getInstancia()->quienSoy()['usuario'];
    
    Validator::make($request->all(),[
      'id_sector' => 'required|exists:sector,id_sector',
      'fecha_generacion' => 'required|date|before_or_equal:' . date('Y-m-d H:i:s'),
    ],[], self::$atributos)->after(function($validator){})->validate();

    $id_pozos = DB::table('pozo')
    ->select('pozo.id_pozo')
    ->join('progresivo','progresivo.id_progresivo','=','pozo.id_progresivo')
    ->join('maquina_tiene_progresivo', 'pozo.id_progresivo', '=', 'maquina_tiene_progresivo.id_progresivo')
    ->join('maquina', 'maquina.id_maquina', '=', 'maquina_tiene_progresivo.id_maquina')
    ->join('isla','maquina.id_isla','=','isla.id_isla')
    ->join('sector','isla.id_sector','=','sector.id_sector')
    ->where('sector.id_sector','=',$request->id_sector)
    ->whereNull('pozo.deleted_at')
    ->whereNull('progresivo.deleted_at')
    ->groupBy('pozo.id_progresivo', 'pozo.id_pozo')
    ->get()->pluck('id_pozo');

    $id_casino = Sector::withTrashed()->find($request->id_sector)->get()->first()->casino->id_casino;
    $minimos_por_moneda = [];
    foreach(TipoMoneda::all() as $tm){
      $min = $this->obtenerMinimorelevamientoProgresivo($id_casino,$tm->id_tipo_moneda);
      $minimos_por_moneda[$tm->id_tipo_moneda] = $min;
    }
    
    $detalles = [];
    foreach($id_pozos as $id_pozo){
      $pozo = Pozo::find($id_pozo);
      $moneda_pozo = $pozo->progresivo->id_tipo_moneda;
      foreach ($pozo->niveles as $nivel) {
        if ($nivel->base >= $minimos_por_moneda[$moneda_pozo]) {
          $detalle = new DetalleRelevamientoProgresivo;
          $detalle->id_pozo = $id_pozo;
          $detalles[] = $detalle;
          break;
        }
      }
    }
    
    if(empty($detalles)) return ['codigo' => 500]; //error, no existen progresivos para relevar.

    //creo y guardo el relevamiento progresivo
    DB::transaction(function() use($request,$fiscalizador,$detalles){
      $relevamiento_progresivo = new RelevamientoProgresivo;
      $relevamiento_progresivo->nro_relevamiento_progresivo = DB::table('relevamiento_progresivo')->max('nro_relevamiento_progresivo') + 1;
      $relevamiento_progresivo->fecha_generacion = $request->fecha_generacion;
      $relevamiento_progresivo->id_sector = $request->id_sector;
      $relevamiento_progresivo->id_estado_relevamiento = 1;
      $relevamiento_progresivo->id_usuario_cargador = $fiscalizador->id_usuario;
      $relevamiento_progresivo->backup = 0;
      $relevamiento_progresivo->save();
      foreach($detalles as $detalle){
        $detalle->id_relevamiento_progresivo = $relevamiento_progresivo->id_relevamiento_progresivo; //DB::table('relevamiento_progresivo')->max('id_relevamiento_progresivo');
        $detalle->save();
      }
    });
    
    return ['codigo' => 200];
  }

  public function cargarRelevamiento(Request $request,$validar = true){
    $rules = [
      'id_relevamiento_progresivo' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo',
      'observaciones'              => 'nullable|string|max:200',
    ];
    $rules = array_merge($rules,$validar? [
      'id_usuario_fiscalizador' => 'required|exists:usuario,id_usuario',
      'fecha_ejecucion' => 'required',
      'detalles.*'      => 'nullable|array',
      'detalles.*.id_detalle_relevamiento_progresivo' => 'required|numeric|exists:detalle_relevamiento_progresivo,id_detalle_relevamiento_progresivo',
      'detalles.*.id_tipo_causa_no_toma' => 'nullable|integer|exists:tipo_causa_no_toma_progresivo,id_tipo_causa_no_toma_progresivo',
      'detalles.*.niveles'               => 'nullable|array',
      'detalles.*.niveles.*'             => 'nullable',
      'detalles.*.niveles.*.id_nivel'    => 'required|integer|exists:nivel_progresivo,id_nivel_progresivo',
      'detalles.*.niveles.*.valor'       => 'required_without:detalles.*.id_tipo_causa_no_toma|numeric|min:0',
    ] : []);
    Validator::make($request->all(),$rules, [
      'detalles.*.niveles.*.valor.numeric' => 'El valor de no es numerico',
      'detalles.*.niveles.*.valor.min' => 'El valor tiene que ser positivo',
      'detalles.*.niveles.*.valor.required_without' => 'El valor es requerido',
      'observaciones.max' => 'La observacion supera los 200 caracteres',
    ], self::$atributos)->after(function($validator) use ($validar){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $relevamiento = RelevamientoProgresivo::find($data['id_relevamiento_progresivo']);
      if($data['fecha_ejecucion'] && $data['fecha_ejecucion'] < $relevamiento->fecha_generacion){
        $validator->errors()->add('error_fecha_ejecucion', 'La fecha de ejecución no puede ser inferior a la fecha de generación del relevamiento');
      }
      if($data['id_usuario_fiscalizador']){
        $UC = UsuarioController::getInstancia();
        if(!$UC->usuarioTieneCasinoCorrespondiente($data['id_usuario_fiscalizador'],$relevamiento->sector->id_casino)){
          return $validator->errors()->add('error_usuario_tiene_casino','No existe ningún casino asociado al fiscalizador ingresado');
        }
        if(!$UC->usuarioEsFiscalizador($data['id_usuario_fiscalizador'])){
          return $validator->errors()->add('error_usuario_es_fiscalizador','El usuario ingresado no es fiscalizador');
        }
      }
    })->validate();

    DB::transaction(function() use($request){
      $rel = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
      $rel->usuario_fiscalizador()->dissociate();
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      $rel->fecha_ejecucion = $request->fecha_ejecucion;
      $rel->estado_relevamiento()->associate(3); // id_estado finalizado
      $rel->observacion_carga = $request->observaciones;
      $rel->save();
      
      $MAX_LVL = (new DetalleRelevamientoProgresivo)->max_lvl;

      foreach(($request->detalles ?? []) as $d) {
        $d_bd = DetalleRelevamientoProgresivo::find($d['id_detalle_relevamiento_progresivo']);
        $viene_con_cnt = array_key_exists('id_tipo_causa_no_toma',$d) && $d['id_tipo_causa_no_toma'] !== null;
        $level_set = [];
        foreach(($d['niveles'] ?? []) as $n){
          $level_set[NivelProgresivo::find($n['id_nivel'])->nro_nivel] = $n['valor'] ?? null;
        }
        for($nro = 1;$nro<=$MAX_LVL;$nro++){
          $d_bd->{"nivel$nro"} = $viene_con_cnt? null : ($level_set[$nro] ?? null);
        }
        $d_bd->id_tipo_causa_no_toma_progresivo = $viene_con_cnt? $d['id_tipo_causa_no_toma'] : null;
        $d_bd->save();
      }
    });

    return ['codigo' => 200];
  }

  public function guardarRelevamiento(Request $request){
    $resultado = $this->cargarRelevamiento($request,false);
    if($resultado['codigo']==200){
      $rel = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
      $rel->estado_relevamiento()->associate(2);
      $rel->save();
    }
    return $resultado;
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento_progresivo' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo,id_estado_relevamiento,3',
        'observacion_validacion' => 'nullable|string|max:200',
    ], [
      'observacion_validacion.max' => 'La observacion supera los 200 caracteres',
      'id_relevamiento_progresivo.exists' => 'El Relevamiento debe estar finalizado para validar',
    ], self::$atributos)->after(function($validator){})->validate();

    DB::transaction(function() use($request){
      $relevamiento = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
      $relevamiento->observacion_validacion = $request->observacion_validacion;
      $relevamiento->estado_relevamiento()->associate(4);
      $relevamiento->save();
    });

    return ['codigo' => 200];
  }

  public function modificarParametrosRelevamientosProgresivo(Request $request) {
    Validator::make($request->all(),[
      'minimo_relevamiento_progresivo' => 'required|numeric',
      'id_casino' => 'required|exists:casino,id_casino',
      'id_tipo_moneda' => 'required|exists:tipo_moneda,id_tipo_moneda',
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'El valor tiene que existir',
      'numeric'  => 'El valor tiene que ser numerico (con punto decimal)',
    ], self::$atributos)->after(function($validator){
      $id_casino = $validator->getData()['id_casino'];
      if(!UsuarioController::getInstancia()->quienSoy()['usuario']->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('id_casino','No tiene los privilegios');
      }
      if($validator->getData()['minimo_relevamiento_progresivo'] < 0){
        return $validator->errors()->add('minimo_relevamiento_progresivo', 'El valor mínimo de base de niveles para un pozo no puede ser negativo');
      }
    })->validate();

    $cas = Casino::find($request->id_casino);
    $json = json_decode($cas->minimo_relevamiento_progresivo ?? "{}",true);
    $json[$request->id_tipo_moneda]=$request->minimo_relevamiento_progresivo;
    $cas->minimo_relevamiento_progresivo = json_encode($json);
    $cas->save();

    return ['codigo' => 200];
  }

  public function eliminarRelevamientoProgresivo ($id_relevamiento_progresivo) {
    $usercontroller = UsuarioController::getInstancia();
    $usuario = $usercontroller->quienSoy()['usuario'];
    $relevamiento_progresivo = RelevamientoProgresivo::find($id_relevamiento_progresivo);
    $casino = Casino::find(Sector::find($relevamiento_progresivo->id_sector)->id_casino);

    if($usuario === null || $relevamiento_progresivo === null) return;

    if(!$usercontroller->usuarioTieneCasinoCorrespondiente($usuario->id_usuario, $casino->id_casino)) return;

    DB::transaction(function() use ($id_relevamiento_progresivo){
        //elimino todos los detalles asociados al relevamiento progresivo
      DB::table('detalle_relevamiento_progresivo')
      ->where('id_relevamiento_progresivo', '=', $id_relevamiento_progresivo)
      ->delete();
      //finalmente, elimino el relevamiento
      DB::table('relevamiento_progresivo')
      ->where('id_relevamiento_progresivo', '=', $id_relevamiento_progresivo)
      ->delete();
    });

    return ['codigo' => 200];
  }

  public function obtenerMinimorelevamientoProgresivo ($id_casino,$id_tipo_moneda) {
    $json = json_decode((Casino::find($id_casino))->minimo_relevamiento_progresivo,true);
    $rta = 10000.0;//Valor por defecto
    if(array_key_exists($id_tipo_moneda,$json ?? [])) $rta = doubleval($json[$id_tipo_moneda]);
    return $rta;
  }
}
