<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'AE','middleware' => 'check_API_token'],function(){
  Route::get('/',function(){//Para probar el acceso
    return 1;
  });
  Route::get('fechas/{DNI}','Autoexclusion\APIAEController@fechas');
  Route::get('finalizar/{DNI}','Autoexclusion\APIAEController@finalizar');
  Route::post('agregar','Autoexclusion\APIAEController@agregar');
  Route::get('constancia/reingreso/{DNI}','Autoexclusion\APIAEController@reingreso');
  Route::get('ultima/{DNI}','Autoexclusion\APIAEController@ultimos_datos');
});

function completarUsuarioParaRetorno(&$u){
  $roles = $u->roles;
  
  $permisos = collect([]);
  foreach($roles as $r){
    $permisos = $permisos->merge($r->permisos->pluck('descripcion'));
  }
  
  $imagen = $u->imagen;
  $u = $u->toArray();
  $u['roles'] = $roles->pluck('descripcion')->sort()->unique()->values();
  $u['permisos'] = $permisos->sort()->unique()->values();
  $u['imagen'] = $imagen;
  
  return $u;
}

use App\Http\Controllers\AuthenticationController;
use App\Usuario;

class VerificarPermisoAccederUsuario {
  public function handle($request,$next){
    $api_token = AuthenticationController::getInstancia()->obtenerAPIToken();
      
    if(is_null($api_token) || !($api_token->metadata['puede_obtener_usuario'] ?? true)){
      return response()->json(['privilegios' => 'No puede realizar la acción'],422);
    }
      
    return $next($request);
  }
};

Route::group(['middleware' => ['check_API_token',VerificarPermisoAccederUsuario::class]],function(){
	Route::post('obtenerUsuarioPorID',function(Request $request){
    $validator = Validator::make($request->all(), [
      'id_usuario' => 'required|exists:usuario,id_usuario,deleted_at,NULL',
	  ], [
      'required' => 'El valor es requerido',
      'exists' => 'No existe ese valor',
	  ], []);
    
    if($validator->errors()->any()) return response()->json($validator->errors(),422);
    
	  $u = Usuario::find($request->id_usuario);
	  if(is_null($u)) return response()->json(['error' => ['Error al obtener el usuario']],422);
	  
	  return completarUsuarioParaRetorno($u);
	});
	
	Route::post('obtenerUsuarioPorUsernamePassword',function(Request $request){
	  $u = null;
	  $validator = Validator::make($request->all(), [
      'user_name' => 'required|string|exists:usuario,user_name,deleted_at,NULL',
      'password' => 'required|string',
	  ], [
      'required' => 'El valor es requerido',
      'exists' => 'No existe ese valor',
	  ], [])
	  ->after(function ($validator) use (&$u){
      if($validator->errors()->any()) return;
		
      $data = $validator->getData();
      $u = Usuario::where('user_name','=',$data['user_name'])->where('password','=',$data['password'])
      ->first();
        
      if(is_null($u)){
        return $validator->errors()->add('password','Contraseña incorrecta');
      }
	  });
	  
	  if($validator->errors()->any()) return response()->json($validator->errors(),422);
		  
	  return completarUsuarioParaRetorno($u);
	});
});

