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
      $url_to_redirect = 'inicio';
      $id_usuario = $request->session()->has('id_usuario') ? $request->session()->get('id_usuario') : null;
      if($id_usuario != null){
        if(!empty($permisos)){
          foreach($permisos as $permiso){
            if(!AuthenticationController::getInstancia()->usuarioTienePermiso($id_usuario,$permiso)){
                if($request->ajax()){
                  return response()->json(['mensaje' => 'No tiene los permisos encesarios para realizar dicha acción.','url' => $url_to_redirect],351,[['Content-Type','application/json']]);
                }
                else{
                  return redirect($url_to_redirect);
                }
            }
          }
          return $next($request);
        }
        else{
          return $next($request);
        }
      }
      else{
        if($request->ajax()){
          return response()->json(['mensaje' => 'No tiene los permisos necesarios para realizar dicha acción.','url' => $url_to_redirect],351,[['Content-Type','application/json']]);
        }
        else{
          return redirect($url_to_redirect);
        }
      }
    }
}
