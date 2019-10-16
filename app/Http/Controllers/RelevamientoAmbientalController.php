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
use Validator;

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
                                ->get();

    $sectores = DB::table('sector')->select('id_sector')
                                    ->where('id_casino','=',$request->id_casino)
                                    ->get();;

    $islas = DB::table('isla')->where('id_casino','=',$request->id_casino)
                              ->get();

     //creo los detalles
     $detalles = array();
     foreach($sectores as $sector){

       foreach ($turnos as $turno) {
         $detalle = new DetalleRelevamientoAmbiental;
         $detalle->id_turno = $turno->id_turno;
         $detalle->id_sector = $sector->id_sector;

         //creo una relacion isla-cantidad de personas para cada detalle
         $cantidades = array();
         foreach ($islas as $isla) {
           if ($isla->id_sector == $sector->id_sector) {
             $cantidad = new CantidadPersonas;
             $cantidad->id_isla = $isla->id_isla;

             $cantidades[] = $cantidad;
           }
         }
       $detalles[] = $detalle;
       }
     }

     if(!empty($detalles)){
       //creo y guardo el relevamiento de control ambiental
       DB::transaction(function() use($request,$fiscalizador,$detalles, $cantidades){
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
            $detalle->turno =
            $detalle->save();

            //guardo las cantidades
            foreach ($cantidades as $cantidad) {
              $cantidad->id_detalle_relevamiento_ambiental = DB::table('detalle_relevamiento_ambiental')->max('id_detalle_relevamiento_ambiental');
              $cantidad->save();
            }
         }
       });

      }else{
       return ['codigo' => 500]; //error, no existen islas para relevar.
     }

    return ['codigo' => 200];
  }

}
