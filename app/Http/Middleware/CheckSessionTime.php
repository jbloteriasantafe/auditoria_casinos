<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\AuthenticationController;

class CheckSessionTime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $tiempo_sesion = 14400;//4 horas en segundos

    public function handle($request, Closure $next)
    {
      $ahora = time();
      $ultima_actividad = $request->session()->has('last_activity_time') ? $request->session()->get('last_activity_time') : null;
      $ultima_actividad = $ultima_actividad === null? ($ahora+$this->tiempo_sesion+1) : $ultima_actividad->getTimestamp();
      $diferencia = $ahora-$ultima_actividad;
      if($diferencia > $this->tiempo_sesion && $request->path() != 'login'){
        AuthenticationController::getInstancia()->logout($request);
        $request->session()->put('redirect',$request->path());
        if($request->ajax()){
          return response()->json(['mensaje' => 'Su sesión ha expirado, ingrese de nuevo al sistema','url' => '/'],351,[['Content-Type', 'application/json']]);
        }
        return redirect('/');
      }
      // se actualiza el tiempo de la ultima actividad
      $ahora_datetime = new \DateTime();
      $ahora_datetime->setTimestamp($ahora);
      $request->session()->put('last_activity_time',$ahora_datetime);
      return $next($request);
    }
}
