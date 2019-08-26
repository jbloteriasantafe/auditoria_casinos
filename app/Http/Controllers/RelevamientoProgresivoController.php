<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
use App\TipoCausaNoToma;


class RelevamientoProgresivoController extends Controller
{
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
      $pozo = Pozo::find($detalle->id_pozo);
      foreach ($pozo->maquinas as $maq){
        $id_maquinas_pozo[] = $maq->id_maquina;
      }
      $resultados = DB::table('isla')->selectRaw('DISTINCT(nro_isla)')->join('maquina','maquina.id_isla','=','isla.id_isla')->whereIn('id_maquina',$id_maquinas_pozo)->get();
      $i=0;
      foreach ($resultados as $resultado){
        if($i == 0){
          $nro_isla = $resultado->nro_isla;
        }else{
          $nro_isla = $nro_isla . '/' . $resultado->nro_isla;
        }
        $i++;
      }
      foreach ($pozo->niveles_progresivo as $nivel){
        $base = ($nivel->pivot->base != null ? $nivel->pivot->base : $nivel->base);
        if($base >= 10000){
          $unNivel = new \stdClass;
          $unNivel->id_detalle_relevamiento_progresivo = $detalle->id_detalle_relevamiento_progresivo;
          $unNivel->nro_isla = $nro_isla;
          $unNivel->nombre_progresivo = $detalle->progresivo->nombre_progresivo;
          $unNivel->base = $base;
          $unNivel->nombre_nivel =$nivel->nombre_nivel;
          $unNivel->nro_nivel = $nivel->nro_nivel;
          $unNivel->valor= $detalle->valor_actual;
          $detalles[] = $unNivel;
        }
      }
    }

    return ['detalles' => $detalles,
            'relevamiento' => $relevamiento,
            'casino' => $casino,
            'usuario_cargador' => $relevamiento->usuario_cargador,
            'usuario_fiscalizador' => $relevamiento->usuario_fiscalizador];
  }

  public function buscarTodo(){

      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = $usuario->casinos;
      $estados = EstadoRelevamiento::all();
      UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Progresivo' , 'relevamientosProgresivo');

      return view('seccionRelevamientoProgresivo', ['casinos' => $casinos , 'estados' => $estados]);
  }

  public function buscarRelevamientosProgresivos(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }
    if(!empty($request->fecha)){
      $reglas[]=['relevamiento_progresivo.fecha', '=', $request->fecha];
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

    foreach ($resultados as $resultado) {
      $resultado->fecha = strftime("%d %b %Y", strtotime($resultado->fecha));
    }

    return $resultados;
  }

  public function crearRelevamientoProgresivos(Request $request){
    //algoritmo sortea progresivos linkeados y selecciona solo los niveles con base mayor a $10.000
    Validator::make($request->all(),[
        'id_sector' => 'required|exists:sector,id_sector',
        'cantidad_fiscalizadores' => 'nullable|numeric|between:1,10'
    ], array(), self::$atributos)->after(function($validator){
      $relevamientos = RelevamientoProgresivo::where([['fecha',date("Y-m-d")],['id_sector',$validator->getData()['id_sector']],['backup',0],['id_estado_relevamiento','!=',1]])->count();
      if($relevamientos > 0){
        $validator->errors()->add('relevamiento_en_carga','El Relevamiento para esa fecha ya está en carga y no se puede reemplazar.');
      }
    })->validate();

    $fecha_hoy = date("Y-m-d"); //fecha de hoy

    //me fijo si ya habia generados relevamientos para el dia de hoy que no sean back up, si hay los borro
    $relevamientos = RelevamientoProgresivo::where([['fecha',$fecha_hoy],['id_sector',$request->id_sector],['backup',0],['id_estado_relevamiento',1]])->get();
    $id_relevamientos_viejo= array();
    foreach($relevamientos as $relevamiento){
      foreach($relevamiento->detalles as $detalle){
        $detalle->delete();
      }
      $id_relevamientos_viejo[]=$relevamiento->id_relevamiento;
      $relevamiento->delete();
    }

    $progresivos = DB::table('pozo')->select('pozo.id_pozo' , 'progresivo.id_progresivo')
                                    ->join('maquina','maquina.id_pozo','=','pozo.id_pozo')
                                    ->join('isla','maquina.id_isla','=','isla.id_isla')
                                    ->join('sector','isla.id_sector','=','sector.id_sector')
                                    ->join('pozo_tiene_nivel_progresivo','pozo.id_pozo','=','pozo_tiene_nivel_progresivo.id_pozo')
                                    ->join('nivel_progresivo','pozo_tiene_nivel_progresivo.id_nivel_progresivo','=','nivel_progresivo.id_nivel_progresivo')
                                    ->join('progresivo','nivel_progresivo.id_progresivo','=','progresivo.id_progresivo')
                                    ->where([['sector.id_sector','=',$request->id_sector] , ['progresivo.linkeado' , '=' , 1]])
                                    ->groupBy('id_pozo', 'id_progresivo')
                                    ->get();// pozo->nivel_progresivo


     //creo los detalles
     $detalles = array();
     foreach($progresivos as $resultado_prog){
       if(ProgresivoController::getInstancia()->existenNivelSuperior($resultado_prog->id_pozo)){ //true si el pozo posee algun nivel con base mayor a 10.000 (pesos)
         $detalle = new DetalleRelevamientoProgresivo;
         $detalle->id_pozo = $resultado_prog->id_pozo;
         $detalle->id_progresivo = $resultado_prog->id_progresivo;
         $detalles[] = $detalle;
       }
     }

     if(empty($detalles)){
       //creo el relevamiento progresivo
       $relevamiento_progresivos = new RelevamientoProgresivo;
       $relevamiento_progresivos->nro_relevamiento_progresivo = DB::table('relevamiento_progresivo')->max('nro_relevamiento_progresivo') + 1;
       $relevamiento_progresivos->fecha = $fecha_hoy;
       $relevamiento_progresivos->fecha_generacion = $fecha_hoy;
       $relevamiento_progresivos->id_sector = $request->id_sector;
       $relevamiento_progresivos->id_estado_relevamiento = 1;
       $relevamiento_progresivos->backup = 0;
       $relevamiento_progresivos->save();

       foreach($detalles as $detalle_a_guardar){
         $detalle->id_progresivo = $resultado_prog->id_progresivo;
         $detalle->save();
       }
     }else{

       return ['codigo' => 500];//error, no existen progresivos para relevar.

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

    foreach ($relevamiento_progresivo->detalles as $detalle_relevamiento) {
      $niveles = array();
      $id_maquinas = array();

      $pozo = Pozo::find($detalle_relevamiento->id_pozo);
      $progresivo = Progresivo::find($detalle_relevamiento->id_progresivo);

      /* codigo viejo!
      foreach ($pozo->maquinas as $maq) {
        $id_maquinas_pozo[] = $maq->id_maquina;
      }
      $resultados = DB::table('isla')->selectRaw('DISTINCT(nro_isla)')->join('maquina' , 'maquina.id_isla' , '=' , 'isla.id_isla')->whereIn('id_maquina' , $id_maquinas_pozo)->get();
      $i = 0;
      foreach ($resultados as $resultado) {
        if($i == 0){
          $nro_isla = $resultado->nro_isla;
        }else{
          $nro_isla = $nro_isla . '/' . $resultado->nro_isla;
        }
        $i++;
      }
      foreach ($pozo->niveles_progresivo as $nivel){
         $unNivel = new \stdClass;
         $unNivel->nro_isla = $nro_isla;
         $unNivel->nombre_progresivo = $detalle_relevamiento->progresivo->nombre_progresivo;
         $unNivel->base = $nivel->pivot->base;
         $unNivel->nombre_nivel =$nivel->nombre_nivel;
         $unNivel->nro_nivel = $nivel->nro_nivel;
         $detalles[] = $unNivel;
      }
      */

    //Si algun nivel del pozo tiene una base menor a 10000, se debe ignorar el detalle relevamiento progresivo asociado.
    $flag=1;
    foreach ($pozo->niveles as $nivel) {
      if ($nivel->base<10000) {
        $flag=0;
        break;
      }
    }


      if ($flag) {

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

        $resultados = DB::table('isla')->selectRaw('DISTINCT(nro_isla)')->join('maquina' , 'maquina.id_isla' , '=' , 'isla.id_isla')->whereIn('id_maquina' , $id_maquinas)->get();

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

        $detalle = array(
        'nro_maquinas' => $nro_maquinas,
        'nro_islas' => $nro_islas,
        'pozo' => $pozo->descripcion,
        'progresivo' => $progresivo->nombre,
        'nivel1' => $detalle_relevamiento->nivel1,
        'nivel2' => $detalle_relevamiento->nivel2,
        'nivel3' => $detalle_relevamiento->nivel3,
        'nivel4' => $detalle_relevamiento->nivel4,
        'nivel5' => $detalle_relevamiento->nivel5,
        'nivel6' => $detalle_relevamiento->nivel6,
        );

        $detalles[] = $detalle;
      }
    }

    // $view = View::make('planillaProgresivos', compact('detalles','rel'));
    $view = View::make('planillaRelevamientoProgresivoEdit', compact('detalles','relevamiento_progresivo'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    // $dompdf->getCanvas()->page_text(20, 815, (($rel->nro_relevamiento != null) ? $rel->nro_relevamiento : "AUX")."/".$rel->casinoCod."/".$rel->sector."/".$rel->fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf;
  }

  public function cargarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento_progresivo' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo',
        'id_usuario_fiscalizador' => 'required|exists:usuario,id_usuario',
        'fecha_ejecucion' => 'required|date',
        'observacion' => 'nullable',
        'id_relevamiento_progresivo' => 'nullable|numeric',
        'detalles.*' => 'nullable',
        'detalles.*.id_detalle_relevamiento_progresivo' => 'required|numeric',
        'detalles.*.valor' => 'required|numeric',
    ], array(), self::$atributos)->after(function($validator){
      $relevamiento = RelevamientoProgresivo::find($validator->getData()['id_relevamiento_progresivo']);
      if($relevamiento->id_estado_relevamiento != 1){
        $validator->errors()->add('error_estado_relevamiento','El Relevamiento para esa fecha ya está en carga y no se puede reemplazar.');
      }
    })->validate();
    $rel = RelevamientoProgresivo::find($request->id_relevamiento_progresivo);
    $rel->usuario_fiscalizador()->associate($request->id_usuario_fiscalizador); //validado
    $rel->usuario_cargador()->associate(UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario']->id_usuario);
    $rel->fecha_ejecucion = $request->fecha_ejecucion;
    $rel->estado_relevamiento()->associate(3); // id_estado finalizado
    $rel->observacion_carga = $request->observacion;
    $rel->save();
    foreach($request->detalles as $detalle) {
      $unDetalle = DetalleRelevamientoProgresivo::find($detalle['id_detalle_relevamiento_progresivo']);
      $unDetalle->valor_actual = $detalle['valor'];
      $unDetalle->save();
    }
    return ['codigo' => 200];
  }

  public function validarRelevamiento(Request $request){
    Validator::make($request->all(),[
        'id_relevamiento' => 'required|exists:relevamiento_progresivo,id_relevamiento_progresivo',
        'observacion_validacion' => 'required',
    ], array(), self::$atributos)->after(function($validator){
      $relevamiento = RelevamientoProgresivo::find($validator->getData()['id_relevamiento']);
      if($relevamiento->id_estado_relevamiento != 3){
        $validator->errors()->add('estado_relevamiento','El Relevamiento debe estar finalizado para validar.');
      }
      $bandera = true;
      foreach ($relevamiento->detalles as $detalle) {
        if($detalle->valor_actual == null){
          $bandera = false;
        }
      }
      if(!$bandera){
        $validator->errors()->add('relevamiento_incompleta','No se han relevado todos los niveles de progresivo.');
      }
    })->validate();

    $relevamiento = RelevamientoProgresivo::find($request->id_relevamiento);
    $relevamiento->observacion_validacion = $request->observacion_validacion;
    $relevamiento->estado_relevamiento()->associate(4);
    $relevamiento->save();

    return ['codigo' => 200];
  }

}
