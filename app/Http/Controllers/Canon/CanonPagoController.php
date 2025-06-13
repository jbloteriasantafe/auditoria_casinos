<?php

namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Canon\CanonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
require_once(app_path('BC_extendido.php'));

class CanonPagoController extends Controller
{
  public $table = 'canon_pago';
  public $id    = 'id_canon_pago';
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function validar(){
    $CC = CanonController::getInstancia();
    return [
      'fecha_vencimiento' => ['nullable','date'],
      'interes_provincial_diario_simple' => ['nullable',$CC->numeric_rule(4)],
      'interes_nacional_mensual_compuesto' => ['nullable',$CC->numeric_rule(4)],
      'canon_pago' => 'array',
      'canon_pago.*.fecha_pago' => ['nullable','date'],
      'canon_pago.*.pago' => ['nullable',$CC->numeric_rule(2)],
    ];
  }
  
  public function recalcular($id_casino,$año_mes,$principal,$R){
    $canon_pago_defecto = (CanonValorPorDefectoController::getInstancia()->valorPorDefecto('canon_pago') ?? [])[$id_casino] ?? [];
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
    
    $canon_pago = $R('canon_pago',[[]]);//Si no tiene pagos le agrego uno vacio.
    foreach($canon_pago as &$p){//Lleno los valores faltantes de los pagos
      $p['fecha_pago'] = $p['fecha_pago'] ?? $PAG['fecha_vencimiento'] ?? null;
      $p['fecha_vencimiento'] = $PAG['fecha_vencimiento'] ?? null;
      $p['interes_provincial_diario_simple'] = $PAG['interes_provincial_diario_simple'] ?? null;
      $p['interes_nacional_mensual_compuesto'] = $PAG['interes_nacional_mensual_compuesto'] ?? null;
    }
    
    //Ordeno segun fecha de pago
    usort($canon_pago,function($a,$b){//Lo ordeno por fecha de pago
      $fa = $a['fecha_pago'] ?? null;
      $fb = $b['fecha_pago'] ?? null;
      
      if(!empty($fa) &&  empty($fb)) return -1;
      if( empty($fa) && !empty($fb)) return  1;
      if( empty($fa) &&  empty($fb)){
        return 0;
      }
      return $fa <= $fb? -1 : 1;
    });
    $canon_pago = array_values($canon_pago);
    
    $a_pagar = $principal;//@RETORNADO
    $pago = '0';//@RETORNADO
    
    $timestamp_venc = $PAG['fecha_vencimiento']?
      \DateTimeImmutable::createFromFormat('Y-m-d', $PAG['fecha_vencimiento'])
    : null;
    $factor_interes_provincial_diario_simple = bcdiv($PAG['interes_provincial_diario_simple'],'100',6);
    $factor_interes_nacional_mensual_compuesto = bcdiv($PAG['interes_nacional_mensual_compuesto'],'100',6);
    
    $restante = $principal;
    foreach($canon_pago as $idx => &$p){
      $p['capital'] = $restante;
      
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
    
    $ret = compact(
      'canon_pago','a_pagar','pago'
    );
    
    return $ret;
  }
  
  public function obtener($id_canon){
    $ret = [];
    $ret['canon_pago'] = DB::table('canon_pago')
    ->where('id_canon',$id_canon)
    ->get();
       
    return $ret;
  }
    
  public function procesar_para_salida($data){
    $ret = [];
    foreach(['id_canon_pago','id_canon'] as $k){
      foreach(($data['canon_pago'] ?? []) as $tipo => $_){
        unset($data['canon_pago'][$tipo][$k]);
      }
    }
    $ret['canon_pago'] = $data['canon_pago'] ?? [];
    
    return $ret;
  }

  public function confluir(array $data){
    return CanonController::confluir_datos(
      $data,
      ['canon_pago'],
      ['fecha_vencimiento','interes_provincial_diario_simple','interes_nacional_mensual_compuesto']
    );
  }
}
