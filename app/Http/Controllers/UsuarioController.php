<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use App\APIToken;
use App\Usuario;
use App\Rol;
use App\Casino;
use App\Relevamiento;
use App\SecRecientes;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class UsuarioController extends Controller
{
  private static $atributos = [
    'user_name' => 'Nombre Usuario',
    'password' => 'Contraseña'
  ];

  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new UsuarioController();
      }
      return self::$instance;
  }

  private function esUnico($campo,$val,$id_exceptuado){
    return Usuario::where([[$campo,'=',$val],['id_usuario','<>',$id_exceptuado]])->whereNull('deleted_at')->count() == 0;
  }

  public function guardarUsuario(Request $request){
    $user = $this->quienSoy()['usuario'];
    $cas = [];
    foreach ($user->casinos as $c) {
      $cas[]=$c->id_casino;
    }
    Validator::make($request->all(), [
      'id_usuario' => 'nullable|integer|exists:usuario,id_usuario',
      'nombre' => 'required|max:100',
      'user_name' => 'required|max:45',
      'email' =>  'required|max:70',
      'password' => 'required_if:id_usuario,""|max:45',
      'imagen' => 'nullable|image',
      'casinos' => 'required|array',
      'casinos.*' => 'exists:casino,id_casino',
      'roles' => 'required|array',
      'roles.*' => 'exists:rol,id_rol',
    ], ['required' => 'No puede estar vacio','required_if' => 'No puede estar vacio','max' => 'Supera el limite'])
    ->after(function ($v) use ($cas){
      if($v->errors()->any()) return;
      $data = $v->getData();
      $email = $data['email'];
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $v->errors()->add('email', 'Formato de email inválido.');
      }
      //No puedo usar reglas de laravel porque tengo que verificar que sea NULL deleted_at
      if(!$this->esUnico('nombre',$data['nombre'],$data['id_usuario'])) $v->errors()->add('nombre','Tiene que ser único');
      if(!$this->esUnico('user_name',$data['user_name'],$data['id_usuario'])) $v->errors()->add('user_name','Tiene que ser único');
      if(!$this->esUnico('email',$data['email'],$data['id_usuario'])) $v->errors()->add('email','Tiene que ser único');
      
      foreach ($data['casinos'] as $c) {
        if(!in_array($c,$cas)){
          $v->errors()->add('id_casino', 'No puede acceder al casino.');
          break;
        }
      }
    })->validate();

    DB::transaction(function () use ($request,$cas){
      $usuario = null;
      if(!empty($request->id_usuario)){ 
        $usuario = Usuario::find($request->id_usuario);
      }
      else{
        $usuario = new Usuario;
        $usuario->password = $request->password; 
      }
      $usuario->nombre = $request->nombre;
      $usuario->user_name = $request->user_name;
      $usuario->email = $request->email;
      if($request->imagen != null){
        $usuario->imagen = base64_encode(file_get_contents($request->imagen->getRealPath()));
      }
      $usuario->save();
      $usuario->roles()->sync($request->roles);
      $usuario->casinos()->detach($cas);//Le saco todos lo que tiene acceso el usuario
      $usuario->casinos()->syncWithoutDetaching($request->casinos);//Les agrego los que mando
    });
    
    return 1;
  }

  public function obtenerControladores($id_casino, $id_usuario){
    $controladores = DB::table('usuario')
                         ->select('usuario.*')
                         ->join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                         ->join('usuario_tiene_casino','usuario.id_usuario','=','usuario_tiene_casino.id_usuario')
                         ->where('usuario_tiene_casino.id_casino','=',$id_casino)
                         ->whereIn('usuario_tiene_rol.id_rol',[2,4])
                         ->whereNotIn('usuario.id_usuario',[$id_usuario])
                         ->whereNotNull('usuario.deleted_at')
                         ->distinct('usuario.id_usuario')
                         ->get();
    return $controladores;
  }

  public function obtenerFiscalizadores($id_casino, $id_usuario){
    $fiscalizadores = DB::table('usuario')
                         ->select('usuario.*')
                         ->join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                         ->join('usuario_tiene_casino','usuario.id_usuario','=','usuario_tiene_casino.id_usuario')
                         ->where('usuario_tiene_casino.id_casino','=',$id_casino)
                         ->whereIn('usuario_tiene_rol.id_rol',[3])
                         ->whereNull('usuario.deleted_at')
                        // ->whereNotIn('usuario.id_usuario',[$id_usuario])
                         ->distinct('usuario.id_usuario')
                         ->get();
    return $fiscalizadores;
  }

  public function modificarImagen(Request $request){
    Validator::make($request->all(),[
        'id_usuario' => 'required|exists:usuario,id_usuario',
        // 'imagen' => ['nullable','image'],
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);

    $imagen = $request->imagen;
    if(!empty($imagen)){
      list(, $imagen) = explode(';', $imagen);
      list(, $imagen) = explode(',', $imagen);
        $usuario->imagen = $imagen;
    }
    $usuario->save();

    return ['imagen' => $usuario->imagen];
  }

  public function modificarPassword(Request $request){
    Validator::make($request->all(),[
        'id_usuario' => 'required|exists:usuario,id_usuario',
        'password_actual' => 'required|max:45',
        'password_nuevo' => 'required|max:45|confirmed',
        'password_nuevo_confirmation' => 'required|max:45'
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
      $usuario = Usuario::find(session('id_usuario'));
      if($usuario->password != $validator->getData()['password_actual']){
        $validator->errors()->add('password_incorrecta','La contraseña actual no coincide con la del usuario.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->password = $request->password_nuevo;
    $usuario->save();

    return $usuario;
  }

  public function modificarDatos(Request $request){
    Validator::make($request->all(),[
      'id_usuario' => 'required|exists:usuario,id_usuario',
      'user_name' => ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      'email' =>  ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')]
    ], array(), self::$atributos)->after(function($validator){
      if($validator->getData()['id_usuario'] != session('id_usuario')){
        $validator->errors()->add('usuario_incorrecto','El usuario enviado no coincide con el de la sesion.');
      }
    })->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->user_name = $request->user_name;
    $usuario->email = $request->email;
    $usuario->save();

    return $usuario;
  }

  public function eliminarUsuario($id_usuario){
    DB::transaction(function () use ($id_usuario){
      $usuario = Usuario::find($id_usuario);
      $usuario->roles()->detach();
      $usuario->casinos()->detach();
      $usuario->delete();
    });
    return ['codigo' => 200];
  }

  public function buscarUsuariosPorNombre($nombre){

    if(empty($nombre) ){
    $nombre = '%';
    }
    else{
      $nombre = '%'.$nombre.'%';
    }
    $resultado = Usuario::where([['nombre','like',$nombre]])->get();
    return ['usuarios' => $resultado];
  }

  public function buscarUsuariosPorNombreYRelevamiento($nombre, $id_relevamiento){
    $relevamiento = Relevamiento::find($id_relevamiento);
    $id_casino = $relevamiento->sector->id_casino;

    if(empty($request->nombre) ){
    $nombre = '%';
    }
    else{
      $nombre = '%'.$request->nombre.'%';
    }

    $resultado = Usuario::join('usuario_tiene_casino' , 'usuario_tiene_casino.id_usuario' ,'=' , 'usuario.id_usuario')->where([['nombre','like',$nombre], ['usuario_tiene_casino.id_casino' , '=' , $id_casino]])->get();

    return ['usuarios' => $resultado];
  }

  public function buscarUsuariosPorNombreYCasino($id_casino,$nombre){
    $nombre = (empty($nombre)) ? '%' : '%'.$nombre.'%';

    $resultado = Usuario::join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
                        ->where([['nombre','like',$nombre],['usuario_tiene_casino.id_casino','=',$id_casino]])
                        ->get();

    return ['usuarios' => $resultado];
  }

  public function buscarUsuarios(Request $request){
    $reglas = [];
    if(!empty($request->nombre)) $reglas[] = ['usuario.nombre','like','%'.$request->nombre.'%'];
    if(!empty($request->usuario)) $reglas[] = ['usuario.user_name','like','%'.$request->usuario.'%'];
    if(!empty($request->email)) $reglas[] = ['usuario.email','like','%'.$request->email.'%'];
    if(!empty($request->id_casino)) $reglas[] = ['usuario_tiene_casino.id_casino','=',$request->id_casino];
    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();
    foreach ($user->casinos as $c) {
      $cas[]=$c->id_casino;
    }

    $resultado=DB::table('usuario')
    ->select('usuario.*')
    ->join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
    ->where($reglas)
    ->whereIn('usuario_tiene_casino.id_casino',$cas)
    ->whereNull('usuario.deleted_at')
    ->distinct('id_usuario')
    ->orderBy('user_name','asc')
    ->get();

    return ['usuarios' => $resultado];

  }

  public function buscarTodo(){
    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $casinos = [];
    $roles = [];
    if($user->es_superusuario){
      $casinos = Casino::all();
      $roles = Rol::all();
    }
    else{
      $casinos = $user->casinos;
      $roles = Rol::whereNotIn('id_rol',[1,5,6])->get();
    }

    $this->agregarSeccionReciente('Usuarios' ,'usuarios');
    return view('seccionUsuarios',  ['roles' => $roles , 'casinos' => $casinos]);
  }

  //sin la session iniciada usa esta funcion ----
  public function buscarUsuario($id_usuario){
    $usuario = Usuario::find($id_usuario);
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'casinos' => $usuario->casinos];
  }

  public function configUsuario(){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $this->agregarSeccionReciente('Configuración de Cuenta', 'configCuenta');
    return view('seccionConfigCuenta')->with('usuario',$usuario);
  }

  public function leerImagenUsuario(){
    $file = Usuario::find(session('id_usuario'));
    $data = $file->imagen;

    return Response::make(base64_decode($data), 200, [ 'Content-Type' => 'image/jpeg',
                                                      'Content-Disposition' => 'inline; filename="lalaaa.jpeg"']);
  }

  public function tieneImagen(){
    $file = Usuario::find(session('id_usuario'));
    $data = $file->imagen;

    return $data != null;
  }

  public function buscarCasinoDelUsuario($id_usuario){

    $casinos = array();
    $auxiliar=DB::table('casino')
                  ->join('usuario_tiene_casino' , 'casino.id_casino', '=', 'usuario_tiene_casino.id_casino')
                  ->join('usuario', 'usuario.id_usuario','=', 'usuario_tiene_casino.id_usuario')
                  ->where( 'usuario.id_usuario' ,'=', $id_usuario)
                  ->get()
                  ->toArray();
    $casinos=array_merge($casinos,$auxiliar);
    $casino= reset($casinos);

    return $casino->id_casino;
  }

  public function buscarCasinosDelUsuario($id_usuario){

    $casinos =DB::table('casino')
                  ->select('usuario.*','casino.id_casino','casino.nombre as nombre_casino')
                  ->join('usuario_tiene_casino' , 'casino.id_casino', '=', 'usuario_tiene_casino.id_casino')
                  ->join('usuario', 'usuario.id_usuario','=', 'usuario_tiene_casino.id_usuario')
                  ->where( 'usuario.id_usuario' ,'=', $id_usuario)
                  ->get();
    return $casinos;
  }

  public function reestablecerContraseña(Request $request){
    $validator=Validator::make($request->all(), [
      'id_usuario' => ['required'  , 'exists:usuario,id_usuario'] ,
     ])->after(function ($validator){

      });
    $validator->validate();

    $usuario = Usuario::find($request->id_usuario);
    $usuario->password = $usuario->dni;
    $usuario->save();

    return ['codigo' => 200];
  }

  public function usuarioEsControlador($usuario){
    $esControlador = 0;
    foreach ($usuario->roles as $rol) {
      if($rol->id_rol == 1 || $rol->id_rol == 2 || $rol->id_rol == 4){
        $esControlador=1;
        break;
      }
    }
    return $esControlador;

  }

  public function agregarSeccionReciente($seccion , $ruta){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $user = Usuario::find($usuario['usuario']->id_usuario);

    //si no tiene creadas las secciones_recientes
    if($user->secciones_recientes->count() == 0){
      for($i=1;$i<=4;$i++){
        $sec1 = new SecRecientes;
        $sec1->orden = $i;
        $sec1->seccion = $seccion;
        $sec1->ruta = $ruta;
        $sec1->usuario()->associate($user->id_usuario);
        $sec1->save();
        $seccion = null;
        $ruta = null;
      }
    }else{
      $secciones = $user->secciones_recientes;
      $secciones_nombres = [];
      foreach($secciones as $s) $secciones_nombres[] = $s->seccion;
      if(!in_array($seccion,$secciones_nombres)){//Evita repetidos
        for($i=3;$i>=1;$i--){
          $secciones[$i]->seccion = $secciones[$i-1]->seccion;
          $secciones[$i]->ruta    = $secciones[$i-1]->ruta;
          $secciones[$i]->save();
        }
        $secciones[0]->seccion = $seccion;
        $secciones[0]->ruta    = $ruta;
        $secciones[0]->save();
      }
    }
  }

  public function obtenerUsuariosRol($id_casino, $id_rol){
    $rta = DB::table('usuario')
                ->join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                ->join('usuario_tiene_casino','usuario.id_usuario','=','usuario_tiene_casino.id_usuario')
                ->whereIn('usuario_tiene_rol.id_rol',$id_rol)
                ->where('usuario_tiene_casino.id_casino','=', $id_casino)
                ->whereNull('usuario.deleted_at')
                ->get();
    return $rta;
  }

  public function quienSoy($token = null){
    $id_usuario = session('id_usuario');
    if(!is_null($token)){
      $api_token = APIToken::where('ip',request()->ip())->where('token',$token)->get()->first();
      if(is_null($api_token)) return ['usuario' => null];
      $id_usuario = $api_token->id_usuario;
    }
    $usuario = $this->buscarUsuario($id_usuario)['usuario'];
    return ['usuario' => $usuario];
  }

  public function buscarFiscaNombreCasino($id_casino, $nombre){
    $nombre = (empty($nombre)) ? '%' : '%'.$nombre.'%';

    $resultado = Usuario::join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                        ->join('rol','rol.id_rol','=','usuario_tiene_rol.id_rol')
                        ->join('usuario_tiene_casino',
                              'usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
                        ->where([['nombre','like',$nombre],
                            ['usuario_tiene_casino.id_casino','=',$id_casino]])
                        ->where('rol.descripcion','=','FISCALIZADOR')
                        ->get();

    return ['usuarios' => $resultado];
  }

  public function chequearRolFiscalizador(){
    $usuario = $this->buscarUsuario(session('id_usuario'))['usuario'];

    $resultado = Usuario::join('usuario_tiene_rol','usuario.id_usuario','=','usuario_tiene_rol.id_usuario')
                        ->join('rol','rol.id_rol','=','usuario_tiene_rol.id_rol')
                        ->where('rol.descripcion','=','FISCALIZADOR')
                        ->where('rol.descripcion','<>','SUPERUSUARIO')
                        ->where('usuario.id_usuario','=',$usuario->id_usuario)
                        ->get();
    if(count($resultado) == 1){
      return 1;
    }else{
      return 0;
    }
  }
  public function getCasinos(){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $casinos=array();
    foreach($usuario['usuario']->casinos as $casino){
      $casinos[]=$casino->id_casino;
    }
    return $casinos;
  }

  public function obtenerUsuario(Request $request){
    if($request->session()->has("id_usuario")){
      $id_usuario = $request->session()->get("id_usuario");
      $usuario = Usuario::find($id_usuario);
      return $usuario;
    }
    return null;
  }

  public function usuarioTieneCasinoCorrespondiente ($id_usuario, $id_casino) {
    $casinos = DB::table('usuario_tiene_casino')    ->select('id_casino')
                                                    ->where('usuario_tiene_casino.id_usuario','=',$id_usuario)
                                                    ->get();


    $casinos_array = $casinos->toArray();

    foreach ($casinos_array as $casino) {
        if ($casino->id_casino == $id_casino) {
          return true;
        }
    }

    return false;
  }

  public function usuarioEsFiscalizador ($id_usuario) {
    $roles = DB::table('usuario_tiene_rol') ->select('id_rol')
                                            ->where('usuario_tiene_rol.id_usuario','=',$id_usuario)
                                            ->get();


    $roles_array = $roles->toArray();

    foreach ($roles_array as $rol) {
        if ($rol->id_rol == 3) {
          return true;
        }
    }

    return false;
  }

  public function obtenerOpcionesGeneralidades () {
    $climas = DB::table('clima')->get();
    $temperaturas = DB::table('temperatura')->get();
    $eventos = DB::table('evento_control_ambiental')->get();

    $generalidades = array(
      'climas' => $climas,
      'temperaturas' => $temperaturas,
      'eventos' => $eventos
    );
    return $generalidades;
  }
}
