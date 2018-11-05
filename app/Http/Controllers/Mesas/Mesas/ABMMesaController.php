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

class ABMMesaController extends Controller
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
    $this->middleware(['tiene_permiso:m_gestionar_mesas']);
  }


  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function guardar(Request $request)
  {
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      $id_casino = $request->id_casino;
      switch ($id_casino) {
        case 1://mel
          return $this->store_nro_mesa_continuo($request, $id_casino);
          break;
        case 2://sfe
          return $this->store_nro_mesa_continuo($request, $id_casino);
          break;
        case 3://ros
          return $this->store_nro_mesa_continuo($request, $id_casino,$request->id_juego_mesa);
          break;
        default:
          $validator = new Validator;
          $validator->errors()
                    ->add('id_casino',
                          'La identificación del casino no es válida.');
          return ['errors' => $validator->messages()->toJson()];
          break;
      }

  }



  private function store_nro_mesa_continuo(Request $request, $id_casino)
  {
      $validator=  Validator::make($request->all(),[
        'nro_mesa' => ['required','integer'],
        'nombre' => 'required|max:100',
        'descripcion' => 'required|max:100',
        'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
        'id_casino' => 'required|exists:casino,id_casino',
        'id_moneda' => 'required|exists:moneda,id_moneda',
        'id_sector_mesas' => 'required|exists:sector_mesas,id_sector_mesas',
      ], array(), self::$atributos)->after(function($validator) use ($id_casino){

        $mesas = Mesa::where([['nro_mesa','=',$validator->getData()['nro_mesa']],
                              ['id_casino','=',$id_casino]])
                        ->get();
        if(count($mesas)> 0){
          $validator->errors()->add('nro_mesa', 'Ya existe una mesa con el numero '.$validator->getData()['nro_mesa'].'.');
        }

      })->validate();
      if(isset($validator)){
        if ($validator->fails()){
            return ['errors' => $validator->messages()->toJson()];
            }
       }
       $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if($user->usuarioTieneCasino($id_casino)){
         $mesa = Mesa::create($request->all());
        return $mesa;
      }else{
        return ['errors' => ['autorizacion'=>'No está autorizado para realizar esta accion.']];
      }
  }

  private function store_nro_mesa_rosario(Request $request, $id_casino,$id_juego)
  {
      $validator=  Validator::make($request->all(),[
        'nro_mesa' => ['required','integer'],
        'nombre' => 'required|max:100',
        'descripcion' => 'required|max:100',
        'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
        'id_casino' => 'required|exists:casino,id_casino',
        'id_moneda' => 'required|exists:moneda,id_moneda',
        'id_sector_mesas' => 'required|exists:sector_mesas,id_sector_mesas',
      ], array(), self::$atributos)->after(function($validator)use ($id_casino,$id_juego){

        $mesas = Mesa::where([['nro_mesa','=',$validator->getData()['nro_mesa']],
                              ['id_casino','=',$id_casino],
                              ['id_juego_mesa','=',$id_juego]])
                        ->get();
        if(count($mesas)> 0){
          $validator->errors()->add('nro_mesa', 'Ya existe una mesa con el numero '.$validator->getData()['nro_mesa'].'.');
        }

      })->validate();
      if(isset($validator)){
        if ($validator->fails()){
            return ['errors' => $validator->messages()->toJson()];
            }
       }
       $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if($user->usuarioTieneCasino($id_casino)){
         $mesa = Mesa::create($request->all());
        return $mesa;
      }else{
        return ['errors' => ['autorizacion'=>'No está autorizado para realizar esta accion.']];
      }

  }


  public function modificar(Request $request)
  {
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];

      $id_casino = $request->id_casino;
      $validator = new Validator;
      switch ($id_casino) {
        case 1://mel
        return
          $this->modif_nro_mesa_continuo($request, $id_casino,$request->id_mesa_de_panio);
          break;
        case 2://sfe
        return
          $this->modif_nro_mesa_continuo($request, $id_casino,$request->id_mesa_de_panio);
          break;
        case 3://ros
        return
          $this->modif_nro_mesa_continuo($request, $id_casino,$request->id_juego_mesa,$request->id_mesa_de_panio);
          break;
        default:
          $validator->errors()
                    ->add('id_casino',
                          'La identificación del casino no es válida.');
          return ['errors' => $validator->messages()->toJson()];
          break;
      }

  }

  private function modif_nro_mesa_continuo(Request $request, $id_casino,$id_mesa_de_panio)
  {
      $validator=  Validator::make($request->all(),[
        'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
        'nro_mesa' => ['required','integer'],
        'nombre' => 'required|max:100',
        'descripcion' => 'required|max:100',
        'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
        'id_casino' => 'required|exists:casino,id_casino',
        'id_moneda' => 'required|exists:moneda,id_moneda',
        'id_sector_mesas' => 'required|exists:sector_mesas,id_sector_mesas',
      ], array(), self::$atributos)->after(function($validator) use ($id_casino,$id_mesa_de_panio){

        $mesas = Mesa::where([['nro_mesa','=',$validator->getData()['nro_mesa']],
                              ['id_casino','=',$id_casino],
                              ['id_mesa_de_panio','!=',$id_mesa_de_panio]])
                        ->get();
        if(count($mesas)> 0){
          $validator->errors()->add('nro_mesa', 'Ya existe una mesa con el numero '.$validator->getData()['nro_mesa'].'.');
        }

      })->validate();
      if(isset($validator)){
        if ($validator->fails()){
            return ['errors' => $validator->messages()->toJson()];
            }
       }
       $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if($user->usuarioTieneCasino($id_casino)){
         $mesa = Mesa::where('id_mesa_de_panio','=',$request->id_mesa_de_panio)->update($request->all());
         return $mesa;
      }else{
        return ['errors' => ['autorizacion'=>'No está autorizado para realizar esta accion.']];
      }
  }

  private function modif_nro_mesa_rosario(Request $request, $id_casino,$id_juego,$id_mesa_de_panio)
  {
      $validator=  Validator::make($request->all(),[
        'id_mesa_de_panio' => 'required|exists:mesa_de_panio,id_mesa_de_panio',
        'nro_mesa' => ['required','integer'],
        'nombre' => 'required|max:100',
        'descripcion' => 'required|max:100',
        'id_juego_mesa' => 'required|exists:juego_mesa,id_juego_mesa',
        'id_casino' => 'required|exists:casino,id_casino',
        'id_moneda' => 'required|exists:moneda,id_moneda',
        'id_sector_mesas' => 'required|exists:sector_mesas,id_sector_mesas',
      ], array(), self::$atributos)->after(function($validator) use ($id_casino,$id_juego,$id_mesa_de_panio){

        $mesas = Mesa::where([['nro_mesa','=',$validator->getData()['nro_mesa']],
                              ['id_casino','=',$id_casino],
                              ['id_juego_mesa','=',$id_juego],
                              ['id_mesa_de_panio','!=',$id_mesa_de_panio]])
                        ->get();
        if(count($mesas)> 0){
          $validator->errors()->add('nro_mesa', 'Ya existe una mesa con el numero '.$validator->getData()['nro_mesa'].'.');
        }

      })->validate();
      if(isset($validator)){
        if ($validator->fails()){
            return ['errors' => $validator->messages()->toJson()];
            }
       }

       $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
      if($user->usuarioTieneCasino($id_casino)){
        $mesa = Mesa::where('id_mesa_de_panio','=',$request->id_mesa_de_panio)->update($request->all());

        return $mesa;

      }else{
      return ['errors' => ['autorizacion'=>'No está autorizado para realizar esta accion.']];
      }
  }

  public function eliminar($id_casino,$id_mesa_de_panio){
      $user = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
     if($user->usuarioTieneCasino($id_casino)){
     $mesa = Mesa::find($id_mesa_de_panio);
     $mesa->delete();
     return 1;
    }else{
      return ['errors' => ['autorizacion'=>'No está autorizado para realizar esta accion.']];
    }
  }

}
