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

class CanonController extends Controller
{
  static $max_scale = 64;
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function index(){
    $casinos = UsuarioController::getInstancia()->quienSoy()['usuario']->casinos;
    $plataformas = Plataforma::all();                 
    return View::make('Canon.ncanon', compact('casinos','plataformas'));
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
      'bruto_devengado' => ['nullable',$numeric_rule(2)],
      'deduccion' => ['nullable',$numeric_rule(2)],
      'devengado' => ['nullable',$numeric_rule(2)],
      'porcentaje_seguridad' => ['nullable',$numeric_rule(4)],
      'fecha_vencimiento' => ['nullable','date'],
      'fecha_pago' => ['nullable','date'],
      'bruto_pagar' => ['nullable',$numeric_rule(2)],
      'interes_mora'=> ['nullable',$numeric_rule(4)],
      'mora' => ['nullable',$numeric_rule(2)],
      'a_pagar' => ['nullable',$numeric_rule(2)],
      'saldo_anterior' => ['nullable',$numeric_rule(2)],
      'saldo_posterior' => ['nullable',$numeric_rule(2)],
      'canon_variable' => 'array',
      'canon_variable.*.apostado_sistema' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.apostado_informado' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.apostado_porcentaje_aplicable' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.base_imponible_devengado' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.base_imponible_pagar' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.apostado_porcentaje_impuesto_ley' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.impuesto_devengado' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.impuesto_pagar' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.bruto' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.subtotal_devengado' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.subtotal_pagar' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.alicuota' => ['nullable',$numeric_rule(4)],
      'canon_variable.*.total_devengado' => ['nullable',$numeric_rule(2)],
      'canon_variable.*.total_pagar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas' => 'array',
      'canon_fijo_mesas.*.fecha_cotizacion' => 'date',
      'canon_fijo_mesas.*.cotizacion_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.cotizacion_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.valor_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.valor_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.dias_valor' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas.*.valor_diario_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.valor_diario_euro' => ['nullable',$numeric_rule(2)],
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
      'canon_fijo_mesas.*.total_dolar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.total_euro' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.total_devengado' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas.*.total_pagar' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.valor_mensual' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.valor_diario' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.valor_hora' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',$numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',$numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.total_devengado' => ['nullable',$numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.total_pagar' => ['nullable',$numeric_rule(2)],
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
      return (($request[$s] ?? null) === null || ($request[$s] == '') || ($request[$s] == []))? $dflt : $request[$s];
    };
        
    $año_mes = $R('año_mes');//@RETORNADO
    $id_casino = $R('id_casino');//@RETORNADO
    $estado = $R('estado','Nuevo');//@RETORNADO
    $fecha_cotizacion = $R('fecha_cotizacion');//@RETORNADO
    $fecha_vencimiento = $R('fecha_vencimiento');//@RETORNADO
    $fecha_pago = $R('fecha_pago');//@RETORNADO
    $es_antiguo = $R('es_antiguo',0)? 1 : 0;//@RETORNADO
    $adjuntos = $R('adjuntos',[]);//@RETORNADO
    
    $fecha_cotizacion_devengado = $R('fecha_cotizacion_devengado',$año_mes);
    $fecha_cotizacion_pagar = $R('fecha_cotizacion_pagar',$año_mes);
    
    if($año_mes !== null && $año_mes !== ''){
      $f = explode('-',$año_mes);
      $f[2] = '10';
      $f = implode('-',$f);
      $f = new \DateTimeImmutable($f);
      $viernes_anterior = clone $f;
      $proximo_lunes = clone $f;
      for($break = 9;$break > 0 && in_array($viernes_anterior->format('w'),['0','6']);$break--){
        $viernes_anterior = $viernes_anterior->sub(\DateInterval::createFromDateString('1 day'));
      }
      for($break = 9;$break > 0 && in_array($proximo_lunes->format('w'),['0','6']);$break--){
        $proximo_lunes = $proximo_lunes->add(\DateInterval::createFromDateString('1 day'));
      }
      $fecha_cotizacion_pagar = $R('fecha_cotizacion_pagar',$viernes_anterior->format('Y-m-d'));//@RETORNADO
      $fecha_vencimiento = $R('fecha_vencimiento',$proximo_lunes->format('Y-m-d'));//@RETORNADO
      $fecha_pago = $R('fecha_pago',$fecha_vencimiento);//@RETORNADO
    }
    
    $bruto_devengado = '0.00';//@RETORNADO
    $bruto_pagar = '0.00';//@RETORNADO
    $canon_variable = [];//@RETORNADO
    $canon_fijo_mesas = [];//@RETORNADO
    $canon_fijo_mesas_adicionales = [];//@RETORNADO
    if($es_antiguo){
      $bruto_devengado = bcadd($R('bruto_devengado',$bruto_devengado),'0',2);
      $bruto_pagar = bcadd($R('bruto_pagar',$bruto_devengado),'0',2);
    }
    else{
      {//Varios tipos (JOL, Bingo, Maquinas)
        $defecto = ($this->valorPorDefecto('canon_variable') ?? [])[$id_casino] ?? [];
        foreach(($request['canon_variable'] ?? $defecto ?? []) as $tipo => $_){
          $canon_variable[$tipo] = $this->canon_variable_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_variable'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_variable[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_variable[$tipo]['total_pagar'] ?? '0',2);
        }
      }
      {//Dos tipos muy parecidos (Fijas y Diarias), se hace asi mas que nada para que sea homogeneo
        $defecto = $this->valorPorDefecto('canon_fijo_mesas')[$id_casino] ?? [];
        foreach(($request['canon_fijo_mesas'] ?? $defecto ?? []) as $tipo => $_){
          $canon_fijo_mesas[$tipo] = $this->mesas_recalcular(
            $año_mes,
            $id_casino,
            $fecha_cotizacion_devengado,
            $fecha_cotizacion_pagar,
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_fijo_mesas'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_fijo_mesas[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_fijo_mesas[$tipo]['total_pagar'] ?? '0',2);
        }
      }
      {//Las mesas adicionales pueden ser varios tipos (Torneo Truco, Torneo Poker, etc)
        $defecto = $this->valorPorDefecto('canon_fijo_mesas_adicionales')[$id_casino] ?? [];
        foreach(($request['canon_fijo_mesas_adicionales'] ?? $defecto ?? []) as $tipo => $_){
          $canon_fijo_mesas_adicionales[$tipo] = $this->mesasAdicionales_recalcular(
            $tipo,
            $defecto[$tipo] ?? [],
            ($request['canon_fijo_mesas_adicionales'] ?? [])[$tipo] ?? []
          );
          $bruto_devengado = bcadd($bruto_devengado,$canon_fijo_mesas_adicionales[$tipo]['total_devengado'] ?? '0',2);
          $bruto_pagar     = bcadd($bruto_pagar,$canon_fijo_mesas_adicionales[$tipo]['total_pagar'] ?? '0',2);
        }
      }
    }
    
    $deduccion = bcadd($R('deduccion','0.00'),'0',2);//@RETORNADO
    $devengado = bcsub($bruto_devengado,$deduccion,2);//@RETORNADO
    
    $porcentaje_seguridad = bccomp($bruto_devengado,'0.00') > 0?//@RETORNADO
       bcdiv(bcmul('100.0',$deduccion),$bruto_devengado,4)
      : '0.00';
    
    $interes_mora = bcadd($R('interes_mora','0.0000'),'0',4);//@RETORNADO
    $a_pagar = bcadd($R('a_pagar','0.00'),'0',2);//@RETORNADO
    $mora = bcadd($R('mora','0.00'),'0',2);//@RETORNADO
    
    $calcular_interes_mora = function($a_pagar,$bruto_pagar,$cantidad_dias){
      //$coeff = log($a_pagar/$bruto_pagar)/$cantidad_dias;
      //$interes_mora = (exp($coeff)-1)*100;
      $coeff = bcln(bcdiv($a_pagar,$bruto_pagar,self::$max_scale),16);
      $coeff = bcdiv($coeff,$cantidad_dias,self::$max_scale);
      $interes_mora = bcepow($coeff,self::$max_scale);
      $interes_mora = bcsub($interes_mora,'1',self::$max_scale);
      $interes_mora = bcround_ndigits($interes_mora,6);
      return bcmul($interes_mora,'100',4);
    };
    
    if(bccomp($bruto_pagar,'0.00',2) <= 0){
      $a_pagar = '0.00';
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
        $a_pagar = $bruto_pagar;
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
      else if($R('interes_mora',null) !== null){//Si envio el interes, calculo el pago
        $interes_1dia = bcadd('1',bcdiv($interes_mora,'100',6),6);
        $interes_final = bcpowi($interes_1dia,$cantidad_dias.'',self::$max_scale);
        //$a_pagar = $bruto_pagar*pow(1+$interes_mora/100.0,$cantidad_dias);
        $a_pagar = bcmul($bruto_pagar,$interes_final,self::$max_scale);
        $a_pagar = bcround_ndigits($a_pagar,2);
        $mora = bcsub($a_pagar,$bruto_pagar,2);
      }
      else if($R('a_pagar',null) !== null){//Si envio el pago, calculo el interes
        $interes_mora = $calcular_interes_mora($a_pagar,$bruto_pagar,$cantidad_dias);
        $mora = bcsub($a_pagar,$bruto_pagar,2);//$mora = $a_pagar - $bruto_pagar;
      }
      else if($R('mora',null) !== null){
        $a_pagar = bcadd($bruto_pagar,$mora,2);
        $interes_mora = $calcular_interes_mora($a_pagar,$bruto_pagar,$cantidad_dias);
      }
      else {//Son todos nulos... asumo interes 0...
        $a_pagar = $bruto_pagar;
        $interes_mora = '0.0000';
        $mora = '0.00';
      }
    }
    
    $pago = bcadd($R('pago','0.00'),'0',2);//@RETORNADO
    $diferencia = bcsub($pago,$a_pagar,2);//@RETORNADO
    $saldo_anterior = '0.00';//@RETORNADO
    if($año_mes !== null && $id_casino !== null){
      $saldo_anterior = $this->calcular_saldo_hasta($año_mes,$id_casino);
    }
    
    $saldo_posterior = bcadd($saldo_anterior,$diferencia,2);//@RETORNADO
    
    return compact(
      'año_mes','id_casino','estado','es_antiguo',
      'canon_variable','canon_fijo_mesas','canon_fijo_mesas_adicionales','adjuntos',
      'bruto_devengado','deduccion','devengado','porcentaje_seguridad',
      'bruto_pagar','fecha_vencimiento','fecha_pago','interes_mora','mora',
      'a_pagar','pago','diferencia','saldo_anterior','saldo_posterior'
    );
  }
  
  private function calcular_saldo_hasta($año_mes,$id_casino){
    $saldo_anterior = DB::table('canon')
    ->selectRaw('SUM(diferencia) as saldo')//esto deberia ser DECIMAL asi que retorna un string
    ->where('id_casino',$id_casino)
    ->where('año_mes','<',$año_mes)
    ->groupBy(DB::raw('"constant"'))
    ->first();
    return $saldo_anterior === null? 0 : $saldo_anterior->saldo;
  }
  
  public function canon_variable_recalcular($tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    $apostado_sistema = bcadd($R('apostado_sistema','0.00'),'0',2);
    $apostado_informado = bcadd($R('apostado_informado','0.00'),'0',2);
    //El apostado es de DECIMAL(7,4)
    //@TODO: agregar asserts o sacar precision de la BD
    $apostado_porcentaje_aplicable = bcadd($RD('apostado_porcentaje_aplicable','0.0000'),'0',4);
    $base_imponible_devengado = bcdiv(bcmul($apostado_sistema,$apostado_porcentaje_aplicable,self::$max_scale),'100',2);
    $base_imponible_pagar     = bcdiv(bcmul($apostado_informado,$apostado_porcentaje_aplicable,self::$max_scale),'100',2);
    
    $apostado_porcentaje_impuesto_ley = bcadd($RD('apostado_porcentaje_impuesto_ley','0.0000'),'0',4);
    $impuesto_devengado = bcdiv(bcmul($base_imponible_devengado,$apostado_porcentaje_impuesto_ley,self::$max_scale),'100',2);
    $impuesto_pagar = bcdiv(bcmul($base_imponible_pagar,$apostado_porcentaje_impuesto_ley,self::$max_scale),'100',2);
    
    $bruto = bcadd($R('bruto','0.00'),'0',2);
    $subtotal_devengado = bcsub($bruto,$impuesto_devengado,2);
    $subtotal_pagar     = bcsub($bruto,$impuesto_pagar,2);
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);
    $total_devengado = bcdiv(bcmul($subtotal_devengado,$alicuota,self::$max_scale),'100',2);
    $total_pagar = bcdiv(bcmul($subtotal_pagar,$alicuota,self::$max_scale),'100',2);
    
    return compact('tipo',
      'apostado_sistema','apostado_informado',
      'apostado_porcentaje_aplicable','base_imponible_devengado','base_imponible_pagar',
      'apostado_porcentaje_impuesto_ley','impuesto_devengado','impuesto_pagar',
      'bruto','subtotal_devengado','subtotal_pagar',
      'alicuota','total_devengado','total_pagar'
    );
  }
  
