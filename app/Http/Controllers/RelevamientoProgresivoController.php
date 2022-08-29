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
    $end_range  = $list[0];
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
      return $r[0]!=$r[1]? $r[0].'-'.$r[1] : $r[0];
    },$newlist);
    return implode(",",$newlist);
  }

  public function obtenerRelevamiento($id_relevamiento_progresivo){
    $relevamiento = RelevamientoProgresivo::findOrFail($id_relevamiento_progresivo);
        
    $detalles = [];
    foreach ($relevamiento->detalles as $drel) {
      $pozo = Pozo::find($drel->id_pozo);
      if($pozo == null) continue;
      
      $nro_admins  = $pozo->progresivo->maquinas()
      ->select('maquina.nro_admin')->distinct()
      ->orderBy('maquina.nro_admin','asc')
      ->get()->map(function($m){return intval($m->nro_admin);})->toArray();
      
      $nro_islas = $pozo->progresivo->maquinas()
      ->join('isla','isla.id_isla','=','maquina.id_isla')
      ->selectRaw('isla.nro_isla')->distinct()
      ->orderBy('isla.nro_isla','asc')
      ->get()->map(function($i){return intval($i->nro_isla);})->toArray();
      
      $d = new \stdClass;
      $d->nro_isla_0    = count($nro_islas) > 0? $nro_islas[0] : -9999;//para ordenar
      $d->nro_isla      = self::SIMPLIFY_INT_RANGES($nro_islas);
      $d->pozo_unico    = count($pozo->progresivo->pozos) == 1;
      $d->nombre_pozo   = $pozo->descripcion;
      $d->id_pozo       = $pozo->id_pozo;
      $d->nro_admins    = self::SIMPLIFY_INT_RANGES($nro_admins);
      $d->es_individual = $pozo->progresivo->es_individual;
      $d->niveles       = $pozo->niveles->map(function($n) use ($drel){
        $ret = new \stdClass;
        $ret->base          = $n->base;
        $ret->nombre_nivel  = $n->nombre_nivel;
        $ret->nro_nivel     = $n->nro_nivel;
        $ret->valor         = $drel->{'nivel' . $n->nro_nivel};
        $ret->id_nivel_progresivo = $n->id_nivel_progresivo;
        return $ret;
      })->toArray();
      
      $d->nombre_progresivo = $pozo->progresivo->nombre;
      $d->id_detalle_relevamiento_progresivo = $drel->id_detalle_relevamiento_progresivo;
      $d->id_tipo_causa_no_toma_progresivo   = $drel->id_tipo_causa_no_toma_progresivo;
      $detalles[]=$d;
    }
    
    usort($detalles,function($a,$b){
      return $a->nro_isla_0 > $b->nro_isla_0;
    });

    return [
      'relevamiento' => $relevamiento,
      'detalles' => $detalles, 
      'sector' => $relevamiento->sector,
      'casino' => $relevamiento->sector->casino,
      'usuario_cargador' => $relevamiento->usuario_cargador,
      'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador
    ];
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
      'causasNoToma'   => TipoCausaNoTomaProgresivo::all()
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
      $min = $this->obtenerMinimorelevamientoProgresivo($id_casino,$tm->id_tipo_moneda)['rta'];
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

  public function generarPlanillaProgresivos($id_relevamiento_progresivo){
    $rel = RelevamientoProgresivo::find($id_relevamiento_progresivo);

    $html = false;
    $dompdf = $this->crearPlanillaProgresivos($rel,$html);//poner en true si se quiere ver como html (DEBUG)

    if($html) return $dompdf;
    else return $dompdf->stream("Relevamiento_Progresivo_" . $rel->sector->descripcion . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function crearPlanillaProgresivos($relevamiento_progresivo,$html = false){
    $detalles_linkeados    = [];
    $detalles_individuales = [];
    $MAX_LVL = (new DetalleRelevamientoProgresivo)->max_lvl;
    foreach ($relevamiento_progresivo->detalles as $detalle_relevamiento) {
      $pozo = Pozo::withTrashed()->find($detalle_relevamiento->id_pozo);
      $progresivo = $pozo->progresivo()->withTrashed()->get()->first();
      
      $nro_maquinas = $progresivo->maquinas()
      ->selectRaw('DISTINCT maquina.nro_admin as nro_admin')
      ->orderBy('maquina.nro_admin','asc')->get();

      $nro_islas = $progresivo->maquinas()
      ->join('isla','isla.id_isla','=','maquina.id_isla')
      ->selectRaw('DISTINCT isla.nro_isla as nro_isla, isla.orden')
      ->orderBy('nro_isla', 'asc')->get();
      

      $detalle = new \stdClass;
      $detalle->nro_maquinas = $this->SIMPLIFY_INT_RANGES(
        $nro_maquinas->map(function($m){return intval($m->nro_admin);})->toArray()
      );
      $detalle->nro_islas    = $this->SIMPLIFY_INT_RANGES(
        $nro_islas->map(function($i){return intval($i->nro_isla);})->toArray()
      );
      
      $detalle->nro_isla_0 = -9999;
      $detalle->orden_min  = -9999;
      if(count($nro_islas) > 0){
        $detalle->nro_isla_0 = $nro_islas[0]->nro_isla;
        $detalle->orden_min  = min($nro_islas->map(function($i){return intval($i->orden);})->toArray());
      }
        
      $detalle->pozo = $pozo->descripcion;
      //Si venimos de un progresivo borrado, nos va a dar 0 pozos, que le muestre el nombre por si las moscas
      //No habria forma de saber si era pozo unico o no.
      $detalle->pozo_unico = count($progresivo->pozos) == 1;
      $detalle->progresivo = $progresivo->nombre;
      $detalle->es_individual = $progresivo->es_individual;
      
      {
        $cnt = $detalle_relevamiento->tipo_causa_no_toma;
        $detalle->causa_no_toma_progresivo = is_null($cnt)? null : $cnt->descripcion;
      }
      
      {
        $nombre_nivel = [];
        foreach($pozo->niveles as $n){
          $nombre_nivel[$n->nro_nivel]= $n->nombre_nivel ?? '';
        }
        for($i=1;$i<=$MAX_LVL;$i++){
          $n = $detalle_relevamiento->{"nivel$i"};
          $detalle->{"nivel$i"} = is_null($n)? '' : number_format($n,2,'.','');
          $detalle->{"nombre_nivel$i"} = $nombre_nivel[$i] ?? '';
        }
      }
      
      if($detalle->es_individual){
        $detalles_individuales[] = $detalle;
      }
      else{
        $detalles_linkeados[] = $detalle;
      }
    }

    {
      $ROS = $relevamiento_progresivo->sector->id_casino == 3;
      usort($detalles_linkeados,function($a,$b) use ($ROS){
        if($ROS && $a->orden_min != $b->orden_min){
          return $a->orden_min > $b->orden_min;
        }
        return $a->nro_isla_0 > $b->nro_isla_0;
      });
    }
    
    $sector = Sector::find($relevamiento_progresivo->id_sector);
    $casino = Casino::find($sector->id_casino);
    $fiscalizador = $relevamiento_progresivo->usuario_fiscalizador;
    $otros_datos = [
      'sector' => $sector->descripcion,
      'casino' => $casino->nombre,
      'codigo_casino' => $casino->codigo,
      'fiscalizador'  => is_null($fiscalizador)? "" : $fiscalizador->nombre,
      'estado' => $relevamiento_progresivo->estado_relevamiento->descripcion,
      'MAX_LVL' => $MAX_LVL,
    ];

    $view = View::make('planillaRelevamientosProgresivo', compact('detalles_linkeados', 'detalles_individuales', 'relevamiento_progresivo', 'otros_datos'));
    if(!$html){
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'landscape');
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 575, "$relevamiento_progresivo->nro_relevamiento_progresivo/$otros_datos[codigo_casino]/$otros_datos[sector]", $font, 10, array(0,0,0));
      $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
      return $dompdf;
    }
    return $view;
  }

  public function cargarRelevamiento(Request $request,$validar = true){
    if($validar){
      Validator::make($request->all(),[
          'id_relevamiento_progresivo' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo',
          'id_usuario_fiscalizador' => 'required|exists:usuario,id_usuario',
          'id_casino' => 'required|exists:casino,id_casino',
          'fiscalizador' => 'exists:usuario,id_usuario',
          'fecha_ejecucion' => 'required',
          'observaciones' => 'nullable|string',
          'detalles.*' => 'nullable|array',
          'detalles.*.id_detalle_relevamiento_progresivo' => 'required|numeric',
          'detalles.*.niveles' => 'nullable|array',
          'detalles.*.niveles.*' => 'nullable',
          'detalles.*.niveles.*.valor'=> 'required|numeric|min:0',
          'detalles.*.niveles.*.numero' => 'required|string',
          'detalles.*.niveles.*.id_nivel' => 'required|integer|exists:nivel_progresivo,id_nivel_progresivo',
          'detalles.*.id_tipo_causa_no_toma' => 'nullable|integer|exists:tipo_causa_no_toma_progresivo,id_tipo_causa_no_toma_progresivo'
      ], array(
        'detalles.*.niveles.*.valor.numeric' => 'El valor de un nivel no es numerico.'
      ), self::$atributos)->after(function($validator){
        $relevamiento = RelevamientoProgresivo::find($validator->getData()['id_relevamiento_progresivo']);
        $controller = UsuarioController::getInstancia();

        if($validator->getData()['fecha_ejecucion'] < $relevamiento->fecha_generacion){
          $validator->errors()->add('error_fecha_ejecucion', 'La fecha de ejecución no puede ser inferior a la fecha de generación del relevamiento');
        }
        if(!$controller->usuarioTieneCasinoCorrespondiente($validator->getData()['id_usuario_fiscalizador'], $validator->getData()['id_casino'])) {
            $validator->errors()->add('error_usuario_tiene_casino','No existe ningún casino asociado al fiscalizador ingresado');
        }
        if(!$controller->usuarioEsFiscalizador($validator->getData()['id_usuario_fiscalizador'])) {
            $validator->errors()->add('error_usuario_es_fiscalizador','El usuario ingresado no es fiscalizador');
        }
        //Hasta donde se, no hay forma de pedirle la longitud de un campo a eloquent
        //De una forma que no sea una raw query. Lo hardcodeo.
        if(strlen($validator->getData()['observaciones'])>200){
          $validator->errors()->add('error_observaciones', 'La observacion supera los 200 caracteres');
        }
      })->validate();
    }

    DB::transaction(function() use($request){
      $rel = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
      $rel->usuario_fiscalizador()->dissociate();
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador);
      $rel->fecha_ejecucion = $request->fecha_ejecucion;
      $rel->estado_relevamiento()->associate(3); // id_estado finalizado
      $rel->observacion_carga = $request->observaciones;
      $rel->save();

      foreach($request->detalles as $detalle) {
        $unDetalle = DetalleRelevamientoProgresivo::find($detalle['id_detalle_relevamiento_progresivo']);
        if (!array_key_exists('id_tipo_causa_no_toma', $detalle) || $detalle['id_tipo_causa_no_toma'] === null) {
          $unDetalle->nivel1 = array_key_exists(0, $detalle['niveles']) ? $detalle['niveles'][0]['valor'] : NULL;
          $unDetalle->nivel2 = array_key_exists(1, $detalle['niveles']) ? $detalle['niveles'][1]['valor'] : NULL;
          $unDetalle->nivel3 = array_key_exists(2, $detalle['niveles']) ? $detalle['niveles'][2]['valor'] : NULL;
          $unDetalle->nivel4 = array_key_exists(3, $detalle['niveles']) ? $detalle['niveles'][3]['valor'] : NULL;
          $unDetalle->nivel5 = array_key_exists(4, $detalle['niveles']) ? $detalle['niveles'][4]['valor'] : NULL;
          $unDetalle->nivel6 = array_key_exists(5, $detalle['niveles']) ? $detalle['niveles'][5]['valor'] : NULL;
          $unDetalle->id_tipo_causa_no_toma_progresivo = NULL;
        }
        else {
          $unDetalle->nivel1 = NULL;
          $unDetalle->nivel2 = NULL;
          $unDetalle->nivel3 = NULL;
          $unDetalle->nivel4 = NULL;
          $unDetalle->nivel5 = NULL;
          $unDetalle->nivel6 = NULL;
          $unDetalle->id_tipo_causa_no_toma_progresivo = $detalle['id_tipo_causa_no_toma'];
        }
        $unDetalle->save();
      }
    });

    return ['codigo' => 200];
  }

  public function guardarRelevamiento(Request $request){
    //Como no se hace validacion, puede mandar texto, si es texto
    //Lo pongo como nulo.
    $detalles = $request->detalles;
    //Tengo que hacer todo este berenjenal porque
    //PHP te hace copias en vez de referencias
    //Y no pude hacer andar el array con &
    //Un foreach seria mucho mas facil...
    $maxlvl = (new DetalleRelevamientoProgresivo)->max_lvl;
    for($didx=0;$didx<sizeof($detalles);$didx++){
      if(array_key_exists('niveles',$detalles[$didx])){
        for($n = 0;$n<$maxlvl;$n++){
          if(array_key_exists($n,$detalles[$didx]['niveles'])){
            $aux = $detalles[$didx]['niveles'][$n]['valor'];
            $value = is_numeric($aux)? $aux : NULL;
            $detalles[$didx]['niveles'][$n]['valor']=$value;
          }
        }
      }
      if(array_key_exists('id_tipo_causa_no_toma',$detalles[$didx])){
        $causa = $detalles[$didx]['id_tipo_causa_no_toma'];
        $detalles[$didx]['id_tipo_causa_no_toma'] = is_numeric($causa) && $causa > 0? $causa : NULL;
      }
    }
    $request->merge(['detalles'=>$detalles]);
    $resultado = $this->cargarRelevamiento($request,false);
    if(array_key_exists('codigo',$resultado) && $resultado['codigo']==200){
      $rel = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
      $rel->estado_relevamiento()->associate(2);
      $rel->save();
      return ['codigo' => 200];
    }
    else return $resultado;
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento_progresivo' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo',
        'observacion_validacion' => 'nullable|string',
    ], array(), self::$atributos)->after(function($validator){
      $relevamiento = RelevamientoProgresivo::find($validator->getData()['id_relevamiento_progresivo']);
      if($relevamiento->id_estado_relevamiento != 3){
        $validator->errors()->add('error_estado_relevamiento','El Relevamiento debe estar finalizado para validar.');
      }
      //Hasta donde se, no hay forma de pedirle la longitud de un campo a eloquent
      //De una forma que no sea una raw query. Lo hardcodeo.
      if(strlen($validator->getData()['observacion_validacion'])>200){
        $validator->errors()->add('error_observacion_validacion', 'La observacion supera los 200 caracteres');
      }
    })->validate();

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
    $rta = ['rta' => 10000.0];//Valor por defecto
    if(array_key_exists($id_tipo_moneda,$json ?? [])) $rta['rta'] = doubleval($json[$id_tipo_moneda]);
    return $rta;
  }
}
