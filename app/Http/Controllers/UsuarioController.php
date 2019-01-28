<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use App\Usuario;
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

  public function guardarUsuario(Request $request){
    /*
    validacion
    */
    $validator=Validator::make($request->all(), [
      'usuario' => ['required' , 'max:45' , 'unique:usuario,user_name'] ,
      'email' => ['required' , 'max:45' , 'unique:usuario,email'],
      'contraseña' => ['required', 'max:45'],
      'nombre' => ['required'],
      'imagen' => ['nullable', 'image'],
     ])->after(function ($validator){
                 //validar que descripcion no exista
                $email =$validator->getData()['email'];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                  $validator->errors()->add('email', 'Formato de email inválido.');
                }
                $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
                $cas = array();
                foreach ($user->casinos as $cass) {
                  $cas[]=$cass->id_casino;
                }
                $lotiene = false;
                foreach ($cas as $c) {
                  if($c == $validator->getData()['id_casino']){
                    $lotiene = true;
                  }
                }
                if(!$lotiene){
                  $validator->errors()->add('id_casino', 'FAIL.');
                }
      });

     $validator->validate();
    /*
    captura de datos
    */
    $nombre=$request->nombre;
    $pass=$request->contraseña;
    $username=$request->usuario;
    $email=$request->email;
    $roles=$request->roles;
    //falta validar que sea de al menos un casino
    $casinos = $request->casinos;
    /*
    crea modelo
    */
    $usuario= new Usuario;
    $usuario->nombre=$nombre;
    $usuario->user_name=$username;
    $usuario->password=$pass;
    $usuario->email=$email;
    if($request->imagen != null){
      $usuario->imagen = base64_encode(file_get_contents($request->imagen->getRealPath()));
    }
    $usuario->save();

    if(!empty($roles)){
    $usuario->roles()->sync($roles);
    }
    if(!empty($casinos)){
      $usuario->casinos()->sync($casinos);
    }
    $usuario->save();
    return ['usuario' => $usuario];

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
                         ->whereNotIn('usuario.id_usuario',[$id_usuario])
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

  public function modificarUsuario(Request $request){
    $messages = [
    'user_name.required' => 'El campo Nombre de usuario no puede estar vacio',
    ];
    $this->validate($request, [
      'nombre' => ['required','max:45', Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      'user_name' => ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')],
      // 'password' => ['required', 'max:45'],

      'email' =>  ['required', 'max:45' , Rule::unique('usuario')->ignore( $request->id_usuario,'id_usuario')]
    ], $messages);


    $usuario=Usuario::find($request->id_usuario);
    $usuario->nombre = $request->nombre;
    $usuario->user_name = $request->user_name;
    $usuario->email = $request->email;
    // $usuario->password = $request->password;
    $usuario->save();

    $roles=$request->roles;
    $casinos=$request->casinos;
        if(!empty($roles)){
          $usuario->roles()->sync($roles);
        }else{
          $usuario->roles()->detach();
        }
        if(!empty($casinos)){
          $usuario->casinos()->sync($casinos);
        }else {
          $usuario->casinos()->detach();
        }

    return ['usuario' => $usuario];
  }

  public function eliminarUsuario(Request $request){
    $usuario= Usuario::find($request->id);
    $usuario->roles()->detach();
    $usuario->casinos()->detach();
    $usuario->delete();
    return ['usuario' => $usuario];
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
    $nombre = (empty($request->nombre)) ? '%' : '%'.$request->nombre.'%';
    $usuario = (empty($request->usuario)) ? '%' : '%'.$request->usuario.'%';
    $email = (empty($request->email)) ? '%' : '%'.$request->email.'%';

    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $resultado=DB::table('usuario')
                    ->select('usuario.*')
                    ->join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
                    ->join('casino','casino.id_casino','=','usuario_tiene_casino.id_casino')
                    ->where([['nombre','like',$nombre],['user_name','like',$usuario],['email','like',$email]])
                    ->whereIn('casino.id_casino',$cas)
                    ->whereNull('usuario.deleted_at')
                    ->distinct('id_usuario')
                    ->orderBy('user_name','asc')
                    ->get();

    return ['usuarios' => $resultado];

  }

  public function buscarTodo(){
    $user = $this->buscarUsuario(session('id_usuario'))['usuario'];
    $cas = array();
    foreach ($user->casinos as $cass) {
      $cas[]=$cass->id_casino;
    }

    $resultados=DB::table('usuario')
                    ->select('usuario.*')
                    ->join('usuario_tiene_casino','usuario_tiene_casino.id_usuario','=','usuario.id_usuario')
                    ->join('casino','casino.id_casino','=','usuario_tiene_casino.id_casino')
                    ->whereIn('casino.id_casino',$cas)
                    ->distinct('id_usuario')
                    ->whereNull('usuario.deleted_at')
                    ->orderBy('user_name','asc')
                    ->get();
    $rolController= RolController::getInstancia();
    $resultado = DB::table('usuario_tiene_rol')
                    ->whereIn('id_rol',[2])
                    ->where('id_usuario','=',$user->id_usuario)
                    ->get();
    if(count($resultado) > 0){
      $casinos=Casino::whereIn('id_casino',$cas)->get();
    }else{
      $casinos=Casino::all();
    }

    $roles=$rolController->getAll();
    $this->agregarSeccionReciente('Usuarios' ,'usuarios');
    return view('seccionUsuarios',  ['usuarios' => $resultados , 'roles' => $roles , 'casinos' => $casinos]);
  }

  //sin la session iniciada usa esta funcion ----
  public function buscarUsuario($id){
    $usuario=Usuario::find($id);
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'casinos' => $usuario->casinos];
  }
  //en la seccion usuarios (ajaxUsuarios.js)
  public function buscarUsuarioSecUsuarios($id){
    $usuario=Usuario::find($id);
    $user = session('id_usuario');
    $esSuper = DB::table('usuario_tiene_rol')->where([['id_rol','=',1],['id_usuario','=',$user]])->get();
    $bool = 0;
    if(count($esSuper)>0){
      $bool = 1;
    }
    return ['usuario' => $usuario, 'roles' => $usuario->roles , 'casinos' => $usuario->casinos,
            'superusuario' => $bool
            ];
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

  public function buscarControladoresCasino($id_casino){
    $usuarios = DB::table('usuario')
                  ->select('usuario.*')
                  ->join('usuario_tiene_rol','usuario_tiene_rol.id_usuario','=','usuario.id_usuario')
                  ->whereIn('usuario_tiene_rol.id_rol',[2,4])//controlador y administrador
                  ->get();
                  return $usuarios;
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

  public function getSecRecientes(){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $user = Usuario::find($usuario->id_usuario);
    //si no tiene creadas las secciones_recientes
    if(!isset($user->secciones_recientes)){
      $array = array();
      return $array;
    }else{
      $sec1=null;
      $sec2=null;
      $sec3=null;
      foreach ($user->secciones_recientes as $sec) {
        switch ($sec->orden) {
          case 1:
            $sec1=$sec->seccion;
            break;
          case 2:
            $sec2=$sec->seccion;
            break;
          case 3:
            $sec3=$sec->seccion;
            break;
          default:
            # code...
            break;
        }
      }
      return ['ultima'=> $sec1, 'anteultima'=> $sec2, 'penultima' => $sec3];
    }
  }

  public function agregarSeccionReciente($seccion , $ruta){
    $usuario = $this->buscarUsuario(session('id_usuario'));
    $user = Usuario::find($usuario['usuario']->id_usuario);

    //si no tiene creadas las secciones_recientes
    if($user->secciones_recientes->count() == 0){
      $sec1 = new SecRecientes;
      $sec1->orden = 1;
      $sec1->seccion = $seccion;
      $sec1->ruta = $ruta;
      $sec1->usuario()->associate($user->id_usuario);
      $sec1->save();

      $sec2 = new SecRecientes;
      $sec2->orden = 2;
      $sec2->seccion = null;
      $sec2->ruta = null;
      $sec2->usuario()->associate($user->id_usuario);
      $sec2->save();

      $sec3 = new SecRecientes;
      $sec3->orden = 3;
      $sec3->seccion = null;
      $sec3->ruta = null;
      $sec3->usuario()->associate($user->id_usuario);
      $sec3->save();

      $sec4 = new SecRecientes;
      $sec4->orden = 3;
      $sec4->seccion = null;
      $sec4->ruta = null;
      $sec4->usuario()->associate($user->id_usuario);
      $sec4->save();


    }else{
      $secciones = $user->secciones_recientes;
      if($seccion != $secciones[1]->seccion && $seccion != $secciones[0]->seccion && $seccion != $secciones[2]->seccion && $seccion != $secciones[3]->seccion){//evito repetidos

        $secciones[3]->seccion = $secciones[2]->seccion;
        $secciones[3]->ruta = $secciones[2]->ruta;
        $secciones[3]->save();
        $secciones[2]->seccion = $secciones[1]->seccion;
        $secciones[2]->ruta = $secciones[1]->ruta;
        $secciones[2]->save();
        $secciones[1]->seccion = $secciones[0]->seccion;
        $secciones[1]->ruta = $secciones[0]->ruta;
        $secciones[1]->save();
        $secciones[0]->seccion = $seccion;
        $secciones[0]->ruta = $ruta;
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

  public function quienSoy(){
    $usuario = $this->buscarUsuario(session('id_usuario'))['usuario'];
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

}
