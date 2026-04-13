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
      return DB::table('cotizacion')->where('fecha','>=',$fecha2 . '%')->get();
    }

    public function guardarCotizacion(request $cotizacion){
      $validator = Validator::make($cotizacion->all(), [
        'fecha' => 'required|date',
        'valor' => 'required|numeric|min:25',
      ])->validate();

      $cot = Cotizacion::Find($cotizacion['fecha']);
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
    
    public function cotizacionesBNA(Request $request){
      $validator = Validator::make($request->all(), [
        'año_mes' => ['required','regex:/^\d{4}\-((0\d)|(1[0-2]))$/'],
      ],[
        'required' => 'El valor es requerido',
        'año_mes.regex' => 'Tiene que tener formato YYYY-MM'
      ],[])->validate();
                  
      $CC = \App\Http\Controllers\CacheController::getInstancia();
      
      $fechaDesde = $request->año_mes.'-01';
      $fechaHasta = date("Y-m-t",strtotime($fechaDesde));
      
      $CACHE_CODIGO = 'cotizacionesBNA';
      $CACHE_SUBCODIGO = $request->año_mes;
      //Dentro de los ultimos 30 minutos
      $cache = $CC->buscarUltimoDentroDeSegundos($CACHE_CODIGO,$CACHE_SUBCODIGO,60*30);
      if(!is_null($cache)){
        return json_decode($cache->data,true);
      }
      
      $ISO_a_barras = function($f){
        return implode('/',array_map(function($v){
          return str_pad(substr($v,strlen($v)-2),2,'0',STR_PAD_LEFT);
        },array_reverse(explode('-',$f))));
      };
      
      $responses = [];
      foreach(['dolar' => 'filtroDolarDescarga=1','euro' => 'filtroEuroDescarga=1'] as $moneda => $param_moneda){
        $params = http_build_query([
          'fechaDesde' => $ISO_a_barras($fechaDesde),
          'fechaHasta' => $ISO_a_barras($fechaHasta)
        ]);
        
        set_time_limit(5);
        $ch = curl_init();
        $url = "https://www.bna.com.ar/Cotizador/DescargarPorFecha";
        $url .= '?'.$params.'&'.$param_moneda;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $proxy_url  = env('HTTP_PROXY_URL',null);
        $proxy_port = env('HTTP_PROXY_PORT',null);
        if($proxy_url === null || $proxy_port === null){
          curl_setopt($ch, CURLOPT_PROXY, null);
        }
        else{
          curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
          curl_setopt($ch, CURLOPT_PROXYPORT, $proxy_port);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'Content-Type: application/x-www-form-urlencoded'
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if(curl_errno($ch)){
          $responses[$moneda] = [500,['CURL_ERROR' => [curl_error($ch)]]];
          curl_close($ch);
          continue;
        }
        else if($httpCode != 200){
          $responses[$moneda] = [$httpCode,['RESPONSE_ERROR' => [$httpCode,$response]]];
          curl_close($ch);
          continue;
        }
        curl_close($ch);
        $response = str_replace("\r\n","\n",$response);
        $body = [];
        $header = [];
        $max_cols = 0;
        foreach(explode("\n",$response) as $lidx => $line){
          $line_arr = str_getcsv($line,';');
          if(empty($line)) continue;
          if(empty($header)){
            $header = $line_arr;
          }
          else{
            $body[] = $line_arr;
          }
          $max_cols = max(count($line_arr),$max_cols);
        }
        
        $add_cols = max(0,$max_cols-count($header));
        if($add_cols){//Agrego columnas si no es homogeneo el csv
          for($i=0;$i<$add_cols;$i++){
            $headers[] = '';
          }
          foreach($body as $line){
            for($i=0;$i<$add_cols;$i++){
              $line[] = '';
            }  
          }
        }
        
        $a_eliminar = [];
        
        foreach($header as $hidx => $h){
          $columna_vacia = empty($h);
          if(!$columna_vacia) continue;
          foreach($body as $line){
            $columna_vacia = empty($line[$hidx]);
            if(!$columna_vacia) break;
          }
          //Toda la columna vacia
          $a_eliminar[] = $hidx;
        }
        
        foreach($a_eliminar as &$hidx){
          unset($header[$hidx]);
          foreach($body as $lidx => $line){
            unset($line[$hidx]);
            $body[$lidx] = $line;
          }
        }
        
        $header = array_values($header);
        foreach($body as $lidx => $line){
          $body[$lidx] = array_values($line);
        }
        
        if($header != ['Fecha cotizacion','Compra','Venta']){
          $responses[$moneda] = [500,['CODE_ERROR' => ['Header inesperado',implode(',',$header)]]];
          continue;
        }
        
        $responses[$moneda] = [200,compact('header','body')];
      }
      
      $con_error = array_filter($responses,function($r){
        return $r[0] != 200;
      });
      if(count($con_error)){
        return response()->json($con_error,$con_error[array_keys($con_error)[0]][0]);
      }
      
      $merged = [];
      foreach($responses as $moneda => $code_headerbody){
        $headerbody = $code_headerbody[1];
        $header_idx = array_flip($headerbody['header']);
        foreach($headerbody['body'] as $row){
          $fecha_cotizacion = $row[$header_idx['Fecha cotizacion']];
          $yyyymmdd = array_reverse(explode('/',$fecha_cotizacion));
          $yyyymmdd[0] = str_pad($yyyymmdd[0],4,'0',STR_PAD_LEFT);
          $yyyymmdd[1] = str_pad($yyyymmdd[1],2,'0',STR_PAD_LEFT);
          $yyyymmdd[2] = str_pad($yyyymmdd[2],2,'0',STR_PAD_LEFT);
          $fecha_cotizacion = implode('-',$yyyymmdd);
          $merged[$fecha_cotizacion] = $merged[$fecha_cotizacion] ?? [];
          $compra = str_replace('.',',',$row[$header_idx['Compra']]);
          $venta = str_replace('.',',',$row[$header_idx['Venta']]);
          $merged[$fecha_cotizacion][$moneda] = compact('compra','venta');          
        }
      }
      
      ksort($merged);
      
      $CC->agregar($CACHE_CODIGO,$CACHE_SUBCODIGO,json_encode($merged),[]);
      
      return $merged;
    }
}
