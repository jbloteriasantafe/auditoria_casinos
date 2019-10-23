<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Maquina;
use App\Sector;
use App\Casino;
use App\Isla;
use App\EstadoRelevamiento;
use App\RelevamientoAmbiental;
use App\DetalleRelevamientoAmbiental;
use App\CantidadPersonas;
use App\Turno;
use Validator;
use View;
use Dompdf\Dompdf;
use PDF;

class RelevamientoAmbientalController extends Controller
{
  private static $atributos = [];
  private static $instance;

  public function buscarTodo(){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = $usuario->casinos;
      $estados = EstadoRelevamiento::all();
      UsuarioController::getInstancia()->agregarSeccionReciente('Relevamiento Control Ambiental' , 'relevamientosControlAmbiental');

      return view('seccionRelevamientosAmbientalMaquinas',
      [ 'casinos' => $casinos,
        'estados' => $estados
      ]
      )->render();
  }

  public function buscarRelevamientosAmbiental(Request $request){
    $reglas = Array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    foreach ($usuario->casinos as $casino) {
      $casinos[] = $casino->id_casino;
    }

    if(!empty($request->fecha_generacion)){
      $fecha_desde = $request->fecha_generacion . ' 00:00:00';
      $fecha_hasta = $request->fecha_generacion . ' 23:59:59';
      $reglas[]=['relevamiento_ambiental.fecha_generacion','>=',$fecha_desde];
      $reglas[]=['relevamiento_ambiental.fecha_generacion','<=',$fecha_hasta];
    }

    if($request->casino!=0){
      $reglas[]=['casino.id_casino', '=', $request->casino];
    }
    if(!empty($request->estadoRelevamiento)){
      $reglas[] = ['estado_relevamiento.id_estado_relevamiento' , '=' , $request->estadoRelevamiento];
    }

    $sort_by = $request->sort_by;
    $resultados=DB::table('relevamiento_ambiental')
    ->select('relevamiento_ambiental.*'   , 'casino.nombre as casino', 'estado_relevamiento.descripcion as estado')
      //->join('sector' ,'sector.id_sector' , '=' , 'relevamiento_progresivo.id_sector')
      ->join('casino' , 'relevamiento_ambiental.id_casino' , '=' , 'casino.id_casino')
      ->join('estado_relevamiento' , 'relevamiento_ambiental.id_estado_relevamiento' , '=' , 'estado_relevamiento.id_estado_relevamiento')
      ->when($sort_by,function($query) use ($sort_by){
                      return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                  })
      ->where($reglas)
      //->whereIn('casino.id_casino' , $casinos)
      //->where('backup' , '=', 0)
      ->paginate($request->page_size);

    return $resultados;
  }

