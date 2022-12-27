<?php

namespace App\Http\Controllers;

use App\Rol;
use App\Permiso;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Validator;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
{

  private static $instance;

  public static function getInstancia() {

      if (!isset(self::$instance)) {
          self::$instance = new PermisoController();
      }

      return self::$instance;
  }

  public function guardarPermiso(Request $request){

     $validator=Validator::make($request->all(), [
        'descripcion' => 'required'
      ])->after(function ($validator){
                  //validar que descripcion no exista
                  $descripcion =strtolower($validator->getData()['descripcion']);
                  $resultado=permiso::where('descripcion' , '=' , $descripcion )->get();
                  if($resultado->count() >= 1){
                      $validator->errors()->add('existe', 'Ya existe rol con misma descripción.');
                  }
                });

      $validator->validate();


      $permiso= new Permiso;
      $permiso->descripcion=strtolower($request->descripcion);
      $permiso->save();

      if(!empty($request->roles)){
      $permiso->roles()->sync($request->roles);
      }

      return  ['permiso' =>$permiso , 'roles' => $permiso->roles];
  }

  public function modificarPermiso(Request $request){

    $validator=Validator::make($request->all(), [
      'descripcion' => 'required'
      ])->after(function ($validator){
        //validar que descripcion no exista
        $descripcion =strtolower($validator->getData()['descripcion']);


        $resultados=Permiso::where('descripcion' , '=' , $descripcion )->get();
        $permiso=Permiso::find($validator->getData()['id']);
        if($resultados->count() >= 1){
          foreach ($resultados as $resultado) {
            if($permiso->id_permiso != $resultado->id_permiso && $descripcion == $resultado->descripcion){
              $validator->errors()->add('existe', 'Ya existe permiso con misma descripción.');
            }
          }

        }
      });

      $validator->validate();

      $permiso= Permiso::find($request->id);
      $permiso->descripcion=strtolower($request->descripcion);
      $permiso->save();
      if(!empty($request->roles)){
      $permiso->roles()->sync($request->roles);
    }  else {
      $permiso->roles()->detach();
    }

      return ['permiso' => $permiso, 'roles' => $permiso->roles];
    }

  public function eliminarPermiso($id){
      $permiso= Permiso::find($id);
      $roles=$permiso->roles;
      $permiso->roles()->detach();
      $permiso->delete();
    return ['permiso' => $permiso , 'roles' => $roles] ;
  }

  public function getPermiso($id){
    $permiso=Permiso::findorfail($id);
    return ['permiso' => $permiso , 'roles' => $permiso->roles ];

  }

  public function buscarPermisos(Request $request){
    if(empty($request->permiso) ){
    $permiso='%';
    }
    else{
      $permiso= '%' . $request->permiso . '%';
    }
      $resultado = Permiso::where([
      ['descripcion' ,'like' , $permiso],
    ])->get();
    return ['permiso' => $resultado];
  }

  public function buscarPermisosPorRoles(Request $request) {
    $roles = array();

    if ($request->roles != null) {
      foreach ($request->roles as $rol) {
        $roles[] = $rol;
      }
    }

    if($roles != null) {
      $permisos = DB::table("permiso")->select("permiso.id_permiso","permiso.descripcion")
                                      ->join("rol_tiene_permiso","permiso.id_permiso","=","rol_tiene_permiso.id_permiso")
                                      ->whereIn("rol_tiene_permiso.id_rol",$roles)->distinct()->get();

      return ["permisos" => $permisos];

    }
  }

}
