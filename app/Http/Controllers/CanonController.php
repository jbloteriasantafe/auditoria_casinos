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
use App\Casino;

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

class CanonController extends Controller
{
  static $valoresDefecto_fallback = [
    'canon_variable' => '{"1":{"Maquinas":{"alicuota":"21","devengado_deduccion":"250000"},"Bingo":{"alicuota":"35"}},"2":{"Maquinas":{"alicuota":"25","devengado_deduccion":"500000"},"Bingo":{"alicuota":"55"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}},"3":{"Maquinas":{"alicuota":"20.56","devengado_apostado_porcentaje_aplicable":"19","devengado_apostado_porcentaje_impuesto_ley":"0.95","devengado_deduccion":"1000000"},"Bingo":{"alicuota":"78.5"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}}}',
    'canon_fijo_mesas' => '{"1":{"Fijas":{"dias_valor":30,"calcular_dias_lunes_jueves":false,"calcular_dias_viernes_sabados":false,"calcular_dias_domingos":false,"calcular_dias_todos":false,"dias_fijos":30,"mesas_fijos":15,"devengado_deduccion":"60000"}},"2":{"Diarias":{"dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"120000","mesas_lunes_jueves":16,"mesas_viernes_sabados":25,"mesas_domingos":21}},"3":{"Diarias":{"dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"240000","mesas_lunes_jueves":40,"mesas_viernes_sabados":50,"mesas_domingos":45}}}',
    'canon_fijo_mesas_adicionales' => '{"1":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Póker y RA":{"dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Truco":{"dias_mes":30,"horas_dia":16,"porcentaje":"20"}},"2":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":24,"porcentaje":"100"},"Torneos":{"dias_mes":30,"horas_dia":24,"porcentaje":"100"}},"3":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":17,"porcentaje":"100"},"Torneos":{"dias_mes":30,"horas_dia":17,"porcentaje":"100"}}}',
    'valores_confluir' => '{"1":{"valor_dolar":"1973.92","valor_euro":"2135.92"},"2":{"valor_dolar":"3287.21","valor_euro":"3215.91"},"3":{"valor_dolar":"2881.51","valor_euro":"2569.56"}}'
  ];
  static $max_scale = 64;
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
  
  public static function formatear_decimal(string $val) : string {//number_format castea a float... lo hacemos a pata...
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
      //canon
      'id_canon' => ['nullable','integer','exists:canon,id_canon,deleted_at,NULL'],
      'año_mes' => [$requireds_f('año_mes'),'regex:/^\d{4}\-((0\d)|(1[0-2]))\-01$/'],
      'id_casino' => [$requireds_f('id_casino'),'integer','exists:casino,id_casino,deleted_at,NULL'],
      'estado' => ['nullable','string','max:32'],
      'es_antiguo' => [$requireds_f('es_antiguo'),'integer','in:1,0'],
      'intereses_y_cargos' => ['nullable',$numeric_rule(2)],
      'motivo_intereses_y_cargos' => ['nullable','string','max:128'],
      'fecha_vencimiento' => ['nullable','date'],
      'interes_provincial_diario_simple' => ['nullable',$numeric_rule(4)],
      'interes_nacional_mensual_compuesto' => ['nullable',$numeric_rule(4)],
      'canon_pago.*.fecha_pago' => ['nullable','date'],
      'canon_pago.*.pago' => ['nullable',$numeric_rule(2)],
      'ajuste' => ['nullable',$numeric_rule(2)],
      'motivo_ajuste' => ['nullable','string','max:128'],
      //Valores que se "difunden" a cada subcanon >:(
      'valor_dolar' => ['nullable',$numeric_rule(2)],
      'valor_euro' => ['nullable',$numeric_rule(2)],
      'devengado_fecha_cotizacion' => ['nullable','date'],
      'devengado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'devengado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'determinado_fecha_cotizacion' => ['nullable','date'],
      'determinado_cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'determinado_cotizacion_euro' => ['nullable',$numeric_rule(2)],
      //subcanons
      'canon_variable' => 'array',
      'canon_variable.*.devengado_apostado_sistema' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.devengado_apostado_porcentaje_aplicable' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_apostado_porcentaje_impuesto_ley' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.devengado_bruto' => ['nullable',$numeric_rule(2)],
      //'canon_variable.*.devengado_total' => ['nullable',$numeric_rule(20)],
      'canon_variable.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_impuesto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.determinado_bruto' => ['nullable',$numeric_rule(2)],
      //'canon_variable.*.determinado_total' => ['nullable',$numeric_rule(20)],
      'canon_variable.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
      'canon_variable.*.alicuota' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas' => 'array',
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
      'canon_fijo_mesas.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
      'canon_fijo_mesas.*.bruto' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_ajuste' => ['nullable',$numeric_rule(22)],
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
    
    $canon_anterior = collect([]);//@RETORNADO
    if($año_mes !== null && $id_casino !== null){
      $canon_anterior = DB::table('canon')
      ->select('id_canon')
      ->whereNull('deleted_at')
      ->where('id_casino',$id_casino)
      ->where('año_mes','<',$año_mes)
      ->orderBy('año_mes','desc')
      ->first();
      
      if($canon_anterior !== null){
        $canon_anterior = $this->obtener_arr(['id_canon' => $canon_anterior->id_canon]);
      }
    }
    
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    $adjuntos = $R('adjuntos',[]);//@RETORNADO
    
    $devengado_bruto = '0.00';//@RETORNADO
    $devengado_deduccion = '0.00';//@RETORNADO
    $devengado = '0.00';//@RETORNADO
    $determinado_bruto = '0.00';//@RETORNADO
    $determinado_ajuste = '0.00';//@RETORNADO
    $determinado = '0.00';//@RETORNADO
    $canon_variable = [];//@RETORNADO
    $canon_fijo_mesas = [];//@RETORNADO
    $canon_fijo_mesas_adicionales = [];//@RETORNADO
    
    //Esto se hace asi porque originalmente se pensaba que las mesas tenian c/u fechas y cotizaciones distintas
    //despues me entere que eran la misma. De todos modos al guardarse en cada tabla de BD, facilita su recalculo en caso
    //de modificaciones al codigo y lo hace mas robusto, lo malo es que complica un poco el codigo
    //Entonces por ejemplo, si cambia la logica, podemos seguir recalculando cada subcanon independientemente de los demas
    $COT_D = ($this->valorPorDefecto('valores_confluir') ?? [])[$id_casino] ?? [];
    $COT = [
      'valor_dolar' => bcadd($R('valor_dolar',$COT_D['valor_dolar'] ?? null),'0',2),
      'valor_euro'  => bcadd($R('valor_euro',$COT_D['valor_euro'] ?? null),'0',2),
      'devengado_fecha_cotizacion'   => $R('devengado_fecha_cotizacion',null),
      'devengado_cotizacion_dolar'   => $R('devengado_cotizacion_dolar',null),
      'devengado_cotizacion_euro'    => $R('devengado_cotizacion_euro',null),
      'determinado_fecha_cotizacion' => $R('determinado_fecha_cotizacion',null),
      'determinado_cotizacion_dolar' => $R('determinado_cotizacion_dolar',null),
      'determinado_cotizacion_euro'  => $R('determinado_cotizacion_euro',null),
    ];
    
    if($año_mes !== null && $año_mes !== '' && ($COT['devengado_fecha_cotizacion'] === null || $COT['determinado_fecha_cotizacion'] === null)){
      $f = explode('-',$año_mes);
      
      $f[0] = $f[1] == '12'? intval($f[0])+1 : $f[0];
      $f[1] = $f[1] == '12'? '01' : str_pad(intval($f[1])+1,2,'0',STR_PAD_LEFT);
      
      if($COT['devengado_fecha_cotizacion'] === null){
        $COT['devengado_fecha_cotizacion'] = implode('-',$f);
      }
      
      if($COT['determinado_fecha_cotizacion'] === null){
        $f[2] = '09';
        $f = implode('-',$f);
        $f = new \DateTimeImmutable($f);
        $viernes_anterior = clone $f;
        for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
          $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
        }
        $COT['determinado_fecha_cotizacion'] = $viernes_anterior->format('Y-m-d');//@RETORNADO
      }
    }
    
