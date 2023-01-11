<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthenticationController;
use App\APIToken;

class CheckAPIToken
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
      $token = $request->header('API-Token');
      $ip = $request->ip();
      $en_bd = APIToken::where('ip',$ip)->where('token',$token)->count();
      if($en_bd == 0){
        return response()->json(
          ['error' => 'Token o IP invalida.'],
          422,
          [['Content-Type', 'application/json']]
        );
      }
      return $next($request);
    }
}
