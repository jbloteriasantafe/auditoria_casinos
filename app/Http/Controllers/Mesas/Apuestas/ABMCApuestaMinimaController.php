<?php

namespace App\Http\Controllers\Mesas\Apuestas;

use Auth;
use Session;
use Illuminate\Http\Request;
use Response;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;

use App\Usuario;
use App\Casino;
use App\Turno;
use App\SecRecientes;
use App\Mesas\Mesa;
use App\Mesas\Moneda;
use App\Mesas\JuegoMesa;
use App\Mesas\ApuestaMinimaJuego;
use App\Http\Controllers\UsuarioController;
class ABMCApuestaMinimaController extends Controller
{
  private static $atributos = [
    'apuesta_minima' => 'Apuesta Minima',
    'id_juego_mesa'=>'Juego',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware(['tiene_permiso:m_abm_apuesta_minima']);
  }

  public function guardar(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
      'apuesta_minima' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
    ], array(), self::$atributos)->after(function($validator){})->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
     $apuestaMinima = new ApuestaMinimaJuego;
     $apuestaMinima->juego()->associate($request['id_juego_mesa']);
     $apuestaMinima->apuesta_minima = $request->apuesta_minima;
     $apuestaMinima->save();

     return response()->json(['exito' => 'Monto de Apuesta Mínima creada.'], 200);
  }

  public function obtenerApuestaMinima(){

    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($user->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $todos = ApuestaMinimaJuego::whereIn('id_casino',[$casinos])->with('juego','casino','moneda')
      ->get();
    $pesos = null;
    $dolares = null;

    foreach ($todos as $todo) {
      $juego = JuegoMesa::find($todo->id_juego_mesa);
      if($todo->id_moneda == 1){
        $pesos = ['apuesta' => $todo->apuesta_minima,
                 'cant_mesas' => $todo->cantidad_requerida,
                 'juego' => $juego->nombre_juego,
                 'casino' => $todo->casino,
                 'moneda' => $todo->moneda
                ];
      }else{
        $dolares = ['apuesta' => $todo->apuesta_minima,
                 'cant_mesas' => $todo->cantidad_requerida,
                 'juego' => $juego->nombre_juego,
                 'casino' => $todo->casino,
                 'moneda' => $todo->moneda
                ];
      }

    }

    $haymesasdolares = Mesa::where('id_moneda','=',2)->get();

    if($pesos == null){
      $pesos = new ApuestaMinimaJuego;
      $pesos->moneda()->associate(1);
      $pesos->save();
    }
    if($dolares == null && count($haymesasdolares)>0){
      $dolares = new ApuestaMinimaJuego;
      $dolares->moneda()->associate(2);
      $dolares->save();
    }
    return ['pesos' => $apuestas, 'dolares' =>$dolares];
  }

  public function eliminar($id){

    $apuestaMinima = ApuestaMinimaJuego::findOrFail($id);
    $apuestaMinima->delete();
    return response()->json(['exito' => 'Monto de Apuesta Mínima eliminada.'], 200);
  }

  public function modificar(Request $request){
    $validator=  Validator::make($request->all(),[
      //'id_apuesta_minima' => 'required:exists:apuesta_minima_juego,id_apuesta_minima',
      'modificaciones' =>'required',
      'modificaciones.*.id_juego' => 'required|exists:juego_mesa,id_juego_mesa',
      'modificaciones.*.apuesta' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'modificaciones.*.cantidad' =>['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'modificaciones.*.id_moneda' => 'required|exists:moneda,id_moneda'
    ], array(), self::$atributos)->after(function($validator){})->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
     $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
     $casinos = array();
     foreach($user->casinos as $casino){
       $casinos[]=$casino->id_casino;
     }
     foreach ($modificaciones as $m) {
       $apuestaMinima = ApuestaMinimaJuego::whereIn('id_casino',$casinos)
                                    ->where('id_moneda','=',$m['id_moneda'])
                                    ->first();//($request['id_apuesta_minima']);

      if(count($apuestaMinima) == 0){
        $apuestaMinima = new ApuestaMinimaJuego;
      }
       $apuestaMinima->juego()->associate($m['id_juego']);
       $apuestaMinima->apuesta_minima = $m->apuesta;
       $apuestaMinima->cantidad_requerida =$m->cantidad;
       $apuestaMinima->moneda()->associate($m->id_moneda);
       $apuestaMinima->save();
     }


     return response()->json(['exito' => 'Monto de Apuesta Mínima modificada.'], 200);
  }

}
