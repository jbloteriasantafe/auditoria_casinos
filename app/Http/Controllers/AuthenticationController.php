<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class AuthenticationController extends Controller
{
  private static $atributos = [
    'user_name' => 'Nombre Usuario',
    'password' => 'Contraseña'
  ];
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new AuthenticationController();
      }
      return self::$instance;
  }
  //generarToken
  private function generarToken(){
    $length = 20;
    $token = bin2hex(random_bytes($length));
    return $token;
  }
  //guardar TOKEN
  private function guardarTokenBD($id_usuario, $token){
    DB::table('usuario')
    ->where('id_usuario', $id_usuario)
    ->update(['token' => $token]);
  }
  //verficar TOKEN
  public function verificarToken($id_usuario, $token){
    $users = DB::table('usuario')->where([
      ['id_usuario', '=',$id_usuario],
      ['token', '=',$token],
      ])->first();

      return !empty($users);
  }
  //eliminar TOKEN
  public function eliminarToken($id_usuario, $token){
    DB::table('usuario')->where([
      ['id_usuario', '=', $id_usuario],
      ['token', '=',  $token],
      ])->update(['token' => null]);
  }

  public function login(Request $request){

    Validator::make($request->all(),[
          'user_name' => 'required',
          'password' => 'required',
        ], array(), self::$atributos)->after(function ($validator){
          $users = DB::table('usuario')->where([
            ['user_name', '=', $validator->getData()['user_name']],
            ['password', '=', $validator->getData()['password']],
            ])->first();
            if(empty($users)){
              $validator->errors()->add('existe', 'Las credenciales ingresadas no son válidas');
            }
        })->validate();

        $users = DB::table('usuario')->where([
          ['user_name', '=', $request->user_name],
          ['password', '=', $request->password],
          ])->first();

        if(!empty($users)){
          $token = $this->generarToken();
          $this->guardarTokenBD($users->id_usuario,$token);
          $request->session()->put('id_usuario', $users->id_usuario);
          $request->session()->put('token', $token);
          if($request->session()->has('redirect_to')){
            $redirect_to = $request->session()->get('redirect_to');
            $request->session()->forget('redirect_to');
            return ['redirect_to' => $redirect_to];
          }
        }
  }

  public function logout(Request $request){
    $this->eliminarToken($request->session()->get('id_usuario'),$request->session()->get('token'));
    $request->session()->flush();
  }

  public function usuarioTienePermiso($id_usuario,$permiso){
    $result = DB::table('permiso')
    ->where('permiso.descripcion','=',$permiso)
    ->join('rol_tiene_permiso','permiso.id_permiso','=','rol_tiene_permiso.id_permiso')
    ->join('usuario_tiene_rol','rol_tiene_permiso.id_rol','=','usuario_tiene_rol.id_rol')
    ->where('usuario_tiene_rol.id_usuario','=',$id_usuario)
    ->first();

    return !empty($result);
  }

  public function usuarioTieneAlgunPermiso($id_usuario,$permisos){
    $result = DB::table('usuario_tiene_rol')
    ->join('rol_tiene_permiso','usuario_tiene_rol.id_rol','=','rol_tiene_permiso.id_rol')
    ->join('permiso','permiso.id_permiso','=','rol_tiene_permiso.id_permiso')
    ->whereIn('permiso.descripcion',$permisos)
    ->where('usuario_tiene_rol.id_usuario','=',$id_usuario)
    ->count();

    return $result > 0;
  }

  public function usuarioTienePermisos(Request $request){
    $retorno = array();
    $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];


    foreach ($request->permisos as $permiso) {
          $retorno[$permiso]= $this->usuarioTienePermiso($usuario->id_usuario , $permiso);
    }

    $data = json_encode($retorno);
    return $data;
  }
}
