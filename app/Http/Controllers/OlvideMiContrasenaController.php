<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class OlvideMiContrasenaController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }
  
  public function __construct(){
    DB::statement('CREATE TABLE IF NOT EXISTS recuperar_contrasena (
      id_recuperar_contrasena INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
      email VARCHAR(128) NOT NULL,
      email_simplificado VARCHAR(128) NOT NULL,
      codigo VARCHAR(16) NOT NULL,
      token_recuperacion VARCHAR(64) NULL,
      usuario_id_usuario INT NOT NULL,
      usuario_user_name VARCHAR(64) NOT NULL,
      usuario_email VARCHAR(128) NOT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      verified_at TIMESTAMP NULL,
      resetted_at TIMESTAMP NULL,
      expired_at TIMESTAMP NULL,
      invalidated_at TIMESTAMP NULL,
      KEY `fk_recuperar_contrasena_usuario` (`usuario_id_usuario`),
      CONSTRAINT `fk_recuperar_contrasena_usuario` FOREIGN KEY (`usuario_id_usuario`) REFERENCES `usuario` (`id_usuario`)
    )');
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
      session()->forget('token_recuperacion');
    }
    
    return ['success' => !env('DESHABILITAR_OLVIDE_CONTRASEÑA',0),'error' => env('DESHABILITAR_OLVIDE_CONTRASEÑA',null)];
  }
  
  public function enviarCodigo(Request $request){
    $now = new \DateTimeImmutable();
    $usuarios = null;
    $email_simplificado = null;
    $V = Validator::make($request->all(),[
      'email' => 'required|email',
    ], [
      'email.required' => 'El email es requerido',
      'email.email' => 'El email no esta en formato correcto',
    ], [])->after(function ($validator) use (&$usuarios,&$email_simplificado) {
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $email_simplificado = $this->simplificar_email($data['email']);
      if($email_simplificado === false){
        return $validator->errors()->add('email','Formato incorrecto de email');
      }
      
      $usuarios = \App\Usuario::where([
        ['email', 'LIKE', $email_simplificado['local'].'%'],//chequeos rapidos
        ['email', 'LIKE', '%'.$email_simplificado['domain']]
      ])->get()
      ->filter(function($u) use ($email_simplificado){
        $ep = $this->simplificar_email($u->email);//Verifico que sean emails equivalentes
        return ($ep['local'] == $email_simplificado['local']) && ($ep['domain'] == $email_simplificado['domain']);
      });
      
      if($usuarios->count() == 0){
        return $validator->errors()->add('email', 'No existe usuario asociado a ese correo');
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
    
    try {
      return DB::transaction(function() use ($request,&$usuarios,&$email_simplificado,$now){
        $codigo = (function($longitud_codigo,$caracteres_validos){
          $caracteres_validos_length_m1 = strlen($caracteres_validos)-1;
          $ret = '';
          for($cidx = 0;$cidx < $longitud_codigo;$cidx++){
            $randidx = random_int(0,$caracteres_validos_length_m1);
            $ret .= $caracteres_validos[$randidx];
          }
          return $ret;
        })(
          env('RESTAURAR_CONTRASEÑA_LONGITUD_CODIGO',6),
          env('RESTAURAR_CONTRASEÑA_CARACTERES_CODIDO','0123456789')
        );
        
        $interval_expired_at = new \DateInterval(
          'PT'.//period
          env('RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX',360).//6 minutos
          'S'//seconds
        );
        
        $created_at = $now;
        $expired_at = $created_at->add($interval_expired_at);
        $created_at_str = $created_at->format('Y-m-d H:i:s');
        $expired_at_str = $expired_at->format('Y-m-d H:i:s');
        $email_simplificado_str = implode('@',$email_simplificado);
        
        //Invalido el codigo enviado previamente
        DB::table('recuperar_contrasena')
        ->where('email_simplificado',$email_simplificado_str)
        ->whereNull('invalidated_at')
        ->whereNull('verified_at')
        ->whereNull('resetted_at')
        ->update([
          'invalidated_at' => $created_at_str
        ]);
        
        $rcs = $usuarios->map(function($u) use ($request,$email_simplificado_str,$codigo,$created_at_str,$expired_at_str){
          return [
            'email' => $request->email,
            'email_simplificado' => $email_simplificado_str,
            'codigo' => $codigo,
            'token_recuperacion' => NULL,
            'usuario_id_usuario' => $u->id_usuario,//Guardo por si cambia el nombre de usuario
            'usuario_user_name' => $u->user_name,//Guardo por si cambia el email de usuario
            'usuario_email' => $u->email,
            'created_at' => $created_at_str,
            'verified_at' => NULL,
            'resetted_at' => NULL,
            'expired_at' => $expired_at_str,
            'invalidated_at' => NULL
          ];
        })->toArray();
        
        DB::table('recuperar_contrasena')->insert($rcs);
        
        $link = url('/login').'?'.http_build_query([
          'router' => 'olvideMiContrasena',
          'accion' => 'verificarCodigo',
          'email' => $request->email,
          'codigo' => $codigo
        ]);
        
        \Illuminate\Support\Facades\Mail::to($email_simplificado_str)
        ->send(new \App\Mail\RecuperarContrasena($request->email,$link,$codigo));
        
        return ['success' => true,'email' => $request->email,'error' => null];
      });
    }
    catch(\Exception $e){
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }
  
  public function verificarCodigo(Request $request){
    $now = new \DateTimeImmutable();
    $rcs = null;
    $V = Validator::make($request->all(),[
      'email' => 'required|email|exists:recuperar_contrasena,email,verified_at,NULL',
      'codigo' => 'required'
    ], [
      'email.required' => 'El email es requerido',
      'email.email' => 'El email no esta en formato correcto',
      'email.exists' => 'No existe código de validación para ese email',
      'codigo.required' => 'Se necesita el código de validación'
    ], [])->after(function ($validator) use (&$rcs,$now){
      if($validator->errors()->any()) return;
      $data = $validator->getData();
      
      $rcs = DB::table('recuperar_contrasena')
      ->where('email',$data['email'])
      ->whereNull('invalidated_at')
      ->whereNull('verified_at')
      ->whereNull('resetted_at')
      ->where('expired_at','>',$now->format('Y-m-d H:i:s'))//Busco los que no expiraron
      ->get();
      
      if($rcs->count() == 0){
        return $validator->errors()->add('email','No existe o expiro el código de validación para ese email');
      }
      
      $codigo = $data['codigo'];
      
      $rcs = $rcs->filter(function($_rc) use (&$codigo){
        return $_rc->codigo == $codigo;
      });
      
      if($rcs->count() == 0){
        return $validator->errors()->add('codigo','Código incorrecto');
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
    
    try {
      return DB::transaction(function() use ($request,&$rcs,$now){
        $token_recuperacion = bin2hex(random_bytes(20));
                
        $verified_at = $now;
        $interval_expired_at = new \DateInterval(
          'PT'.//period
          env('RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX',360).//6 minutos
          'S'//seconds
        );
        $expired_at = $verified_at->add($interval_expired_at);
                
        DB::table('recuperar_contrasena')
        ->whereIn('id_recuperar_contrasena',$rcs->pluck('id_recuperar_contrasena'))
        ->update([
          'token_recuperacion' => $token_recuperacion,
          'verified_at' => $verified_at->format('Y-m-d H:i:s'),
          'expired_at' => $expired_at->format('Y-m-d H:i:s')
        ]);
                
        request()->session()->put('token_recuperacion', $token_recuperacion);
                
        $usuarios = \App\Usuario::whereIn('id_usuario',$rcs->pluck('usuario_id_usuario'))
        ->get();
        
        foreach($usuarios as $u){
          $u->preferencial = $u->email == $request->email;
        }
        
        return ['success' => true,'usuarios' => $usuarios,'error' => null];
      });
    }
    catch(\Exception $e){
      request()->session()->forget('token_recuperacion');
      return ['success' => false, 'email' => $request->email,'error' => $e->getMessage()];
    }
  }
  
  public function verificarSeleccionUsuarios(Request $request){
    $now = new \DateTimeImmutable();
    $rcs = null;
    $token_recuperacion = session()->get('token_recuperacion') ?? null;
    
    $V = Validator::make($request->all(),[
      'usuarios' => 'required|array|min:1',
      'usuarios.*' => 'required|exists:usuario,id_usuario,deleted_at,NULL',
    ], [
      'usuarios.required' => 'Se necesita al menos un usuario',
      'usuarios.array' => 'Formato incorrecto',
      'usuarios.min' => 'Se necesita al menos un usuario',
      'usuarios.*.required' => 'Se necesita al menos un usuario',
    ], [])->after(function ($validator) use(&$rcs,$token_recuperacion,$now) {
      if($validator->errors()->any()) return;
      if($token_recuperacion === null){
        return $validator->errors()->add('session','No hay una sesión de recuperacion iniciada');
      }
      
      $data = $validator->getData();
      
      $rcs = DB::table('recuperar_contrasena')
      ->where('token_recuperacion',$token_recuperacion)
      ->whereIn('usuario_id_usuario',$data['usuarios'])
      ->whereNull('invalidated_at')
      ->whereNull('resetted_at')
      ->where('expired_at','>',$now->format('Y-m-d H:i:s'))//No expiro
      ->get();
      
      if($rcs->count() == 0){
        return $validator->errors()->add('session','Selección invalida o sesión expirada');
      }
      if(count($data['usuarios']) > $rcs->count()){
        return $validator->errors()->add('session','Selección invalida para la sesión');
      }
    });
    
    $rcs_todos = DB::table('recuperar_contrasena')
    ->where('token_recuperacion',$token_recuperacion)
    ->whereNull('invalidated_at')
    ->whereNull('resetted_at')
    ->get();
    //Recreo los usuarios elegibles en la sesion
    $usuarios_todos = \App\Usuario::whereIn('id_usuario',$rcs_todos->pluck('usuario_id_usuario'))->get();
      
    if($V->fails()){
      return ['success' => false,'usuarios' => $usuarios_todos,'error' => $this->validatorError($V)];
    }
    
    try {
      return DB::transaction(function() use ($request,&$rcs,$now,$token_recuperacion){
        $rcs_invalidar = DB::table('recuperar_contrasena')
        ->where('token_recuperacion',$token_recuperacion)
        ->whereNotIn('id_recuperar_contrasena',$rcs->pluck('id_recuperar_contrasena'))
        ->whereNull('invalidated_at')
        ->whereNull('resetted_at')
        ->update([
          'invalidated_at' => $now->format('Y-m-d H:i:s')
        ]);
        
        $interval_expired_at = new \DateInterval(
          'PT'.//period
          env('RESTAURAR_CONTRASEÑA_SEGUNDOS_MAX',360).//6 minutos
          'S'//seconds
        );
        $expired_at = $now->add($interval_expired_at);
        $expired_at_str = $expired_at->format('Y-m-d H:i:s');
        
        DB::table('recuperar_contrasena')
        ->whereIn('id_recuperar_contrasena',$rcs->pluck('id_recuperar_contrasena'))
        ->update([
          'expired_at' => $expired_at_str
        ]);
        
        return ['success' => true,'error' => null];
      });
    }
    catch(\Exception $e){
      return ['success' => false,'usuarios' => $usuarios_todos,'error' => $e->getMessage()];
    }
  }
  
  public function resetearPasswords(Request $request){
    $rcs = null;
    $now = new \DateTimeImmutable();
    $token_recuperacion = session()->get('token_recuperacion') ?? null;
    
    $V = Validator::make($request->all(),[
      'password' => 'required|string|min:8|confirmed',
      'password_confirmation' => 'required'
    ], [
      'password.required' => 'Ingresar una contraseña',
      'password_confirmation.required' => '',
      'password.min' => 'Se necesita una longitud de al menos 8 caracteres',
      'password.confirmed' => 'Tienen que coincidir las contraseñas'
    ], [])->after(function ($validator) use (&$rcs,&$token_recuperacion,$now){
      if($validator->errors()->any()) return;
      
      $rcs = DB::table('recuperar_contrasena')
      ->where('token_recuperacion',$token_recuperacion)
      ->whereNull('invalidated_at')
      ->whereNull('resetted_at')
      ->where('expired_at','>',$now->format('Y-m-d H:i:s'))
      ->get();
      
      if($rcs->count() == 0){
        return $validator->errors()->add('sesion','Token expirado o no hay sesión activa');
      }
    });
    
    if($V->fails()){
      return ['success' => false,'error' => $this->validatorError($V)];
    }
        
    try {
      DB::transaction(function() use (&$rcs,$request,$now){
        $usuarios = \App\Usuario::whereIn('id_usuario',$rcs->pluck('usuario_id_usuario'))->get();
        foreach($usuarios as $u){
          $u->password = $request->password;
          //Tengo que setear el id_usuario para guardar el reseteo de password
          //porque tiene attacheado un Observer que guarda en Log quien modifico la entidad
          //y si no hay usuario en la sesión, tira un error porque no puede ser
          //Nulo en la BD
          session()->put('id_usuario',$u->id_usuario);
          $u->save();
          session()->forget('id_usuario');
        }
        
        DB::table('recuperar_contrasena')
        ->whereIn('id_recuperar_contrasena',$rcs->pluck('id_recuperar_contrasena'))
        ->update([
          'resetted_at' => $now->format('Y-m-d H:i:s')
        ]);
        
        session()->forget('token_recuperacion');
      });
    }
    catch(\Exception $e){
      session()->forget('id_usuario');
      return ['success' => false,'error' => $e->getMessage()];
    }
        
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
    //$domain = idn_to_ascii($local_domain[1]);
    $actual_domain = null;
    
    if(preg_match_all("/[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,}$/i", $domain, $actual_domain)){
      if(count($actual_domain) == 0 || count($actual_domain[0]) == 0)
        return false;
      $actual_domain = $actual_domain[0][0];  
    }
    if($actual_domain === null || count($actual_domain) == 0 || count($actual_domain[0]) == 0) return false;
    
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
}
