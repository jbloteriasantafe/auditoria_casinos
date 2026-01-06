<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use App\APIToken;

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
  
  public function loginUserName($user_name){
    $token = request()->session()->get('token') ?? null;
    if(empty($token)) {
      return ['success' => false,'error' => 'Error al obtener el token de sesión', 'id_usuario' => null];
    }
    
    $usuario = \App\Usuario::where('user_name','=',$user_name)->first();
    if(empty($usuario)){
      return ['success' => false,'error' => 'No se encontro el usuario '.$user_name, 'id_usuario' => null];
    }
    
    if($usuario->token != $token){
      return ['success' => false,'error' => 'Token invalido para '.$user_name, 'id_usuario' => null];
    }
    
    {
      $limpiar_tokens = \App\Usuario::where('token',$token)
      ->where('id_usuario','<>',$usuario->id_usuario);
      foreach($limpiar_tokens as $u){
        $this->eliminarToken($u->id_usuario,$token);
      }
    }
    
    request()->session()->put('id_usuario', $usuario->id_usuario);
    return ['success' => true,'error' => null,'id_usuario' => $usuario->id_usuario];
  }
  
  public function loginCASTicket($CAS_ENDPOINT,$service,$ticket){
    $ret = $this->CASserviceValidate($CAS_ENDPOINT,$service,$ticket);
    if(!$ret['success']) return $ret;
    
    $usuarios = [];
    foreach(($ret['attributes']['cuil'] ?? []) as $cuil){
      $cuil = substr(trim($cuil),2);//Saco los 2 primeros
      $dni = substr($cuil,0,strlen($cuil)-1);//Saco el ultimo
      foreach(\App\Usuario::where('dni',$dni)->get() as $u){
        $usuarios[$u->id_usuario] = $u;
      }
    }
    foreach(($ret['attributes']['mail'] ?? []) as $email){
      foreach(\App\Usuario::where('email',trim($email))->get() as $u){
        $usuarios[$u->id_usuario] = $u;
      }
    }
    
    if(!empty($usuarios)){
      $token = $this->generarToken();
      request()->session()->put('token', $token);
      foreach($usuarios as $id_usuario => $u){
        $this->guardarTokenBD($id_usuario,$token);
      }
    }
    
    return ['success' => true,'usuarios' => $usuarios];
  }
  
  private function CASserviceValidate($CAS_ENDPOINT,$service,$ticket){
    $params = [
      'service' => $service,
      'ticket'  => $ticket,
      'format'  => 'JSON'
    ];
    
    set_time_limit(5);
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $CAS_ENDPOINT.'/serviceValidate?'.http_build_query($params));
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_PROXY, getenv('HTTP_PROXY') ?? null);
    curl_setopt($ch, CURLOPT_PROXYPORT, getenv('HTTP_PROXY_PORT') ?? null);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, (getenv('HTTP_PROXY') ?? null !== null));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);//https
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//https
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    
    $response = curl_exec($ch);
    if($response === false){
      $errno = curl_errno($ch);
      $error = curl_error($ch);
      curl_close($ch);
      http_response_code(500);
      return ['success' => false,'error' => "CURL($errno): $error",'attributes' => []];
    }

    $code = http_response_code(curl_getinfo($ch,CURLINFO_HTTP_CODE));
    $headersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    //$headers = substr($response, 0, $headersSize);
    $body = substr($response, $headersSize);
    curl_close($ch);

    if($code != 200){
      return ['success' => false,'error' => "HTTP($code): $body",'attributes' => []];
    }

    $json = json_decode($body,true);
    if(empty($json['serviceResponse'])){
      return ['success' => false,'error' => "RESPONSE: malformed response got (".json_encode($json).')','attributes' => []];
    }

    $serviceResponse = $json['serviceResponse'];
    if(empty($serviceResponse['authenticationFailure']) && !empty($serviceResponse['authenticationSuccess'])){//Success
      $attributes = $serviceResponse['authenticationSuccess']['attributes'] ?? [];
      return ['success' => true,'error' => null,'attributes' => $attributes];
    }
    elseif(!empty($serviceResponse['authenticationFailure']) && empty($serviceResponse['authenticationSuccess'])){//Error
      $code = $serviceResponse['authenticationFailure']['code'] ?? null;
      $error = $serviceResponse['authenticationFailure']['description'] ?? null;
      return ['success' => false,'error' => "CAS($code): $error",'attributes' => []];
    }
    else{//Malformed
      return ['success' => false,'error' => "RESPONSE: malformed response got (".json_encode($json).')','attributes' => []];
    }
  }

  public function login(Request $request){

    Validator::make($request->all(),[
          'user_name' => 'required',
          'password' => 'required',
        ], array(), self::$atributos)->after(function ($validator){
          $users = DB::table('usuario')->where([
            ['user_name', '=', $validator->getData()['user_name']],
            ['password', '=', $validator->getData()['password']],
            ])->whereNull('deleted_at')->first();
            if(empty($users)){
              $validator->errors()->add('existe', 'Las credenciales ingresadas no son válidas');
            }
        })->validate();

        $users = DB::table('usuario')->where([
          ['user_name', '=', $request->user_name],
          ['password', '=', $request->password],
          ])->whereNull('deleted_at')->first();

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
  
  public function logoutGET(Request $request){
    $this->logout($request);
    return redirect('inicio');
  }

  public function usuarioTieneRol($id_usuario,$rol){
    return !empty(DB::table('rol')
    ->where('rol.descripcion','=',$rol)
    ->join('usuario_tiene_rol','usuario_tiene_rol.id_rol','=','rol.id_rol')
    ->where('usuario_tiene_rol.id_usuario','=',$id_usuario)
    ->first());
  }
  
  public function usuarioTieneAlgunRol($id_usuario,$roles){
    return DB::table('rol')
    ->whereIn('rol.descripcion',$roles)
    ->join('usuario_tiene_rol','usuario_tiene_rol.id_rol','=','rol.id_rol')
    ->where('usuario_tiene_rol.id_usuario','=',$id_usuario)
    ->count() > 0;
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

  public function obtenerIdUsuario(){
    $session = null;
    try{
      $session = request()->session();
    }
    catch(\Exception $e){}
    
    $id_usuario = null;
    if(!is_null($session)){
      $id_usuario = $session->has('id_usuario') ? $session->get('id_usuario') : null;
    }
    
    if(is_null($id_usuario)){
      $api_token = $this->obtenerAPIToken();
      if(!is_null($api_token)){
        $metadata = $api_token->metadata ?? [];
        $id_usuario = $metadata['id_usuario'] ?? null;
        if($metadata['puede_post_user_name'] ?? false){//Este permiso solo deberia usarse entre servidores locales
          $usuario = \App\Usuario::where('user_name',request()->user_name ?? null)->select('id_usuario')->first();
          $id_usuario = $usuario? $usuario->id_usuario : $id_usuario;
        }
      }
    }
    
    return $id_usuario;
  }

  public function obtenerAPIToken(){
    $APIToken = request()->header('API-Token');
    $api_token = APIToken::where('ip',request()->ip())->where('token',$APIToken)->orderBy('id_api_token','asc')->get()->first();
    return $api_token;
  }
}
