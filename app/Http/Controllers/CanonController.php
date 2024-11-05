<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use PDF;
use Dompdf\Dompdf;
use View;
use Zipper;
use File;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UsuarioController;
use App\Plataforma;
use App\Archivo;

require_once(app_path('BC_extendido.php'));

function csvstr(array $fields) : string
{
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
        return false;
    }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line);
}

function formatear_decimal(string $val) : string {//number_format castea a float... lo hacemos a pata...
  $negativo = ($val[0] ?? false) == '-'? '-' : '';
  $val = strlen($negativo)? substr($val,1) : $val;
  
  $parts   = explode('.',$val);
  $entero  = $parts[0] ?? '';
  $decimal = $parts[1] ?? null;
  $entero_separado = [];
  for($i=0;$i<strlen($entero);$i++){
    $bucket = intdiv($i,3);
    if($i%3 == 0) $entero_separado[$bucket] = '';
    $entero_separado[$bucket] = $entero[strlen($entero)-1-$i] . $entero_separado[$bucket];
  }

  $newval = implode('.',array_reverse($entero_separado));
  $decimal = is_null($decimal)? null : rtrim($decimal,'0');
  if(!is_null($decimal) && strlen($decimal) > 0){
    $newval .= ','.$decimal;
  }
  return $negativo.$newval;
}

class CanonController extends Controller
{
  static $valoresDefecto_fallback = [
    'canon_variable' => '{"1":{"Maquinas":{"alicuota":"21","devengado_deduccion":"250000"},"Bingo":{"alicuota":"35"}},"2":{"Maquinas":{"alicuota":"25","devengado_deduccion":"500000"},"Bingo":{"alicuota":"55"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}},"3":{"Maquinas":{"alicuota":"20.56","devengado_apostado_porcentaje_aplicable":"19","devengado_apostado_porcentaje_impuesto_ley":"0.95","devengado_deduccion":"1000000"},"Bingo":{"alicuota":"78.5"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}}}',
    'canon_fijo_mesas' => '{"1":{"Fijas":{"valor_dolar":"1973.92","valor_euro":"2135.92","dias_valor":30,"calcular_dias_lunes_jueves":false,"calcular_dias_viernes_sabados":false,"calcular_dias_domingos":false,"calcular_dias_todos":false,"dias_fijos":30,"mesas_fijos":15,"devengado_deduccion":"60000"}},"2":{"Diarias":{"valor_dolar":"3287.21","valor_euro":"3215.91","dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"120000","mesas_lunes_jueves":16,"mesas_viernes_sabados":25,"mesas_domingos":21}},"3":{"Diarias":{"valor_dolar":"2881.51","valor_euro":"2569.56","dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"240000","mesas_lunes_jueves":40,"mesas_viernes_sabados":50,"mesas_domingos":45}}}',
    'canon_fijo_mesas_adicionales' => '{"1":{"Mesas Adicionales de Póker":{"valor_dolar":"1973.92","valor_euro":"2135.92","dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Póker y RA":{"valor_dolar":"1973.92","valor_euro":"2135.92","dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Truco":{"valor_dolar":"1973.92","valor_euro":"2135.92","dias_mes":30,"horas_dia":16,"porcentaje":"20"}},"2":{"Mesas Adicionales de Póker":{"valor_dolar":"3287.21","valor_euro":"3215.91","dias_mes":30,"horas_dia":24,"porcentaje":"100"},"Torneos":{"valor_dolar":"3287.21","valor_euro":"3215.91","dias_mes":30,"horas_dia":24,"porcentaje":"100"}},"3":{"Mesas Adicionales de Póker":{"valor_dolar":"2881.51","valor_euro":"2569.56","dias_mes":30,"horas_dia":17,"porcentaje":"100"},"Torneos":{"valor_dolar":"2881.51","valor_euro":"2569.56","dias_mes":30,"horas_dia":17,"porcentaje":"100"}}}',
  ];
  static $max_scale = 64;
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
      
  public function index(){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $casinos = $u->casinos;     
    $es_superusuario = $u->es_superusuario;
    $puede_cargar = $es_superusuario || $u->tienePermiso('m_a_pagos');
    return View::make('Canon.index', compact('casinos','plataformas','es_superusuario','puede_cargar'));
  }
  
  private static  $errores = [
    'required' => 'El valor es requerido',
    'regex'    => 'Formato incorrecto',
    'date'     => 'Tiene que ser una fecha en formato YYYY-MM-DD',
    'min'      => 'Es inferior al limite',
    'max'      => 'Supera el limite',
    'integer'  => 'Tiene que ser un número entero',
    'exists'   => 'El valor es incorrecto',
  ];
    
