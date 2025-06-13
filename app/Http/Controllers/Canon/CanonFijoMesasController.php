<?php

namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Canon\CanonController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Plataforma;
use App\Casino;
require_once(app_path('BC_extendido.php'));

class CanonFijoMesasController extends Controller
{
  public $table = 'canon_fijo_mesas';
  public $id    = 'id_canon_fijo_mesas';
  public $valorPorDefecto = '{"1":{"Fijas":{"dias_valor":30,"calcular_dias_lunes_jueves":false,"calcular_dias_viernes_sabados":false,"calcular_dias_domingos":false,"calcular_dias_todos":false,"dias_fijos":30,"mesas_fijos":15,"devengado_deduccion":"60000"}},"2":{"Diarias":{"dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"120000","mesas_lunes_jueves":16,"mesas_viernes_sabados":25,"mesas_domingos":21}},"3":{"Diarias":{"dias_valor":30,"calcular_dias_lunes_jueves":true,"calcular_dias_viernes_sabados":true,"calcular_dias_domingos":true,"calcular_dias_todos":true,"dias_fijos":0,"devengado_deduccion":"240000","mesas_lunes_jueves":40,"mesas_viernes_sabados":50,"mesas_domingos":45}}}';
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
      
  public function validar(){
    return [
      'canon_fijo_mesas' => 'array',
      'canon_fijo_mesas.*.dias_valor' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.dias_lunes_jueves' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_lunes_jueves' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.dias_viernes_sabados' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_viernes_sabados' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.dias_domingos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_domingos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.dias_todos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_todos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.dias_fijos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.mesas_fijos' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas.*.devengado_deduccion' => ['nullable',AUX::numeric_rule(2)],
      'canon_fijo_mesas.*.determinado_ajuste' => ['nullable',AUX::numeric_rule(22)],
      'canon_fijo_mesas.*.bruto' => ['nullable',AUX::numeric_rule(2)],
    ];
  }
    
  public function recalcular($año_mes,$id_casino,$es_antiguo,$tipo,$accessors){
    extract($accessors);
    
    $valor_dolar = $COT('valor_dolar');//@RETORNADO
    $valor_euro  = $COT('valor_euro');//@RETORNADO
    $devengado_fecha_cotizacion = $COT('devengado_fecha_cotizacion');//@RETORNADO
    $determinado_fecha_cotizacion = $COT('determinado_fecha_cotizacion');//@RETORNADO
    $devengado_cotizacion_dolar = $COT('devengado_cotizacion_dolar','0');//@RETORNADO
    $devengado_cotizacion_euro = $COT('devengado_cotizacion_euro','0');//@$RETORNADO
    $determinado_cotizacion_dolar = $COT('determinado_cotizacion_dolar','0');//@RETORNADO
    $determinado_cotizacion_euro = $COT('determinado_cotizacion_euro','0');//@RETORNADO
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    
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
    
  public function guardar($id_canon,$id_canon_anterior,$datos){
    foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo'] = $tipo;
      unset($d['id_canon_fijo_mesas']);
      DB::table('canon_fijo_mesas')
      ->insert($d);
    }
    return 1;
  }
    
  public function obtener($id_canon){
    $ret = [];
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
       
    return $ret;
  }
    
  public function procesar_para_salida($data){
    $ret = [];
    foreach(['id_canon_fijo_mesas','id_canon'] as $k){
      foreach(($data['canon_fijo_mesas'] ?? []) as $tipo => $_){
        unset($data['canon_fijo_mesas'][$tipo][$k]);
      }
    }
    $ret['canon_fijo_mesas'] = $data['canon_fijo_mesas'] ?? [];
    
    return $ret;
  }
  
  public function confluir($data){
    return CanonController::confluir_datos(
      $data,
      ['canon_fijo_mesas'],
      [
        'valor_dolar','valor_euro','devengado_fecha_cotizacion',
        'determinado_fecha_cotizacion',
        'devengado_cotizacion_dolar','devengado_cotizacion_euro',
        'determinado_cotizacion_dolar','determinado_cotizacion_euro'
      ]
    );
  }

  public function bruto($tipo,$año_mes,$id_casino){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
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