  public function crearRelevamientoAmbientalMaquinas(Request $request){
    $usuario_actual = UsuarioController::getInstancia()->quienSoy();
    $fiscalizador = $usuario_actual['usuario'];

    Validator::make($request->all(),[
        'id_casino' => 'required|exists:casino,id_casino',
        'fecha_generacion' => 'required|date|before_or_equal:' . date('Y-m-d H:i:s'),
    ], array(), self::$atributos)->after(function($validator){
    })->validate();

    $turnos = DB::table('turno')->select('id_turno')
                                ->where('id_casino','=',$request->id_casino)
                                ->where('deleted_at','=',NULL)
                                ->get();

    $sectores = DB::table('sector')->where('id_casino','=',$request->id_casino)
                                    ->where('deleted_at','=',NULL)
                                    ->get();;

    $islas = DB::table('isla')->where('id_casino','=',$request->id_casino)
                              ->where('deleted_at','=',NULL)
                              ->get();

    $islotes = DB::table('isla')->where()
     //creo los detalles
     $detalles = array();
     foreach($sectores as $sector){

       $islas = (Sector::find($sector->id_sector))->islas;
       foreach ($turnos as $turno) {
         $detalle = new DetalleRelevamientoAmbiental;
         $detalle->id_turno = $turno->id_turno;
         $detalle->id_sector = $sector->id_sector;
         $detalle->tamanio_vector = sizeof($islas);

         //creo una relacion isla-cantidad de personas para cada detalle
         /*
         $cantidades = array();

         foreach ($islas as $isla) {
           $cantidad = new CantidadPersonas;
           $cantidad->id_isla = $isla->id_isla;
           $cantidades[] = $cantidad;
         }
         */
       $detalles[] = $detalle;
       }
     }

     if(!empty($detalles)){
       //creo y guardo el relevamiento de control ambiental
       DB::transaction(function() use($request,$fiscalizador,$detalles){
         $relevamiento_ambiental = new RelevamientoAmbiental;
         $relevamiento_ambiental->nro_relevamiento_ambiental = DB::table('relevamiento_ambiental')->max('nro_relevamiento_ambiental') + 1;
         $relevamiento_ambiental->fecha_generacion = $request->fecha_generacion;
         $relevamiento_ambiental->id_casino = $request->id_casino;
         $relevamiento_ambiental->id_estado_relevamiento = 1;
         $relevamiento_ambiental->id_tipo_relev_ambiental = 0;
         $relevamiento_ambiental->id_usuario_cargador = $fiscalizador->id_usuario;
         //$relevamiento_ambiental->backup = 0;
         $relevamiento_ambiental->save();

         //guardo los detalles
         foreach($detalles as $detalle){
            $detalle->id_relevamiento_ambiental = DB::table('relevamiento_ambiental')->max('id_relevamiento_ambiental');
            $detalle->save();

            //guardo las cantidades
            /*
            foreach ($cantidades as $cantidad) {
              $cantidad->id_detalle_relevamiento_ambiental = DB::table('detalle_relevamiento_ambiental')->max('id_detalle_relevamiento_ambiental');
              $cantidad->save();
            }
            */
         }
       });

      }else{
       return ['codigo' => 500]; //error, no existen islas para relevar.
     }

    return ['codigo' => 200];
  }


  public function generarPlanillaAmbiental($id_relevamiento_ambiental){
    $rel = RelevamientoAmbiental::find($id_relevamiento_ambiental);

    $dompdf = $this->crearPlanillaAmbiental($rel);

    return $dompdf->stream("Relevamiento_Control_Ambiental_" . $rel->casino->id_casino . "_" . date('Y-m-d') . ".pdf", Array('Attachment'=>0));

  }

  public function crearPlanillaAmbiental($relevamiento_ambiental){

    $detalles = array();

    foreach ($relevamiento_ambiental->detalles as $detalle_relevamiento) {

      $detalle = array(
        'id_sector' => $detalle_relevamiento->id_sector,
        'id_turno' => $detalle_relevamiento->id_turno,
        'nro_turno' => (Turno::find($detalle_relevamiento->id_turno))->nro_turno,
        'tamanio_vector' => $detalle_relevamiento->tamanio_vector,
        'total' => $detalle_relevamiento->total
      );

      $detalles[] = $detalle;
    }

    $otros_datos = array(
      'casino' => $relevamiento_ambiental->casino->nombre,
      'fiscalizador' => ($relevamiento_ambiental->id_usuario_fiscalizador != NULL) ? (Usuario::find($relevamiento_ambiental->id_usuario_fiscalizador)->nombre) : "",
      'estado' => EstadoRelevamiento::find($relevamiento_ambiental->id_estado_relevamiento)->descripcion
    );

    $view = View::make('planillaRelevamientosAmbiental', compact('relevamiento_ambiental', 'detalles', 'otros_datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    $dompdf->getCanvas()->page_text(20, 575, $relevamiento_ambiental->nro_relevamiento_ambiental . "/" . $relevamiento_ambiental->casino->codigo, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(765, 575, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

    return $dompdf;
  }

}
