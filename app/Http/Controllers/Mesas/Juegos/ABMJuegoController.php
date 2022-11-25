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


  public function obtenerJuego($id){
    $juego = JuegoMesa::find($id);
    $mesas = DB::table('juego_mesa')
          ->join('mesa_de_panio','mesa_de_panio.id_juego_mesa','=','juego_mesa.id_juego_mesa')
          ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
          ->where('juego_mesa.id_juego_mesa','=',$id)
          ->get();
    return [
      'juego' => $juego,
      'mesas' => $mesas,
      'tipo_mesa' => $juego->tipo_mesa,
      'casino' => $juego->casino,
      'nombre_casino' => $juego->casino->nombre
    ];
  }

  public function guardar(Request $request){
    return $this->modificarJuego($request,true);
  }
  
  public function modificarJuego(Request $request,$creando = false){
    $reglas = [
      'nombre_juego'  => 'required|max:100',
      'siglas'        => 'required|max:4',
      'posiciones'    => 'required|integer'
    ];
    
    if($creando){
      $reglas['id_tipo_mesa']  = 'required|exists:tipo_mesa,id_tipo_mesa';
      $reglas['id_casino']     = 'required|exists:casino,id_casino';
    }
    else{
      $reglas['id_juego_mesa'] = 'required|exists:juego_mesa,id_juego_mesa';
    }
    
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    
    $validator = Validator::make($request->all(),$reglas, [
      'required' => 'El valor es requerido',
      'exists'   => 'No existe el valor en la base de datos',
      'integer'  => 'El valor tiene que ser un numero entero',
      'max'      => 'El valor supera el limite',
    ], self::$atributos)->after(function($validator) use ($user,$creando){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $id_casino = $creando? 
        $data['id_casino'] :
        JuegoMesa::find($data['id_juego_mesa'])->casino->id_casino;
      if(!$user->usuarioTieneCasino($id_casino)){
        return $validator->errors()->add('privilegios','No puede realizar esa accion');
      }
    })->validate();
    
    $juego = null;
    if($creando){
      $juego = new JuegoMesa;
      $juego->id_tipo_mesa = $request->id_tipo_mesa;
      $juego->id_casino    = $request->id_casino;
    } 
    else{
      $juego = JuegoMesa::find($request->id_juego_mesa);
    }
    $juego->nombre_juego = $request->nombre_juego;
    $juego->siglas       = $request->siglas;
    $juego->posiciones   = $request->posiciones;
    $juego->save();
    return ['juego' => $juego];
  }

  public function eliminarJuego($id){
    $juego = JuegoMesa::find($id);
    foreach ($juego->mesas as $mesa) {
      return 0;
    }
    $juego->delete();
    return ['juego' => $juego];
  }
}