  private function validarCanon(array $request,array $requireds = []){
    $numeric_rule = function(int $digits) {
      static $cache = [];
      if($cache[$digits] ?? false) return $cache[$digits];
      $regex = '-?\d+';
      if($digits){
        $digits_regexp = implode('',array_fill(0,$digits,'\d?'));
        $regex .= '\.?'.$digits_regexp;
      }
      $cache[$digits] = 'regex:/^'.$regex.'$/';
      return $cache[$digits];
    };
    $requireds_f = function(string $s) use ($requireds) {
      return in_array($s,$requireds)? 'required' : 'nullable';
    };

    Validator::make($request,[
      'id_canon' => ['nullable','integer','exists:canon,id_canon,deleted_at,NULL'],
      'año_mes' => [$requireds_f('año_mes'),'date','regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_casino' => [$requireds_f('id_casino'),'integer','exists:casino,id_casino,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
      'es_antiguo' => [$requireds_f('es_antiguo'),'integer','in:1,0'],
      'devengado_bruto' => ['nullable',$numeric_rule(20)],
      'devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'fecha_vencimiento' => ['nullable','date'],
      'fecha_pago' => ['nullable','date'],
      'determinado_bruto' => ['nullable',$numeric_rule(20)],
      'interes_mora'=> ['nullable',$numeric_rule(4)],
      'mora' => ['nullable',$numeric_rule(2)],
      'determinado' => ['nullable',$numeric_rule(2)],
      'ajuste' => ['nullable',$numeric_rule(2)],
      'motivo_ajuste' => ['nullable','string','max:128'],
      'saldo_anterior' => ['nullable',$numeric_rule(2)],
      'saldo_posterior' => ['nullable',$numeric_rule(2)],
      'canon_variable' => 'array',
      'canon_variable.*.devengado_bruto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.devengado_apostado_sistema' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.devengado_apostado_porcentaje_aplicable' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_apostado_porcentaje_impuesto_ley' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_bruto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_impuesto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.alicuota' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas' => 'array',
      'canon_fijo_mesas.*.valor_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.valor_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.dias_valor' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_lunes_jueves' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_lunes_jueves' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_viernes_sabados' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_viernes_sabados' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_domingos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_domingos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_todos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_todos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.dias_fijos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_fijos' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.devengado_fecha_cotizacion' => ['nullable','date'],
      'canon_fijo_mesas.*.devengado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.devengado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.determinado_fecha_cotizacion' => ['nullable','date'],
      'canon_fijo_mesas.*.determinado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.determinado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.devengado_fecha_cotizacion' => ['nullable','date'],
      'canon_fijo_mesas_adicionales.*.devengado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.devengado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_fecha_cotizacion' => ['nullable','date'],
      'canon_fijo_mesas_adicionales.*.determinado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'adjuntos' => 'array',
      'adjuntos.*.descripcion' => ['nullable','string','max:256'],
      'adjuntos.*.id_archivo'  => ['nullable','integer','exists:archivo,id_archivo'],
      'adjuntos.*.file'        => 'file',
    ], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
    })->validate();
  }
  
  public function recalcular_req(Request $request){
    $R = $request->all();
    $this->validarCanon($R);
    return $this->recalcular($R);
  }
  
  private function recalcular(array $request){
    $R = function($s,$dflt = null) use (&$request){
      return (($request[$s] ?? null) === null || ($request[$s] === '') || ($request[$s] === []))? $dflt : $request[$s];
    };
        
    $año_mes = $R('año_mes');//@RETORNADO
    $id_casino = $R('id_casino');//@RETORNADO
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $fecha_vencimiento = $R('fecha_vencimiento');//@RETORNADO
    $fecha_pago = $R('fecha_pago');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    $adjuntos = $R('adjuntos',[]);//@RETORNADO
    
    $fecha_vencimiento = $R('fecha_vencimiento',null);//@RETORNADO    
    if($año_mes !== null && $año_mes !== '' && $fecha_vencimiento === null){
      $f = explode('-',$año_mes);
      
      $f[0] = $f[1] == '12'? intval($f[0])+1 : $f[0];
      $f[1] = $f[1] == '12'? '01' : str_pad(intval($f[1])+1,2,'0',STR_PAD_LEFT);
      $f[2] = '10';
      $f = implode('-',$f);
      $f = new \DateTimeImmutable($f);
      $proximo_lunes = clone $f;
      for($break = 9;$break > 0 && in_array($proximo_lunes->format('w'),['0','6']);$break--){
        $proximo_lunes = $proximo_lunes->add(\DateInterval::createFromDateString('1 day'));
      }
      $fecha_vencimiento = $R('fecha_vencimiento',$proximo_lunes->format('Y-m-d'));//@RETORNADO
    }
    
    $fecha_pago = $R('fecha_pago',$fecha_vencimiento);//@RETORNADO
    
    $devengado_deduccion = '0.00';//@RETORNADO
    $devengado_bruto = '0.00';//@RETORNADO
    $determinado_bruto = '0.00';//@RETORNADO
    $canon_variable = [];//@RETORNADO
    $canon_fijo_mesas = [];//@RETORNADO
    $canon_fijo_mesas_adicionales = [];//@RETORNADO
    
    {
      //Si falta algun valor... se les asigna el primer valor recibido como por defecto
      $datos_cotizaciones = [
        'devengado_fecha_cotizacion' => null,
        'devengado_cotizacion_dolar' => null,
        'devengado_cotizacion_euro' => null,
        'determinado_fecha_cotizacion' => null,
        'determinado_cotizacion_dolar' => null,
        'determinado_cotizacion_euro' => null,
      ];
      
      {//Varios tipos (JOL, Bingo, Maquinas)
        $defecto = ($this->valorPorDefecto('canon_variable') ?? [])[$id_casino] ?? [];
        $ret = [];
        foreach(($request['canon_variable'] ?? $defecto ?? []) as $tipo => $_){
          $data_request_tipo = ($request['canon_variable'] ?? [])[$tipo] ?? [];
          
          foreach($datos_cotizaciones as $k => $v){
            $data_request_tipo[$k] = $data_request_tipo[$k] ?? $v;
          }
          
          $ret[$tipo] = $this->canon_variable_recalcular(
            $año_mes,
            $id_casino,
            $tipo,
            $defecto[$tipo] ?? [],
            $data_request_tipo
          );
          
          if($es_antiguo){
            $ret[$tipo]['devengado_deduccion'] = $data_request_tipo['devengado_deduccion'] ?? '0';
            $ret[$tipo]['devengado_total'] = $data_request_tipo['devengado_total'] ?? '0';
            $ret[$tipo]['determinado_total'] = $data_request_tipo['determinado_total'] ?? '0';
          }
          
          $devengado_deduccion = bcadd($devengado_deduccion,$ret[$tipo]['devengado_deduccion'] ?? '0',2);
          $devengado_bruto = bcadd($devengado_bruto,$ret[$tipo]['devengado_total'] ?? '0',20);
          $determinado_bruto = bcadd($determinado_bruto,$ret[$tipo]['determinado_total'] ?? '0',20);
          
          foreach($datos_cotizaciones as $k => $v){
            $datos_cotizaciones[$k] = $datos_cotizaciones[$k] ?? $ret[$tipo][$k] ?? null;
          }
        }
        
        $canon_variable = $ret;
        unset($ret);
      }
            
      {//Dos tipos muy parecidos (Fijas y Diarias), se hace asi mas que nada para que sea homogeneo
        $defecto = $this->valorPorDefecto('canon_fijo_mesas')[$id_casino] ?? [];
        $ret = [];
        foreach(($request['canon_fijo_mesas'] ?? $defecto ?? []) as $tipo => $_){
          $data_request_tipo = ($request['canon_fijo_mesas'] ?? [])[$tipo] ?? [];
          
          foreach($datos_cotizaciones as $k => $v){
            $data_request_tipo[$k] = $data_request_tipo[$k] ?? $v;
          }
          
          $ret[$tipo] = $this->canon_fijo_mesas_recalcular(
            $año_mes,
            $id_casino,
            $tipo,
            $defecto[$tipo] ?? [],
            $data_request_tipo
          );
          
          if($es_antiguo){
            $ret[$tipo]['devengado_deduccion'] = $data_request_tipo['devengado_deduccion'] ?? '0';
            $ret[$tipo]['devengado_total'] = $data_request_tipo['devengado_total'] ?? '0';
            $ret[$tipo]['determinado_total'] = $data_request_tipo['determinado_total'] ?? '0';
          }
          
          $devengado_deduccion = bcadd($devengado_deduccion,$ret[$tipo]['devengado_deduccion'] ?? '0',2);
          $devengado_bruto = bcadd($devengado_bruto,$ret[$tipo]['devengado_total'] ?? '0',20);
          $determinado_bruto = bcadd($determinado_bruto,$ret[$tipo]['determinado_total'] ?? '0',20);
          
          foreach($datos_cotizaciones as $k => $v){
            $datos_cotizaciones[$k] = $datos_cotizaciones[$k] ?? $ret[$tipo][$k] ?? null;
          }
        }
        
        $canon_fijo_mesas = $ret;
        unset($ret);
      }
      {//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
        $defecto = $this->valorPorDefecto('canon_fijo_mesas_adicionales')[$id_casino] ?? [];
        $ret = [];
        foreach(($request['canon_fijo_mesas_adicionales'] ?? $defecto ?? []) as $tipo => $_){
          $data_request_tipo = ($request['canon_fijo_mesas_adicionales'] ?? [])[$tipo] ?? [];
          
          foreach($datos_cotizaciones as $k => $v){
            $data_request_tipo[$k] = $data_request_tipo[$k] ?? $v;
          }
          
          $ret[$tipo] = $this->canon_fijo_mesas_adicionales_recalcular(
            $año_mes,
            $id_casino,
            $tipo,
            $defecto[$tipo] ?? [],
            $data_request_tipo
          );
          
          if($es_antiguo){
            $ret[$tipo]['devengado_deduccion'] = $data_request_tipo['devengado_deduccion'] ?? '0';
            $ret[$tipo]['devengado_total'] = $data_request_tipo['devengado_total'] ?? '0';
            $ret[$tipo]['determinado_total'] = $data_request_tipo['determinado_total'] ?? '0';
          }
          
          $devengado_deduccion = bcadd($devengado_deduccion,$ret[$tipo]['devengado_deduccion'] ?? '0',2);
          $devengado_bruto = bcadd($devengado_bruto,$ret[$tipo]['devengado_total'] ?? '0',20);
          $determinado_bruto = bcadd($determinado_bruto,$ret[$tipo]['determinado_total'] ?? '0',20);
          
          foreach($datos_cotizaciones as $k => $v){
            $datos_cotizaciones[$k] = $datos_cotizaciones[$k] ?? $ret[$tipo][$k] ?? null;
          }
        }
        
        $canon_fijo_mesas_adicionales = $ret;
        unset($ret);
      }
    }
    
    $devengado = bcround_ndigits(bcsub($devengado_bruto,$devengado_deduccion,20),2);//@RETORNADO
    
    $porcentaje_seguridad = bccomp($devengado_bruto,'0.00') > 0?//@RETORNADO
       bcdiv(bcmul('100.0',$devengado_deduccion),$devengado_bruto,4)
      : '0.00';
    
    $interes_mora = bcadd($R('interes_mora','0.0000'),'0',4);//@RETORNADO
    $determinado = bcadd($R('determinado','0.00'),'0',2);//@RETORNADO
    $mora = bcadd($R('mora','0.00'),'0',2);//@RETORNADO
    
    $calcular_interes_mora = function($determinado,$determinado_bruto,$cantidad_dias){
      //$coeff = log($determinado/$determinado_bruto)/$cantidad_dias;
      //$interes_mora = (exp($coeff)-1)*100;
      $coeff = bcln(bcdiv($determinado,$determinado_bruto,self::$max_scale),16);
      $coeff = bcdiv($coeff,$cantidad_dias,self::$max_scale);
      $interes_mora = bcepow($coeff,self::$max_scale);
      $interes_mora = bcsub($interes_mora,'1',self::$max_scale);
      $interes_mora = bcround_ndigits($interes_mora,6);
      return bcmul($interes_mora,'100',4);
    };
    
    if(bccomp($determinado_bruto,'0.00',2) <= 0){
      $determinado = '0.00';
      $interes_mora = '0.00';
      $mora = '0.00';
    }
    else if($fecha_vencimiento && $fecha_pago){
      $timestamp_venc = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_vencimiento);
      $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $fecha_pago);
      $date_interval  = $timestamp_pago->diff($timestamp_venc);
      $cantidad_dias = intval($date_interval->format('%d'));
      if($cantidad_dias < 0){}
      else if($cantidad_dias == 0){
        $determinado = bcround_ndigits($determinado_bruto,2);
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
      else if($R('interes_mora',null) !== null){//Si envio el interes, calculo el pago
        $interes_1dia = bcadd('1',bcdiv($interes_mora,'100',6),6);
        $interes_final = bcpowi($interes_1dia,$cantidad_dias.'',self::$max_scale);
        //$determinado = $determinado_bruto*pow(1+$interes_mora/100.0,$cantidad_dias);
        $determinado = bcmul($determinado_bruto,$interes_final,self::$max_scale);
        $determinado = bcround_ndigits($determinado,2);
        $mora = bcsub($determinado_bruto,$determinado_bruto,2);
      }
      else if($R('determinado',null) !== null){//Si envio el pago, calculo el interes
        $interes_mora = $calcular_interes_mora($determinado,$determinado_bruto,$cantidad_dias);
        $mora = bcsub($determinado,$determinado_bruto,2);//$mora = $determinado - $determinado_bruto;
      }
      else if($R('mora',null) !== null){
        $determinado = bcadd($determinado_bruto,$mora,2);
        $interes_mora = $calcular_interes_mora($determinado,$determinado_bruto,$cantidad_dias);
      }
      else {//Son todos nulos... asumo interes 0...
        $determinado = bcround_ndigits($determinado_bruto,2);
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
    }
    
    
    $pago = bcadd($R('pago','0.00'),'0',2);//@RETORNADO
    $ajuste = bcadd($R('ajuste','0.00'),'0',2);//@RETORNADO
    $motivo_ajuste = $R('motivo_ajuste','');
    
    $dinamicos = $this->calcular_campos_dinamicos($año_mes,$id_casino,$determinado,$pago,$ajuste);
    
    $saldo_anterior = $dinamicos['saldo_anterior'];//@RETORNADO
    $a_pagar = $dinamicos['a_pagar'];//@RETORNADO
    $diferencia = $dinamicos['diferencia'];//@RETORNADO
    $saldo_posterior = $dinamicos['saldo_posterior'];//@RETORNADO
    
    return compact(
      'año_mes','id_casino','estado','es_antiguo',
      'canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales','adjuntos',
      'devengado_bruto','devengado_deduccion','devengado','porcentaje_seguridad',
      'determinado_bruto','fecha_vencimiento','fecha_pago','interes_mora','mora',
      'determinado','saldo_anterior','a_pagar','pago','ajuste','motivo_ajuste','diferencia','saldo_posterior'
    );
  }
  
  //@SPEED: cachear en DB
  private function calcular_campos_dinamicos($año_mes,$id_casino,$determinado,$pago,$ajuste){//Lo pongo en una función para mantener consistente el signo
    //Se considera que el "saldo" es positivo cuando es a favor del casino
    //por lo que
    //a pagar = determinado - saldo_anterior
    //diferencia = a_pagar - pago + ajuste
    //saldo posterior = -diferencia
    
    $saldo_anterior = '0.00';//@RETORNADO
    
    $canons_anteriores = $id_casino !== null && $año_mes !== null? DB::table('canon')
    ->where('id_casino',$id_casino)
    ->where('año_mes','<',$año_mes)
    ->whereNull('deleted_at')->get()
    : collect([]);
    
    foreach($canons_anteriores as $c){
      $a_pagar = bcsub($c->determinado,$saldo_anterior,2);
      $diferencia = bcadd(bcsub($a_pagar,$c->pago,2),$c->ajuste,2);
      $saldo_posterior = bcsub('0',$diferencia,2);
      $saldo_anterior = $saldo_posterior;
    }
    
    $a_pagar = bcsub($determinado,$saldo_anterior,2);//@RETORNADO
    $diferencia = bcadd(bcsub($a_pagar,$pago,2),$ajuste,2);//@RETORNADO
    $saldo_posterior = bcsub('0',$diferencia,2);//@RETORNADO
    
    return compact('saldo_anterior','a_pagar','diferencia','saldo_posterior');
  }
  
  public function canon_variable_recalcular($año_mes,$id_casino,$tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengado_apostado_sistema = bcadd($R('devengado_apostado_sistema','0.00'),'0',2);//@RETORNADO    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,8);//2+6 @RETORNADO
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $devengado_impuesto = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,14);//8+6 @RETORNADO
    $determinado_impuesto =  bcadd($R('determinado_impuesto','0.00'),'0',2);//@RETORNADO
    
    $devengado_bruto = $R('devengado_bruto',null);//@RETORNADO
    if($devengado_bruto === null){
      $devengado_bruto = $this->bruto($tipo,$año_mes,$id_casino);//@RETORNADO
    }
    $devengado_bruto = bcadd($devengado_bruto,'0',2);
    
    $devengado_subtotal = bcsub($devengado_bruto,$devengado_impuesto,14);//@RETORNADO
    
    $determinado_bruto = $R('determinado_bruto',null);//@RETORNADO
    if($determinado_bruto === null){
      $determinado_bruto = $devengado_bruto;
    }
    $determinado_bruto = bcadd($determinado_bruto,'0',2);
    
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,2);//@RETORNADO
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $devengado_total =  bcmul($devengado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $determinado_total =  bcmul($determinado_subtotal,$factor_alicuota,8);//6+2 @RETORNADO
    $devengado_deduccion = bcadd($RD('devengado_deduccion','0.00'),'0',2);
    
    return compact('tipo',
      'alicuota',
      'devengado_bruto',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'determinado_bruto',
      'devengado_impuesto','determinado_impuesto',
      'devengado_total','devengado_deduccion',
      'devengado_subtotal','determinado_subtotal','determinado_total'
    );
  }
  
