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
use App\Http\Controllers\UsuarioController;
use App\Usuario;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

class BuscarJuegoController extends Controller
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
    $this->middleware(['tiene_permiso:m_buscar_juegos_mesas']);
  }



  public function getAll(){
    $todos=Juego::all();
    return $todos;
  }

  public function buscarTodo(){
    $uc = new UsuarioController;
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $juegos = JuegoMesa::whereIn('id_casino',$casinos)->with('casino')->get();
    $tipos = TipoMesa::all();
    $uc->agregarSeccionReciente('Juegos','juegos');
    $casinos = $usuario->casinos;
    return view('Juegos.gestionJuegos' , ['casinos' => $casinos,'juegos' => $juegos,
     'tipos_mesas' => $tipos,'es_superusuario' => $usuario->es_superusuario]);
  }

  //busca juegos bajo el criterio "contiene". @param nombre_juego, siglas
  public function buscarJuegoPorCodigoONombre($busqueda){
    $resultados=JuegoMesa::where('nombre_juego' , 'like' , $busqueda . '%')
                      ->orWhere('siglas' , 'like' , $busqueda . '%')->get();

    return ['resultados' => $resultados];
  }

  public function obtenerJuego($id){
    $juego = JuegoMesa::find($id);
    $mesas= DB::table('juego_mesa')
          ->join('mesa_de_panio','mesa_de_panio.id_juego_mesa','=','juego_mesa.id_juego_mesa')
          ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
          ->where('juego_mesa.id_juego_mesa','=',$id)
          ->whereNull('juego_mesa.deleted_at')
          ->get();
    return ['juego' => $juego,
            'mesas' => $mesas,
            'tipo_mesa' => $juego->tipo_mesa,
            'casino' => $juego->casino,
            'nombre_casino' => $juego->casino->nombre
          ];
  }

  //busca UN juego que coincida con el nombre  @param $nombre_juego
  public function buscarJuegoPorNombre($nombre_juego){
    $resultado=JuegoMesa::where('nombre_juego' , 'like' , '%'.trim($nombre_juego).'%')->get();
    return $resultado;
  }

  public function buscarJuegoPorCasinoYNombre($id_casino,$nombre_juego){
    $resultado=JuegoMesa::where('id_casino' , '=' ,($id_casino))->where('nombre_juego' , 'like' , '%'.trim($nombre_juego).'%')->get();
    return [ 'juegos' => $resultado];
  }

  public function buscarJuegos(Request $request){
    $reglas=array();
    if(!empty($request->nombre_juego) ){
      $reglas[]=['juego_mesa.nombre_juego', 'like' , '%' . $request->nombre_juego  .'%'];
    }
  //  if(!empty($request->codigoId)){
  //    $reglas[]=['juego_mesa.siglas', 'like' , '%' . $request->siglas  .'%'];
  //  }
    if(!empty($request->id_tipo_mesa)){
      $reglas[]=['juego_mesa.id_tipo_mesa', '=', $request->id_tipo_mesa];
    }
    if(!empty($request->nro_mesa)){
      $reglas[]=['mesa_de_panio.nro_mesa','like' , '%' . $request->nro_mesa.'%'];
    }
    $casinos = array();
    if($request->id_casino==0){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      foreach($usuario->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
    }else{
      $casinos[]=$request->id_casino;
    }
    if(!empty($request->id_casino)){
      $casinos[]= $request->id_casino;
    }


    $resultados=DB::table('juego_mesa')
              ->select('juego_mesa.*','casino.nombre','tipo_mesa.*')
              ->leftJoin('tipo_mesa','tipo_mesa.id_tipo_mesa','=','juego_mesa.id_tipo_mesa')
              ->join('casino','casino.id_casino','=','juego_mesa.id_casino')
              ->leftJoin('mesa_de_panio','mesa_de_panio.id_juego_mesa','=','juego_mesa.id_juego_mesa')
              ->where($reglas)
              ->whereIn('juego_mesa.id_casino',$casinos)
              ->orderBy('nombre_juego','desc')
              ->whereNull('juego_mesa.deleted_at')
              ->distinct('juego_mesa.id_juego_mesa')
              ->get();


    return $resultados;
  }
}
