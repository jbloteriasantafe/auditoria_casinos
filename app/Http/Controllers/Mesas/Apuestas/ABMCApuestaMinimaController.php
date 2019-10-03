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

  public function consultarMinimo(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $apuestas = ApuestaMinimaJuego::where('id_casino','=',$user->casinos()->first()->id_casino)
                                    ->with('moneda','juego')
                                    ->get()
                                    ->toArray();

    return ['apuestas' => $apuestas];

  }

  public function obtenerApuestaMinima($id_casino,$id_moneda){
    $errores = 'null';
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    if($id_casino == 0 || empty($id_casino)){
      $id_casino = $user->casinos()->first()->id_casino;
    }

    $apuestas = DB::table('apuesta_minima_juego')->select('apuesta_minima_juego.apuesta_minima', 'apuesta_minima_juego.cantidad_requerida', 'apuesta_minima_juego.id_juego_mesa', 'apuesta_minima_juego.id_casino','apuesta_minima_juego.id_moneda')
                                                  ->where('id_casino','=',$id_casino)
                                                  ->where('id_moneda','=',$id_moneda)
                                                  ->where('deleted_at','=',NULL)
                                                  ->get();

    $hayMesas = DB::table('mesa_de_panio')->where('id_moneda','=',$id_moneda)
                                          ->where('id_casino','=',$id_casino)
                                          ->get();

    if(count($apuestas) == 0 && count($hayMesas) == 0){
      $errores = 'No existen mesas para los datos seleccionados.';
    }

    $juegos = JuegoMesa::where('id_casino','=',$id_casino)
                        ->orderBy('nombre_juego','asc')
                        ->get();

    return ['apuestas' => $apuestas, 'errores' => $errores , 'juegos' => $juegos];
  }


  public function eliminar($id){
    $apuestaMinima = ApuestaMinimaJuego::findOrFail($id);
    $apuestaMinima->delete();
    return response()->json(['exito' => 'Monto de Apuesta Mínima eliminada.'], 200);
  }

  public function modificar(Request $request){
    $validator=  Validator::make($request->all(),[
      'modificaciones' =>'required',
      'modificaciones.id_juego' => 'required|exists:juego_mesa,id_juego_mesa',
      'modificaciones.apuesta_minima' => ['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'modificaciones.cantidad_requerida' =>['required','regex:/^\d\d?\d?\d?\d?\d?\d?\d?$/'],
      'modificaciones.id_casino' => 'required|exists:casino,id_casino',
      'modificaciones.id_moneda' => 'required|exists:moneda,id_moneda'
    ], array(), self::$atributos)->after(function($validator){})->validate();


    if(isset($validator) && $validator->fails()) {
      return ['errors' => $validator->messages()->toJson()];
    }

    $modif = collect($request['modificaciones'])->all();


    $apuestaMinima = ApuestaMinimaJuego::whereIn('id_casino',[$modif['id_casino']])
                                        ->where('id_moneda','=',$modif['id_moneda'])
                                        ->where('id_juego_mesa','=',$modif['id_juego'])
                                        ->get()
                                        ->first();

    if($apuestaMinima != null) {$apuestaMinima->delete();}

    $apuestaMinima = new ApuestaMinimaJuego;
    $apuestaMinima->juego()->associate($modif['id_juego']);
    $apuestaMinima->apuesta_minima = $modif['apuesta_minima'];
    $apuestaMinima->cantidad_requerida =$modif['cantidad_requerida'];
    $apuestaMinima->casino()->associate($modif['id_casino']);
    $apuestaMinima->moneda()->associate($modif['id_moneda']);
    $apuestaMinima->save();

   return response()->json(['exito' => 'Monto de Apuesta Mínima modificada.'], 200);
  }

}
