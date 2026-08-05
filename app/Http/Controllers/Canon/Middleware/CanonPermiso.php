<?php

namespace App\Http\Controllers\Canon\Middleware;

use Closure;
use App\Http\Controllers\AuthenticationController;

class CanonPermiso
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
      $CPe = \App\Http\Controllers\Canon\CanonPermisoController::getInstancia();
      $AC = AuthenticationController::getInstancia();
      $id_usuario = $AC->obtenerIdUsuario();

      if(is_null($id_usuario)) return $this->errorOut($request);
      
      if($AC->usuarioTieneRol($id_usuario,'SUPERUSUARIO')){
        return $next($request);
      }
      
      if($CPe->tieneTodosPermisos($id_usuario,$permisos)){
        return $next($request);
      }

      foreach($permisos as $permiso){
        if($AC->usuarioTienePermiso($id_usuario,$permiso)){
          return $next($request);
        }
      }
      
      return $this->errorOut($request);
    }

    private function errorOut($request){
      if($request->ajax()){
        return response()->json(['mensaje' => 'No tiene los permisos necesarios para realizar dicha acción.','url' => 'canon'],
                                351,[['Content-Type','application/json']]);
      }
      return redirect('canon');
    }
}
