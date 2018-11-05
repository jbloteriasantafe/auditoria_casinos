<?php

namespace App\Http\Controllers\Mesas\Juegos;

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
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\UsuarioController;
use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

class ABMJuegoController extends Controller
{
  private static $atributos = [
    'id_mesa_de_panio' => 'Identificacion de la mesa',
    'nro_mesa' => 'Número de Mesa',
    'nombre' => 'Nombre de Mesa',
    'descripcion' => 'Descripción',
    'id_tipo_mesa' => 'Tipo de Mesa',
    'id_juego_mesa' => 'Juego de Mesa',
    'id_casino' => 'Casino',
    'id_moneda' => 'Moneda',
    'id_sector_mesas' => 'Sector',
    'nombre_juego' => 'Nombre de Juego',
    'cod_identificacion' => 'Código de Identificación',
    'siglas' => 'Código de Identificación',
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_gestionar_juegos_mesas']);
  }

  public function guardar(Request $request){
    $id_casino = $request->id_casino;
    $validator=  Validator::make($request->all(),[
      'nombre_juego' => ['required','max:100',Rule::unique('juego_mesa')
                                           ->where('id_casino','=',$id_casino)],
      'siglas' => ['required','max:4',Rule::unique('juego_mesa')
                                           ->where('id_casino','=',$id_casino)],
      'id_tipo_mesa' => 'required|exists:tipo_mesa,id_tipo_mesa',
      'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
     $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if($user->usuarioTieneCasino($id_casino)){
      $juegomesa = JuegoMesa::create($request->all());
     return $juegomesa;
    }else{
      $val = new Validator;
      $val->errors()->add('autorizacion', 'No está autorizado para realizar esta accion.');

      return ['errors' => $val->messages()->toJson()];
    }
  }

  public function obtenerJuego($id){
    $juego = JuegoMesa::find($id);
    $mesas= DB::table('juego_mesa')
          ->join('mesa_de_panio','mesa_de_panio.id_juego_mesa','=','juego_mesa.id_juego_mesa')
          ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
          ->where('juego_mesa.id_juego_mesa','=',$id)
          ->get();
    return ['juego' => $juego,
            'mesas' => $mesas,
            'tipo_mesa' => $juego->tipo_mesa,
            'casino' => $juego->casino,
            'nombre_casino' => $juego->casino->nombre
          ];
  }


  //ver si nos sirve
  public function encontrarOCrear($juego){

        $resultado=$this->buscarJuegoPorNombre($juego);
        if(count($resultado)==0){
            $juegoNuevo=new JuegoMesa;
            $juegoNuevo->nombre_juego=trim($juego);
            //$juegoNuevo->siglas; ??
            $juegoNuevo->save();
        }else{
            $juegoNuevo=$resultado[0];
        }
        return $juegoNuevo;
  }

  public function modificarJuego(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
      'nombre_juego' => ['required','max:100',Rule::unique('juego_mesa')
                                           ->where('id_casino','=',$id_casino)],
      'siglas' => ['required','max:4',Rule::unique('juego_mesa')
                                           ->where('id_casino','=',$id_casino)],
      'id_tipo_mesa' => 'required|exists:tipo_mesa,id_tipo_mesa',
      'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

    $juego = JuegoMesa::find($request->id_juego);
    $juego->nombre_juego= $request->nombre_juego;
    $juego->siglas= $request->siglas;

    $juego->save();


    return ['juego' => $juego];
  }

  public function eliminarJuego($id){
    $juego = JuegoMesa::find($id);
    foreach ($juego->mesas as $mesa) {
      $mesa->juego()->dissociate();
      $mesa->save();
    }
    $juego->delete();
    return ['juego' => $juego];
  }


}
