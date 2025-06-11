<?php

namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Canon\CanonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
require_once(app_path('BC_extendido.php'));

class CanonFijoMesasAdicionalesController extends Controller
{
  public $table = 'canon_fijo_mesas_adicionales';
  public $id    = 'id_canon_fijo_mesas_adicionales';
  public $valorPorDefecto = '{"1":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Póker y RA":{"dias_mes":30,"horas_dia":16,"porcentaje":"100"},"Torneos de Truco":{"dias_mes":30,"horas_dia":16,"porcentaje":"20"}},"2":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":24,"porcentaje":"100"},"Torneos":{"dias_mes":30,"horas_dia":24,"porcentaje":"100"}},"3":{"Mesas Adicionales de Póker":{"dias_mes":30,"horas_dia":17,"porcentaje":"100"},"Torneos":{"dias_mes":30,"horas_dia":17,"porcentaje":"100"}}}';
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function validar(){
    $CC = CanonController::getInstancia();
    return [
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',$CC->numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',$CC->numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',$CC->numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',$CC->numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',$CC->numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['nullable',$CC->numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_ajuste' => ['nullable',$CC->numeric_rule(22)],
    ];
  }
  
  public function recalcular($año_mes,$id_casino,$es_antiguo,$tipo,$valores_defecto,$data,$COT,$anterior){
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
  
  public function guardar($id_canon,$datos){
    foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo']     = $tipo;
      unset($d['id_canon_fijo_mesas_adicionales']);
      DB::table('canon_fijo_mesas_adicionales')
      ->insert($d);
    }
  }
    
  public function obtener($id_canon){
    return DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
  }
    
  public function obtener_para_salida($data){
    foreach(['id_canon_fijo_mesas_adicionales','id_canon'] as $k){
      foreach(($data['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $_){
        unset($data['canon_fijo_mesas_adicionales'][$tipo][$k]);
      }
    }
    return array_values($data['canon_fijo_mesas_adicionales'] ?? []);
  }
  
  public function diario($id,$año_mes){
    $mensual;
    $diario;
    if($id !== null){
      $mensual = DB::table($this->table)->where($this->id,$id)->first();
      $año_mes = DB::table('canon',$mensual->id_canon)
      ->first()->año_mes;
      $dias_mes = intval(date('t',strtotime($año_mes)));
      $diario = array_map(function($d){//@TODO query tabla diaria
        return [
          'dia' => $d,
          'devengado' => [
            'bruto' => rand(100,200),
            'total' => rand(100,200),
          ],
          'determinado' => [
            'bruto' => rand(100,200),
            'total' => rand(100,200),
          ]
        ];
      },range(1,$dias_mes,1));
    }
    else if($año_mes !== null){
      $mensual = null;
      $dias_mes = intval(date('t',strtotime($año_mes)));
      $diario = array_map(function($d){
        return [
          'dia' => $d,
          'devengado' => [
            'bruto' => rand(100,200),
            'total' => rand(100,200),
          ],
          'determinado' => [
            'bruto' => rand(100,200),
            'total' => rand(100,200),
          ]
        ];
      },range(1,$dias_mes,1));
    }
    else{
      throw new \Exception('Unreachable');
    }
    
    return compact('mensual','diario');
  }
}
