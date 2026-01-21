<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;
use App\APIToken;

class OlvideMiContrasenaController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
    
  private function obtenerUsuariosPorMail($email){
    $email_partes = $this->simplificar_email($email);
    return \App\Usuario::where([
      ['email', 'LIKE', $email_partes['local'].'%'],//chequeos rapidos
      ['email', 'LIKE', '%'.$email_partes['domain']]
    ])->get()
    ->filter(function($u) use ($email_partes){
      $ep = $this->simplificar_email($u->email);//Verifico que sean emails equivalentes
      return ($ep['local'] == $email_partes['local']) && ($ep['domain'] == $email_partes['domain']);
    });
  }
  
  private function validatorError($V){
    return implode('<br>',array_map(function($errs){
      return implode('<br>',$errs);
    },$V->errors()->getMessages()));
  }
  
  public function ingresarUser(Request $request){
    //Para deshabilitar setear en .env el mensaje DESHABILITAR_OLVIDE_CONTRASEÑA="Mensaje de error"
    $token_recuperacion = session()->get('token_recuperacion') ?? null;
    if($token_recuperacion !== null){
      $this->eliminarSesionRecuperacion($token_recuperacion);
      session()->forget('token_recuperacion');
    }
    
    return ['success' => !env('DESHABILITAR_OLVIDE_CONTRASEÑA',0),'error' => env('DESHABILITAR_OLVIDE_CONTRASEÑA',null)];
  }
  
  public function enviarCodigo(Request $request){
    $V = Validator::make($request->all(),[
      'email' => 'required|email',
    ], [
      'email.required' => 'El email es requerido',
      'email.email' => 'El email no esta en formato correcto',
    ], [])->after(function ($validator) {
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $usuarios = $this->obtenerUsuariosPorMail($data['email']);
      if($usuarios->count() == 0){
        return $validator->errors()->add('email', 'No existe usuario asociado a ese correo');
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
    
    $codigo = bin2hex(random_bytes(6));
    $time = time();
    $this->guardarCodigoRecuperacion($request->email,$codigo,$time);
    $ok = $this->enviarMailRecuperacion($request->email,$codigo);
    
    if($ok != 0){
      return ['success' => false,'error' => $ok];
    }
    
    {
      //@santafe.gov.ar no permite correos equivalentes con + asi que envio al simplificado
      $email = implode('@',$this->simplificar_email($request->email));
      $link = url('/login').'?'.http_build_query([
        'accion' => 'olvideMiContraseña_verificarCodigo',
        'email' => $email,
        'codigo' => $codigo
      ]);
      
      \Illuminate\Support\Facades\Mail::to($email)
      ->send(new \App\Mail\RecuperarContrasena($email,$request->email,$link));
    }
    
    return ['success' => true,'error' => null];
  }
  
  public function verificarCodigo(Request $request){
    $usuarios = null;
    $V = Validator::make($request->all(),[
      'email' => 'required|email',
      'codigo' => 'required'
    ], [
      'email.required' => 'El email es requerido',
      'email.email' => 'El email no esta en formato correcto',
      'codigo.required' => 'Se necesita el código de validación'
    ], [])->after(function ($validator) use (&$usuarios){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $usuarios = $this->obtenerUsuariosPorMail($data['email']);
      if($usuarios->count() == 0){
        return $validator->errors()->add('email', 'No existe usuario asociado a ese correo');
      }
      
      $codigo = $data['codigo'] ?? null;
      $estado = $this->verificarCodigoRecuperacion($data['email'],$codigo);
      if($estado !== 0) {
        return $validator->errors()->add('codigo',$estado);
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
    
    $this->eliminarCodigoRecuperacion($request->email);
    
    //Codigo aceptado, guardo un token que marca el inicio del proceso 
    $token_recuperacion = bin2hex(random_bytes(20));
    request()->session()->put('token_recuperacion', $token_recuperacion);
    $time = time();
    foreach($usuarios as $u){
      $this->guardarSesionRecuperacion($token_recuperacion,$u->user_name,$time);
      $u->preferencial = $u->email == $request->email;
    }
    return ['success' => true,'usuarios' => $usuarios,'error' => null];
  }
  
  public function verificarSeleccionUsuarios(Request $request){   
    $V = Validator::make($request->all(),[
      'usuarios' => 'required|array|min:1',
      'usuarios.*' => 'required|exists:usuario,user_name,deleted_at,NULL',
    ], [
      'usuarios.required' => 'Se necesita al menos un usuario',
      'usuarios.array' => 'Formato incorrecto',
      'usuarios.min' => 'Se necesita al menos un usuario',
      'usuarios.*.required' => 'Se necesita al menos un usuario',
    ], [])->after(function ($validator) {
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $token_recuperacion = session()->get('token_recuperacion') ?? null;
      if($token_recuperacion === null){
        return $validator->errors()->add('session','No hay una sesión de recuperacion iniciada');
      }
      
      foreach($data['usuarios'] as $user_name){//Verifico que exista sesion para ese (token,usuario)
        $estado = $this->verificarSesionRecuperacion($token_recuperacion,$user_name);
        if($estado !== 0){
          return $validator->errors()->add('usuarios',$estado);
        }
      }
    });
    
    $token_recuperacion = request()->session()->get('token_recuperacion');
    if($V->fails()){
      //Recreo los usuarios elegibles en la sesion
      $usuarios = \App\Usuario::whereIn('user_name',$this->usuariosSesionRecuperacion($token_recuperacion))->get();
      return ['success' => false,'usuarios' => $usuarios,'error' => $this->validatorError($V)];
    }
    
    //Borro las sesiones de usuarios que no eligio
    //y refresco las cuales eligio
    $this->eliminarSesionRecuperacion($token_recuperacion);
    $time = time();
    foreach($request->usuarios as $user_name){
      $this->guardarSesionRecuperacion($token_recuperacion,$user_name,$time);
    }
    
    $usuarios = \App\Usuario::whereIn('user_name',$request->usuarios)->get();
    return ['success' => true,'usuarios' => $usuarios,'error' => null];
  }
  
  public function resetearPasswords(Request $request){
    $V = Validator::make($request->all(),[
      'password' => 'required|string|min:8|confirmed',
      'password_confirmation' => 'required'
    ], [
      'password.required' => 'Ingresar una contraseña',
      'password_confirmation.required' => '',
      'password.min' => 'Se necesita una longitud de al menos 8 caracteres',
      'password.confirmed' => 'Tienen que coincidir las contraseñas'
    ], [])->after(function ($validator) use (&$usuarios,&$email_partes){
      if($validator->errors()->any()) return;
      $estado = $this->verificarSesionRecuperacion(session()->get('token_recuperacion') ?? null);
      if($estado !== 0){
        return $validator->errors()->add('session',$estado);
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
    
    $token_recuperacion = request()->session()->get('token_recuperacion');
    $usuarios = \App\Usuario::whereIn('user_name',$this->usuariosSesionRecuperacion($token_recuperacion))->get();
    
    try {
      DB::transaction(function() use (&$usuarios,$request){
        foreach($usuarios as $u){
          $u->password = $request->password;
          session()->put('id_usuario',$u->id_usuario);
          $u->save();
          session()->forget('id_usuario');
        }
      });
    }
    catch(\Exception $e){
      session()->forget('id_usuario');
      throw $e;
    }
    
    $this->eliminarSesionRecuperacion($token_recuperacion);
    
    return ['success' => true,'mensaje' => 'Contraseña modificada','error' => null];
  }
  //@SAFETY: 
  // La unica forma real de verificar la validad de un mail es mandando un "ping" a la dirección
  // este chequeo no es necesariamente lo mas seguro
  private static function simplificar_email($email) {
    $email = trim(strtolower($email));
    if (!str_contains($email, '@')) return false;

    $local_domain = explode('@', $email);
    $local = $local_domain[0];
    $domain = $local_domain[1];
    //$domain = idn_to_ascii($local_domain[1]);exit(0);
    $actual_domain = null;
    
    //if(preg_match_all("/[a-z0-9.\-]+[.][a-z]{2,4}$/i", $domain, $actual_domain)){
    if(preg_match_all("/[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6}$/i", $domain, $actual_domain)){
      if(count($actual_domain) == 0 || count($actual_domain[0]) == 0)
        return false;
      $actual_domain = $actual_domain[0][0];  
    }
    if($actual_domain === null) return false;
    
    /*$subdomains = $domain == $actual_domain?
      ''
    : substr($domain,0,strlen($domain)-strlen($actual_domain)-1); //-1 por el punto*/
        
    $domainname = explode('.',$actual_domain)[0];
    if ($domainname === 'gmail' || $domainname === 'googlemail') {
      $local = str_replace('.', '', $local); // Un mail de mail con . es lo mismo que uno sin (es.un.mail@gmail.com == esunmail@gmail.com)
    }
    else if($domainname == 'yahoo'){// En yahoo el - es valido como un +
      $local = str_replace('-','+', $local); 
    }
    else if($domainname == 'fastmail'){//En fastmail el subdominio es equivalente a +
      $domain = $actual_domain;
    }
    
    // En el estandar de mail el + idempotente es decir que test+A@domain.com == test+B@domain.com == test@domain.com
    // por eso solo nos quedamos con la parte de adelante
    $local = explode('+', $local)[0];
    return [
      'local' => $local,
      'domain' => $domain
    ];
  }
  
  //@TODO: agregar columna a tabla Usuario con token de recuperación
  private function __path_restaurandoContrasena(){
    return storage_path('app/restaurandoContraseña.json');
  }
    
  private function __leerArchivoRecuperacion(){
    $this->__inicializarArchivoRecuperacion();
    $f_restaurandoContrasena = $this->__path_restaurandoContrasena();
    return json_decode(file_get_contents($f_restaurandoContrasena),true);
  }
  private function __inicializarArchivoRecuperacion(){
    $f_restaurandoContrasena = $this->__path_restaurandoContrasena();
    if(!file_exists($f_restaurandoContrasena)){
      $this->__guardarArchivoRecuperacion((object)[]);
    }
    return 1;
  }
  private function __guardarArchivoRecuperacion($obj){
    $f_restaurandoContrasena = $this->__path_restaurandoContrasena();
    $fh = fopen($f_restaurandoContrasena,'w');
    fwrite($fh,json_encode($obj,JSON_PRETTY_PRINT));
    fclose($fh);
    return 1;
  }
    
  private function guardarCodigoRecuperacion($email,$codigo,$time){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    $restaurandoContrasena['codigos'] = $restaurandoContrasena['codigos'] ?? [];
    $restaurandoContrasena['codigos'][$email] = $restaurandoContrasena['codigos'][$email] ?? [];
    $restaurandoContrasena['codigos'][$email] = compact('codigo','time');
    
    $this->__guardarArchivoRecuperacion($restaurandoContrasena);
    
    return $time;
  }
  private function guardarSesionRecuperacion($token_recuperacion,$user_name,$time){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    $restaurandoContrasena['sesiones'] = $restaurandoContrasena['sesiones'] ?? [];
    $restaurandoContrasena['sesiones'][$token_recuperacion] = $restaurandoContrasena['sesiones'][$token_recuperacion] ?? [];
    $restaurandoContrasena['sesiones'][$token_recuperacion][$user_name] = $time;
    
    $this->__guardarArchivoRecuperacion($restaurandoContrasena);
    
    return $time;
  }
  
  private function usuariosSesionRecuperacion($token_recuperacion){
    $this->__inicializarArchivoRecuperacion();
    $restaurandoContrasena = $this->__leerArchivoRecuperacion()['sesiones'] ?? [];
    return array_keys($restaurandoContrasena[$token_recuperacion] ?? []);
  }
  
  private function verificarCodigoRecuperacion($email,$codigo){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    $stored = $restaurandoContrasena['codigos'] ?? [];
    $stored = $stored[$email] ?? [];
    $valido = ($stored['codigo'] ?? null) === $codigo;
    if(!$valido) return 'Invalido';
    
    $RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX =  env('RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX',21600);//6 minutos => 21600
    $time_valido = (time()-($stored['time'] ?? 0)) < $RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX;
    if(!$time_valido) return 'Expirado';
    
    return 0;
  }
    
  private function verificarSesionRecuperacion($token_recuperacion,$user_name = null){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    
    $sesiones = $restaurandoContrasena['sesiones'] ?? [];
    $sesiones_token_recuperacion = $sesiones[$token_recuperacion] ?? null;
    if($sesiones_token_recuperacion === null){
      return 'Invalido';
    }
    
    if($user_name !== null){
      $aux = [];
      $aux[$user_name] = $sesiones_token_recuperacion[$user_name] ?? null;
      $sesiones_token_recuperacion = $aux;
    }
    
    $RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX = env('RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX',21600);//6 minutos => 21600
    $curr_time = time();
    foreach($sesiones_token_recuperacion as $un => $time){
      if($time === null) return 'Invalido';
      
      $time_valido = ($curr_time-$time) < $RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX;
      if(!$time_valido) return 'Expirado';
    }
    
    return 0;
  }
  
  private function eliminarCodigoRecuperacion($email){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    $codigos = $restaurandoContrasena['codigos'] ?? [];
    if(!empty($codigos[$email])){
      unset($restaurandoContrasena['codigos'][$email]);
      $this->__guardarArchivoRecuperacion($restaurandoContrasena);
      return true;
    }
    
    return false;
  }
  
  private function eliminarSesionRecuperacion($token_recuperacion,$user_name = null){
    $this->__inicializarArchivoRecuperacion();
    
    $restaurandoContrasena = $this->__leerArchivoRecuperacion();
    $sesiones = $restaurandoContrasena['sesiones'] ?? [];
    $sesiones_token_recuperacion = $sesiones[$token_recuperacion] ?? [];
    
    if($user_name !== null) {
      $aux = [];
      $aux[$user_name] = $sesiones_token_recuperacion[$user_name] ?? null;
      $sesiones_token_recuperacion = $aux;
    }
    
    foreach($sesiones_token_recuperacion as $un => $_){
      unset($restaurandoContrasena['sesiones'][$token_recuperacion][$un]);
    }
    
    if(empty($restaurandoContrasena['sesiones'][$token_recuperacion])){
      unset($restaurandoContrasena['sesiones'][$token_recuperacion]);
    }
    
    $this->__guardarArchivoRecuperacion($restaurandoContrasena);
    
    return false;
  }
  
  private function enviarMailRecuperacion($emails,$codigo){
    //@TODO implementar
    return 0;
  }
}
