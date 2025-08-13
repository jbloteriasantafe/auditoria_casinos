<?php

namespace App\Http\Controllers\Canon;

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
    return [
      'canon_fijo_mesas_adicionales' => 'array',
      'canon_fijo_mesas_adicionales.*.dias_mes' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas_dia' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.horas' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.mesas' => ['nullable',AUX::numeric_rule(0)],
      'canon_fijo_mesas_adicionales.*.porcentaje' => ['nullable',AUX::numeric_rule(4)],
      'canon_fijo_mesas_adicionales.*.devengado_deduccion' => ['nullable',AUX::numeric_rule(2)],
      'canon_fijo_mesas_adicionales.*.determinado_ajuste' => ['nullable',AUX::numeric_rule(22)],
    ];
  }
  
  public function es($tipo,$concepto){
    static $tipos = null;
    $tipos = $tipos 
    ?? DB::table('canon_fijo_mesas_adicionales')->selectRaw('tipo')->distinct()->pluck('tipo')->toArray();
    
    if($concepto == '') return true;
    
    $concepto = strtoupper($concepto);
    
    return in_array($tipo,$tipos)
    &&     in_array($concepto,['','MESA','FÍSICO','FíSICO']);
  }
  
  public function totales($id_canon){
    return DB::table('canon_fijo_mesas_adicionales')
    ->select('tipo',
      DB::raw('NULL as beneficio'),
      DB::raw('SUM(IF(devengar,devengado_deduccion+devengado,NULL)) as bruto'),
      DB::raw('SUM(IF(devengar,devengado_deduccion,NULL)) as deduccion'),
      DB::raw('SUM(IF(devengar,devengado,NULL)) as devengado'),
      DB::raw('SUM(determinado) as determinado')
    )
    ->where('id_canon',$id_canon)
    ->groupBy('tipo')
    ->get()
    ->keyBy('tipo')->toArray();
  }
  
  private static function calcular_valor_mesa(
    $cotizacion_dolar,$cotizacion_euro,
    $valor_dolar,$valor_euro,
    $factor_dias_mes,$factor_horas_mes
  ){
    $valor_mes = bcadd(
      bcmul($valor_dolar,$cotizacion_dolar,4),//2+2
      bcmul($valor_euro,$cotizacion_euro,4),//2+2
      4
    );
    $valor_dia  = bcmul($valor_mes,$factor_dias_mes,16);//4+12 @RETORNADO
    $valor_hora = bcmul($valor_mes,$factor_horas_mes,16);//4+12 @RETORNADO
    
    return compact('valor_mes','valor_dia','valor_hora');
  }
  
  //Sumo de valores mas precisos a menos precisos
  private static function calcular_total($valor_mesa,$horas,$mesas,$horas_dia,$horas_mes,$factor_porcentaje){
    $horas_totales = ($horas ?? 0) + ($mesas ?? 0)*$horas_dia;
    
    $meses = intdiv($horas_totales,$horas_mes);
    $horas_restantes = $horas_totales%$horas_mes;
      
    $dias = intdiv($horas_restantes,$horas_dia);
    $horas_restantes = $horas_totales%$horas_dia;
    
    $total = bcmul($valor_mesa['valor_mes'],$meses,4);
    $total = bcadd($total,bcmul($valor_mesa['valor_dia'],$dias,16),16);
    $total = bcadd($total,bcmul($valor_mesa['valor_hora'],$horas_restantes,16),16);
      
    return bcmul($total,$factor_porcentaje,22);//16+6 @RETORNADO
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
    
    $dias_mes      = $RD('dias_mes',0);//@RETORNADO
    $horas_dia     = $RD('horas_dia',0);//@RETORNADO
    
    $factor_dias_mes  = ($dias_mes != 0)? bcdiv('1',$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    $factor_horas_mes = ($horas_dia != 0 && $dias_mes != 0)? bcdiv('1',$horas_dia*$dias_mes,12) : '0.000000000000';//@RETORNADO Un error de una milesima de peso en 1 billon
    
    $horas = $R('horas',0);//@RETORNADO
    $mesas = $R('mesas',0);//@RETORNADO
        
    $porcentaje = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    $factor_porcentaje = bcdiv($porcentaje,'100',6);
        
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    
    $devengado_valor_mesa = self::calcular_valor_mesa(
      $devengado_cotizacion_dolar,$devengado_cotizacion_euro,
      $valor_dolar,$valor_euro,
      $factor_dias_mes,$factor_horas_mes
    );
    
    $devengado_valor_mes  = $devengado_valor_mesa['valor_mes'];//@RETORNADO
    $devengado_valor_dia  = $devengado_valor_mesa['valor_dia'];//@RETORNADO
    $devengado_valor_hora = $devengado_valor_mesa['valor_hora'];//@RETORNADO
    
    $determinado_valor_mesa = self::calcular_valor_mesa(
      $determinado_cotizacion_dolar,$determinado_cotizacion_euro,
      $valor_dolar,$valor_euro,
      $factor_dias_mes,$factor_horas_mes
    );
    
    $determinado_valor_mes  = $determinado_valor_mesa['valor_mes'];//@RETORNADO
    $determinado_valor_dia  = $determinado_valor_mesa['valor_dia'];//@RETORNADO
    $determinado_valor_hora = $determinado_valor_mesa['valor_hora'];//@RETORNADO
    
    $horas_mes = $horas_dia*$dias_mes;
    
    $devengado_total = self::calcular_total(
      $devengado_valor_mesa,
      $horas,$mesas,
      $horas_dia,$horas_mes,
      $factor_porcentaje
    );//@RETORNADO
            
    $determinado_total = self::calcular_total(
      $determinado_valor_mesa,
      $horas,$mesas,
      $horas_dia,$horas_mes,
      $factor_porcentaje
    );//@RETORNADO
        
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste = bcadd($RD('determinado_ajuste','0.00'),'0',22);//@RETORNADO
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,22);
    $determinado = bcadd($determinado_total,$determinado_ajuste,22);
    
    $accesors_diario = [
      'R' => AUX::make_accessor($R('diario',[])),
      'A' => AUX::make_accessor($A('diario',[])),
      'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
    ];
    $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
    
    $diario = $this->recalcular_diario(
      $año_mes,$id_casino,$es_antiguo,$tipo,
      $accesors_diario,
      $valor_dolar,$valor_euro,
      $horas_dia,$horas_mes,
      $factor_dias_mes,$factor_horas_mes
    )['diario'] ?? [];//@RETORNADO
    
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
      'determinado',
      'diario','canon_cotizacion_diaria','errores'
    );
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$es_antiguo,$tipo,
    $accessors,
    $valor_dolar,$valor_euro,
    $horas_dia,$horas_mes,
    $factor_dias_mes,$factor_horas_mes
  ){
    extract($accessors);
    
    $año_mes = explode('-',$año_mes);
    $dias = cal_days_in_month(CAL_GREGORIAN,intval($año_mes[1]),intval($año_mes[0]));
    
    $diario = [];
    
    $horas = 0;
    $mesas = 0;
    
    $año_mes_str = $año_mes[0].'-'.$año_mes[1].'-';
    
    for($dia=1;$dia<=$dias;$dia++){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = $año_mes_str.str_pad($dia,2,'0',STR_PAD_LEFT);
      $horas_diarias = $D('horas_diarias',0);
      $mesas_diarias = $D('mesas_diarias',0);
      $horas+=$horas_diarias;
      $mesas+=$mesas_diarias;
      
      $cotizacion_dolar = '0';//@TODO
      $cotizacion_euro  = '0';//@TODO
      
      //Al tener la misma fecha de cotizacion, el total devengado y el total determinado es el mismo
      $valor_mesa = self::calcular_valor_mesa(
        $cotizacion_dolar,$cotizacion_euro,
        $valor_dolar,$valor_euro,
        $factor_dias_mes,$factor_horas_mes
      );
      
      $devengado_valor_mes  = $valor_mesa['valor_mes'];
      $devengado_valor_dia  = $valor_mesa['valor_dia'];
      $devengado_valor_hora = $valor_mesa['valor_hora'];
      $determinado_valor_mes  = $valor_mesa['valor_mes'];
      $determinado_valor_dia  = $valor_mesa['valor_dia'];
      $determinado_valor_hora = $valor_mesa['valor_hora'];
      
      $devengado_total   = self::calcular_total($valor_mesa,$horas,$mesas,$horas_dia,$horas_mes,'1');
      $determinado_total = $devengado_total;
      
      $diario[$dia] = compact(
        'dia','fecha','horas_diarias','mesas_diarias','horas','mesas',
        'devengado_valor_mes','devengado_valor_dia','devengado_valor_hora','devengado_total',
        'determinado_valor_mes','determinado_valor_dia','determinado_valor_hora','determinado_total'
      );
    }
    
    return compact('diario');
  }
  
  public function guardar($id_canon,$id_canon_anterior,$datos){
    foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo']     = $tipo;
      unset($d['id_canon_fijo_mesas_adicionales']);
      DB::table('canon_fijo_mesas_adicionales')
      ->insert($d);
    }
  }
  
  public function obtener_diario($id_canon_fijo_mesas_adicionales){
    return DB::table('canon_fijo_mesas_adicionales_diario')
    ->where('id_canon_fijo_mesas_adicionales',$id_canon_fijo_mesas_adicionales)
    ->orderBy('fecha','asc')
    ->get()
    ->transform(function(&$d){
      $d->dia = intval(substr($d->fecha,strlen('XXXX-XX-'),2));
      return $d;
    })
    ->keyBy('dia');
  }
  
  public function obtener($id_canon){
    $ret = [];
    $ret['canon_fijo_mesas_adicionales'] = DB::table('canon_fijo_mesas_adicionales')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
    
    foreach($ret['canon_fijo_mesas_adicionales'] as $tipo => $datatipo){
      $datatipo->diario = $this->obtener_diario($datatipo->id_canon_fijo_mesas_adicionales);
    }
       
    return $ret;
  }
    
  public function procesar_para_salida($data){
    $ret = [];
    foreach(['id_canon_fijo_mesas_adicionales','id_canon'] as $k){
      foreach(($data['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $_){
        unset($data['canon_fijo_mesas_adicionales'][$tipo][$k]);
      }
    }
    $ret['canon_fijo_mesas_adicionales'] = $data['canon_fijo_mesas_adicionales'] ?? [];
    
    return $ret;
  }
  
  public function confluir($data){
    $ret = AUX::confluir_datos(
      $data,
      ['canon_fijo_mesas_adicionales'],
      [
        'valor_dolar','valor_euro','devengado_fecha_cotizacion',
        'determinado_fecha_cotizacion',
        'devengado_cotizacion_dolar','devengado_cotizacion_euro',
        'determinado_cotizacion_dolar','determinado_cotizacion_euro'
      ]
    );
    
    $ret['canon_cotizacion_diaria'] = [];
    foreach(($data['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $datatipo){
      foreach(($datatipo['diario'] ?? []) as $dia => $datadia){
        $ret['canon_cotizacion_diaria'][$dia] = [
          'dia' => $dia,'USD' => null,'EUR' => null
        ];
      }
    }
    
    return $ret;
  }
  
  public function datosCanon($tname){
    $attrs_canon = [
      'canon_fisico' => 'SUM(cfma.determinado) as canon_fisico',
      'canon_online' => '0 as canon_online',
      'ganancia_fisico' => '0 as ganancia_fisico',
      'ganancia_online' => '0 as ganancia_online',
      'ganancia' => '0 as ganancia',
      'ganancia_CCO' => '0 as ganancia_CCO',
      'ganancia_BPLAY' => '0 as ganancia_BPLAY'
    ];
    
    $tname2 = 't'.uniqid();
    DB::statement("CREATE TEMPORARY TABLE $tname2 AS
      SELECT $tname.casino,$tname.año,$tname.mes,".implode(',',$attrs_canon)."
      FROM $tname
      LEFT JOIN canon_fijo_mesas_adicionales as cfma ON cfma.id_canon = $tname.id_canon
      LEFT JOIN canon_fijo_mesas_adicionales as cfma_yoy ON cfma_yoy.id_canon = $tname.id_canon_yoy AND cfma_yoy.tipo LIKE cfma.tipo
      LEFT JOIN canon_fijo_mesas_adicionales as cfma_mom ON cfma_mom.id_canon = $tname.id_canon_mom AND cfma_mom.tipo LIKE cfma.tipo
      GROUP BY $tname.casino,$tname.año,$tname.mes
    ");
    
    $tables = [$tname2,array_keys($attrs_canon)];
    
    return $tables;
  }
}
