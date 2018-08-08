<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;

class CheckUserToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
      $url = ($request->path() != '/') ? explode('/',$request->path())[0] : '/';
      $id_usuario = $request->session()->has('id_usuario') ? $request->session()->get('id_usuario') : null;
      $token = $request->session()->has('token') ? $request->session()->get('token') : null;
      if($url == 'login'){// no hace falta verificar el token de inicio de sesion
        return $next($request);
      }
      else{
        if($id_usuario != null && $token != null && AuthenticationController::getInstancia()->verificarToken($id_usuario,$token)){
            return $next($request);
          }
          else{
            $request->session()->flush();
            if($request->ajax()){
              $request->session()->put('redirect_to',$url);
              return response()->json(['mensaje' => 'Debe logearse en el sistema.','url' => 'login'],351,[['Content-Type', 'application/json']]);
            }
            else{
              $request->session()->put('redirect_to',$url);
              return redirect('login');
            }
          }
        }
    }

}
