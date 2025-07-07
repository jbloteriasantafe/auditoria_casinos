<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next, ...$permisos)
    {
      $AC = AuthenticationController::getInstancia();
      $id_usuario = $AC->obtenerIdUsuario();

      if(is_null($id_usuario)) return $this->errorOut($request);

      if(empty($permisos)) return $next($request);

      foreach($permisos as $permiso){
        if(!$AC->usuarioTienePermiso($id_usuario,$permiso)){
          return $this->errorOut($request);
        }
      }
      return $next($request);
    }

    private function errorOut($request){
      $url_to_redirect = 'inicio';
      if($request->ajax()){
        return response()->json(['mensaje' => 'No tiene los permisos necesarios para realizar dicha acción.','url' => $url_to_redirect],
                                351,[['Content-Type','application/json']]);
      }
      return redirect($url_to_redirect);
    }
}
