<?php

namespace App\Http\Controllers;

use App\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;
use Validator;

class CotizacionController extends Controller
{
    private static $instance = null;
    public static function getInstancia() {
        if (!isset(self::$instance)) {
          self::$instance = new CotizacionController();
        }
        return self::$instance;
      }


    public function obtenerCotizaciones($fecha){
        $fecha2 = date("Y-m", strtotime("$fecha - 2 month"));

        return DB::table('cotizacion')
                    ->where('fecha','>=',$fecha2 . '%')
                    ->get();


        //TODO limitar la busqueda con fecha final
    }

    public function guardarCotizacion(request $cotizacion){
        $validator = Validator::make($cotizacion->all(), [
            'fecha' => 'required|date',
            'valor' => 'required|numeric|min:25',
        ])->validate();

        $cot=Cotizacion::Find($cotizacion['fecha']);
        if($cot){
            $cot->valor=$cotizacion['valor'];
            $cot->save();
        }else{
            $nuevaCotizacion= new Cotizacion;
            $nuevaCotizacion->fecha=$cotizacion['fecha'];
            $nuevaCotizacion->valor=$cotizacion['valor'];
            $nuevaCotizacion->save();
        }




        return "OK";

    }
    
    public function dolarOficial(){
      return [];//@HACK: override, los valores que retorna la API no son los mismos que da la pagina (???)
      $CC = \App\Http\Controllers\CacheController::getInstancia();
      $hoy = date('Y-m-d');
      $CC->invalidar('dolar_oficial','',$hoy.' 00:00:00');
      $cache = $CC->buscar('dolar_oficial','',$hoy.' 23:59:59');
      if($cache->count() > 0){
        return json_decode($cache->first()->data,true);
      }
      set_time_limit(30);
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://api.estadisticasbcra.com/usd_of');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $proxy_url = env('HTTP_PROXY_URL',null);
      $proxy_port = env('HTTP_PROXY_PORT',null);
      if(!is_null($proxy_url) && !is_null($proxy_port)){
        curl_setopt($ch,CURLOPT_PROXY,$proxy_url);
        curl_setopt($ch,CURLOPT_PROXYPORT,$proxy_port);
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: '.env('API_KEY_BCRA','')
      ]);
      $result = curl_exec($ch);
      $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);  
      if ($code != 201 && $code != 200) return response()->json($result,422);
      $CC->agregar('dolar_oficial','',$result);
      return json_decode($result,true);
    }
}
