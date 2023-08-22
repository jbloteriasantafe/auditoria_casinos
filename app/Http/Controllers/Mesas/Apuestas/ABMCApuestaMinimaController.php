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
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    if($id_casino == 0 || empty($id_casino)){
      $id_casino = $user->casinos()->first()->id_casino ?? null;
      if(is_null($id_casino)) return ['apuestas' => [], 'juegos' => []];
    }
    
    $apuestas = DB::table('apuesta_minima_juego')
    ->select('apuesta_minima_juego.apuesta_minima', 'apuesta_minima_juego.cantidad_requerida', 'apuesta_minima_juego.id_juego_mesa', 'apuesta_minima_juego.id_casino','apuesta_minima_juego.id_moneda')
    ->where('id_casino','=',$id_casino)
    ->where('id_moneda','=',$id_moneda)
    ->whereNull('deleted_at')
    ->get();

    $juegos = JuegoMesa::where('id_casino','=',$id_casino)
    ->orderBy('nombre_juego','asc')
    ->get();

    return ['apuestas' => $apuestas, 'juegos' => $juegos];
  }


  public function eliminar($id){
    $apuestaMinima = ApuestaMinimaJuego::findOrFail($id);
    $apuestaMinima->delete();
    return response()->json(['exito' => 'Monto de Apuesta Mínima eliminada.'], 200);
  }

  public function modificar(Request $request){
    $validator = Validator::make($request->all(),[
      'id_casino' => 'required|exists:casino,id_casino,deleted_at,NULL',
      'id_moneda' => 'required|exists:moneda,id_moneda,deleted_at,NULL',
      'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa,deleted_at,NULL',
      'apuesta_minima' => 'required|numeric|min:0',
      'cantidad_requerida' => 'required|integer|min:0',
    ], [
      'required' => 'El valor es requerido',
      'exists'   => 'El valor es invalido',
      'numeric'  => 'El valor tiene que ser numérico',
      'integer'  => 'El valor tiene que ser un numero entero',
      'min'      => 'El valor tiene que ser positivo',
    ], self::$atributos)->after(function($validator){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      $user = UsuarioController::getInstancia()->quienSoy()['usuario'];
      if(!$user->usuarioTieneCasino($data['id_casino'])){
        return $validator->errors()->add('id_casino','Error de privilegios');
      }
      $jm = JuegoMesa::find($data['id_juego_mesa']);
      if($jm->id_casino != $data['id_casino']){
        return $validator->errors()->add('id_casino','Error de consistencia');
      }
    })->validate();

    $apuestaMinima = ApuestaMinimaJuego::where('id_casino','=',$request->id_casino)
    ->where('id_moneda','=',$request->id_moneda)
    ->where('id_juego_mesa','=',$request->id_juego_mesa)
    ->get();

    foreach($apuestaMinima as $apMin){
      $apMin->delete();
    }

    $apuestaMinima = new ApuestaMinimaJuego;
    $apuestaMinima->juego()->associate($request->id_juego_mesa);
    $apuestaMinima->casino()->associate($request->id_casino);
    $apuestaMinima->moneda()->associate($request->id_moneda);
    $apuestaMinima->apuesta_minima     = $request->apuesta_minima;
    $apuestaMinima->cantidad_requerida = $request->cantidad_requerida;
    $apuestaMinima->save();

   return response()->json(['exito' => 'Monto de Apuesta Mínima modificada.'], 200);
  }

}
