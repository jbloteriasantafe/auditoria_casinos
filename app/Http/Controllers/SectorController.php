<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Sector;
use Validator;
use Illuminate\Validation\Rule;
use App\Isla;

class SectorController extends Controller
{
  private static $atributos = [
    'descripcion' => 'Descripción',
  ];

  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new SectorController();
    }
    return self::$instance;
  }

  public function buscarTodo(Request $request){
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = array();
    foreach($usuario->casinos as $casino){
      $casinos [] = $casino->id_casino;
    }
    $sectores = Sector::whereIn('id_casino',$casinos)->get();
    UsuarioController::getInstancia()->agregarSeccionReciente('Sectores' , 'sectores');

    return view('seccionSectores', ['sectores' => $sectores]);
  }

  public function obtenerSector($id_sector){
    $sector = Sector::find($id_sector);
    return ['sector' => $sector,'casino' => $sector->casino,'islas' => $sector->islas];
  }

  public function eliminarSector($id_sector){
    $sector = Sector::find($id_sector);

    foreach($sector->layouts_parcial as $unlayoutParcial) {
      $unlayoutParcial->delete();
    }

    foreach ($sector->relevamientos as $unRelevamiento) {
      $unRelevamiento->delete();
    }

    $sector->delete();

    return ['sector' => $sector];
  }

  public function guardarSector(Request $request){
    Validator::make($request->all(), [
        'descripcion' => 'required|max:45',
        'id_casino' => ['required', Rule::exists('usuario_tiene_casino')->where(function($query){$query->where('id_usuario', session('id_usuario'));})],
        'islas' => 'nullable',
        'islas.*.id_isla' => 'required|exists:isla,id_isla'
    ], array(), self::$atributos)->after(function ($validator){
      $res = Sector::where([
        ['descripcion','=',$validator->getData()['descripcion']],
        ['id_casino','=',$validator->getData()['id_casino']],
        ])->count();
      if ($res > 0) {
           $validator->errors()->add('descripcion','El campo Nombre ya está tomado');
       }
    })->validate();

    $sector = new Sector;
    $sector->descripcion = $request->descripcion;
    $sector->id_casino = $request->id_casino;
    $sector->save();
    // $sector->casino()->associate($request->id_casino);

    if(!empty($request->islas)){
      foreach ($request->islas as $isla){
        $aux = Isla::find($isla['id_isla']);
        $aux->sector()->associate($sector->id_sector);
        $aux->save();
      }
    }
    $sector->save();

    return ['sector' => $sector,'casino' => $sector->casino];
  }

  public function modificarSector(Request $request){
    Validator::make($request->all(), [
        'id_sector' => 'required|exists:sector,id_sector',
        'descripcion' => 'required|max:45',
        'id_casino' => ['required', Rule::exists('usuario_tiene_casino')->where(function($query){$query->where('id_usuario', session('id_usuario'));})],
        'islas' => 'nullable',
        'islas.*.id_isla' => 'required|exists:isla,id_isla'
    ], array(), self::$atributos)->after(function ($validator){
      $res = Sector::where([
        ['descripcion','=',$validator->getData()['descripcion']],
        ['id_casino','=',$validator->getData()['id_casino']],
        ['id_sector','<>',$validator->getData()['id_sector']],
        ])->count();
      if ($res > 0) {
           $validator->errors()->add('descripcion','El campo Nombre ya está tomado');
       }
    })->validate();

    $sector = Sector::find($request->id_sector);
    $sector->descripcion = $request->descripcion;
    $sector->casino()->associate($request->id_casino);

    if(!empty($request->islas)){
      $this->desasociarIslas($sector->id_sector);
      $this->asociarIslas($request->islas,$sector->id_sector);
    }else{
      $this->desasociarIslas($sector->id_sector);
    }
    $sector->save();

    return ['sector' => $sector,'casino' => $sector->casino];
  }

  public function desasociarIslas($id_sector){
     $islas=Sector::find($id_sector)->islas;
      foreach($islas as $isla){
        $isla->sector()->dissociate();
        $isla->save();
      }
  }

  public function asociarIslas($islas,$id_sector){
      foreach($islas as $isl){
        $isla = Isla::find($isl['id_isla']);
        $isla->sector()->associate($id_sector);
        $isla->save();
      }
  }

  public function obtenerSectoresPorCasino($id_casino){
    $sectores = Sector::where('id_casino','=',$id_casino)->get();
    return ['sectores' => $sectores];
  }

}
