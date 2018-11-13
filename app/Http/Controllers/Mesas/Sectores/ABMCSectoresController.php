<?php

namespace App\Http\Controllers\Sectores;

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

use App\User;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\RolesPermissions\RoleFinderController;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
      $this->middleware(['auth', 'role:SUPERUSUARIO|ADMINISTRADOR|CONTROLADOR']);
  }

  public function buscarTodo(){
    $uc = new UsuarioController;
    $usuario = Auth::user();
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    $sectores = SectorMesas::whereIn('id_casino',$casinos)->with('casino')->orderBy('descripcion','desc')->get();
    $tipos = TipoMesa::all();
    $casinos = $usuario->casinos;
    $uc->agregarSeccionReciente('Juegos','juegos');
    return view('Juegos.gestionJuegos' , ['casinos' => $casinos,'juegos' => $juegos, 'tipos_mesas' => $tipos]);
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
     $user = Auth::user();
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



  public function modificarSector(Request $request){
    $validator=  Validator::make($request->all(),[
      'id_sector_mesas' => 'required|exists:sector_mesas,id_sector_mesas',
      'descripcion' => ['required','max:100',Rule::unique('sector_mesas')
                                           ->where('id_casino','=',$id_casino)]
    ], array(), self::$atributos)->after(function($validator){  })->validate();
    if(isset($validator)){
      if ($validator->fails()){
          return ['errors' => $validator->messages()->toJson()];
          }
     }

    $sector = SectorMesas::find($request->id_sector_mesas);
    $sector->descripcion= $request->descripcion;

    $sector->save();


    return ['sector' => $sector];
  }

  public function eliminarSector($id_sector_mesas){
    $sector = SectorMesas::find($request->id_sector_mesas);
    if (isset($sector->mesas)) {
      return 0;
    }else{
      $sector->delete();
      return 1;
    }

  }


  //recibe: nro_mesa,id_tipo_mesa,descripcion_sector,casino
  public function filtrarSectores(Request $request){
    $reglas=array();
    if(isset($request->nro_mesa) && !empty($request->nro_mesa) &&  ($request->nro_mesa !=0){
      $reglas[]=['mesa_de_panio.nro_mesa' , 'like' , '%' . $request->nro_mesa . '%'];
    }
    if($request->id_tipo_mesa !=0 && !empty($request->id_tipo_mesa)){
      $reglas[]=['juego_mesa.id_tipo_mesa' , 'like' , '%' . $request->id_tipo_mesa . '%'];
    }
    if($request->descripcion_sector !=0 && !empty($request->descripcion_sector)){
      $reglas[]=['sector_mesas.descripcion' , '=' , $request->descripcion_sector];
    }

    if($request->casino==0 || empty($request->casino)){
      $usuario = Auth::user();
      $casinos = array();
      foreach($usuario->casinos as $casino){
        $casinos[]=$casino->id_casino;
      }
    }else{
      $casinos[]=$request->casino;
    }

    $sectores = DB::table('sector_mesas')
                    ->select('sector_mesas.*','casino.*')
                    ->join('mesa_de_panio','mesa_de_panio.id_sector_mesas',
                                        '=','sector_mesas.id_sector_mesas')
                    ->join('juego_mesa','juego_mesa.id_juego_mesa','=',
                                                  'mesa_de_panio.id_juego_mesa')
                    ->join('casino','casino.id_casino','=','sector_mesas.id_casino')
                    ->where($reglas)
                    ->whereIn('sector_mesas.id_casino',$casinos)
                    ->whereNull('sector_mesas.deleted_at')
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
