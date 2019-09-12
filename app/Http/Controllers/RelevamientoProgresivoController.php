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
  //Parámetro para no mostrar niveles inferiores al valor del mismo
  public static $param_niveles_pozo = 0;

  private static $atributos = [
  ];
  private static $instance;

  private static $cant_dias_backup_relevamiento = 1;//No es necesario backup ya que no cambia las planillas

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
      $maquinas = $pozo->progresivo->maquinas;
      foreach ($maquinas as $maq){
        $id_maquinas_pozo[] = $maq['id_maquina'];
        $nro_admin_maquinas = $nro_admin_maquinas .'/'. $maq['nro_admin'];
      }

      $nro_admin_maquinas = substr_replace($nro_admin_maquinas,'',0,1);

      $resultados = DB::table('isla')
      ->selectRaw('DISTINCT(nro_isla)')
      ->join('maquina','maquina.id_isla','=','isla.id_isla')
      ->whereIn('id_maquina',$id_maquinas_pozo)->get();

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
        $cas[]=array('id_usuario' => $f->id_usuario,'nombre' => $f->nombre);
      }
      $fiscalizadores[$c->id_casino]=$cas;
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

    $dompdf = $this->crearPlanillaProgresivos($rel);


    return $dompdf->stream("Relevamiento_Progresivo_" . $rel->sector->descripcion . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));

  }

  public function crearPlanillaProgresivos($relevamiento_progresivo){
    $detalles = array();
    $detalles_linkeados = array();
    $detalles_individuales = array();

    foreach ($relevamiento_progresivo->detalles as $detalle_relevamiento) {
      $niveles = array();
      $id_maquinas = array();

      $pozo = Pozo::find($detalle_relevamiento->id_pozo);
      $progresivo = $pozo->progresivo;

      if (ProgresivoController::getInstancia()->existenNivelSuperior($detalle_relevamiento->id_pozo) == true) {

        $x=0;
        $nro_maquinas = "";
        foreach ($progresivo->maquinas as $maq) {
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
                                        ->get();

        $i = 0;
        $nro_islas="";
        foreach ($resultados as $resultado) {

          if($i == 0){
            $nro_islas = $resultado->nro_isla;
          }else {
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

        $detalle = array(
        'nro_maquinas' => $nro_maquinas,
        'nro_islas' => $nro_islas,
        'pozo' => $pozo->descripcion,
        'pozo_unico' => count($pozo->progresivo->pozos) == 1,
        'progresivo' => $progresivo->nombre,
        'es_individual' => $progresivo->es_individual,
        'nivel1' => number_format($detalle_relevamiento->nivel1, 2, '.', ''),
        'nivel2' => number_format($detalle_relevamiento->nivel2, 2, '.', ''),
        'nivel3' => number_format($detalle_relevamiento->nivel3, 2, '.', ''),
        'nivel4' => number_format($detalle_relevamiento->nivel4, 2, '.', ''),
        'nivel5' => number_format($detalle_relevamiento->nivel5, 2, '.', ''),
        'nivel6' => number_format($detalle_relevamiento->nivel6, 2, '.', ''),
        'causa_no_toma_progresivo' => $causa_no_toma_progresivo
        );

        $detalles[] = $detalle;
      }
    }

    foreach ($detalles as $detalle) {
      if ($detalle['es_individual'] == 0) {
        array_push($detalles_linkeados, $detalle);
      }
      else {
        array_push($detalles_individuales, $detalle);
      }
    }

    $sector = Sector::find($relevamiento_progresivo->id_sector);
    $otros_datos_relevamiento_progresivo = array(
      'sector' => $sector->descripcion,
      'casino' => (Casino::find($sector->id_casino))->nombre,
      'fiscalizador' => ($relevamiento_progresivo->id_usuario_fiscalizador != NULL) ? (Usuario::find($relevamiento_progresivo->id_usuario_fiscalizador)->nombre) : "",
      'estado' => EstadoRelevamiento::find($relevamiento_progresivo->id_estado_relevamiento)->descripcion
    );

    // $view = View::make('planillaProgresivos', compact('detalles','rel'));
    $view = View::make('planillaRelevamientosProgresivo', compact('detalles_linkeados', 'detalles_individuales', 'relevamiento_progresivo', 'otros_datos_relevamiento_progresivo'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf;
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
      ], array(), self::$atributos)->after(function($validator){
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

}
