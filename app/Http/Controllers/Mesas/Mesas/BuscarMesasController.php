<?php

namespace App\Http\Controllers\Mesas\Mesas;

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
use App\Mesas\Moneda;
use App\Mesas\Ficha;

use App\Http\Controllers\Mesas\Aperturas\BCAperturaController;

class BuscarMesasController extends Controller
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
  ];

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware(['tiene_permiso:m_buscar_mesas']);
  }

  public function getMesa($id_mesa_de_panio){
    $mesa = Mesa::findOrFail($id_mesa_de_panio);
    $sector = $mesa->sector;
    $juego = $mesa->juego;
    $tipo_mesa = $juego->tipo_mesa;

    $casino = $mesa->casino;
    $sectores = SectorMesas::where('id_casino','=',$casino->id_casino)->orderBy('descripcion','desc')->get();
    $juegos = JuegoMesa::where('id_casino','=',$casino->id_casino)->orderBy('nombre_juego','desc')->get();
    $monedas = Moneda::all();
    if($mesa->id_moneda != null){
      $moneda = $mesa->moneda;
      $fichas = $moneda->fichas;
    }else{
      $fichas = Ficha::select('valor_ficha')->distinct('valor_ficha')->orderBy('valor_ficha','DESC')->get();
      $moneda = null;
    }

    return [
            'mesa' => $mesa,
            'sector' => $sector,
            'juego' => $juego,
            'tipo_mesa' => $tipo_mesa,
            'moneda' => $moneda,
            'casino' => $casino,
            'fichas' => $fichas,
            'sectores' => $sectores,
             'juegos' => $juegos,
              'monedas' => $monedas,
          ];
  }

  public function getMesas(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $casinos = DB::table('usuario')
              ->select('casino.*')
              ->join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
              ->join('casino','casino.id_casino','=','usuario_tiene_casino.id_casino')
              ->where('usuario.id_usuario','=',$user->id_usuario)
              ->get();


    $sectores = SectorMesas::whereIn('id_casino',$cas)->get();
    $juegos = JuegoMesa::whereIn('id_casino',$cas)->get();
    $tipos= TipoMesa::all();
    $moneda= Moneda::all();
    return view('Mesas.seccionGestionMesas',  [ 'sectores' => $sectores ,
                                                'juegos' => $juegos,
                                                'tipo_mesa'=>$tipos,
                                                'casinos'=>$casinos,
                                                'monedas'=>$moneda,
                                              ]);

  }

  public function getDatos(){
    $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();

    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $casinos = DB::table('usuario')
                    ->select('casino.*')
                    ->join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
                    ->join('casino','casino.id_casino','=','usuario_tiene_casino.id_casino')
                    ->where('usuario.id_usuario','=',$user->id_usuario)
                    ->get();


    $sectores = SectorMesas::whereIn('id_casino',$cas)->get();
    $juegos = JuegoMesa::whereIn('id_casino',$cas)->get();
    $tipos= TipoMesa::all();
    $moneda = Moneda::all();
    return  [ 'sectores' => $sectores ,
              'juegos' => $juegos,
              'tipo_mesa'=>$tipos,
              'casinos'=>$casinos,
              'moneda' => $moneda,
            ];
  }

  public function buscarMesas(Request $request){
      $reglas=array();
      if(isset($request->nro_mesa)){
        $reglas[]=['mesa_de_panio.nro_mesa' , 'like' , '%' . $request->nro_mesa . '%'];
      }
      if($request->id_tipo_mesa !=0){
        $reglas[]=['juego_mesa.id_tipo_mesa' , 'like' , '%' . $request->id_tipo_mesa . '%'];
      }
      if($request->id_sector!=0){
        $reglas[]=['sector_mesas.id_sector_mesas' , '=' , $request->id_sector];
      }
      if(isset($request->nombre_juego)){
        $reglas[]=['juego_mesa.id_juego_mesa' , '=' ,  $request->nombre_juego  ];
      }

      if($request->casino==0){
        $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
        $casinos = array();
        foreach($usuario->casinos as $casino){
          $casinos[]=$casino->id_casino;
        }
      }else{
        $casinos[]=$request->casino;
      }
      $sort_by = $request->sort_by;

      $mesas = DB::table('mesa_de_panio')
                    ->select('mesa_de_panio.*','tipo_mesa.*','casino.*','sector_mesas.id_sector_mesas','sector_mesas.descripcion as nombre_sector','juego_mesa.*')
                    ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
                    ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                    ->join('tipo_mesa','tipo_mesa.id_tipo_mesa','=','juego_mesa.id_tipo_mesa')
                    ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                    ->where($reglas)
                    ->whereNull('mesa_de_panio.deleted_at')
                    ->when($sort_by,function($query) use ($sort_by){
                                    return $query->orderBy($sort_by['columna'],$sort_by['orden']);
                                })
                    ->whereIn('mesa_de_panio.id_casino',$casinos)
                    ->paginate($request->page_size);
      return $mesas;
  }

  public function buscarMesaPorNroCasino($id_casino,$nro_mesa){
    $mesas = DB::table('mesa_de_panio')
                  ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
                  ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                  ->join('tipo_mesa','tipo_mesa.id_tipo_mesa','=','juego_mesa.id_tipo_mesa')
                  ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                  ->where('mesa_de_panio.nro_mesa','like','%'.$nro_mesa.'%')
                  ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                  ->whereNull('mesa_de_panio.deleted_at')
                  ->orderBy('mesa_de_panio.nro_mesa','asc')->get();
    $resultado = array();
    foreach ($mesas as $m) {
      $resultado[] = [
                      'id_mesa_de_panio' => $m->id_mesa_de_panio,
                      'nro_mesa' => $m->nro_mesa . ' - '.$m->siglas,
                    ];
    }
    return ['mesas'=>$resultado];
  }

  public function buscarMesaPorNroCasinoSinApertura($id_casino,$fecha,$nro_mesa){
    $controllerAP = new BCAperturaController;
    $resultadoo = $controllerAP->buscarIDMesasAperturasDelDia($fecha,$id_casino);
    $id_mesas_a_no_incluir = array();
    foreach ($resultadoo as $r) {
      $id_mesas_a_no_incluir[] = $r->id_mesa_de_panio;
    }
    $mesas = DB::table('mesa_de_panio')
                  ->join('sector_mesas','sector_mesas.id_sector_mesas','=','mesa_de_panio.id_sector_mesas')
                  ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mesa')
                  ->join('tipo_mesa','tipo_mesa.id_tipo_mesa','=','juego_mesa.id_tipo_mesa')
                  ->join('casino','casino.id_casino','=','mesa_de_panio.id_casino')
                  ->where('mesa_de_panio.nro_mesa','like','%'.$nro_mesa.'%')
                  ->whereIn('mesa_de_panio.id_casino',[$id_casino])
                  ->whereNull('mesa_de_panio.deleted_at')
                  ->whereNotIn('mesa_de_panio.id_mesa_de_panio',$id_mesas_a_no_incluir)
                  ->orderBy('mesa_de_panio.nro_mesa','asc')->get();
    return ['mesas'=>$mesas];
  }

  public function getFichasParaMesa($id_mesa_de_panio){
    $id_moneda = Mesa::find($id_mesa_de_panio)->id_moneda;
    return Ficha::where('id_moneda','=',$id_moneda)->get();
  }

  public function datosSegunCasino($id_casino){
  $sectores = SectorMesas::where('id_casino','=',$id_casino)->get();
  $juegos = JuegoMesa::where('id_casino','=',$id_casino)->get();
  $moneda = Moneda::all();
  return ['sectores' => $sectores, 'juegos' => $juegos, 'moneda' => $moneda];
  }
}
