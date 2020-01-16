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

  public function obtenerRelevamiento($id_relevamiento_progresivo){
    $relevamiento = RelevamientoProgresivo::findOrFail($id_relevamiento_progresivo);
    $detalles = array();

    $casino = $relevamiento->sector->casino;
    foreach ($relevamiento->detalles as $detalle) {
      $niveles = array();
      $id_maquinas_pozo = array();
      $nro_admin_maquinas = '';
      $pozo = Pozo::find($detalle->id_pozo);
      if($pozo == null) continue;
      $maquinas = $pozo->progresivo->maquinas()->orderBy('maquina.nro_admin','asc')->get();
      foreach ($maquinas as $maq){
        $id_maquinas_pozo[] = $maq['id_maquina'];
        $nro_admin_maquinas = $nro_admin_maquinas .'/'. $maq['nro_admin'];
      }

      $nro_admin_maquinas = substr_replace($nro_admin_maquinas,'',0,1);

      $resultados = DB::table('isla')->selectRaw('DISTINCT(nro_isla)')
                                     ->join('maquina','maquina.id_isla','=','isla.id_isla')
                                     ->whereIn('id_maquina',$id_maquinas_pozo)
                                     ->orderBy('nro_isla', 'asc')
                                     ->get();

      $i=0;
      $nro_isla='';
      foreach ($resultados as $resultado){
        if($i == 0){
          $nro_isla = $resultado->nro_isla;
        }else{
          $nro_isla = $nro_isla . '/' . $resultado->nro_isla;
        }
        $i++;
      }

      $d = new \stdClass;
      $d->nro_isla = $nro_isla;
      $d->id_detalle_relevamiento_progresivo = $detalle->id_detalle_relevamiento_progresivo;
      $d->nombre_progresivo=$pozo->progresivo->nombre;
      $d->pozo_unico = count($pozo->progresivo->pozos) == 1;
      $d->nombre_pozo=$pozo->descripcion;
      $d->id_pozo = $pozo->id_pozo;
      $d->id_tipo_causa_no_toma_progresivo = $detalle->id_tipo_causa_no_toma_progresivo;
      $d->nro_admins=$nro_admin_maquinas;
      $d->es_individual = $pozo->progresivo->es_individual;
      $d->niveles=array();
      $detalle_arr = $detalle->toArray();
      foreach ($pozo->niveles as $nivel){
          $unNivel = new \stdClass;
          $unNivel->base = $nivel->base;
          $unNivel->nombre_nivel = $nivel->nombre_nivel;
          $unNivel->nro_nivel = $nivel->nro_nivel;
          $unNivel->valor= $detalle_arr['nivel' . $nivel->nro_nivel];
          $unNivel->id_nivel_progresivo = $nivel->id_nivel_progresivo;
          $d->niveles[] = $unNivel;
      }
      $detalles[]=$d;
    }

    $detalles = $this->ordenarArrayBubbleSort($detalles, 0);

    return ['detalles' => $detalles,
            'relevamiento' => $relevamiento,
            'sector' => $relevamiento->sector,
            'casino' => $casino,
            'usuario_cargador' => $relevamiento->usuario_cargador,
            'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador];
  }

  public function buscarTodo(){

      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = $usuario->casinos;
      $estados = EstadoRelevamiento::all();
      UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Progresivo' , 'relevamientosProgresivo');
      $fiscalizadores = $this->obtenerFiscalizadores($casinos,$usuario);

      return view('seccionRelevamientoProgresivo',
      ['casinos' => $casinos ,
      'estados' => $estados,
      "fiscalizadores" => $fiscalizadores,
      "causasNoToma" => TipoCausaNoTomaProgresivo::all()]

      )->render();
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
      ->where('backup' , '=', 0)->paginate($request->page_size);



    return $resultados;
  }

  private function obtenerFiscalizadores($casinos,$user){
    $controller = UsuarioController::getInstancia();
    $fiscalizadores = array();

    foreach($casinos as $c){
      $cas = array();
      $fs = $controller->obtenerFiscalizadores($c->id_casino,$user->id_usuario);

      foreach($fs as $f){
        $cas[] = array(
                      'id_usuario' => $f->id_usuario,
                      'nombre' => $f->nombre
                      );
      }
      $fiscalizadores[$c->id_casino] = $cas;
    }

    return $fiscalizadores;
  }

  public function crearRelevamientoProgresivos(Request $request){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy();
    $fiscalizador = $usuario_actual['usuario'];

    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'fecha_generacion' => 'required|date|before_or_equal:' . date('Y-m-d H:i:s'),
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $pozos = DB::table('pozo')->select('pozo.id_pozo' , 'pozo.id_progresivo')
                                    ->join('maquina_tiene_progresivo', 'pozo.id_progresivo', '=', 'maquina_tiene_progresivo.id_progresivo')
                                    ->join('maquina', 'maquina.id_maquina', '=', 'maquina_tiene_progresivo.id_maquina')
                                    ->join('isla','maquina.id_isla','=','isla.id_isla')
                                    ->join('sector','isla.id_sector','=','sector.id_sector')
                                    ->where('sector.id_sector','=',$request->id_sector)
                                    ->whereNull('pozo.deleted_at')
                                    ->groupBy('id_progresivo', 'id_pozo')
                                    ->get();



     //creo los detalles
     $detalles = array();
     foreach($pozos as $pozo){
       if(ProgresivoController::getInstancia()->existenNivelSuperior($pozo->id_pozo)){
         $detalle = new DetalleRelevamientoProgresivo;
         $detalle->id_pozo = $pozo->id_pozo;
         $detalles[] = $detalle;
       }
     }

     if(!empty($detalles)){

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

         //guardo los detalles
         foreach($detalles as $detalle){
            $detalle->id_relevamiento_progresivo = DB::table('relevamiento_progresivo')->max('id_relevamiento_progresivo');
           $detalle->save();
         }
       });


      }else{
       return ['codigo' => 500]; //error, no existen progresivos para relevar.
     }

    return ['codigo' => 200];
  }

  public function generarPlanillaProgresivos($id_relevamiento_progresivo){
    $rel = RelevamientoProgresivo::find($id_relevamiento_progresivo);

    $html = false;//poner en true si se quiere ver como html (DEBUG)
    $dompdf = $this->crearPlanillaProgresivos($rel,$html);

    if($html) return $dompdf;
    else return $dompdf->stream("Relevamiento_Progresivo_" . $rel->sector->descripcion . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));
  }

  public function crearPlanillaProgresivos($relevamiento_progresivo,$html = false){
    $detalles = array();
    $detalles_link_sin_ordenar = array();
    $detalles_individuales = array();

    foreach ($relevamiento_progresivo->detalles as $detalle_relevamiento) {
      $niveles = array();
      $id_maquinas = array();

      $pozo = Pozo::withTrashed()->find($detalle_relevamiento->id_pozo);
      $progresivo = $pozo->progresivo()->withTrashed()->get()->first();
      $niveles = $pozo->niveles()->get();

      $x=0;
      $nro_maquinas = "";
      foreach ($progresivo->maquinas()->orderBy('maquina.nro_admin','asc')->get() as $maq) {
        $id_maquinas[] = $maq->id_maquina;
        if ($x == 0) {
          $nro_maquinas = $maq->nro_admin;
        }
        else {
          $nro_maquinas = $nro_maquinas . '/' . $maq->nro_admin;
        }
        $x++;
      }

      $resultados = DB::table('isla') ->selectRaw('DISTINCT(nro_isla)')
                                      ->join('maquina' , 'maquina.id_isla' , '=' , 'isla.id_isla')
                                      ->whereIn('id_maquina' , $id_maquinas)
                                      ->orderBy('nro_isla', 'asc')
                                      ->get();
      $i = 0;
      $nro_islas="";
      $flag_isla_unica = 0;
      foreach ($resultados as $resultado) {

        if($i == 0){
          $nro_islas = $resultado->nro_isla;
        }else {
          $flag_isla_unica = 1;
          $nro_islas = $nro_islas . '/' . $resultado->nro_isla;
        }
        $i++;
      }

      if($detalle_relevamiento->id_tipo_causa_no_toma_progresivo != NULL) {
        $causa_no_toma_progresivo = TipoCausaNoTomaProgresivo::find($detalle_relevamiento->id_tipo_causa_no_toma_progresivo)->descripcion;
      }
      else {
        $causa_no_toma_progresivo = -1;
      }

      $nombre_nivel = [];
      foreach($niveles as $n){
        if(isset($n->nro_nivel)){
          $nombre_nivel[$n->nro_nivel]=isset($n->nombre_nivel)? $n->nombre_nivel : '';
        }
      }
      $formatearNum = function($n){
        if(is_null($n)) return '';
        return number_format($n,2,'.','');
      };
      $nivel1 = $formatearNum($detalle_relevamiento->nivel1);
      $nivel2 = $formatearNum($detalle_relevamiento->nivel2);
      $nivel3 = $formatearNum($detalle_relevamiento->nivel3);
      $nivel4 = $formatearNum($detalle_relevamiento->nivel4);
      $nivel5 = $formatearNum($detalle_relevamiento->nivel5);
      $nivel6 = $formatearNum($detalle_relevamiento->nivel6);


      $detalle = array(
        'nro_maquinas' => $nro_maquinas,
        'nro_islas' => $nro_islas,
        'flag_isla_unica' => $flag_isla_unica,
        'pozo' => $pozo->descripcion,
        //Si venimos de un progresivo borrado, nos va a dar 0 pozos, que le muestre el nombre por si las moscas
        //No habria forma de saber si era pozo unico o no.
        'pozo_unico' => count($progresivo->pozos) == 1,
        'progresivo' => $progresivo->nombre,
        'es_individual' => $progresivo->es_individual,
        'nivel1' => $nivel1,
        'nivel2' => $nivel2,
        'nivel3' => $nivel3,
        'nivel4' => $nivel4,
        'nivel5' => $nivel5,
        'nivel6' => $nivel6,
        'nombre_nivel1' => isset($nombre_nivel[1])? $nombre_nivel[1] : '',
        'nombre_nivel2' => isset($nombre_nivel[2])? $nombre_nivel[2] : '',
        'nombre_nivel3' => isset($nombre_nivel[3])? $nombre_nivel[3] : '',
        'nombre_nivel4' => isset($nombre_nivel[4])? $nombre_nivel[4] : '',
        'nombre_nivel5' => isset($nombre_nivel[5])? $nombre_nivel[5] : '',
        'nombre_nivel6' => isset($nombre_nivel[6])? $nombre_nivel[6] : '',
        'causa_no_toma_progresivo' => $causa_no_toma_progresivo
      );

      $detalles[] = $detalle;
    }

    foreach ($detalles as $detalle) {
      if ($detalle['es_individual'] == 0) {
        array_push($detalles_link_sin_ordenar, $detalle);
      }
      else {
        array_push($detalles_individuales, $detalle);
      }
    }

    $detalles_linkeados = $this->ordenarArrayBubbleSort($detalles_link_sin_ordenar, 1);

    $sector = Sector::find($relevamiento_progresivo->id_sector);
    $casino = Casino::find($sector->id_casino);
    $otros_datos_relevamiento_progresivo = array(
      'sector' => $sector->descripcion,
      'casino' => $casino->nombre,
      'codigo_casino'=> $casino->codigo,
      'fiscalizador' => ($relevamiento_progresivo->id_usuario_fiscalizador != NULL) ? (Usuario::find($relevamiento_progresivo->id_usuario_fiscalizador)->nombre) : "",
      'estado' => EstadoRelevamiento::find($relevamiento_progresivo->id_estado_relevamiento)->descripcion
    );

    $view = View::make('planillaRelevamientosProgresivo', compact('detalles_linkeados', 'detalles_individuales', 'relevamiento_progresivo', 'otros_datos_relevamiento_progresivo'));
    if(!$html){
      $dompdf = new Dompdf();
      $dompdf->set_paper('A4', 'landscape');
      $dompdf->loadHtml($view->render());
      $dompdf->render();
      $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
      $dompdf->getCanvas()->page_text(20, 575, $relevamiento_progresivo->nro_relevamiento_progresivo . "/" . $otros_datos_relevamiento_progresivo['codigo_casino'] . "/" . $otros_datos_relevamiento_progresivo['sector'], $font, 10, array(0,0,0));
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
          'detalles.*.niveles.*.id_nivel' => 'required|integer|exists:nivel_progresivo,id_nivel_progresivo'
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
      $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador); //validado
      //$rel->usuario_cargador()->associate(UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario);
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
    for($didx=0;$didx<sizeof($detalles);$didx++){
      for($n = 0;$n<6;$n++){
        if(array_key_exists($n,$detalles[$didx]['niveles'])){
          $aux = $detalles[$didx]['niveles'][$n]['valor'];
          $value = is_numeric($aux)? $aux : NULL;
          $detalles[$didx]['niveles'][$n]['valor']=$value;
        }
      }
    }
    //dump($detalles);
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
        'minimo_relevamiento_progresivo' => 'required',
    ], array(), self::$atributos)->after(function($validator){

      if($validator->getData()['minimo_relevamiento_progresivo'] < 0){
        $validator->errors()->add('error_minimo_relevamiento_progresivo', 'El valor mínimo de base de niveles para un pozo no puede ser negativo');
      }
    })->validate();


    $cas = Casino::find($request->id_casino);
    $cas->minimo_relevamiento_progresivo = $request->minimo_relevamiento_progresivo;
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

  public function obtenerMinimorelevamientoProgresivo ($id_casino) {
    return ['rta' => (Casino::find($id_casino))->minimo_relevamiento_progresivo];
  }

  public function ordenarArrayBubbleSort ($array, $paraPlanilla) {

    //CASO 1: ordenamiento para planillas (entra un array comun)
    if ($paraPlanilla == 1) {
      //primero separo el array entre los que tienen isla unica y los que no
      //(estos ultimos no me sirven para el ordenamiento, los pusheo al final del array resultante)
      $arr_ordenar = array();
      $arr_no_ordenar = array();
      foreach ($array as $a) {
        if ($a['flag_isla_unica'] == 0) {
          array_push ($arr_ordenar, $a);
        }
        else {
          array_push ($arr_no_ordenar, $a);
        }
      }

        //bubble sort
        $n = sizeof($arr_ordenar);
        for ($i=0; $i<$n; $i++) {
          for ($j=0; $j < $n-$i-1; $j++) {
            if ($arr_ordenar[$j]['nro_islas'] > $arr_ordenar[$j+1]['nro_islas']) {
              $temp = $arr_ordenar[$j];
              $arr_ordenar[$j] = $arr_ordenar[$j+1];
              $arr_ordenar[$j+1] = $temp;
            }
          }
        }
    }
    //CASO 2: ordenamiento para modal carga (entra un object)
    else {
      //primero separo el array entre los que tienen isla unica y los que no
      //(estos ultimos no me sirven para el ordenamiento, los pusheo al final del array resultante)
      $arr_ordenar = array();
      $arr_no_ordenar = array();
      foreach ($array as $a) {
        if ($a->es_individual == 0) {
          array_push ($arr_ordenar, $a);
        }
        else {
          array_push ($arr_no_ordenar, $a);
        }
      }

        //bubble sort
        $n = sizeof($arr_ordenar);
        for ($i=0; $i<$n; $i++) {
          for ($j=0; $j < $n-$i-1; $j++) {
            if ($arr_ordenar[$j]->nro_isla > $arr_ordenar[$j+1]->nro_isla) {
              $temp = $arr_ordenar[$j];
              $arr_ordenar[$j] = $arr_ordenar[$j+1];
              $arr_ordenar[$j+1] = $temp;
            }
          }
        }
    }

    return array_merge($arr_ordenar, $arr_no_ordenar);
  }

}
