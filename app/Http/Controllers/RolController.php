<?php

namespace App\Http\Controllers;

use App\Rol;
use App\Permiso;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Validator;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new RolController();
    }
    return self::$instance;
  }

  public function buscarTodo(){
    $roles= Rol::orderBy('descripcion' , 'asc')->get();
    $permisos= Permiso::orderBy('descripcion' , 'asc')->get();

    UsuarioController::getInstancia()->agregarSeccionReciente('Roles y Permisos' , 'roles');
    return view('seccionRolesPermisos' , ['roles' => $roles , 'permisos' => $permisos]);
  }

  public function guardarRol(Request $request){

    $validator=Validator::make($request->all(), [
      'descripcion' => 'required'
      ])->after(function ($validator){
        //validar que descripcion no exista
        $descripcion =strtoupper($validator->getData()['descripcion']);
        $resultado=Rol::where('descripcion' , '=' , $descripcion )->get();
        if($resultado->count() >= 1){
          $validator->errors()->add('existe', 'Ya existe rol con misma descripción.');
        }
      });

      $validator->validate();


      $rol= new Rol;
      $rol->descripcion=strtoupper($request->descripcion);
      $rol->save();
      if(!empty($request->permisos)){
      $rol->permisos()->sync($request->permisos);
      }

      return ['rol' => $rol , 'permisos' => $rol->permisos];



    }

    public function modificarRol(Request $request){

      $validator=Validator::make($request->all(), [
        'descripcion' => 'required'
        ])->after(function ($validator){
          //validar que descripcion no exista
          $descripcion =strtoupper($validator->getData()['descripcion']);


          $resultados=Rol::where('descripcion' , '=' , $descripcion )->get();
          $rol=Rol::find($validator->getData()['id']);
          if($resultados->count() >= 1){
            foreach ($resultados as $resultado) {
              if($rol->id_rol != $resultado->id_rol && $descripcion == $resultado->descripcion){
                $validator->errors()->add('existe', 'Ya existe rol con misma descripción.');
              }
            }

          }
        });

        $validator->validate();


        $rol= Rol::find($request->id);
        $rol->descripcion=strtoupper($request->descripcion);

        $rol->save();
        if(!empty($request->permisos)){
          $rol->permisos()->sync($request->permisos);
        }else{
          $rol->permisos()->detach();
        }


        return ['rol' => $rol, 'permisos' => $rol->permisos];
      }

        public function eliminarRol($id){
            $rol= Rol::find($id);
            $rol->permisos()->detach();
            $rol->usuarios()->detach();
            $rol->delete();
          return ['rol' => $rol] ;
        }

      public function getRol($id){
        $rol=Rol::findorfail($id);
        return ['rol' => $rol , 'permisos' => $rol->permisos, 'usuarios' => $rol->usuarios];
      }


      public function getAll(){
        $todos=Rol::all();
        return $todos;
      }

      public function buscarRoles(Request $request){
        $reglas=array();
        if(empty($request->rol) ){
        $reglas[]= ['descripcion' ,'like' , '%'];
        }
        else{
        $reglas[] = ['descripcion' ,'like' , '%' . $request->rol . '%'];
        }

        $roles = Rol::where($reglas)->get();

        $resultado=array();
        foreach ($roles as $rol) {
            $listaPermisos=array();
            foreach ($rol->permisos as $permiso) {
              $listaPermisos[]=$permiso->descripcion;
            }
            $unRol=['rol' => $rol, 'permisos' => $listaPermisos];
            $resultado[]=$unRol;
        }

        return ['roles' => $resultado];
      }


    }
