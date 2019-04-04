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

  public function obtenerApuestaMinima($id_casino,$id_moneda){
    $errores = 'null';
    $rta = null;
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

    if($id_casino == 0 || empty($id_casino)){
      $id_casino = $user->casinos()->first()->id_casino;
    }
    $todos = ApuestaMinimaJuego::whereIn('id_casino',[$id_casino])
                                  ->where('id_moneda','=',$id_moneda)
                                  ->with('juego','casino','moneda')
                                  ->get();

    foreach ($todos as $todo) {
      $juego = JuegoMesa::find($todo->id_juego_mesa);

      $rta = [
               'apuesta' => $todo->apuesta_minima,
               'cant_mesas' => $todo->cantidad_requerida,
               'juego' => $juego->nombre_juego,
               'id_juego' => $juego->id_juego_mesa,
               'casino' => $todo->casino,
               'moneda' => $todo->moneda
              ];
    }


    $hayMesas = DB::table('mesa_de_panio')->where('id_moneda','=',$id_moneda)
                      ->where('id_casino','=',$id_casino)
                      ->get();

    if($rta == null && count($hayMesas) > 0){
      $nuevo = new ApuestaMinimaJuego;
      $nuevo->moneda()->associate($id_moneda);
      $nuevo->casino()->associate($id_casino);
      $nuevo->save();
      $rta = [
               'apuesta' => 0,
               'cant_mesas' => 0,
               'juego' => '',
               'id_juego' => 0,
               'casino' => $nuevo->casino,
               'moneda' => $nuevo->moneda
              ];
    }else{
      if($rta == null){
        $errores = 'No existen mesas para los datos seleccionados.';
      }
    }

    $juegos = JuegoMesa::where('id_casino','=',$id_casino)->orderBy('nombre_juego','asc')->get();

    return ['rta' => $rta, 'errores' => $errores , 'juegos' => $juegos];
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
      'modificaciones.*.id_casino' => 'required|exists:casino,id_casino',
      'modificaciones.*.id_moneda' => 'required|exists:moneda,id_moneda'
    ], array(), self::$atributos)->after(function($validator){})->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
     $modif = collect($request['modificaciones'])->all();

     foreach ($modif as $m) {
       $apuestaMinima = ApuestaMinimaJuego::whereIn('id_casino',[$m['id_casino']])
                                    ->where('id_moneda','=',$m['id_moneda'])
                                    ->get()
                                    ->first();//($request['id_apuesta_minima']);
        if($apuestaMinima != null){$apuestaMinima->delete();}

        $apuestaMinima = new ApuestaMinimaJuego;
        $apuestaMinima->juego()->associate($m['id_juego']);
        $apuestaMinima->apuesta_minima = $m['apuesta'];
        $apuestaMinima->cantidad_requerida =$m['cantidad'];
        $apuestaMinima->casino()->associate($m['id_casino']);
        $apuestaMinima->moneda()->associate($m['id_moneda']);
        $apuestaMinima->save();
     }


     return response()->json(['exito' => 'Monto de Apuesta Mínima modificada.'], 200);
  }

}