  public function canon_fijo_mesas_recalcular(
      $año_mes,
      $id_casino,
      $tipo,//@RETORNADO
      $valores_defecto,
      $data
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengado_fecha_cotizacion = $R('devengado_fecha_cotizacion',null);//@RETORNADO
    $determinado_fecha_cotizacion = $R('determinado_fecha_cotizacion',null);//@RETORNADO
    if($año_mes !== null && $año_mes !== '' && ($devengado_fecha_cotizacion === null || $determinado_fecha_cotizacion === null)){
      $f = explode('-',$año_mes);
      
      $f[0] = $f[1] == '12'? intval($f[0])+1 : $f[0];
      $f[1] = $f[1] == '12'? '01' : str_pad(intval($f[1])+1,2,'0',STR_PAD_LEFT);
      
      if($devengado_fecha_cotizacion === null){
        $devengado_fecha_cotizacion = implode('-',$f);
      }
      
      if($determinado_fecha_cotizacion === null){
        $f[2] = '09';
        $f = implode('-',$f);
        $f = new \DateTimeImmutable($f);
        $viernes_anterior = clone $f;
        for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
          $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
        }
        $determinado_fecha_cotizacion = $viernes_anterior->format('Y-m-d');//@RETORNADO
      }
    }
    
    $devengado_cotizacion_dolar = bcadd($R(
      'devengado_cotizacion_dolar',
      $devengado_fecha_cotizacion !== null? ($this->cotizacion($devengado_fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $devengado_cotizacion_euro = bcadd($R(
      'devengado_cotizacion_euro',
      $devengado_fecha_cotizacion !== null? ($this->cotizacion($devengado_fecha_cotizacion,3) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $determinado_cotizacion_dolar = bcadd($R(
      'determinado_cotizacion_dolar',
      $determinado_fecha_cotizacion !== null? ($this->cotizacion($determinado_fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $determinado_cotizacion_euro = bcadd($R(
      'determinado_cotizacion_euro',
      $determinado_fecha_cotizacion !== null? ($this->cotizacion($determinado_fecha_cotizacion,3) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $valor_dolar = '0.00';//@RETORNADO
    $valor_euro  = '0.00';//@RETORNADO
    if($id_casino !== null){
      $valor_dolar = bcadd($RD('valor_dolar',$valor_dolar),'0.00',2);
      $valor_euro  = bcadd($RD('valor_euro',$valor_euro),'0.00',2);
    }
    
    $dias_valor = $RD('dias_valor',0);//@RETORNADO
    $factor_dias_valor = $dias_valor != 0? bcdiv('1',$dias_valor,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $devengado_valor_dolar_cotizado = bcmul($devengado_cotizacion_dolar,$valor_dolar,4);//2+2
    $devengado_valor_dolar_diario_cotizado  = '0.0000000000000000';//@RETORNADO
    $devengado_valor_euro_cotizado  = bcmul($devengado_cotizacion_euro,$valor_euro,4);//2+2
    $devengado_valor_euro_diario_cotizado   = '0.0000000000000000';//@RETORNADO
    $determinado_valor_dolar_cotizado = bcmul($determinado_cotizacion_dolar,$valor_dolar,4);//2+2
    $determinado_valor_dolar_diario_cotizado  = '0.0000000000000000';//@RETORNADO
    $determinado_valor_euro_cotizado  = bcmul($determinado_cotizacion_euro,$valor_euro,4);//2+2
    $determinado_valor_euro_diario_cotizado   = '0.0000000000000000';//@RETORNADO
    
    if($dias_valor != 0){//No entra si es =0, nulo, o falta
      $devengado_valor_dolar_diario_cotizado = bcmul($devengado_valor_dolar_cotizado,$factor_dias_valor,16);//4+12
      $devengado_valor_euro_diario_cotizado  = bcmul($devengado_valor_euro_cotizado,$factor_dias_valor,16);//4+12
      $determinado_valor_dolar_diario_cotizado = bcmul($determinado_valor_dolar_cotizado,$factor_dias_valor,16);//4+12
      $determinado_valor_euro_diario_cotizado  = bcmul($determinado_valor_euro_cotizado,$factor_dias_valor,16);//4+12
    }
    
    $dias_lunes_jueves = 0;//@RETORNADO
    $dias_viernes_sabados = 0;//@RETORNADO
    $dias_domingos = 0;//@RETORNADO
    $dias_todos = 0;//@RETORNADO
    $dias_fijos = $RD('dias_fijos',0);//@RETORNADO
    
    if($año_mes !== null){     
      $wdmin_wdmax_count_arr = [
        'dias_lunes_jueves'    => [1,4,0],
        'dias_viernes_sabados' => [5,6,0],
        'dias_domingos'        => [0,0,0],
        'dias_todos'           => [0,6,0],
      ];
      
      $calcular_dias_lunes_jueves = $D('calcular_dias_lunes_jueves',true);
      $calcular_dias_viernes_sabados = $D('calcular_dias_viernes_sabados',true);
      $calcular_dias_domingos = $D('calcular_dias_domingos',true);
      $calcular_dias_todos = $D('calcular_dias_todos',true);
      //@SPEED: unset K si no hay que calcular?
      if($calcular_dias_lunes_jueves || $calcular_dias_viernes_sabados || $calcular_dias_domingos || $calcular_dias_todos){
        $año_mes_arr = explode('-',$año_mes);
        $dias_en_el_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        for($d=1;$d<=$dias_en_el_mes;$d++){
          $año_mes_arr[2] = $d;
          $f = new \DateTime(implode('-',$año_mes_arr));
          $wd = $f->format('w');
          foreach($wdmin_wdmax_count_arr as $k => &$wdmin_wdmax_count){
            if($wd >= $wdmin_wdmax_count[0] && $wd <= $wdmin_wdmax_count[1]){
              $wdmin_wdmax_count[2] = $wdmin_wdmax_count[2] + 1;
            }
          }
        }
      }
      
      $dias_lunes_jueves = $R('dias_lunes_jueves',$wdmin_wdmax_count_arr['dias_lunes_jueves'][2]);
      $dias_viernes_sabados = $R('dias_viernes_sabados',$wdmin_wdmax_count_arr['dias_viernes_sabados'][2]);
      $dias_domingos = $R('dias_domingos',$wdmin_wdmax_count_arr['dias_domingos'][2]);
      $dias_todos = $R('dias_todos',$wdmin_wdmax_count_arr['dias_todos'][2]);
    }
    
    $mesas_lunes_jueves      = $RD('mesas_lunes_jueves',0);//@RETORNADO
    $mesas_viernes_sabados   = $RD('mesas_viernes_sabados',0);//@RETORNADO
    $mesas_domingos          = $RD('mesas_domingos',0);//@RETORNADO
    $mesas_todos             = $RD('mesas_todos',0);//@RETORNADO
    $mesas_fijos             = $RD('mesas_fijos',0);//@RETORNADO
        
    $mesas_dias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;//@RETORNADO
    
    $devengado_total_dolar   = '0';//@RETORNADO
    $devengado_total_euro    = '0';//@RETORNADO
    $determinado_total_dolar = '0';//@RETORNADO
    $determinado_total_euro  = '0';//@RETORNADO
    //Lo desprendo en sumas para hacerlo mas preciso (disminuyo las divisiones)
    //$total_MONEDA = $valor_diario_MONEDA * $mesas_dias
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA/$dias_valor) * $mesas_dias
    //$total_MONEDA = $valor_MONEDA*$cotizacion_MONEDA*($mesas_dias/$dias_valor)
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA)*($mesas_dias intdiv $dias_valor + ($mesas_dias % $dias_valor)/$dias_valor)
    //$total_MONEDA = ($valor_MONEDA*$cotizacion_MONEDA)*($mesas_dias intdiv $dias_valor + ($mesas_dias % $dias_valor)*$factor_dias_valor)
    //$total_MONEDA = $valor_mensual_MONEDA*($mesas_dias intdiv $dias_valor) + $valor_diario_MONEDA*($mesas_dias % $dias_valor)
    //$total_MONEDA = $valor_mensual_MONEDA*$mesas_meses + $valor_diario_MONEDA*$mesas_dias_restantes
    if($dias_valor > 0){
      $mesas_meses = intdiv($mesas_dias,$dias_valor);
      $mesas_dias_restantes  = $mesas_dias % $dias_valor;
      
      $devengado_total_dolar_cotizado = bcmul($devengado_valor_dolar_cotizado,$mesas_meses,4);
      $devengado_total_euro_cotizado  = bcmul($devengado_valor_euro_cotizado,$mesas_meses,4);
      $determinado_total_dolar_cotizado = bcmul($determinado_valor_dolar_cotizado,$mesas_meses,4);
      $determinado_total_euro_cotizado  = bcmul($determinado_valor_euro_cotizado,$mesas_meses,4);
      
      $devengado_total_dolar_cotizado = bcadd($devengado_total_dolar_cotizado,bcmul($devengado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $devengado_total_euro_cotizado  = bcadd($devengado_total_euro_cotizado,bcmul($devengado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_dolar_cotizado = bcadd($determinado_total_dolar_cotizado,bcmul($determinado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_euro_cotizado  = bcadd($determinado_total_euro_cotizado,bcmul($determinado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
    }
    
    $devengado_deduccion = bcadd($RD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $devengado_total   = bcadd($devengado_total_dolar_cotizado,$devengado_total_euro_cotizado,16);//@RETORNADO
    $determinado_total = bcadd($determinado_total_dolar_cotizado,$determinado_total_euro_cotizado,16);//@RETORNADO
    
    return compact(
      'tipo','dias_valor','factor_dias_valor','valor_dolar','valor_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'mesas_dias',
      
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_dolar_cotizado','devengado_valor_euro_cotizado',
      'devengado_valor_dolar_diario_cotizado','devengado_valor_euro_diario_cotizado',
      'devengado_total_dolar_cotizado','devengado_total_euro_cotizado','devengado_total',
      'devengado_deduccion',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_dolar_cotizado','determinado_valor_euro_cotizado',
      'determinado_valor_dolar_diario_cotizado','determinado_valor_euro_diario_cotizado',
      'determinado_total_dolar_cotizado','determinado_total_euro_cotizado','determinado_total'
    );
  }
  
  public function canon_fijo_mesas_adicionales_recalcular($año_mes,$id_casino,$tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    
    $factor_dias_mes  = ($dias_mes != 0)? bcdiv('1',$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    $factor_horas_mes = ($horas_dia != 0 && $dias_mes != 0)? bcdiv('1',$horas_dia*$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $valor_dolar = bcadd($RD('valor_dolar','0.00'),'0',2);//@RETORNADO
    $valor_euro = bcadd($RD('valor_euro','0.00'),'0',2);//@RETORNADO
    $horas = $R('horas',0);//@RETORNADO
    $porcentaje = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    $factor_porcentaje = bcdiv($porcentaje,'100',6);
    
    $devengado_fecha_cotizacion = $R('devengado_fecha_cotizacion',null);//@RETORNADO
    $devengado_cotizacion_dolar = bcadd($R(
      'devengado_cotizacion_dolar',
      $devengado_fecha_cotizacion !== null? ($this->cotizacion($devengado_fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $devengado_cotizacion_euro = bcadd($R(
      'devengado_cotizacion_euro',
      $devengado_fecha_cotizacion !== null? ($this->cotizacion($devengado_fecha_cotizacion,3) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $determinado_fecha_cotizacion = $R('determinado_fecha_cotizacion',null);//@RETORNADO
    $determinado_cotizacion_dolar = bcadd($R(
      'determinado_cotizacion_dolar',
      $determinado_fecha_cotizacion !== null? ($this->cotizacion($determinado_fecha_cotizacion,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $determinado_cotizacion_euro = bcadd($R(
      'determinado_cotizacion_euro',
      $determinado_fecha_cotizacion !== null? ($this->cotizacion($determinado_fecha_cotizacion,3) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $devengado_valor_mes = bcadd(
      bcmul($valor_dolar,$devengado_cotizacion_dolar,4),//2+2
      bcmul($valor_euro,$devengado_cotizacion_euro,4),//2+2
      4
    );//@RETORNADO
    $devengado_valor_dia  = bcmul($devengado_valor_mes,$factor_dias_mes,16);//4+12 @RETORNADO
    $devengado_valor_hora = bcmul($devengado_valor_mes,$factor_horas_mes,16);//4+12 @RETORNADO
    
    $determinado_valor_mes = bcadd(
      bcmul($valor_dolar,$determinado_cotizacion_dolar,4),//2+2
      bcmul($valor_euro,$determinado_cotizacion_euro,4),//2+2
      4
    );//@RETORNADO
    $determinado_valor_dia  = bcmul($determinado_valor_mes,$factor_dias_mes,16);//4+12 @RETORNADO
    $determinado_valor_hora = bcmul($determinado_valor_mes,$factor_horas_mes,16);//4+12 @RETORNADO
    
    $devengado_total_sin_aplicar_porcentaje = '0';
    $determinado_total_sin_aplicar_porcentaje = '0';
    {//Sumo de valores mas precisos a menos precisos
      $horas_mes = $horas_dia*$dias_mes;
      
      $meses = intdiv($horas,$horas_mes);
      $restantes = $horas%$horas_mes;
      
      $dias = intdiv($restantes,$horas_dia);
      $restantes = $restantes%$horas_dia;
      
      $devengado_total_meses = bcmul($devengado_valor_mes,$meses,4);
      $devengado_total_dias  = bcmul($devengado_valor_dia,$dias,16);
      $devengado_total_horas = bcmul($devengado_valor_hora,$restantes,16);
      $determinado_total_meses = bcmul($determinado_valor_mes,$meses,4);
      $determinado_total_dias  = bcmul($determinado_valor_dia,$dias,16);
      $determinado_total_horas = bcmul($determinado_valor_hora,$restantes,16);
      
      $devengado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($devengado_total_sin_aplicar_porcentaje,$devengado_total_meses,16),$devengado_total_dias,16),$devengado_total_horas,16
      );
      $determinado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($determinado_total_sin_aplicar_porcentaje,$determinado_total_meses,16),$determinado_total_dias,16),$determinado_total_horas,16
      );
    }
        
    $devengado_total = bcmul($devengado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    $determinado_total = bcmul($determinado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    
    $devengado_deduccion = bcadd($RD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    
    return compact(
      'tipo',
      'dias_mes','horas_dia','factor_dias_mes','factor_horas_mes',
      'valor_dolar','valor_euro',
      'horas','porcentaje',
      
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_mes','devengado_valor_dia','devengado_valor_hora',
      'devengado_total','devengado_deduccion',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_mes','determinado_valor_dia','determinado_valor_hora',
      'determinado_total'
    );
  }
  
  public function adjuntar(Request $request){
    return $this->guardar($request,false);
  }
  
  public function guardar(Request $request,$recalcular = true){
    $requeridos = $recalcular? ['año_mes','id_casino','es_antiguo'] : ['id_canon'];
    $this->validarCanon($request->all(),$requeridos);
    
    Validator::make($request->all(),[], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
      $D = $validator->getData();
      if(!isset($D['id_canon'])){//Nuevo
        $ya_existe = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$D['año_mes'])
        ->where('id_casino',$D['id_casino'])
        ->count() > 0;
        
        if($ya_existe){
          $validator->errors()->add('año_mes','Ya existe un canon para ese periodo');
          $validator->errors()->add('id_casino','Ya existe un canon para ese periodo');
          return;
        }
      }
    })->validate();
    
    return DB::transaction(function() use ($request,$recalcular){
      $datos;
      if($recalcular){
        $datos = $this->recalcular($request->all());
        $datos['estado'] = 'Generado';
      }
      else{
        $datos = $this->obtener_arr(['id_canon' => $request['id_canon']]);
        $datos = json_decode(json_encode($datos),true);//obj->array 
        $datos['adjuntos'] = $request['adjuntos'] ?? [];
      }
      
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $id_canon_anterior = null;
      {
        $canon_viejos = DB::table('canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$request->año_mes)
        ->where('id_casino',$request->id_casino)
        ->orderBy('created_at','desc')
        ->get();
        
        foreach($canon_viejos as $idx => $cv){
          if($idx == 0){//Saco todos los id_archivos para pasarselos a la version de canon nueva
            $id_canon_anterior = $cv->id_canon;
          }
          $this->borrar_arr(['id_canon' => $cv->id_canon],$created_at,$id_usuario);
        }
      }
            
      $id_canon = DB::table('canon')
      ->insertGetId([
        'año_mes' => $datos['año_mes'],
        'id_casino' => $datos['id_casino'],
        'estado' => $datos['estado'],
        'devengado_bruto' => $datos['devengado_bruto'],
        'devengado_deduccion' => $datos['devengado_deduccion'],
        'devengado' => $datos['devengado'],
        'porcentaje_seguridad' => $datos['porcentaje_seguridad'], 
        'fecha_vencimiento' => $datos['fecha_vencimiento'],
        'fecha_pago' => $datos['fecha_pago'],
        'determinado_bruto' => $datos['determinado_bruto'],
        'interes_mora' => $datos['interes_mora'],
        'mora' => $datos['mora'],
        'determinado' => $datos['determinado'],
        'pago' => $datos['pago'],
        'ajuste' => $datos['ajuste'],
        'motivo_ajuste' => $datos['motivo_ajuste'],
        'es_antiguo' => $datos['es_antiguo'],
        'created_at' => $created_at,
        'created_id_usuario' => $id_usuario,
      ]);
      
      foreach(($datos['canon_variable'] ?? []) as $tipo => $d){
        $d['id_canon'] = $id_canon;
        $d['tipo'] = $tipo;
        unset($d['id_canon_variable']);
        DB::table('canon_variable')
        ->insert($d);
      }
      
      foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $d){
        $d['id_canon'] = $id_canon;
        $d['tipo'] = $tipo;
        unset($d['id_canon_fijo_mesas']);
        DB::table('canon_fijo_mesas')
        ->insert($d);
      }
      
      foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $d){
        $d['id_canon'] = $id_canon;
        $d['tipo']     = $tipo;
        unset($d['id_canon_fijo_mesas_adicionales']);
        DB::table('canon_fijo_mesas_adicionales')
        ->insert($d);
      }
      
      {
        $archivos_existentes = $id_canon_anterior === null? 
          collect([])
        : DB::table('canon_archivo as ca')
        ->select('ca.descripcion','ca.type','a.*')
        ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
        ->where('id_canon',$id_canon_anterior)
        ->get()
        ->keyBy('id_archivo');
        
        $archivos_enviados = collect($datos['adjuntos'] ?? [])->groupBy('id_archivo');
        $archivos_resultantes = [];
        foreach($archivos_enviados as $id_archivo_e => $archivos_e){
          if($id_archivo_e !== ''){//Es "existente"
            //Se recibio un id archivo que no estaba antes
            if(!$archivos_existentes->has($id_archivo_e)) continue;
            
            $archivo_bd = $archivos_existentes[$id_archivo_e];
            
            $archivo = null;//Por si me mando varios con el mismo id_archivo, busco el que tenga mismo nombre de archivo
            foreach($archivos_e as $ae){
              if($ae['nombre_archivo'] == $archivo_bd->nombre_archivo){
                $archivo = $ae;
                break;
              }
            }
            
            if($archivo === null) continue;//No encontre, lo ignoro
                        
            //El archivo se repite para el nuevo canon pero posiblemente con otra descripcion
            $archivos_resultantes[] = [
              'id_archivo'  => $archivo_bd->id_archivo,
              'id_canon'    => $id_canon,
              'descripcion' => ($archivo['descripcion'] ?? ''),
              'type'        => $archivo_bd->type,
            ];
          }
          else{//Archivos nuevos
            foreach($archivos_e as $a){
              $file=$a['file'] ?? null;
              if($file === null) continue;
              
              $archivo_bd = new Archivo;
              $data = base64_encode(file_get_contents($file->getRealPath()));
              $nombre_archivo = $file->getClientOriginalName();
              $archivo_bd->nombre_archivo = $nombre_archivo;
              $archivo_bd->archivo = $data;
              $archivo_bd->save();
              
              $archivos_resultantes[] = [
                'id_archivo' => $archivo_bd->id_archivo,
                'id_canon' => $id_canon,
                'descripcion' => ($a['descripcion'] ?? ''),
                'type' => $file->getMimeType() ?? 'application/octet-stream'
              ];
            } 
          }
        }
        
        DB::table('canon_archivo')
        ->insert($archivos_resultantes);
      }
      
      return 1;
    });
  }
  
  public function obtener_arr(array $request){
    $ret = (array) DB::table('canon as c')
    ->select('cas.nombre as casino','c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->where('id_canon',$request['id_canon'])
    ->first();
        
    if(!empty($ret)){
      $dinamicos = $this->calcular_campos_dinamicos($ret['año_mes'],$ret['id_casino'],$ret['determinado'],$ret['pago'],$ret['ajuste']);
      foreach($dinamicos as $k => $v) $ret[$k] = $v;
    }
    else{
      $ret['saldo_anterior']  = '';
      $ret['a_pagar']         = '';
      $ret['diferencia']      = '';
      $ret['saldo_posterior'] = '';
    }
        
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
        
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$request['id_canon'])
    ->get()
    ->keyBy('tipo');
    
    $ret['adjuntos'] = DB::table('canon_archivo as ca')
    ->select('ca.id_canon','ca.descripcion','a.id_archivo','a.nombre_archivo')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->orderBy('id_archivo','asc')
    ->get()
    ->transform(function(&$adj){
      $adj->link = '/canon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    
    return !empty($ret)? $ret : $this->recalcular($ret);
  }
  
  public function archivo(Request $request){
    if(($request['id_canon'] ?? null) === null || ($request['nombre_archivo'] ?? null) === null)
      return null;
    
    $a = DB::table('canon_archivo as ca')
    ->select('ca.type','a.*')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$request['id_canon'])
    ->where('a.nombre_archivo',$request['nombre_archivo'])
    ->first();
    
    if($a === null) 
      return null;
    
    return \Response::make(
      base64_decode($a->archivo), 
      200, 
      [
        'Content-Type' => $a->type,
        'Content-Disposition' => 'inline; filename="'.$a->nombre_archivo.'"'
      ]
    );
  }
  
  public function obtener(Request $request){
    return $this->obtener_arr($request->all());
  }
  
  public function obtenerConHistorial(Request $request){
    $ultimo = $this->obtener($request);
    $ultimo['historial'] = ($ultimo['id_canon'] ?? null) !== null?
      DB::table('canon')
      ->select('created_at','id_canon')->distinct()
      ->where('año_mes',$ultimo['año_mes'])
      ->where('id_casino',$ultimo['id_casino'])
      ->orderBy('created_at','desc')
      ->get()->map(function($idc,$idc_idx){
        return $this->obtener_arr(['id_canon' => $idc->id_canon]);
      })
    : collect([]);
    return $ultimo;
  }
  
  public function borrar(Request $request){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $check_estado = $u->es_superusuario? '' : ',estado,Generado,estado,Pagado';
    
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL'.$check_estado]
    ], ['exists' => 'No existe Canon eliminable'],[])->after(function($validator){
      if($validator->errors()->any()) return;
    })->validate();
    
    return $this->borrar_arr($request->all());
  }
  
  public function borrar_arr(array $arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$arr['id_canon'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  public function buscar(){
    $ret = DB::table('canon')
    ->select('canon.*','casino.nombre as casino')
    ->join('casino','casino.id_casino','=','canon.id_casino')
    ->whereNull('canon.deleted_at')
    ->orderBy('año_mes','desc')
    ->orderBy('casino.nombre','asc')
    ->paginate($request->page_size ?? 10);
    //Necesito transformar la data paginada pero si llamo transform() elimina toda la data de paginado
    $ret2 = $ret->toArray();
    
    //@HACK @SLOW: usar algun tipo de cache calculado hasta
    $ret2['data'] = $ret->reverse()->transform(function(&$c){
      $dinamicos = $this->calcular_campos_dinamicos($c->año_mes,$c->id_casino,$c->determinado,$c->pago,$c->ajuste);
      foreach($dinamicos as $k => $v) $c->{$k} = $v;
      return $c;
    })->reverse();
    
    return $ret2;
  }
  
  private function cotizacion($fecha_cotizacion,$id_tipo_moneda){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return null;
    if($id_tipo_moneda == 2){
      $cotizacion = DB::table('cotizacion as cot')
      ->where('fecha',$fecha_cotizacion)
      ->first();
      return $cotizacion !== null? $cotizacion->valor : null;
    }
    return null;//@TODO Euro
  }
  
  private function bruto($tipo,$año_mes,$id_casino){//@TODO: modularizar
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.valor*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
      case 'Bingo':{
        $resultado = DB::table('bingo_importacion as b')
        ->selectRaw('SUM(b.recaudado-b.premio_bingo-b.premio_linea) as valor')
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      };
      case 'JOL': return null;//@TODO JOL
    }
    return null;
  }
  
  public function cambiarEstado(Request $request){
    return DB::transaction(function() use ($request){
      $updateado = DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$request->id_canon)
      ->update(['estado' => $request->estado]) == 1;
      
      $estado = 200;
      $ret = ['id_canon' => $request->id_canon,'estado' => $request->estado,'mensaje' => ''];
      if($updateado != 1){
        $estado = 422;
        $ret['mensaje'] = 'Error, canon no encontrado';
      }
      return $ret;
    });
  }
  
  private function valorPorDefecto($k){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
        
    $val = is_null($db)? null : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    $val = $val ?? self::$valoresDefecto_fallback[$k] ?? '{}';
    
    return json_decode($val,true);
  }
    
  public function valoresPorDefecto(Request $request){
    return DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->orderBy('campo','asc')
    ->paginate($request->page_size);
  }
  
  public function valoresPorDefecto_ingresar(Request $request){    
    Validator::make($request->all(),[
      'campo' => ['required','string'],
      'valor' => ['required','string'],
    ], self::$errores,[])->after(function($validator){
      if($validator->errors()->any()) return;
      json_decode($validator->getData()['valor']);
      if(json_last_error() !== JSON_ERROR_NONE){
        return $validator->errors()->add('valor','Error '.json_last_error_msg());
      }
    })->validate();
    
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $vals_viejos = DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('campo',$request->campo)->get();
      foreach($vals_viejos as $v){
        $this->valoresPorDefecto_borrar_arr(['id_canon_valor_por_defecto' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
      }
      
      DB::table('canon_valores_por_defecto')
      ->insert([
        'campo' => $request->campo,
        'valor' => $request->valor,
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
  }
  
  public function valoresPorDefecto_borrar(Request $request){
    return $this->valoresPorDefecto_borrar_arr($request->all());
  }
  
  private function valoresPorDefecto_borrar_arr(array $arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('id_canon_valor_por_defecto',$arr['id_canon_valor_por_defecto'] ?? null)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      return 1;
    });
  }
  
  private function obtener_para_salida($id_canon,$formatear_decimal = true){
    $data = json_decode(json_encode($this->obtener_arr(compact('id_canon'))),true);
    $ret = [];
    
    $ret['canon'] = [];
    foreach(['id_canon','id_casino','created_at','created_id_usuario','deleted_at','deleted_id_usuario','usuario','es_antiguo'] as $k){
      unset($data[$k]);
    }
    foreach($data as $k => $v){
      if(!is_array($v)){
        $ret['canon'][$k] = $v;
      }
    }
    $ret['canon'] = [$ret['canon']];
    
    foreach(['id_canon_variable','id_canon'] as $k){
      foreach(($data['canon_variable'] ?? []) as $tipo => $_){
        unset($data['canon_variable'][$tipo][$k]);
      }
    }
    $ret['canon_variable'] = array_values($data['canon_variable'] ?? []);
    
    foreach(['id_canon_fijo_mesas','id_canon'] as $k){
      foreach(($data['canon_fijo_mesas'] ?? []) as $tipo => $_){
        unset($data['canon_fijo_mesas'][$tipo][$k]);
      }
    }
    $ret['canon_fijo_mesas'] = array_values($data['canon_fijo_mesas'] ?? []);
    
    foreach(['id_canon_fijo_mesas_adicionales','id_canon'] as $k){
      foreach(($data['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $_){
        unset($data['canon_fijo_mesas_adicionales'][$tipo][$k]);
      }
    }
    $ret['canon_fijo_mesas_adicionales'] = array_values($data['canon_fijo_mesas_adicionales'] ?? []);
    
    foreach(['id_archivo','id_canon'] as $k){
      foreach(($data['adjuntos'] ?? []) as $tipo => $_){
        unset($data['adjuntos'][$tipo][$k]);
      }
    }
    $ret['adjuntos'] = array_values($data['adjuntos'] ?? []);
    
    foreach($ret as $k => $d){
      if(count($d) == 0) unset($ret[$k]);
    }
    
    $SB = DB::getSchemaBuilder();
    $types = [];
    $types['canon']['saldo_anterior']  = 'decimal';
    $types['canon']['a_pagar'] = 'decimal';
    $types['canon']['diferencia'] = 'decimal';
    $types['canon']['saldo_posterior'] = 'decimal';
    foreach($ret as $tabla => $d){
      foreach($SB->getColumnListing($tabla) as $cidx => $col){
        $types[$tabla][$col] = $SB->getColumnType($tabla, $col);
      }
    }
    
    foreach($ret as $tabla => $d){
      foreach($d as $rowidx => $row){
        foreach($row as $col => $val){
          switch($types[$tabla][$col] ?? null){
            case 'smallint':
            case 'integer':
            case 'decimal': if($formatear_decimal){
              $ret[$tabla][$rowidx][$col] = formatear_decimal((string)$val);//number_format castea a float... lo hacemos a pata...
            }break;
            default:
            case 'string':{
              $ret[$tabla][$rowidx][$col] = trim($val);
            }break;
          }
        }
      }
    }
    
    return $ret;
  }
  
  public function planilla(Request $request){
    $ret = $this->obtener_para_salida($request->id_canon);
    $lineas = [];
    foreach($ret as $tipo => $arreglo){
      $lineas[] = $tipo;
      
      foreach($arreglo as $v){
        $lineas[] = csvstr(array_keys($v));
        break;
      }
      
      foreach($arreglo as $v){
        $lineas[] = csvstr($v);
      }
      
      $lineas[] = '';
    }
    
    $año_mes = $ret['canon'][0]['año_mes'];
    $casino  = $ret['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.csv";
    return \Response::make(
      implode("\r\n",$lineas), 
      200, 
      [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"'
      ]
    );
  }
  
  public function planillaPDF(Request $request){
    $datos = $this->obtener_para_salida($request->id_canon);
    $view = View::make('Canon.planillaSimple', compact('datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'landscape');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    //$dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.csv";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
  
  public function planillaDevengado(Request $request){
    if(!isset($request->id_canon)) return;
    
    $c_año_mes = DB::table('canon')
    ->select('año_mes')
    ->whereNull('deleted_at')
    ->where('id_canon',$request->id_canon)
    ->first();
    
    if(empty($c_año_mes)) return;
    
    $año_mes = explode('-',$c_año_mes->año_mes);
    
    $cs = DB::table('canon')
    ->select('id_canon')->distinct()
    ->whereNull('deleted_at')
    ->whereYear('año_mes',$año_mes[0])
    ->whereMonth('año_mes',$año_mes[1])
    ->get();
    
    $canons = [];
    foreach($cs as $c){
      $datac = $this->obtener_para_salida($c->id_canon,false);
      $canons[$datac['canon'][0]['casino']] = $datac;
    }
    
    $mes = $año_mes[1].'/'.substr($año_mes[0],2);
    
    $conceptos = [
      'Paños' => [['canon_fijo_mesas',null],['canon_fijo_mesas_adicionales',null]],
      'MTM' => [['canon_variable','Maquinas']],
      'Bingo' => [['canon_variable','Bingo']],
      'Total Físico' => [['canon_variable','Maquinas'],['canon_variable','Bingo'],['canon_fijo_mesas',null],['canon_fijo_mesas_adicionales',null]],
      'JOL' => [['canon_variable','JOL']],
      'Total' => [['canon',null]]
    ];
    
    //Agrupo segun los conceptos
    $datos = [];
    foreach($canons as $casino => $canons_casino){
      $dcas = [];
      $max_scale = 2;//Sumo usando la maxima escala posible...
      foreach($conceptos as $concepto => $matcheables){     
        $acumulado = null;
        foreach($matcheables as $matcheable){
          foreach($canons_casino as $tipo => $canons_casino_tipo){ foreach($canons_casino_tipo as $canon_casino_subtipo){
              $matchea = ($matcheable[0] === null || $matcheable[0] == $tipo) 
              && (
                ($matcheable[1] === null) || ($matcheable[1] === ($canon_casino_subtipo['tipo'] ?? null))
              );
              if(!$matchea) continue;
              
              $devengado;
              if(isset($canon_casino_subtipo['devengado'])){
                $max_scale = max($max_scale,bcscale_string($canon_casino_subtipo['devengado']));
                $devengado = $canon_casino_subtipo['devengado'];
              }
              else{
                $max_scale = max($max_scale,bcscale_string($canon_casino_subtipo['devengado_total']));
                $devengado = bcsub($canon_casino_subtipo['devengado_total'],$canon_casino_subtipo['devengado_deduccion'],$max_scale);
              }
              
              $acumulado = bcadd($acumulado ?? '0',$devengado,$max_scale);
          }}
        }
        $dcas[$concepto] = $acumulado;
      }
      
      //Calculo MTM a partir de los demas redondeados
      //Esto es asi porque MTM es el mas "aproximado", osea que que este unos centavos arriba o abajo no cambia mucho
      foreach($dcas as $concepto => $v){
        $dcas[$concepto] = $v === null? null : bcround_ndigits($v,2);
      }
      
      $valor_MTM_restante = $dcas['Total'];
      foreach($dcas as $concepto => $vr){
        if($concepto != 'MTM' && $concepto != 'Total' && $concepto != 'Total Físico' && $vr !== null){
          $valor_MTM_restante = bcsub($valor_MTM_restante,$vr,2);
        }
      }
      $dcas['MTM'] = $valor_MTM_restante;
      
      $datos[$casino] = $dcas;
    }
    
    {//Agrego una columna Total
      $total = [];
      foreach($datos as $casino => $valores_casino){
        foreach($valores_casino as $concepto => $vr){
          $total[$concepto] = $total[$concepto] ?? '0.00';
          $total[$concepto] = bcadd($vr,$total[$concepto],2);
        }
      }
      $datos['Total'] = $total;
    }
    
    foreach($datos as $casino => $valores_casino){//Formateo a español
      foreach($valores_casino as $concepto => $v){
        $datos[$casino][$concepto] = $v === null? null : formatear_decimal($v);
      }
    }
    
    $conceptos = array_keys($conceptos);
    $view = View::make('Canon.planillaDevengado', compact('conceptos','mes','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    return $dompdf->stream("Devengado-Canon-$mes.pdf", Array('Attachment'=>0));
  }
  
  public function planillaDeterminado(Request $request){
    if(!isset($request->id_canon)) return;
    return $this->planillaInforme('Canon.planillaDeterminado','determinado','devengado',$request->id_canon);
  }
  
  private function planillaInforme(string $planilla,string $tipo,string $sacar,int $id_canon){
    $datos = $this->obtener_para_salida($id_canon);
    $sacable = function($s) use ($sacar){
      return substr($s,0,strlen($sacar)) == $sacar;
    };
    $simplificable = function($s) use ($tipo){
      if(substr($s,0,strlen($tipo)) == $tipo){
        return substr($s,strlen($tipo)+1);//+1 por el guion bajo
      }
      return false;
    };
        
    foreach($datos as $c => $datos_c){
      foreach($datos_c as $tc => $datos_tc){
        unset($datos[$c][$tc]['tipo']);
        foreach($datos_tc as $k => $v){
          if($sacable($k)){
            unset($datos[$c][$tc][$k]);
          }
          $s = $simplificable($k);
          if($s !== false){
            unset($datos[$c][$tc][$k]);
            $datos[$c][$tc][$s] = $v;
          }
        }
      }
    }
    
    $view = View::make($planilla, compact('tipo','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    //$dompdf->getCanvas()->page_text(20, 815, $codigo_casino."/".$fecha, $font, 10, array(0,0,0));
    $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));
    $año_mes = $datos['canon'][0]['año_mes'];
    $casino  = $datos['canon'][0]['casino'];
    $filename = "Canon-$año_mes-$casino.pdf";
    return $dompdf->stream($filename, Array('Attachment'=>0));
  }
}