    if($COT['devengado_fecha_cotizacion'] !== null){
      $COT['devengado_cotizacion_dolar'] = $COT['devengado_cotizacion_dolar'] ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],2,$id_casino) ?? '0';
      $COT['devengado_cotizacion_euro']  = $COT['devengado_cotizacion_euro']  ?? $this->cotizacion($COT['devengado_fecha_cotizacion'],3,$id_casino) ?? '0';
    }
    
    if($COT['determinado_fecha_cotizacion'] !== null){
      $COT['determinado_cotizacion_dolar'] = $COT['determinado_cotizacion_dolar'] ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],2,$id_casino) ?? '0';
      $COT['determinado_cotizacion_euro']  = $COT['determinado_cotizacion_euro']  ?? $this->cotizacion($COT['determinado_fecha_cotizacion'],3,$id_casino) ?? '0';
    }
    
    {
      $ret = [
        'canon_variable' => [],//Varios tipos (JOL, Bingo, Maquinas)
        'canon_fijo_mesas' => [],//Dos tipos muy parecidos (Fijas y Diarias), se hace asi mas que nada para que sea homogeneo
        'canon_fijo_mesas_adicionales' => []//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
      ];
      
      foreach($ret as $subcanon => &$retsc){
        $defecto = ($this->valorPorDefecto($subcanon) ?? [])[$id_casino] ?? [];
        $subcanon_anterior = $canon_anterior[$subcanon] ?? [];
        foreach(($request[$subcanon] ?? $defecto ?? []) as $tipo => $_){
          $data_request_tipo = ($request[$subcanon] ?? [])[$tipo] ?? [];
          $retsc[$tipo] = $this->{$subcanon.'_recalcular'}(
            $año_mes,
            $id_casino,
            $es_antiguo,
            $tipo,
            $defecto[$tipo] ?? [],
            $data_request_tipo,
            $COT,
            $subcanon_anterior[$tipo] ?? []
          );
          
          if($retsc[$tipo]['devengar'] ?? 1){
            $devengado_deduccion = bcadd($devengado_deduccion,$retsc[$tipo]['devengado_deduccion'] ?? '0',2);
            $devengado_bruto = bcadd($devengado_bruto,$retsc[$tipo]['devengado_total'] ?? '0',22);
            $devengado = bcadd($devengado,$retsc[$tipo]['devengado'] ?? 0,22);
          }
          
          $determinado_ajuste = bcadd($determinado_ajuste,$retsc[$tipo]['determinado_ajuste'] ?? '0',22);
          $determinado_bruto = bcadd($determinado_bruto,$retsc[$tipo]['determinado_total'] ?? '0',22);
          $determinado = bcadd($determinado,$retsc[$tipo]['determinado'] ?? '0',22);
        }
      }
      
      $canon_variable = $ret['canon_variable'];
      $canon_fijo_mesas = $ret['canon_fijo_mesas'];
      $canon_fijo_mesas_adicionales = $ret['canon_fijo_mesas_adicionales'];
    }
    
    $COT = $this->confluir_datos_cotizacion(compact('canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales'));
    $valor_dolar = $COT['valor_dolar'] ?? null;//@RETORNADO
    $valor_euro  = $COT['valor_euro'] ?? null;//@RETORNADO
    $devengado_fecha_cotizacion = $COT['devengado_fecha_cotizacion'] ?? null;//@RETORNADO
    $devengado_cotizacion_dolar = $COT['devengado_cotizacion_dolar'] ?? null;//@RETORNADO
    $devengado_cotizacion_euro  = $COT['devengado_cotizacion_euro'] ?? null;//@RETORNADO
    $determinado_fecha_cotizacion = $COT['determinado_fecha_cotizacion'] ?? null;//@RETORNADO
    $determinado_cotizacion_dolar = $COT['determinado_cotizacion_dolar'] ?? null;//@RETORNADO
    $determinado_cotizacion_euro  = $COT['determinado_cotizacion_euro'] ?? null;//@RETORNADO
    
    $devengado   = bcround_ndigits($devengado,2);//@RETORNADO
    $determinado = bcround_ndigits($determinado,2);//@RETORNADO
    
    $porcentaje_seguridad = bccomp($devengado,'0.00',2) <> 0?//@RETORNADO
       bcdiv(bcmul('100',bcsub($determinado,$devengado,2),2),$devengado,19)
      : '0.00';
      
    //porcentaje_seguridad es DECIMAL(41,19)
    //da 22 digitos decimales y 19 de precision (sacando divisiones periodicas o irracionales que se truncan)
    //esto es porque
    //MAX porcentaje_seguridad = 100 * MAX num / MIN num = 100 * 9...9[18].99 / 0.01 = 100 * 9....9[20] -> 22
    //MIN porcentaje_seguridad = 100 * MIN num / MAX num = 100 * 0.01 / 9...9[18].99 > 100 * 0.01 / 10**19 = 10**(-19) -> 19
    $MAX_PORCENTAJE_SEGURIDAD = str_repeat('9',22).'.'.str_repeat('9',19);//El maximo posible es este... lo clampeo por las dudas
    $porcentaje_seguridad = bcclamp($porcentaje_seguridad,
      '-'.$MAX_PORCENTAJE_SEGURIDAD,
      $MAX_PORCENTAJE_SEGURIDAD,
      bcscale_string($MAX_PORCENTAJE_SEGURIDAD)
    );
    
    //PRINCIPAL
    $c_ant = DB::table('canon')
    ->where('año_mes','<',$año_mes)
    ->where('id_casino','=',$id_casino)
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->first();
        
    $saldo_anterior = ($c_ant !== null)? $c_ant->saldo_posterior : '0';//@RETORNADO
    $saldo_anterior_cerrado = $saldo_anterior;//@RETORNADO
    
    $intereses_y_cargos = bcadd($R('intereses_y_cargos','0'),'0',2);//@RETORNADO
    $motivo_intereses_y_cargos = $R('motivo_intereses_y_cargos','');//@RETORNADO
    $principal = bcsub(bcadd($determinado,$intereses_y_cargos,2),$saldo_anterior_cerrado,2);//@RETORNADO
    
    //PAGOS
    $canon_pago = $request['canon_pago'] ?? [[]];//Si no tiene pagos le agrego uno vacio.
    {//Manteno las keys y el orden de las keys... importante para el front cuando se borra/cambia fecha etc
      $ordenado_por_fecha = json_decode(json_encode($canon_pago),true);
      usort($ordenado_por_fecha,function($a,$b){//Lo ordeno por fecha de pago
        $fa = $a['fecha_pago'] ?? null;
        $fb = $b['fecha_pago'] ?? null;
        
        if(!empty($fa) &&  empty($fb)) return -1;
        if( empty($fa) && !empty($fb)) return  1;
        if( empty($fa) &&  empty($fb)){
          return 0;
        }
        return $fa <= $fb? -1 : 1;
      });
      $keys = array_keys($canon_pago);
      foreach($ordenado_por_fecha as $idx => $v){
        $canon_pago[$keys[$idx]] = $v;
      }
    }
    
    $a_pagar = $principal;//@RETORNADO
    $pago = '0';//@RETORNADO
    $canon_pago_defecto = ($this->valorPorDefecto('canon_pago') ?? [])[$id_casino] ?? [];
    
    $PAG = [
      'interes_provincial_diario_simple' => $R(
        'interes_provincial_diario_simple',
        $canon_pago_defecto['interes_provincial_diario_simple'] ?? '0'
      ),
      'interes_nacional_mensual_compuesto' => $R(
        'interes_nacional_mensual_compuesto',
        $canon_pago_defecto['interes_nacional_mensual_compuesto'] ?? '0'
      ),
      'fecha_vencimiento' => $R('fecha_vencimiento',null),
    ];
      
    if($año_mes !== null && $año_mes !== '' && $PAG['fecha_vencimiento'] === null){
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
      $PAG['fecha_vencimiento'] = $proximo_lunes->format('Y-m-d');//@RETORNADO
    }
    
    $timestamp_venc = $PAG['fecha_vencimiento']?
      \DateTimeImmutable::createFromFormat('Y-m-d', $PAG['fecha_vencimiento'])
    : null;
    $factor_interes_provincial_diario_simple = bcdiv($PAG['interes_provincial_diario_simple'],'100',6);
    $factor_interes_nacional_mensual_compuesto = bcdiv($PAG['interes_nacional_mensual_compuesto'],'100',6);
    
    $restante = $principal;
    foreach($canon_pago as $idx => &$p){
      $p['capital'] = $restante;
      $p['fecha_pago'] = $p['fecha_pago'] ?? $PAG['fecha_vencimiento'] ?? null;
      $p['fecha_vencimiento'] = $PAG['fecha_vencimiento'] ?? null;
      $p['interes_provincial_diario_simple'] = $PAG['interes_provincial_diario_simple'] ?? null;
      $p['interes_nacional_mensual_compuesto'] = $PAG['interes_nacional_mensual_compuesto'] ?? null;
      
      if($timestamp_venc && $p['fecha_pago'] != null){
        $timestamp_pago = \DateTimeImmutable::createFromFormat('Y-m-d', $p['fecha_pago']);
        $date_interval  = $timestamp_pago->diff($timestamp_venc);
        $p['dias_vencidos'] = ($timestamp_pago <= $timestamp_venc)? 0
        : intval($date_interval->days);
      }
      else{
        $p['dias_vencidos'] = 0;
      }
      
      $p['mora_provincial'] = bcmul($p['dias_vencidos'],bcmul($p['capital'],$factor_interes_provincial_diario_simple,8),8);
      $p['mora_provincial'] = bcround_ndigits($p['mora_provincial'],2);
      $p['mora_provincial'] = bcmax($p['mora_provincial'],'0',2);
      
      $p['mora_nacional'] = '0';
      {
        $capitalizaciones = intdiv($p['dias_vencidos'],30);
        $capital_final    = $p['capital'];
        $interes_nacional = bcadd('1',$factor_interes_nacional_mensual_compuesto,6);
        $digitos_capital_final = 2;
        for($c=0;$c<$capitalizaciones;$c++){
          $capital_final = bcmul($capital_final,$interes_nacional,$digitos_capital_final+6);
        }
        $p['mora_nacional'] = bcsub($capital_final,$p['capital'],$digitos_capital_final);
        $p['mora_nacional'] = bcround_ndigits($p['mora_nacional'],2);
      }
      $p['mora_nacional'] = bcmax($p['mora_nacional'],'0',2);
      
      $p['a_pagar'] = bcadd($p['capital'],$p['mora_provincial'],2);
      $p['a_pagar'] = bcadd($p['a_pagar'],$p['mora_nacional'],2);
      $p['pago'] = $p['pago'] ?? '0';
      $p['diferencia'] = bcsub($p['a_pagar'],$p['pago'],2);
      
      $a_pagar = bcadd($a_pagar,$p['mora_provincial'],2);
      $a_pagar = bcadd($a_pagar,$p['mora_nacional'],2);
      $pago = bcadd($pago,$p['pago'],2);
      $restante = $p['diferencia'];
    }
    
    $PAG = $this->confluir_datos_pago(compact('canon_pago'));
    $interes_provincial_diario_simple   = $PAG['interes_provincial_diario_simple'] ?? null;//@RETORNADO
    $interes_nacional_mensual_compuesto = $PAG['interes_nacional_mensual_compuesto'] ?? null;//@RETORNADO
    $fecha_vencimiento                  = $PAG['fecha_vencimiento'] ?? null;//@RETORNADO
    
    $ajuste = bcadd($R('ajuste','0.00'),'0',2);//@RETORNADO
    $motivo_ajuste = $R('motivo_ajuste','');//@RETORNADO
    $diferencia = bcadd(bcsub($a_pagar,$pago,2),$ajuste,2);//@RETORNADO
    $saldo_posterior = bcsub('0',$diferencia,2);//@RETORNADO @HACK: Lo mismo que diferencia? el saldo ya esta en el a_pagar
    $saldo_posterior_cerrado = $saldo_posterior;//@RETORNADO
    
    return compact(
      'canon_anterior',
      'año_mes','id_casino','estado','es_antiguo',
      'canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales','adjuntos',
      
      //Confluidos
      'valor_dolar','valor_euro',
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      
      'devengado_bruto','devengado_deduccion','devengado',
      'determinado_bruto','determinado_ajuste','determinado','porcentaje_seguridad',
      'saldo_anterior','saldo_anterior_cerrado',
      'intereses_y_cargos','motivo_intereses_y_cargos','principal',
      //Confluidos
      'fecha_vencimiento','interes_provincial_diario_simple','interes_nacional_mensual_compuesto',
      //Pagos
      'canon_pago',
      'a_pagar','pago','ajuste','motivo_ajuste','diferencia',
      'saldo_posterior','saldo_posterior_cerrado'
    );
  }
  
  public function canon_variable_recalcular($año_mes,$id_casino,$es_antiguo,$tipo,$valores_defecto,$data,$COT,$anterior){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $devengado_apostado_sistema = bcadd($R('devengado_apostado_sistema',$this->apostado($tipo,$año_mes,$id_casino)),'0',2);//@RETORNADO    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,8);//2+6 @RETORNADO
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $devengado_impuesto   = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,14);//8+6 @RETORNADO
    $determinado_impuesto =  bcadd($R('determinado_impuesto','0.00'),'0',14);//@RETORNADO
    
    $devengado_bruto   = $R('devengado_bruto',null);//@RETORNADO
    $determinado_bruto = $R('determinado_bruto',null);//@RETORNADO
    if($devengado_bruto === null || $determinado_bruto === null){
      $bruto = $this->bruto($tipo,$año_mes,$id_casino);
      $devengado_bruto   = $devengado_bruto   ?? $bruto;
      $determinado_bruto = $determinado_bruto ?? $bruto;
    }
    
    $devengado_bruto   = bcadd($devengado_bruto,'0',2);
    $determinado_bruto = bcadd($determinado_bruto,'0',2);
    
    $devengado_subtotal   = bcsub($devengado_bruto,$devengado_impuesto,14);//@RETORNADO
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,14);//@RETORNADO
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $devengado_total   =  bcmul($devengado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $determinado_total =  bcmul($determinado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',20);
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
    
    $devengado = bcsub($devengado_total,$devengado_deduccion,20);
    $determinado = bcadd($determinado_total,$determinado_ajuste,20);
    
    return compact('tipo',
      'alicuota','devengar',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'devengado_bruto','devengado_impuesto','devengado_subtotal','devengado_total','devengado_deduccion',
      'devengado',
      'determinado_impuesto','determinado_bruto','determinado_subtotal','determinado_total','determinado_ajuste',
      'determinado'
    );
  }
  
  public function canon_fijo_mesas_recalcular(
      $año_mes,
      $id_casino,
      $es_antiguo,
      $tipo,//@RETORNADO
      $valores_defecto,
      $data,
      $COT,
      $anterior
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $devengado_fecha_cotizacion = $COT['devengado_fecha_cotizacion'] ?? null;//@RETORNADO
    $determinado_fecha_cotizacion = $COT['determinado_fecha_cotizacion'] ?? null;//@RETORNADO
    $devengado_cotizacion_dolar = $COT['devengado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $devengado_cotizacion_euro = $COT['devengado_cotizacion_euro'] ?? '0';//@RETORNADO
    $determinado_cotizacion_dolar = $COT['determinado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $determinado_cotizacion_euro = $COT['determinado_cotizacion_euro'] ?? '0';//@RETORNADO
    
    $valor_dolar = $COT['valor_dolar'] ?? null;//@RETORNADO
    $valor_euro  = $COT['valor_euro']  ?? null;//@RETORNADO
    
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
      
      //Esto en teoria aumenta la precision pero puede introducir errores de +-1... prefiero desactivarlo
      /*
      //Si es menor o igual a la mitad... lo hago como esta la formula arriba
      //Si es mayor a la mitad lo hago restando desde un multiplo mas... para disminuir el error de truncamiento
      if($mesas_dias_restantes > ($dias_valor/2.0)){
        $mesas_meses += 1;
        $mesas_dias_restantes = -($dias_valor-$mesas_dias_restantes);
      }
      */
      
      $devengado_total_dolar_cotizado = bcmul($devengado_valor_dolar_cotizado,$mesas_meses,4);
      $devengado_total_euro_cotizado  = bcmul($devengado_valor_euro_cotizado,$mesas_meses,4);
      $determinado_total_dolar_cotizado = bcmul($determinado_valor_dolar_cotizado,$mesas_meses,4);
      $determinado_total_euro_cotizado  = bcmul($determinado_valor_euro_cotizado,$mesas_meses,4);
      
      $devengado_total_dolar_cotizado = bcadd($devengado_total_dolar_cotizado,bcmul($devengado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $devengado_total_euro_cotizado  = bcadd($devengado_total_euro_cotizado,bcmul($devengado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_dolar_cotizado = bcadd($determinado_total_dolar_cotizado,bcmul($determinado_valor_dolar_diario_cotizado,$mesas_dias_restantes,16),16);
      $determinado_total_euro_cotizado  = bcadd($determinado_total_euro_cotizado,bcmul($determinado_valor_euro_diario_cotizado,$mesas_dias_restantes,16),16);
    }
    
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',16);//@RETORNADO
    $devengado_total   = bcadd($devengado_total_dolar_cotizado,$devengado_total_euro_cotizado,16);//@RETORNADO
    $determinado_total = bcadd($determinado_total_dolar_cotizado,$determinado_total_euro_cotizado,16);//@RETORNADO
    $bruto = bcadd($R('bruto',$this->bruto($tipo,$año_mes,$id_casino)),'0',2);//@RETORNADO

    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,16);
    $determinado = bcadd($determinado_total,$determinado_ajuste,16);
    
    return compact(
      'tipo','dias_valor','factor_dias_valor','valor_dolar','valor_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'mesas_dias','bruto',
      'devengar',
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_dolar_cotizado','devengado_valor_euro_cotizado',
      'devengado_valor_dolar_diario_cotizado','devengado_valor_euro_diario_cotizado',
      'devengado_total_dolar_cotizado','devengado_total_euro_cotizado','devengado_total',
      'devengado_deduccion','devengado',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_dolar_cotizado','determinado_valor_euro_cotizado',
      'determinado_valor_dolar_diario_cotizado','determinado_valor_euro_diario_cotizado',
      'determinado_total_dolar_cotizado','determinado_total_euro_cotizado','determinado_total',
      'determinado_ajuste','determinado'
    );
  }
  
  public function canon_fijo_mesas_adicionales_recalcular(
    $año_mes,
    $id_casino,
    $es_antiguo,
    $tipo,
    $valores_defecto,
    $data,
    $COT,
    $anterior
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] === '') || ($data[$s] === []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] === '') || ($valores_defecto[$s] === []))? $dflt : $valores_defecto[$s];
    };
    $A = function($s,$dflt = null) use (&$anterior){
      return (($anterior[$s] ?? null) === null || ($anterior[$s] === '') || ($anterior[$s] === []))? $dflt : $anterior[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $RAD = function($s,$dflt = null) use ($R,$A,$D){
      return $R($s,null) ?? $A($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    
    $factor_dias_mes  = ($dias_mes != 0)? bcdiv('1',$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    $factor_horas_mes = ($horas_dia != 0 && $dias_mes != 0)? bcdiv('1',$horas_dia*$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $valor_dolar = $COT['valor_dolar'] ?? null;//@RETORNADO
    $valor_euro  = $COT['valor_euro']  ?? null;//@RETORNADO
    
    $horas = $R('horas',0);//@RETORNADO
    $mesas = $R('mesas',0);//@RETORNADO
    if($horas != 0) $mesas = 0;
    if($mesas != 0) $horas = 0;
    
    $porcentaje = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    $factor_porcentaje = bcdiv($porcentaje,'100',6);
        
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $devengado_fecha_cotizacion = $COT['devengado_fecha_cotizacion'] ?? null;//@RETORNADO
    $determinado_fecha_cotizacion = $COT['determinado_fecha_cotizacion'] ?? null;//@RETORNADO
    $devengado_cotizacion_dolar = $COT['devengado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $devengado_cotizacion_euro = $COT['devengado_cotizacion_euro'] ?? '0';//@$RETORNADO
    $determinado_cotizacion_dolar = $COT['determinado_cotizacion_dolar'] ?? '0';//@RETORNADO
    $determinado_cotizacion_euro = $COT['determinado_cotizacion_euro'] ?? '0';//@RETORNADO
    
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
      $horas_aux = $horas != 0? $horas : bcmul($mesas,$horas_dia,0);
      $horas_mes = $horas_dia*$dias_mes;
      
      $meses = intdiv($horas_aux,$horas_mes);
      $horas_dias_restantes = $horas_aux%$horas_mes;
      
      $dias = intdiv($horas_dias_restantes,$horas_dia);
      $horas_restantes = $horas_dias_restantes%$horas_dia;
      
      $devengado_total_meses = bcmul($devengado_valor_mes,$meses,4);
      $devengado_total_dias  = bcmul($devengado_valor_dia,$dias,16);
      $devengado_total_horas = bcmul($devengado_valor_hora,$horas_restantes,16);
      $determinado_total_meses = bcmul($determinado_valor_mes,$meses,4);
      $determinado_total_dias  = bcmul($determinado_valor_dia,$dias,16);
      $determinado_total_horas = bcmul($determinado_valor_hora,$horas_restantes,16);
      
      $devengado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($devengado_total_sin_aplicar_porcentaje,$devengado_total_meses,16),$devengado_total_dias,16),$devengado_total_horas,16
      );
      $determinado_total_sin_aplicar_porcentaje = bcadd(
        bcadd(bcadd($determinado_total_sin_aplicar_porcentaje,$determinado_total_meses,16),$determinado_total_dias,16),$determinado_total_horas,16
      );
    }
        
    $devengado_total = bcmul($devengado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    $determinado_total = bcmul($determinado_total_sin_aplicar_porcentaje,$factor_porcentaje,22);//16+6 @RETORNADO
    
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste = bcadd($RD('determinado_ajuste','0.00'),'0',22);//@RETORNADO
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,22);
    $determinado = bcadd($determinado_total,$determinado_ajuste,22);
    
    return compact(
      'tipo',
      'dias_mes','horas_dia','factor_dias_mes','factor_horas_mes',
      'valor_dolar','valor_euro',
      'horas','mesas','porcentaje',
      'devengar',
      'devengado_fecha_cotizacion','devengado_cotizacion_dolar','devengado_cotizacion_euro',
      'devengado_valor_mes','devengado_valor_dia','devengado_valor_hora',
      'devengado_total','devengado_deduccion',
      'devengado',
      
      'determinado_fecha_cotizacion','determinado_cotizacion_dolar','determinado_cotizacion_euro',
      'determinado_valor_mes','determinado_valor_dia','determinado_valor_hora',
      'determinado_total','determinado_ajuste',
      'determinado'
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
        $datos['adjuntos'] = $request['adjuntos'] ?? [];
      }
      
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $canon_anterior = ($datos['año_mes'] !== null && $datos['id_casino'] !== null)?
        DB::table('canon')//Necesito la variable para despues sacarle los archivos
        ->select('id_canon')
        ->whereNull('deleted_at')
        ->where('año_mes',$datos['año_mes'])
        ->where('id_casino',$datos['id_casino'])
        ->orderBy('created_at','desc')
        ->get()
      : [];
      
      foreach($canon_anterior as $c){
        $this->borrar_arr(['id_canon' => $c->id_canon],$created_at,$id_usuario);
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
        'determinado_bruto' => $datos['determinado_bruto'],
        'determinado_ajuste' => $datos['determinado_ajuste'],
        'determinado' => $datos['determinado'],
        'saldo_anterior' => $datos['saldo_anterior'],
        'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
        'intereses_y_cargos' => $datos['intereses_y_cargos'],
        'motivo_intereses_y_cargos' => $datos['motivo_intereses_y_cargos'],
        'principal' => $datos['principal'],
        'a_pagar' => $datos['a_pagar'],
        'pago' => $datos['pago'],
        'ajuste' => $datos['ajuste'],
        'motivo_ajuste' => $datos['motivo_ajuste'],
        'diferencia' => $datos['diferencia'],
        'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
        'saldo_posterior' => $datos['saldo_posterior'],
        'es_antiguo' => $datos['es_antiguo'],
        'created_at' => $created_at,
        'created_id_usuario' => $id_usuario,
      ]);
      
      foreach(($datos['canon_pago'] ?? []) as $idx => $d){
        DB::table('canon_pago')
        ->insert([
          'id_canon' => $id_canon,
          'capital' => $d['capital'],
          'fecha_vencimiento' => $d['fecha_vencimiento'],
          'fecha_pago' => $d['fecha_pago'],
          'dias_vencidos' => $d['dias_vencidos'],
          'interes_provincial_diario_simple' => $d['interes_provincial_diario_simple'],
          'interes_nacional_mensual_compuesto' => $d['interes_nacional_mensual_compuesto'],
          'mora_provincial' => $d['mora_provincial'],
          'mora_nacional' => $d['mora_nacional'],
          'a_pagar' => $d['a_pagar'],
          'pago' => $d['pago'],
          'diferencia' => $d['diferencia'],
        ]);
      }
      
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
        $archivos_existentes = count($canon_anterior) == 0? 
          collect([])
        : DB::table('canon_archivo as ca')
        ->select('ca.descripcion','ca.type','a.*')
        ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
        ->where('id_canon',$canon_anterior[0]->id_canon)
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
      
      if($recalcular){
        $this->cambio_canon_recalcular_saldos($id_canon);
      }
      
      return 1;
    });
  }
  
  //@HACK: usar CoW/SoftDelete?
  private function recalcular_saldos($saldo_posterior_prev,$año_mes,$id_casino){
    $canons = DB::table('canon')
    ->whereNull('deleted_at')
    ->where('año_mes','>',$año_mes)
    ->where('id_casino','=',$id_casino)
    ->orderBy('año_mes','asc')->get();
    
    if(count($canons) <= 0) return;
        
    foreach($canons as $c){
      //Si esta cerrado, solo actualizo los saldos "no cerrados" y que se use en un canon proximo
      if(in_array(strtoupper($c->estado),['PAGADO','CERRADO'])){
        $c->saldo_anterior = $saldo_posterior_prev;
        $diffsaldos = bcsub($c->saldo_anterior,$c->saldo_anterior_cerrado,2);
        $c->saldo_posterior = bcadd($c->saldo_posterior_cerrado,$diffsaldos,2);
        
        DB::table('canon')
        ->where('id_canon',$c->id_canon)
        ->update([
          'saldo_anterior' => $c->saldo_anterior,
          'saldo_posterior' => $c->saldo_posterior
        ]);
        
        $saldo_posterior_prev = $c->saldo_posterior;
      }
      else{//El saldo influye en el principal y por ende en todos los calculos de pagos
        $c_para_recalcular = $this->obtener_arr(['id_canon' => $c->id_canon]);
        $c_para_recalcular['saldo_anterior'] = $saldo_posterior_prev;
        $c_para_recalcular['saldo_anterior_cerrado'] = $saldo_posterior_prev;
                
        $datos = $this->recalcular($c_para_recalcular);
        
        DB::table('canon')
        ->where('id_canon',$c->id_canon)
        ->update([
          'saldo_anterior' => $datos['saldo_anterior'],
          'saldo_anterior_cerrado' => $datos['saldo_anterior_cerrado'],
          'principal' => $datos['principal'],
          'a_pagar' => $datos['a_pagar'],
          'diferencia' => $datos['diferencia'],
          'saldo_posterior_cerrado' => $datos['saldo_posterior_cerrado'],
          'saldo_posterior' => $datos['saldo_posterior'],
        ]);
        
        $pagos_bd = DB::table('canon_pago')
        ->where('id_canon',$c->id_canon)
        ->get()->keyBy('id_canon_pago');
        
        $pagos_actualizados = collect($datos['canon_pago'])
        ->keyBy('id_canon_pago');
        
        assert($pagos_bd->keys()->sort() == $pagos_actualizados->keys()->sort());
        
        foreach($pagos_bd as $id_canon_pago => $pbd){
          $pact = $pagos_actualizados[$id_canon_pago];
          
          DB::table('canon_pago')
          ->where('id_canon_pago',$id_canon_pago)
          ->update([
            'capital' => $pact['capital'],
            'mora_provincial' => $pact['mora_provincial'],
            'mora_nacional' => $pact['mora_nacional'],
            'a_pagar' => $pact['a_pagar'],
            'diferencia' => $pact['diferencia']
          ]);
        }
        
        $saldo_posterior_prev = $datos['saldo_posterior'];
      }
    }
  }
  
  public function recalcular_saldos_Req(Request $request){
    DB::transaction(function(){
      foreach(Casino::all() as $c){
        $this->recalcular_saldos('0','1970-01-01',$c->id_casino);
      }
      return 1;
    });
  }
  
  public function obtener_arr(array $request,$confluir = true){
    $ret = (array) DB::table('canon as c')
    ->select('cas.nombre as casino','c.*','u.user_name as usuario')
    ->join('usuario as u','u.id_usuario','=','c.created_id_usuario')
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->where('id_canon',$request['id_canon'])
    ->first();
    
    $ret['canon_pago'] = DB::table('canon_pago')
    ->where('id_canon',$request['id_canon'])
    ->orderBy('fecha_pago','asc')
    ->get();
        
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
    
    $ret = json_decode(json_encode($ret),true);
    
    if($confluir){
      $COT = $this->confluir_datos_cotizacion($ret);
      $PAG = $this->confluir_datos_pago($ret);
      foreach($COT as $k => $v) $ret[$k] = $v;
      foreach($PAG as $k => $v) $ret[$k] = $v;
    }
    
    return !empty($ret)? $ret : $this->recalcular($ret);
  }
  
  private function confluir_datos(array $canon,array $tablas,array $atributos){
    $ret = [];
    //Obtengo data de cotización, si no es uniforme devuelvo nulo
    foreach($tablas as $tabla){
      foreach($atributos as $attr){
        foreach($canon[$tabla] as $tipo => $data_tabla){
          if(!isset($data_tabla[$attr])) continue;
          $val = $data_tabla[$attr];
          if(isset($ret[$attr])){//Si es distinto, hay conflicto y pongo en nulo
            $ret[$attr] = $val != $ret[$attr]? null : $val;
          }
          else{
            $ret[$attr] = $val;
          }
        }
      }
    }
    return $ret;
  }
  
  private function confluir_datos_cotizacion(array $canon){
    return $this->confluir_datos(
      $canon,
      ['canon_variable','canon_fijo_mesas_adicionales','canon_fijo_mesas_adicionales'],
      [
        'valor_dolar','valor_euro','devengado_fecha_cotizacion',
        'determinado_fecha_cotizacion',
        'devengado_cotizacion_dolar','devengado_cotizacion_euro',
        'determinado_cotizacion_dolar','determinado_cotizacion_euro'
      ]
    );
  }
  
  private function confluir_datos_pago(array $canon){
    return $this->confluir_datos(
      $canon,
      ['canon_pago'],
      ['fecha_vencimiento','interes_provincial_diario_simple','interes_nacional_mensual_compuesto']
    );
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
    $check_estado = !$u->es_superusuario;
    
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL']
    ], ['exists' => 'No existe Canon eliminable'],[])->after(function($validator) use ($check_estado){
      if($validator->errors()->any()) return;
      $estado_bd = DB::table('canon')
      ->where('id_canon',$validator->getData()['id_canon'])
      ->whereNull('deleted_at')
      ->select('estado')
      ->first()
      ->estado;
      if($check_estado && !in_array($estado_bd,['Generado','Pagado'])){
        return $validator->errors()->add('estado','No puede borrar un Canon en estado '.$estado_bd);
      }
    })->validate();
    
    return $this->borrar_arr($request->all());
  }
  
  public function borrar_arr(array $arr,$deleted_at = null,$deleted_id_usuario = null){
    return DB::transaction(function() use ($arr,$deleted_at,$deleted_id_usuario){
      $deleted_at = $deleted_at ?? date('Y-m-d h:i:s');
      $deleted_id_usuario = $deleted_id_usuario ?? UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      $id_canon = $arr['id_canon'];
      
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$id_canon)
      ->update(compact('deleted_at','deleted_id_usuario'));
      
      $this->cambio_canon_recalcular_saldos($id_canon);
      
      return 1;
    });
  }
  
  private function cambio_canon_recalcular_saldos($id_canon){
    $c = DB::table('canon')
    ->where('id_canon',$id_canon)
    ->first();
    
    $cprev = DB::table('canon')
    ->where('año_mes','<',$c->año_mes)
    ->where('id_casino','=',$c->id_casino)
    ->whereNull('deleted_at')
    ->orderBy('año_mes','desc')
    ->first();
    
    if($cprev === null){
      $this->recalcular_saldos('0','1970-01-01',$c->id_casino);
    }
    else{
      $this->recalcular_saldos($cprev->saldo_posterior,$cprev->año_mes,$c->id_casino);
    }
  }
  
  public function buscar(Request $request,bool $paginar = true){
    $u = UsuarioController::getInstancia()->quienSoy()['usuario'];
    $reglas = [];
    if(isset($request->id_casino)){
      $reglas[] = ['c.id_casino','=',$request->id_casino];
    }
    
    $desde = date('Y-m-d');
    $hasta = $desde;
    {
      $minmax = DB::table('canon')->selectRaw('MIN(año_mes) as min_año_mes,MAX(año_mes) max_año_mes')
      ->groupBy(DB::raw('"constant"'))->first();
      if($minmax !== null){
        $desde = $minmax->min_año_mes;
        $hasta = $minmax->max_año_mes;
      }
    }
    if(isset($request->año_mes)){
      $desde = isset($request->año_mes[0])? $request->año_mes[0].'-01' : $desde;
      $hasta = isset($request->año_mes[1])? $request->año_mes[1].'-01' : $hasta;
    }
    $reglas[] = ['año_mes','>=',$desde];
    $reglas[] = ['año_mes','<=',$hasta];
    
    $sort_by = [
      'columna' => 'año_mes',
      'orden' => 'desc'
    ];
    
    if(!empty($request->sort_by) && !empty($request->sort_by['columna'])){
      $sort_by['columna'] = $request->sort_by['columna'];
      if(!empty($request->sort_by['orden'])){
        $sort_by['orden'] = $request->sort_by['orden'];
      }
    }
    
    $ret = DB::table('canon as c')
    ->select('c.id_canon','c.deleted_at',
      DB::raw('IF(c.es_antiguo,"ANT","") as antiguo'),
      DB::raw('DATE_FORMAT(c.año_mes,"%Y-%m") as año_mes'),
      'cas.nombre as casino','c.estado','c.devengado','c.determinado',
      DB::raw('(
        c.intereses_y_cargos
        +(
          SELECT SUM(mora_provincial)+SUM(mora_nacional)
          FROM canon_pago as cp
          WHERE cp.id_canon = c.id_canon
          GROUP BY "constant"
          LIMIT 1
        )
      ) as intereses_y_cargos'),
      'c.pago','c.saldo_posterior'
    )
    ->join('casino as cas','cas.id_casino','=','c.id_casino')
    ->whereRaw(($u->es_superusuario && ($request->eliminados ?? false))?
      'NOT EXISTS (
        SELECT * 
        FROM canon c2 
        WHERE c2.id_casino = c.id_casino 
        AND   c2.año_mes   = c.año_mes 
        AND   (c2.deleted_at IS NULL OR c2.created_at > c.created_at)
        LIMIT 1
       )
       AND c.deleted_at IS NOT NULL'
    : 'c.deleted_at IS NULL'
    )
    ->where($reglas)
    ->whereIn('c.id_casino',$u->casinos->pluck('id_casino'))
    ->orderBy($sort_by['columna'],$sort_by['orden'])
    ->orderBy('cas.nombre','asc');
    
    if($paginar){
      $ret = $ret->paginate($request->page_size ?? 10);
    }
    else {
      $ret = $ret->get();
    }
    
    return $ret;
  }
    
  private $cotizacion_DB = null;
  private function cotizacion($fecha_cotizacion,$id_tipo_moneda,$id_casino){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return '0';
    if($id_tipo_moneda == 1){
      return 1;
    }
    
    if($this->cotizacion_DB === null){//Armo cotizacion_DB
      $fecha_cotizacion_arr = explode('-',$fecha_cotizacion);
      if($fecha_cotizacion_arr[1] == '01'){
        $fecha_cotizacion_arr[0] = str_pad(intval($fecha_cotizacion_arr[0])-1,4,'0',STR_PAD_LEFT);
        $fecha_cotizacion_arr[1] = '12';
      }
      else{
        $fecha_cotizacion_arr[1] = str_pad(intval($fecha_cotizacion_arr[1])-1,2,'0',STR_PAD_LEFT);
      }
      $fecha_cotizacion_arr[2] = '01';
      
      $q_base = DB::table('canon as c')//Busco en otros canons del mismo mes
      ->whereNull('c.deleted_at')
      ->where('c.id_casino','<>',$id_casino)
      ->where('c.año_mes',implode('-',$fecha_cotizacion_arr));//Para buscar entre menos
      
      $q_cfm = (clone $q_base)
      ->leftJoin('canon_fijo_mesas as cfm','cfm.id_canon','=','c.id_canon');
      $q_cfma = (clone $q_base)
      ->leftJoin('canon_fijo_mesas_adicionales as cfma','cfma.id_canon','=','c.id_canon');
      
      $vals_db = collect([])
      ->merge(
        (clone $q_cfm)->selectRaw('
          devengado_fecha_cotizacion as dev_fecha,
          NULLIF(devengado_cotizacion_dolar,"0") as dev_moneda_2,
          NULLIF(devengado_cotizacion_euro,"0") as dev_moneda_3,
          determinado_fecha_cotizacion as det_fecha,
          NULLIF(determinado_cotizacion_dolar,"0") as det_moneda_2,
          NULLIF(determinado_cotizacion_euro,"0") as det_moneda_3
        ')->get()
      )
      ->merge(
        (clone $q_cfma)->selectRaw('
          devengado_fecha_cotizacion as dev_fecha,
          NULLIF(devengado_cotizacion_dolar,"0") as dev_moneda_2,
          NULLIF(devengado_cotizacion_euro,"0") as dev_moneda_3,
          determinado_fecha_cotizacion as det_fecha,
          NULLIF(determinado_cotizacion_dolar,"0") as det_moneda_2,
          NULLIF(determinado_cotizacion_euro,"0") as det_moneda_3
        ')->get()
      );
      
      $this->cotizacion_DB = [];
      foreach($vals_db as $v){
        $this->cotizacion_DB[$v->dev_fecha] = $this->cotizacion_DB[$v->dev_fecha] ?? [2 => [],3 => []];
        $this->cotizacion_DB[$v->dev_fecha][2][$v->dev_moneda_2] = 1;
        $this->cotizacion_DB[$v->dev_fecha][3][$v->dev_moneda_3] = 1;
        
        $this->cotizacion_DB[$v->det_fecha] = $this->cotizacion_DB[$v->det_fecha] ?? [2 => [],3 => []];
        $this->cotizacion_DB[$v->det_fecha][2][$v->det_moneda_2] = 1;
        $this->cotizacion_DB[$v->det_fecha][3][$v->det_moneda_3] = 1;
      }
      
      //Si hay una cotizacion sola para la fecha, la guardo sino pongo en nulo
      foreach($this->cotizacion_DB as $fcot => $cots){
        foreach($cots as $idtm => $valores_moneda){
          if(count($valores_moneda) > 1 || count($valores_moneda) == 0){
            $this->cotizacion_DB[$fcot][$idtm] = null;
          }
          else{
            $this->cotizacion_DB[$fcot][$idtm] = array_keys($valores_moneda)[0];
            $this->cotizacion_DB[$fcot][$idtm] = empty($this->cotizacion_DB[$fcot][$idtm])? 
            null
            : $this->cotizacion_DB[$fcot][$idtm];
          }
        }
      }
    }
    
    //Si existe una cotización comun en la DB, devuelvo esa
    $cot = ($this->cotizacion_DB[$fecha_cotizacion] ?? [])[$id_tipo_moneda] ?? null;
    if($cot !== null) return $cot;
    
    if($id_tipo_moneda == 2){//Busco en las cotizaciones de los auditores
      $cotizacion = DB::table('cotizacion as cot')
      ->where('fecha',$fecha_cotizacion)
      ->first();
      if($cotizacion !== null){
        return $cotizacion->valor;
      }
    }
    
    return '0';
  }
  
  private function apostado($tipo,$año_mes,$id_casino){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.coinin*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
    }
    return null;
  }
  
  public function bruto($tipo,$año_mes,$id_casino){//@TODO: modularizar
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
      case 'JOL':{
        $JOL_connect_config = $this->valorPorDefecto('JOL_connect_config') ?? null;
        $debug = $JOL_connect_config['debug'] ?? false;
        if(empty($JOL_connect_config)) return $debug? '-0.99' : null;
        if(empty($JOL_connect_config['ip_port'])) return $debug? '-0.98' : null;
        if(empty($JOL_connect_config['API-Token']))  return $debug? '-0.97' : null;
        
        set_time_limit(5);
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, "http://{$JOL_connect_config['ip_port']}/api/bruto");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(compact('año_mes','id_casino')));
        //curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_PROXY, NULL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'API-Token: '.$JOL_connect_config['API-Token']
        ]);
        
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($result === false){//Error de curl https://curl.se/libcurl/c/libcurl-errors.html
          $ret = $debug? (-curl_errno($ch)).'.99' : null;
          curl_close($ch);
          return $ret;
        }
        curl_close($ch);
        
        if($code != 200){
          return $debug? (-$code).'.98' : null;
        }
                
        return $result;
      }break;
      case 'Mesas':
      case 'Fijas':
      case 'Diarias': {
        $resultado = DB::table('importacion_diaria_mesas as idm')
        ->selectRaw('SUM(idm.utilidad*IF(idm.id_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('idm.id_moneda',2)->on('idm.fecha','=','cot.fecha');
        })
        ->whereNull('idm.deleted_at')
        ->where('idm.id_casino',$id_casino)
        ->whereYear('idm.fecha',$año_mes_arr[0])
        ->whereMonth('idm.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
    }
    return null;
  }
  
  public function cambiarEstado(Request $request){
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon,deleted_at,NULL'],
      'estado' => ['required','string','in:Generado,Pagado,Cerrado'],
    ], self::$errores,[
      'in.estado' => 'El valor de estado no es correcto',
    ])->after(function($validator){
      if($validator->errors()->any()) return;
      $id_canon = $validator->getData()['id_canon'];
      $estado_db = DB::table('canon')->select('estado')->where('id_canon',$id_canon)->first()->estado;
      $estado = $validator->getData()['estado'];
      $validos = ['Generado' => ['Pagado'],'Pagado' => ['Cerrado','Generado'],'Cerrado' => ['Pagado']];
      if(!in_array($estado,$validos[$estado_db] ?? [])){
        return $validator->errors()->add('estado','Transición de estado incorrecta.');
      }
    })->validate();
    
    //@HACK: usar CoW/SoftDelete?
    return DB::transaction(function() use ($request){
      DB::table('canon')
      ->whereNull('deleted_at')
      ->where('id_canon',$request->id_canon)
      ->update(['estado' => $request->estado]);
      
      $c = DB::table('canon')
      ->where('id_canon',$request->id_canon)
      ->first();
      
      $this->recalcular_saldos($c->saldo_posterior,$c->año_mes,$c->id_casino);
      
      return 1;
    });
  }
  
  public function desborrar(Request $request){//Se valida superusuario en el ruteo
    Validator::make($request->all(),[
      'id_canon' => ['required','integer','exists:canon,id_canon'],
    ],self::$errores,[])->validate();
    
    return DB::transaction(function() use ($request){
      DB::table('canon')
      ->where('id_canon',$request->id_canon)
      ->update(['deleted_at' => null]);
            
      $this->cambio_canon_recalcular_saldos($request->id_canon);
      
      return 1;
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
    $data = $this->obtener_arr(compact('id_canon'),false);
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
    
    foreach(['id_canon_pago','id_canon'] as $k){
      foreach(($data['canon_pago'] ?? []) as $tipo => $_){
        unset($data['canon_pago'][$tipo][$k]);
      }
    }
    $ret['canon_pago'] = array_values($data['canon_pago'] ?? []);
    
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
    
    //@HACK: Solo para MySQl, lo hago así porque se elimino una dependencia con Doctrine... no quiero tocar el composer.json
    $types = DB::table(DB::raw('INFORMATION_SCHEMA.COLUMNS'))
    ->selectRaw("table_name as 'table', column_name as col, data_type as type")
    ->whereIn('table_name',array_keys($ret))
    ->get()->groupBy('table')->map(function($tcols){
      return $tcols->keyBy('col')->map(function($T){
        return $T->type;
      });
    });
    
    foreach($ret as $tabla => $d){
      foreach($d as $rowidx => $row){
        foreach($row as $col => $val){
          switch($types[$tabla][$col] ?? null){
            case 'smallint':
            case 'integer':
            case 'int':
            case 'decimal': if($formatear_decimal){
              $ret[$tabla][$rowidx][$col] = self::formatear_decimal((string)$val);//number_format castea a float... lo hacemos a pata...
            }break;
            
            case 'bool':
            case 'boolean':
            case 'tinyint':{
              $ret[$tabla][$rowidx][$col] = intval($val)? 'SÍ' : 'NO';
            }break;
            
            default:{
              $ret[$tabla][$rowidx][$col] = trim($val);
            }break;
          }
        }
      }
    }
    
    return $ret;
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
  
  private static function rawsql_round_bankers($val){
    $abs = "ABS($val)";
    $tru = "TRUNCATE($val,2)";
    return "IF(
      ($abs-$tru) = 0.005,
      $tru+0.01*(($tru*100) % 2),
      ROUND($val,2)
    )";
  }
  
  private static function totalesCanon_prepare($agrupar_concepto_adicionales = 'concepto'){
    static $prepared = null;
    if($prepared === $agrupar_concepto_adicionales){
      return 'temp_subcanons_redondeados_con_totales_con_mensuales';
    }
    
    if($agrupar_concepto_adicionales !== 'concepto'){
      $agrupar_concepto_adicionales = '"'.$agrupar_concepto_adicionales.'"';
    }
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons
    SELECT
      c.año_mes as año_mes,
      c.id_casino as id_casino,
      IFNULL(sc.concepto,"Total") as concepto,
      IF(sc.concepto IS NULL,6,CASE
        WHEN sc.concepto = "Paños"       THEN 0
        WHEN sc.concepto = "Adicionales" THEN 1
        WHEN sc.concepto = "MTM"         THEN 2
        WHEN sc.concepto = "Bingo"       THEN 3
        WHEN sc.concepto = "JOL"         THEN 5
        ELSE 99999
      END) as orden,
      SUM(sc.beneficio) as beneficio,
      SUM(sc.bruto) as bruto,
      SUM(sc.deduccion) as deduccion,
      SUM(sc.devengado) as devengado,
      SUM(sc.determinado) as determinado,
      '.self::rawsql_round_bankers('SUM(sc.beneficio)').' as red_beneficio,
      '.self::rawsql_round_bankers('SUM(sc.bruto)').' as red_bruto,
      '.self::rawsql_round_bankers('SUM(sc.deduccion)').' as red_deduccion,
      '.self::rawsql_round_bankers('SUM(sc.devengado)').' as red_devengado,
      '.self::rawsql_round_bankers('SUM(sc.determinado)').' as red_determinado,
      MAX(c.devengado_deduccion) as canon_deduccion,
      MAX(c.devengado) as canon_devengado,
      MAX(c.determinado+c.ajuste) as canon_determinado
    FROM canon as c
    JOIN (
      SELECT 
        sc.id_canon,
        CASE
          WHEN tipo = "Maquinas" THEN "MTM"
          WHEN tipo = "Bingo"    THEN "Bingo"
          WHEN tipo = "JOL"      THEN "JOL"
          ELSE tipo
        END as concepto,
        sc.determinado_subtotal as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_variable as sc
      
      UNION ALL SELECT
        sc.id_canon,
        "Paños" as concepto,
        sc.bruto as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_fijo_mesas AS sc
      
      UNION ALL SELECT
        sc.id_canon,
        '.$agrupar_concepto_adicionales.' as concepto,
        0 as beneficio,
        IF(sc.devengar,sc.devengado_total,NULL) as bruto,
        IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
        IF(sc.devengar,sc.devengado,NULL) as devengado,
        sc.determinado as determinado
      FROM canon_fijo_mesas_adicionales AS sc
    ) as sc ON sc.id_canon = c.id_canon
    WHERE c.deleted_at IS NULL
    GROUP BY c.año_mes,c.id_casino,sc.concepto
    WITH ROLLUP
    HAVING año_mes IS NOT NULL AND id_casino IS NOT NULL');
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_total
    SELECT
      sc.id_casino,
      sc.año_mes,
      sc.red_beneficio,
      sc.red_bruto,
      sc.red_deduccion,
      sc.red_devengado,
      sc.red_determinado
    FROM temp_subcanons as sc
    WHERE sc.concepto = "Total"');
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_total_red
    SELECT
      sc.id_casino,
      sc.año_mes,
      SUM(sc.red_beneficio)   as beneficio,
      SUM(sc.red_bruto)       as bruto,
      SUM(sc.red_deduccion)   as deduccion,
      SUM(sc.red_devengado)   as devengado,
      SUM(sc.red_determinado) as determinado
    FROM temp_subcanons as sc
    WHERE sc.concepto <> "Total"
    GROUP BY sc.id_casino,sc.año_mes');
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados
    SELECT
      sc.id_casino,
      sc.año_mes,
      sc.concepto,
      sc.orden,
      sc.red_beneficio+(sc.concepto = "Paños")*(T.red_beneficio-Tred.beneficio)   as beneficio,
      sc.red_bruto+(sc.concepto = "Paños")*(T.red_bruto-Tred.bruto)       as bruto,
      sc.red_deduccion+(sc.concepto = "Paños")*(sc.canon_deduccion-Tred.deduccion)   as deduccion,
      sc.red_devengado+(sc.concepto = "Paños")*(sc.canon_devengado-Tred.devengado)   as devengado,
      sc.red_determinado+(sc.concepto = "Paños")*(sc.canon_determinado-Tred.determinado) as determinado
    FROM temp_subcanons as sc
    JOIN temp_subcanons_total as T on T.id_casino = sc.id_casino AND T.año_mes = sc.año_mes
    JOIN temp_subcanons_total_red as Tred ON Tred.id_casino = sc.id_casino AND Tred.año_mes = sc.año_mes
    WHERE sc.concepto <> "Total"');
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados_con_totales
    SELECT *
    FROM temp_subcanons_redondeados');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales
    SELECT 
      id_casino,
      año_mes,
      "Total Físico" as concepto,
      4 as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados
    WHERE concepto IN ("Paños","MTM","Bingo","Adicionales")
    GROUP BY id_casino,año_mes');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales
    SELECT 
      id_casino,
      año_mes,
      "Total" as concepto,
      6 as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados
    GROUP BY id_casino,año_mes');
    
    DB::statement('CREATE TEMPORARY TABLE temp_subcanons_redondeados_con_totales_con_mensuales
    SELECT * FROM temp_subcanons_redondeados_con_totales');
    DB::statement('INSERT INTO temp_subcanons_redondeados_con_totales_con_mensuales
    SELECT
      0,
      año_mes,
      concepto,
      MAX(orden) as orden,
      SUM(beneficio) as beneficio,
      SUM(bruto) as bruto,
      SUM(deduccion) as deduccion,
      SUM(devengado) as devengado,
      SUM(determinado) as determinado
    FROM temp_subcanons_redondeados_con_totales
    GROUP BY año_mes,concepto');
    
    $prepared = $discriminar_adicionales;
    return 'temp_subcanons_redondeados_con_totales_con_mensuales';
  }
  public function totalesCanon($año,$mes,$discriminar_adicionales){
    $table = self::totalesCanon_prepare($discriminar_adicionales);
    $año_mes = str_pad($año,4,'0',STR_PAD_LEFT).'-'.str_pad($mes,2,'0',STR_PAD_LEFT).'-01';
    $ret = DB::table($table.' as tc')
    ->select('tc.*',DB::raw('IF(tc.id_casino = 0,"Total",IFNULL(cas.nombre,tc.id_casino)) as casino'))
    ->where('tc.año_mes',$año_mes)
    ->leftJoin('casino as cas','cas.id_casino','=','tc.id_casino')
    ->leftJoin(DB::raw('(
      SELECT 1 as id_casino,1 as orden
      UNION ALL
      SELECT 2 as id_casino,3 as orden
      UNION ALL
      SELECT 3 as id_casino,2 as orden
    ) as orden_cas'),'orden_cas.id_casino','=','tc.id_casino')
    ->orderBy(DB::raw('IFNULL(orden_cas.orden,100+tc.id_casino)*1000+tc.orden'),'asc')
    ->get()->groupBy('casino')->map(function($g){
      return $g->keyBy('concepto')->map(function($obj){
        $ret = ['beneficio' => null,'bruto' => null,'deduccion' => null,'devengado' => null,'determinado' => null];
        foreach($ret as $k => &$v){
          $v = $obj->{$k} ?? null;
          $v = $v === null? null : self::formatear_decimal($v);
        }
        return $ret;
      });
    })->toArray();
        
    return $ret;
  }
  
  public function planillaDevengado(Request $request,$tipo_presupuesto = 'devengado'){
    if(!isset($request->id_canon)) return;
    
    $c_año_mes = DB::table('canon')
    ->select('año_mes')
    ->whereNull('deleted_at')
    ->where('id_canon',$request->id_canon)
    ->first();
    
    if(empty($c_año_mes)) return;
    
    $año_mes = explode('-',$c_año_mes->año_mes);
    $mes = $año_mes[1].'/'.substr($año_mes[0],2);
    
    $datos = $this->totalesCanon($año_mes[0],$año_mes[1],false);
    
    $tablas = [];
    if($tipo_presupuesto == 'devengado'){
      $tablas = ['','deduccion','bruto'];
      foreach($datos as $cas => &$t){
        foreach($t as $nombre_sc => &$subcanon){
          $subcanon[''] = $subcanon['devengado'];
          unset($subcanon['beneficio']);
          unset($subcanon['devengado']);
          unset($subcanon['determinado']);
        }
      }
    }
    else if($tipo_presupuesto == 'determinado'){
      $tablas = [''];
      foreach($datos as &$t){
        foreach($t as &$subcanon){
          $subcanon[''] = $subcanon['determinado'];
          unset($subcanon['beneficio']);
          unset($subcanon['devengado']);
          unset($subcanon['bruto']);
          unset($subcanon['deduccion']);
          unset($subcanon['determinado']);
        }
      }
    }
    
    $conceptos = ['Paños','MTM','Bingo','Total Físico','JOL','Total'];
        
    $view = View::make('Canon.planillaDevengado', compact('tipo_presupuesto','tablas','conceptos','mes','datos'));
    $dompdf = new Dompdf();
    $dompdf->set_paper('A4', 'portrait');
    $dompdf->loadHtml($view->render());
    $dompdf->render();
    $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
    return $dompdf->stream("Devengado-Canon-$mes.pdf", Array('Attachment'=>0));
  }
  
  public function planillaDeterminado(Request $request){
    return $this->planillaDevengado($request,'determinado');
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
  
  public function planillaInformeCanon(Request $request){
    $planilla = 'canon_mensual';
    
    $canon = DB::table('canon')
    ->where('id_canon',$request->id_canon)
    ->whereNull('deleted_at')
    ->first();
    if($canon === null) return 'Canon no existente';
    
    $casino = Casino::find($canon->id_casino);
    if($casino === null) return 'Casino no existente';
    $casino = $casino->nombre;
    
    $meses_calendario = collect([null,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']);
    unset($meses_calendario[0]);
    $año_mes_arr = explode('-',$canon->año_mes);
    $año = $año_mes_arr[0];
    $mes = $meses_calendario[intval($año_mes_arr[1])];
    
    $params = http_build_query(compact('planilla','casino','año','mes'));
    
    return redirect('/canon/descargarPlanillas?'.$params);
  }
  
  public function descargar(Request $request){
    $data = $this->buscar($request,false);
    
    $conceptos = [
      'MTM','Bingo','JOL','Paños','Adicionales'
    ];
    
    $tipo_valores = [
      'beneficio','bruto','deduccion','devengado','determinado'
    ];
    
    $arreglo_a_csv = [];
    $totales_cache = [];//Si busco para un periodo me devuelve todos los casinos por eso lo cacheo
    foreach($data as $d){
      $año_mes = explode('-',$d->año_mes);
      
      $t = null;
      if(!array_key_exists($d->año_mes,$totales_cache)){
        $totales_cache[$d->año_mes] = $this->totalesCanon(intval($año_mes[0]),intval($año_mes[1]),true);
      }
      $t = $totales_cache[$d->año_mes][$d->casino];//Deberia existir porque buscar() lo devolvio
      
      $fila = [
        'año_mes' => $d->año_mes,
        'casino'  => $d->casino,
      ];
      foreach($tipo_valores as $tval){
        foreach($conceptos as $cncpt){
          $fila[$tval.'_'.$cncpt] = ($t[$cncpt] ?? [])[$tval] ?? '0';
        }
        $fila[$tval] = ($t['Total'] ?? [])[$tval] ?? '0';
      }
      $fila['intereses_y_cargos'] = self::formatear_decimal($d->intereses_y_cargos);
      $fila['pago']      = self::formatear_decimal($d->pago);
      $fila['saldo_posterior'] = self::formatear_decimal($d->saldo_posterior);
      $arreglo_a_csv[] = $fila;
    }
    
    $header = array_keys($arreglo_a_csv[0] ?? []);
    
    $f = fopen('php://memory', 'r+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    fputcsv($f, $header,',','"',"\\");
    foreach ($arreglo_a_csv as $fila) {
      fputcsv($f, array_values($fila),',','"',"\\");
    }
    rewind($f);
        
    return stream_get_contents($f);
  }
  
  private static function rawsql_ranged($begin,$end,$step=1){
    $aux = $begin;
    $begin = min($begin,$end);
    $end = max($aux,$end);
    
    $ret = "( SELECT $begin as val ";
    for($i=$begin+$step;$i<=$end;$i+=$step){
      $ret.= 'UNION ALL SELECT '.$i.' ';
    }
    return $ret.')';
  }
  
  private static function array_from_pairs($arr1,$arr2){
    $ks = [];
    foreach($arr1 as $k => $_)
      $ks[$k] = 1;
    foreach($arr2 as $k => $_)
      $ks[$k] = 1;
    $ks = array_keys($ks);
    
    $ret = [];
    foreach($ks as $k)
      $ret[$k] = [$arr1[$k] ?? null,$arr2[$k] ?? null];
    
    return $ret;
  }
    
  public function descargarPlanillas(Request $request){    
    $fecha_inicio = [//@TODO: poner en valores por defecto
      'Melincué' => '2007-09-28',
      'Santa Fe' => '2008-08-11',
      'Rosario'  => '2009-10-15',
      'Santa Fe - Melincué' => null
    ];
    
    $primer_fecha = null;
    $casino = $request->casino ?? null;
    if($casino !== null){
      $primer_fecha = $fecha_inicio[$casino] ?? 'XXXX-XX-XX';
    }
    else{
      $primer_fecha = array_reduce($fecha_inicio,function($carry,$f){
        return min($carry,$f ?? $carry);
      },'XXXX-99-99');
    }
    
    $ultima_fecha = date('Y-m-d');
    
    $primer_año = intval(substr($primer_fecha,0,strlen('XXXX')));
    $ultimo_año = intval(substr($ultima_fecha,0,strlen('XXXX')));
    $primer_mes = intval(substr($primer_fecha,strlen('XXXX-'),strlen('XX')));
    $ultimo_mes = intval(substr($ultima_fecha,strlen('XXXX-'),strlen('XX')));
    $planilla  = $request->planilla ?? null;
    
    if($primer_año == 0) return 'Sin configuración de fechas de inicio';
    
    $años = [];
    if($primer_año !== null && $ultimo_año !== null){
      $años = collect(array_reverse(range($primer_año,$ultimo_año,1)));
    }
    
    $año  = $request->año ?? null;
    $año  = $año === null? null : intval($año);
    $años_sql = $año === null?
      self::rawsql_ranged($primer_año,$ultimo_año)
    : self::rawsql_ranged($año-1,$año+1);
    
    $mes  = $request->mes ?? null;
    $mes  = $mes === null? null : intval($mes);
    
    $meses_calendario = collect([null,'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']);
    unset($meses_calendario[0]);
    
    $meses = clone $meses_calendario;
    if($año == $primer_año){//Queda mas legible en dos loops, mas que unirlo
      $_mescheck = $primer_mes ?? 1;
      $_unsetting = true;
      foreach($meses as $mnum => $m){
        if($mnum == $_mescheck){
          $_unsetting = false;
        }
        if($_unsetting){
          unset($meses[$mnum]);
        }
      }
    }
    elseif($año == $ultimo_año){
      $_mescheck = $ultimo_mes ?? 12;
      $_unsetting = false;
      foreach($meses as $mnum => $m){
        if($_unsetting){
          unset($meses[$mnum]);
        }
        if($mnum == $_mescheck){
          $_unsetting = true;
        }
      }
    }
    
    //@SPEED
    //Para los mensuales resulta en 3 veces la cantidad de querys necesarias
    //Ej pido Marzo 2023
    //-> deberia solo obtener Febrero 2023, Marzo 2023, Abril 2023
    //-> devuelve Febrero 2022,23,24, Marzo 2022,23,24, Abril 2022,23,24
    //No me molesto en arreglarlo porque no creo que ralentize mucho
    $meses_sql = null;
    if($mes === null){
      $meses_sql = self::rawsql_ranged(1,12);
    }
    else{
      $_prev = ($mes <= 1? 13 : $mes)-1;
      $_prox = ($mes >= 12? 0 : $mes)+1;
      $meses_sql = "(
        SELECT $_prev as val
        UNION ALL 
        SELECT $mes as val
        UNION ALL
        SELECT $_prox as val
      )";
    }
    
    $casinos_sql = null;
    if($planilla == 'participacion'){
      $casinos_sql = '(
        SELECT 2 as id_casino,"Santa Fe" as casino,"SFE" as abbr,"CSF" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Santa Fe - Melincué" as casino,"SFE-MEL" as abbr,"CSF-CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Santa Fe - Melincué" as casino,"SFE-MEL" as abbr,"CSF-CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Rosario" as casino,"ROS" as abbr,"CRO" as codigo,"CCO" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"CCO" as plataforma
      )';
    }
    else{
      $casinos_sql = '(
        SELECT 1 as id_casino,"Melincué" as casino,"MEL" as abbr,"CME" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Santa Fe" as casino,"SFE" as abbr,"CSF" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Rosario" as casino,"ROS" as abbr,"CRO" as codigo,"CCO" as plataforma
        UNION ALL
        SELECT 1 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 2 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"BPLAY" as plataforma
        UNION ALL
        SELECT 3 as id_casino,"Total" as casino,"TOTAL" as abbr,"TOTAL" as codigo,"CCO" as plataforma
      )';
    }
        
    $abbr_casinos = DB::table(DB::raw($casinos_sql.' as cas'))
    ->select('casino','codigo')->distinct()
    ->get()
    ->keyBy('casino')
    ->map(function($v){
      return $v->codigo;
    });
    $casinos = $abbr_casinos->keys();
    $casinos_sin_total = $casinos->filter(function($c){return $c != 'Total';});
    $abbr_casinos = $abbr_casinos->toArray();
    $plataformas = Plataforma::orderBy('id_plataforma','asc')->get();
    $relacion_plat_cas = ['CCO' => 'Rosario','BPLAY' => 'Santa Fe - Melincué'];
    
    $planillas = [
      'evolucion_historica' => 'Evolución Historica',
      'canon_total' => 'Canon Total',
      'canon_fisico_online' => 'Canon Físico-On Line',
      'participacion' => 'Particip. % Resultado CF-JOL',
      'actualizacion_valores' => 'Actualización Valores Mesas',
      'evolucion_cotizacion' => 'Evolución Cotizacion'
    ];
    $planillas_botones = [
      '' => ['planilla'],
      'evolucion_historica' => ['planilla'],
      'canon_total' => ['planilla','año'],
      'canon_fisico_online' => ['planilla','año'],
      'participacion' => ['planilla','año'],
      'actualizacion_valores' => ['planilla','casino','año'],
      'evolucion_cotizacion' => ['planilla','casino']
    ];
    $botones = [];
    $botones_elegidos = true;{
      $_pbot = $planillas_botones[$planilla] ?? [];
      $breaker = false;
      foreach($_pbot as $_p){
        switch($_p){
          case 'planilla':{
            $botones['planilla'] = self::array_from_pairs(array_keys($planillas),array_values($planillas));
          }break;
          case 'casino':{
            $botones['casino'] = self::array_from_pairs($casinos_sin_total,$casinos_sin_total);
          }break;
          case 'año':{
            $_años = $años->toArray();
            $botones['año'] = self::array_from_pairs(array_map(function($a){
              return $a;
            },$_años),$_años);
          }break;
          case 'mes':{
            $_meses = $meses->reverse();
            $botones['mes'] = self::array_from_pairs($_meses->keys(),$_meses->values());
          }break;
        }
        if(($request[$_p] ?? null) === null){//Falta elegir algun boton para la planilla, asi que corto aca
          $botones_elegidos = false;
          break;
        }
      }
    }
        
    $data = collect([]);
    $data_anual = collect([]);
    $tipos_variables_fisicos = ['Maquinas','Bingo'];
    $tipos_variables_online = ['JOL'];
    $tipos_fijos_mesas = DB::table('canon_fijo_mesas')
    ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
    $tipos_fijos_mesas_adicionales = DB::table('canon_fijo_mesas_adicionales')
    ->select('tipo')->distinct()->get()->pluck('tipo')->values()->toArray();
                
    $q = DB::table(DB::raw($meses_sql.' as mes'))
    ->crossJoin(DB::raw($años_sql.' as año'))
    ->crossJoin(DB::raw($casinos_sql.' as cas'))
    ->leftJoin('canon as c',function($j){
      return $j->whereNull('c.deleted_at')
      ->on('c.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c.año_mes)'),'=','año.val')
      ->on(DB::raw('MONTH(c.año_mes)'),'=','mes.val');
    })
    ->leftJoin('canon as c_yoy',function($j){
      return $j->whereNull('c_yoy.deleted_at')
      ->on('c_yoy.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c_yoy.año_mes)'),'=',DB::raw('(año.val-1)'))
      ->on(DB::raw('MONTH(c_yoy.año_mes)'),'=','mes.val');
    })
    ->leftJoin('canon as c_mom',function($j){
      return $j->whereNull('c_mom.deleted_at')
      ->on('c_mom.id_casino','=','cas.id_casino')
      ->on(DB::raw('YEAR(c_mom.año_mes)'),'=', DB::raw('IF(
        mes.val<>1,
        año.val,
        año.val-1
      )'))
      ->on(DB::raw('MONTH(c_mom.año_mes)'),'=',DB::raw('IF(
        mes.val<>1,
        mes.val-1,
        12
      )'));
    })
    ->where(DB::raw('1'),'=',DB::raw($botones_elegidos? '1' : '0'));
    
    if($casino !== null){
      $q = $q->where('cas.casino','=',$casino);
    }
    
    $fisicos = [];
    $online  = [];
    $variables = [];
    $fijos = [];
    $fijos_adicionales = [];
    foreach($tipos_variables_fisicos as $tidx => $t){
      $alias = 'c_fis_v'.$tidx;
      $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_variable as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $variables[] = $alias;
    }
    
    foreach($tipos_variables_online as $tidx => $t){
      $alias = 'c_ol_v'.$tidx;
      $q = $q->leftJoin('canon_variable as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_variable as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $online[] = $alias;
      $variables[] = $alias;
    }
    
    foreach($tipos_fijos_mesas as $tidx => $t){
      $alias = 'c_fis_mf'.$tidx;
      $q = $q->leftJoin('canon_fijo_mesas as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_fijo_mesas as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $fijos[] = $alias;
    }
    
    foreach($tipos_fijos_mesas_adicionales as $tidx => $t){
      $alias = 'c_fis_mfa'.$tidx;
      $q = $q->leftJoin('canon_fijo_mesas_adicionales as '.$alias,function($j) use ($alias,$t){
        return $j->on($alias.'.id_canon','=','c.id_canon')
        ->where($alias.'.tipo','=',$t);
      });
      $q = $q->leftJoin('canon_fijo_mesas_adicionales as '.$alias.'_yoy',function($j) use ($alias,$t){
        return $j->on($alias.'_yoy.id_canon','=','c_yoy.id_canon')
        ->where($alias.'_yoy.tipo','=',$t);
      });
      $fisicos[] = $alias;
      $fijos_adicionales[] = $alias;
    }
    
    $canon_fisico = 'ROUND('.implode('+',array_map(function($t){
      return "IFNULL(SUM($t.determinado),0)";
    },$fisicos)).'+AVG(c.ajuste),2)';
    
    $canon_online = 'ROUND('.implode('+',array_map(function($t){
      return "IFNULL(SUM($t.determinado),0)";
    },$online)).',2)';
          
    if($planilla == 'evolucion_historica'){
      $sel_aggr = 'SUM(c.devengado) as devengado,
      SUM(c.determinado+c.ajuste) as canon,
      SUM(c_yoy.devengado) as yoy_devengado,
      SUM(c_yoy.determinado+c_yoy.ajuste) as yoy_canon,
      SUM(c_mom.devengado) as mom_devengado,
      SUM(c_mom.determinado+c_mom.ajuste) as mom_canon,
      ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_yoy.devengado),0)-1),2) as variacion_anual_devengado,
      ROUND(100*(SUM(c.devengado)/NULLIF(SUM(c_mom.devengado),0)-1),2) as variacion_mensual_devengado,
      ROUND(100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c_yoy.ajuste),0)-1),2) as variacion_anual_canon,
      ROUND(100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c_mom.ajuste),0)-1),2) as variacion_mensual_canon,
      (SUM(c.determinado)-SUM(c.devengado)) as diferencia,
      ROUND(100*(1-SUM(c.devengado)/NULLIF(SUM(c.determinado),0)),2) as variacion_sobre_devengado';
    }
    elseif($planilla == 'actualizacion_valores' || $planilla == 'evolucion_cotizacion'){
      $bruto = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM({$t}.bruto),0)";
      },$fijos)).')';
      $bruto_yoy = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM({$t}_yoy.bruto),0)";
      },$fijos)).')';
      
      $fecha_cotizacion = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_fecha_cotizacion";
      },$fijos)).'))';
      $fecha_cotizacion_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_fecha_cotizacion";
      },$fijos)).'))';
      
      $cotizacion_euro = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_cotizacion_euro";
      },$fijos)).'))';
      $cotizacion_dolar = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.determinado_cotizacion_dolar";
      },$fijos)).'))';
      $cotizacion_euro_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_cotizacion_euro";
      },$fijos)).'))';
      $cotizacion_dolar_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.determinado_cotizacion_dolar";
      },$fijos)).'))';
      
      $valor_euro = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.valor_euro";
      },$fijos)).'))';
      $valor_dolar = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}.valor_dolar";
      },$fijos)).'))';
      $valor_euro_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.valor_euro";
      },$fijos)).'))';
      $valor_dolar_yoy = 'MAX(COALESCE('.implode(',',array_map(function($t){
        return "{$t}_yoy.valor_dolar";
      },$fijos)).'))';
      
      $bruto_euro = "($bruto/2/$cotizacion_euro)";
      $bruto_dolar = "($bruto/2/$cotizacion_dolar)";
      $bruto_euro_yoy = "($bruto_yoy/2/$cotizacion_euro_yoy)";
      $bruto_dolar_yoy = "($bruto_yoy/2/$cotizacion_dolar_yoy)";
      $variacion_cotizacion_euro = "100*($cotizacion_euro/$cotizacion_euro_yoy - 1)";
      $variacion_cotizacion_dolar = "100*($cotizacion_dolar/$cotizacion_dolar_yoy - 1)";
      $variacion_euro = "100*($bruto_euro/$bruto_euro_yoy-1)";
      $variacion_dolar = "100*($bruto_dolar/$bruto_dolar_yoy-1)";
      
      $sel_aggr = "
        $bruto as bruto,
        $bruto_yoy as bruto_yoy,
        $fecha_cotizacion as fecha_cotizacion,
        $fecha_cotizacion_yoy as fecha_cotizacion_yoy,
        $cotizacion_euro as cotizacion_euro,
        $cotizacion_dolar as cotizacion_dolar,
        $cotizacion_euro_yoy as cotizacion_euro_yoy,
        $cotizacion_dolar_yoy as cotizacion_dolar_yoy,
        ROUND($bruto_euro,2) as bruto_euro,
        ROUND($bruto_dolar,2) as bruto_dolar,
        ROUND($bruto_euro_yoy,2) as bruto_euro_yoy,
        ROUND($bruto_dolar_yoy,2) as bruto_dolar_yoy,
        ROUND($variacion_cotizacion_euro,3) as variacion_cotizacion_euro,
        ROUND($variacion_cotizacion_dolar,3) as variacion_cotizacion_dolar,
        ROUND($variacion_euro,3) as variacion_euro,
        ROUND($variacion_dolar,3) as variacion_dolar,
        $valor_euro as valor_euro,
        $valor_dolar as valor_dolar,
        $valor_euro_yoy as valor_euro_yoy,
        $valor_dolar_yoy as valor_dolar_yoy
      ";
    }
    elseif($planilla == 'canon_total'){
      $sel_aggr = 'SUM(c.determinado+c.ajuste) as canon_total,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c.ajuste),0)-1) as variacion_anual,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c.ajuste),0)-1) as variacion_mensual';
    }
    elseif($planilla == 'canon_fisico_online'){
      $sel_aggr = $canon_fisico.' as canon_fisico,
      '.$canon_online.' as canon_online,
      SUM(c.determinado+c.ajuste) as canon_total,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_yoy.determinado+c_yoy.ajuste),0)-1) as variacion_anual,
      100*(SUM(c.determinado+c.ajuste)/NULLIF(SUM(c_mom.determinado+c_mom.ajuste),0)-1) as variacion_mensual';
    }
    elseif($planilla == 'participacion'){
      $ganancia_total_variable = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
      },$variables)).')';
      
      $ganancia_online_variable = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.determinado_subtotal),0)";//Con el impuesto sacado
      },$online)).')';
            
      $ganancia_fisico_fijo = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM($t.bruto),0)";//Con el impuesto sacado
      },$fijos)).')';
            
      $ganancia_online = "($ganancia_online_variable)";
      $ganancia_total  = "($ganancia_total_variable+$ganancia_fisico_fijo)";
      $ganancia_fisico = "($ganancia_total-$ganancia_online)";
      
      $porcentaje_fisico = "ROUND(100*$ganancia_fisico/NULLIF($ganancia_total,0),2)";
      $porcentaje_online = "ROUND(100*$ganancia_online/NULLIF($ganancia_total,0),2)";
    
      $ganancia_cco = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM(IF(cas.plataforma = 'CCO',$t.determinado_subtotal,0)),0)";
      },$online)).')';
      
      $ganancia_bplay = '('.implode('+',array_map(function($t){
        return "IFNULL(SUM(IF(cas.plataforma = 'BPLAY',$t.determinado_subtotal,0)),0)";
      },$online)).')';
      
      $porcentaje_CCO = "ROUND(100*$ganancia_cco/NULLIF($ganancia_online,0),2)";
      $porcentaje_BPLAY = "ROUND(100*$ganancia_bplay/NULLIF($ganancia_online,0),2)";
      //Como son dos porcentajes el rounding uno siempre compensa tal que sea 100 la suma
      $sel_aggr = "$canon_online as canon_online,
      $porcentaje_fisico as porcentaje_fisico,
      $porcentaje_online as porcentaje_online,
      $porcentaje_CCO as porcentaje_CCO,
      $porcentaje_BPLAY as porcentaje_BPLAY";
    }
    else {
      $sel_aggr = 'NULL as no_sel';
    }
    
    $data = (clone $q)
    ->selectRaw('
      cas.casino,
      cas.codigo,
      cas.abbr,
      año.val as año,
      mes.val as mes,
      '.$sel_aggr
    )
    ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,año.val,mes.val'))
    ->orderBy('año','asc')
    ->orderBy('mes','asc')
    ->get()
    ->merge(//Por casino por mes
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        0 as año,
        mes.val as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,mes.val'))
      ->orderBy('mes','asc')
      ->get()
    )
    ->merge(//Por casino por año
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        año.val as año,
        0 as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr,año.val'))
      ->orderBy('año','asc')
      ->get()
    )
    ->merge(//Por casino
      (clone $q)
      ->selectRaw('
        cas.casino,
        cas.codigo,
        cas.abbr,
        0 as año,
        0 as mes,
        '.$sel_aggr
      )
      ->groupBy(DB::raw('cas.casino,cas.codigo,cas.abbr'))
      ->get()
    )
    ->transform(function($d) use ($fecha_inicio){
      $fini = $fecha_inicio[$d->casino] ?? null;
      $d->fecha_inicio = $fini;
      if($fini !== null && $d->año > 0 && $d->mes > 0){
        $d->rel_mes = ($d->mes-intval(substr($fini,strlen('XXXX-'),2)))%12;
        $d->rel_mes += $d->rel_mes < 0? 12 : 0;
        
        $aux = new \DateTime("{$d->año}-{$d->mes}-01");
        $aux->modify('-'.$d->rel_mes.' months');
        $aux = intval($aux->format('Y'))-intval(substr($fini,0,strlen('XXXX')));
        
        $d->rel_mes += 1;//Lo paso a [1,12]
        $d->rel_año = $aux+1;//Base-1
        
        if($d->rel_año <= 0){//Si es anterior a la fecha de inicio lo nulifico
          $d->rel_mes = null;
          $d->rel_año = null;
        }
      }
      else{
        $d->rel_mes = null;
        $d->rel_año = null;
      }
      return $d;
    })
    ->groupBy('casino')
    ->map(function($d_cas){
      return $d_cas->groupBy('año')
      ->map(function($d_cas_año){
        return $d_cas_año->keyBy('mes');
      });
    });
    
    $parametros = $request->all();
    return View::make('Canon.planillaPlanillas',compact(
      'fecha_inicio',
      'primer_fecha','ultima_fecha',
      'primer_año','ultimo_año',
      'primer_mes','ultimo_mes',
      'botones','botones_elegidos','parametros','data','data_plataformas','años_planilla','años','año','año_anterior','meses','meses_calendario','planillas','planilla','es_anual','casinos','abbr_casinos','plataformas','relacion_plat_cas'));
  }
}
