<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;

class CheckRole
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
      $AC = AuthenticationController::getInstancia();
      $id_usuario = $AC->obtenerIdUsuario();

      if(is_null($id_usuario)) return $this->errorOut($request);

      if(empty($roles)) return $next($request);
      
      foreach($roles as $rol){
        if(!$AC->usuarioTieneRol($id_usuario,$rol)){
          return $this->errorOut($request);
        }
      }
      
      return $next($request);
    }

    private function errorOut($request){
      $url_to_redirect = 'inicio';
      if($request->ajax()){
        return response()->json(['mensaje' => 'No tiene los roles necesarios para realizar dicha acción.','url' => $url_to_redirect],
                                351,[['Content-Type','application/json']]);
      }
      return redirect($url_to_redirect);
    }
}
