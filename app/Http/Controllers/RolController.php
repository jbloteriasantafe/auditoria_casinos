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
    $usuario = session('id_usuario');
    $id_rol= DB::table('usuario_tiene_rol')->select('id_rol')
                  ->where('id_usuario','=',$usuario)
                  ->min('id_rol');

    if($id_rol != 1 && $id_rol != 5){
      $roles= Rol::whereNotIn('id_rol',[5,6])
                  ->where('id_rol','>',$id_rol)
                  ->orderBy('id_rol' , 'desc')->get();
    }else{
      $roles= Rol::orderBy('id_rol' , 'desc')->where('id_rol','>=',$id_rol)->get();
    }


    $permisos = DB::table("permiso")->select("permiso.id_permiso","permiso.descripcion")
                                    ->join("rol_tiene_permiso","permiso.id_permiso","=","rol_tiene_permiso.id_permiso")
                                    //->where("rol_tiene_permiso.id_rol",'=',$id_rol)
                                    ->orderBy('permiso.descripcion' , 'asc')
                                    ->distinct()->get();

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

        $usuario = session('id_usuario');
        $id_rol= DB::table('usuario_tiene_rol')->select('id_rol')
                      ->where('id_usuario','=',$usuario)
                      ->min('id_rol');
        //dd(['mi_Rol'=> $id_rol,'hola']);

        if($id_rol != 1 && $id_rol != 5){
          $roles= Rol::orderBy('id_rol' , 'desc')->where('id_rol','>',$id_rol)->where($reglas)->whereNotIn('id_rol',[5,6])->get();
        }else{
          $roles= Rol::orderBy('id_rol' , 'desc')->where('id_rol','>=',$id_rol)->where($reglas)->get();
        }

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
