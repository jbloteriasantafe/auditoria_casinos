<?php

namespace App\Http\Controllers\Mesas\Sectores;

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
use App\SecRecientes;

use App\Http\Controllers\UsuarioController;

use App\Mesas\Mesa;
use App\Mesas\JuegoMesa;
use App\Mesas\SectorMesas;
use App\Mesas\TipoMesa;

class ABMCSectoresController extends Controller
{
  private static $atributos = [
    'id_sectores' => 'Identificacion del sector',
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
    $validator =  Validator::make($request->all(),[
      'descripcion' => ['required','max:100',Rule::unique('sector_mesas')
                                           ->where('id_casino','=',$id_casino)],
      'id_casino' => 'required|exists:casino,id_casino'
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }
     $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    if($user->usuarioTieneCasino($id_casino)){
      $sector = SectorMesas::create($request->all());
     return $sector;
    }else{
      return ['errors' => ['autorizacion' => 'No está autorizado para realizar esta accion.']];
    }
  }

  public function obtenerSector($id){
    $sector = SectorMesas::find($id);
    $mesas= DB::table('mesa_de_panio')
          ->join('juego_mesa','juego_mesa.id_juego_mesa','=','mesa_de_panio.id_juego_mes')
          ->where('sector_mesas.id_sector_mesas','=',$id)
          ->get();

    return ['sector' => $sector,
            'mesas' => $mesas,
            'casino' => $juego->casino,
            'nombre_casino' => $juego->casino->nombre
          ];
  }

  public function eliminarSector($id_sector_mesas){
    $sector = SectorMesas::find($id_sector_mesas);
    if (count($sector->mesas) != 0) {
      return 0;
    }else{
      $sector->delete();
      return 1;
    }

  }


  //recibe: nro_mesa,id_tipo_mesa,descripcion_sector,casino
  public function filtrarSectores(Request $request){
    $reglas=array();
    if(isset($request->nro_mesa) && !empty($request->nro_mesa) && $request->nro_mesa !=0){
      $reglas[]=['mesa_de_panio.nro_mesa' , 'like' , '%' . $request->nro_mesa . '%'];
    }
    if($request->id_tipo_mesa !=0 && !empty($request->id_tipo_mesa)){
      $reglas[]=['juego_mesa.id_tipo_mesa' , 'like' , '%' . $request->id_tipo_mesa . '%'];
    }
    if($request->descripcion_sector !=0 && !empty($request->descripcion_sector)){
      $reglas[]=['sector_mesas.descripcion' , '=' , $request->descripcion_sector];
    }

    if($request->casino==0 || empty($request->casino)){
      $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
    }else{
      $casinos[]=$request->casino;
    }

    $sectores = DB::table('sector_mesas')
                    ->select('sector_mesas.*','casino.*')
                    ->leftJoin('mesa_de_panio','mesa_de_panio.id_sector_mesas',
                                        '=','sector_mesas.id_sector_mesas')
                    ->leftJoin('juego_mesa','juego_mesa.id_juego_mesa','=',
                                                  'mesa_de_panio.id_juego_mesa')
                    ->join('casino','casino.id_casino','=','sector_mesas.id_casino')
                    ->where($reglas)
                    ->whereIn('sector_mesas.id_casino',$casinos)
                    ->whereNull('sector_mesas.deleted_at')
                    ->distinct('sector_mesas.id_sector_mesas')
                    ->get();
    $sectoresymesas = array();
    foreach ($sectores as $sector) {
      $s = SectorMesas::find($sector->id_sector_mesas);
      $sectoresymesas[] = ['sector_casino' => $sector,
                          'mesas' => $s->lista_mesas,
                        ];
    }
    return ['sectores' => $sectoresymesas];
  }
}