  public function mesas_recalcular(
      $año_mes,$id_casino,
      $fecha_cotizacion_devengado,//@RETORNADO
      $fecha_cotizacion_pagar,//@RETORNADO
      $tipo,//@RETORNADO
      $valores_defecto,
      $data
  ){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $cotizacion_dolar_devengado = bcadd($R(
      'cotizacion_dolar_devengado',
      $fecha_cotizacion_devengado !== null? ($this->cotizacion($fecha_cotizacion_devengado,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $cotizacion_euro_devengado = bcadd($R(
      'cotizacion_euro_devengado',
      $fecha_cotizacion_devengado !== null? ($this->cotizacion($fecha_cotizacion_devengado,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $cotizacion_dolar_pagar = bcadd($R(
      'cotizacion_dolar_pagar',
      $fecha_cotizacion_pagar !== null? ($this->cotizacion($fecha_cotizacion_pagar,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    $cotizacion_euro_pagar = bcadd($R(
      'cotizacion_euro_pagar',
      $fecha_cotizacion_pagar !== null? ($this->cotizacion($fecha_cotizacion_pagar,2) ?? '0.00') : '0.00'
    ),'0',2);//@RETORNADO
    
    $valor_dolar = '0.00';//@RETORNADO
    $valor_euro  = '0.00';//@RETORNADO
    if($id_casino !== null){
      $valor_dolar = bcadd($RD('valor_dolar',$valor_dolar),'0',2);
      $valor_euro  = bcadd($RD('valor_euro',$valor_euro),'0',2);
    }
    
    $dias_valor = $RD('dias_valor',0);//@RETORNADO
    $valor_diario_dolar_devengado = '0.00';//@RETORNADO
    $valor_diario_euro_devengado  = '0.00';//@RETORNADO
    $valor_diario_dolar_pagar = '0.00';//@RETORNADO
    $valor_diario_euro_pagar  = '0.00';//@RETORNADO
    if($dias_valor != 0){//No entra si es =0, nulo, o falta
      $valor_diario_dolar_devengado = bcdiv(bcmul($cotizacion_dolar_devengado,$valor_dolar,self::$max_scale),$dias_valor,self::$max_scale);
      $valor_diario_euro_devengado  = bcdiv(bcmul($cotizacion_euro_devengado,$valor_euro,self::$max_scale),$dias_valor,self::$max_scale);
      $valor_diario_dolar_pagar = bcdiv(bcmul($cotizacion_dolar_pagar,$valor_dolar,self::$max_scale),$dias_valor,self::$max_scale);
      $valor_diario_euro_pagar  = bcdiv(bcmul($cotizacion_euro_pagar,$valor_euro,self::$max_scale),$dias_valor,self::$max_scale);
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
      
      $dias_lunes_jueves = $calcular_dias_lunes_jueves? 
        $R('dias_lunes_jueves',$wdmin_wdmax_count_arr['dias_lunes_jueves'][2])
      : 0;
      $dias_viernes_sabados = $calcular_dias_viernes_sabados? 
        $R('dias_viernes_sabados',$wdmin_wdmax_count_arr['dias_viernes_sabados'][2])
      : 0;
      $dias_domingos = $calcular_dias_domingos? 
        $R('dias_domingos',$wdmin_wdmax_count_arr['dias_domingos'][2])
      : 0;
      $dias_todos = $calcular_dias_todos? 
        $R('dias_todos',$wdmin_wdmax_count_arr['dias_todos'][2])
      : 0;
    }
    
    $mesas_lunes_jueves      = $R('mesas_lunes_jueves',0);//@RETORNADO
    $mesas_viernes_sabados   = $R('mesas_viernes_sabados',0);//@RETORNADO
    $mesas_domingos          = $R('mesas_domingos',0);//@RETORNADO
    $mesas_todos             = $R('mesas_todos',0);//@RETORNADO
    $mesas_fijos             = $R('mesas_fijos',0);//@RETORNADO
        
    $mesasdias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;
    
    $total_dolar_devengado = bcmul($valor_diario_dolar_devengado,$mesasdias,self::$max_scale);//@RETORNADO
    $total_euro_devengado  = bcmul($valor_diario_euro_devengado,$mesasdias,self::$max_scale);//@RETORNADO
    $total_devengado = bcadd($total_dolar_devengado,$total_euro_devengado,self::$max_scale);//@RETORNADO
    
    $total_dolar_pagar = bcmul($valor_diario_dolar_pagar,$mesasdias,self::$max_scale);//@RETORNADO
    $total_euro_pagar  = bcmul($valor_diario_euro_pagar,$mesasdias,self::$max_scale);//@RETORNADO
    $total_pagar = bcadd($total_dolar_pagar,$total_euro_pagar,self::$max_scale);//@RETORNADO
    
    //Redondeo todo
    $total_dolar_devengado = bcround_ndigits($total_dolar_devengado,2);
    $total_euro_devengado = bcround_ndigits($total_euro_devengado,2);
    $total_devengado = bcround_ndigits($total_devengado,2);
    $total_dolar_pagar = bcround_ndigits($total_dolar_pagar,2);
    $total_euro_pagar = bcround_ndigits($total_euro_pagar,2);
    $total_pagar = bcround_ndigits($total_pagar,2);
    $valor_diario_dolar_devengado = bcround_ndigits($valor_diario_dolar_devengado,2);
    $valor_diario_euro_devengado  = bcround_ndigits($valor_diario_euro_devengado,2);
    $valor_diario_dolar_pagar = bcround_ndigits($valor_diario_dolar_pagar,2);
    $valor_diario_euro_pagar  = bcround_ndigits($valor_diario_euro_pagar,2);
    
    return compact(
      'tipo','dias_valor','valor_dolar','valor_euro',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      
      'fecha_cotizacion_devengado','cotizacion_dolar_devengado','cotizacion_euro_devengado',
      'valor_diario_dolar_devengado','valor_diario_euro_devengado',
      'total_dolar_devengado','total_euro_devengado','total_devengado',
      
      'fecha_cotizacion_pagar','cotizacion_dolar_pagar','cotizacion_euro_pagar',
      'valor_diario_dolar_pagar','valor_diario_euro_pagar',
      'total_dolar_pagar','total_euro_pagar','total_pagar'
    );
  }
  
  public function mesasAdicionales_recalcular($tipo,$valores_defecto,$data){
    $R = function($s,$dflt = null) use (&$data){
      return (($data[$s] ?? null) === null || ($data[$s] == '') || ($data[$s] == []))? $dflt : $data[$s];
    };
    $D = function($s,$dflt = null) use (&$valores_defecto){
      return (($valores_defecto[$s] ?? null) === null || ($valores_defecto[$s] == '') || ($valores_defecto[$s] == []))? $dflt : $valores_defecto[$s];
    };
    $RD = function($s,$dflt = null) use ($R,$D){
      return $R($s,null) ?? $D($s,null) ?? $dflt;
    };
    
    $valor_mensual = bcadd($RD('valor_mensual','0.00'),'0',2);//@RETORNADO
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    $porcentaje    = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    
    $valor_diario = '0.00';//@RETORNADO
    if($dias_mes != 0){
      $valor_diario = bcdiv($valor_mensual,$dias_mes,2);
    }
    
    $valor_hora = '0.00';//@RETORNADO
    if($dias_mes != 0 && $horas_dia != 0){
      $valor_hora = bcdiv($valor_mensual,$horas_dia*$dias_mes,2);
    }
       
    $horas = $R('horas',0);//@RETORNADO
    $mesas = $R('mesas',0);//@RETORNADO
    $total_sin_aplicar_porcentaje = bcmul($valor_hora,($horas*$mesas),2);
    
    $total_devengado = bcdiv(bcmul($total_sin_aplicar_porcentaje,$porcentaje,self::$max_scale),'100',2);//@RETORNADO
    $total_pagar = $total_devengado;//@RETORNADO
    
    return compact('tipo','valor_mensual','dias_mes','valor_diario','horas_dia','valor_hora','horas','mesas','porcentaje','total_devengado','total_pagar');
  }
  
  public function adjuntar(Request $request){
    return $this->guardar($request,false);
  }
  
  public function guardar(Request $request,$recalcular = true){
    $requeridos = $recalcular? ['año_mes','id_casino','es_antiguo'] : ['id_canon'];
    $this->validarCanon($request->all(),$requeridos);
    
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
        'bruto_devengado' => $datos['bruto_devengado'],
        'deduccion' => $datos['deduccion'],
        'devengado' => $datos['devengado'],
        'porcentaje_seguridad' => $datos['porcentaje_seguridad'], 
        'fecha_vencimiento' => $datos['fecha_vencimiento'],
        'fecha_pago' => $datos['fecha_pago'],
        'bruto_pagar' => $datos['bruto_pagar'],
        'interes_mora' => $datos['interes_mora'],
        'mora' => $datos['mora'],
        'a_pagar' => $datos['a_pagar'],
        'pago' => $datos['pago'],
        'diferencia' => $datos['diferencia'],
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
    
    $existe_canon = $ret !== null;
    $ret = $ret ?? [];
        
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
      $adj->link = '/Ncanon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    
    return $existe_canon? $ret : $this->recalcular($ret);
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
  
  public function borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
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
    
    //@HACK: asume que esta ordenado por año_mes descendiente
    //cambiar el algoritmo si se da la posibilidiad de reordenar
    $saldo_anterior = [];
    $ret2['data'] = $ret->reverse()->transform(function(&$c) use (&$saldo_anterior){
      if(($saldo_anterior[$c->id_casino] ?? null) === null){
        $saldo_anterior[$c->id_casino] = $this->calcular_saldo_hasta($c->año_mes,$c->id_casino);
      }
      $c->saldo_posterior = $saldo_anterior[$c->id_casino]+$c->diferencia;
      $saldo_anterior[$c->id_casino] = $c->saldo_posterior;
      return $c;
    })->reverse();
    
    return $ret2;
  }
  
  public function cotizacion($fecha_cotizacion,$id_tipo_moneda){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return null;
    return null;//@TODO
  }
  
  private function valorPorDefecto($k){
    $db = DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->where('campo',$k)
    ->first();
        
    $val = is_null($db)? '{}' : preg_replace('/(\r\n|\n|\s\s+)/i','',$db->valor);
    
    return json_decode($val,true);
  }
    
  public function valoresPorDefecto(Request $request){
    return DB::table('canon_valores_por_defecto')
    ->whereNull('deleted_at')
    ->orderBy('campo','asc')
    ->paginate($request->page_size);
  }
  
  public function valoresPorDefecto_ingresar(Request $request){
    return DB::transaction(function() use ($request){
      $created_at = date('Y-m-d h:i:s');
      $id_usuario = UsuarioController::getInstancia()->quienSoy()['usuario']->id_usuario;
      
      $vals_viejos = DB::table('canon_valores_por_defecto')
      ->whereNull('deleted_at')
      ->where('campo',$request->campo ?? '')->get();
      foreach($vals_viejos as $v){
        $this->valoresPorDefecto_borrar_arr(['id_canon_valor_por_defecto' => $v->id_canon_valor_por_defecto],$created_at,$id_usuario);
      }
      
      DB::table('canon_valores_por_defecto')
      ->insert([
        'campo' => $request->campo ?? '',
        'valor' => $request->valor ?? '',
        'created_at' => $created_at,
        'deleted_at' => null,
        'created_id_usuario' => $id_usuario,
        'deleted_id_usuario' => null,
      ]);
      
      return 1;
    });
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
  
  public function valoresPorDefecto_borrar(Request $request,$deleted_at = null,$deleted_id_usuario = null){
    return $this->valoresPorDefecto_borrar_arr($request,$deleted_at,$deleted_id_usuario);
  }
  
  public function valoresPorDefecto_borrar_arr($arr,$deleted_at = null,$deleted_id_usuario = null){
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
  
  private function obtener_para_salida($id_canon){
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
        'Content-Type' => 'text/plain',
        'Content-Disposition' => 'inline; filename="'.$filename.'"'
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
}
