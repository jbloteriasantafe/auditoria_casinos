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
  
  private static function calcular_valores($valor_dolar,$valor_euro,$dias_mes,$horas_dia,$porcentaje){
    $horas_mes = $dias_mes*$horas_dia;
    $factor_dias_mes   = ($dias_mes  != 0)? bcdiv('1', $dias_mes,12) : '0.000000000000';//Un error de una milesima de peso en 1 billon
    $factor_horas_mes  = ($horas_mes != 0)? bcdiv('1',$horas_mes,12) : '0.000000000000';
    $factor_porcentaje = bcdiv($porcentaje,'100',6);
    $valor_dolar_dia  = bcmul_precise($valor_dolar, $factor_dias_mes);
    $valor_euro_dia   = bcmul_precise( $valor_euro, $factor_dias_mes);
    $valor_dolar_hora = bcmul_precise($valor_dolar,$factor_horas_mes);
    $valor_euro_hora  = bcmul_precise( $valor_euro,$factor_horas_mes);
    return compact(
      'valor_dolar','valor_euro',
      'dias_mes','horas_dia','horas_mes','porcentaje',
      'factor_dias_mes','factor_horas_mes','factor_porcentaje',
      'valor_dolar_dia','valor_euro_dia',
      'valor_dolar_hora','valor_euro_hora'
    );
  }
  
  private static function calcular_valores_cotizados($cotizacion_dolar,$cotizacion_euro,$valores){
    $valor_euro_cotizado_mes = bcmul_precise($valores['valor_euro'],$cotizacion_euro);
    $valor_dolar_cotizado_mes = bcmul_precise($valores['valor_dolar'],$cotizacion_dolar);
    $valor_mes = bcadd_precise($valor_euro_cotizado_mes,$valor_dolar_cotizado_mes);
    
    $valor_euro_cotizado_dia = bcmul_precise($valor_euro_cotizado_mes,$valores['factor_dias_mes']);
    $valor_dolar_cotizado_dia = bcmul_precise($valor_dolar_cotizado_mes,$valores['factor_dias_mes']);
    $valor_dia  = bcmul_precise($valor_mes,$valores['factor_dias_mes']);
    
    $valor_euro_cotizado_hora = bcmul_precise($valor_euro_cotizado_mes,$valores['factor_horas_mes']);
    $valor_dolar_cotizado_hora = bcmul_precise($valor_dolar_cotizado_mes,$valores['factor_horas_mes']);
    $valor_hora = bcmul_precise($valor_mes,$valores['factor_horas_mes']);
    
    return compact(
      'cotizacion_dolar','cotizacion_euro',
      'valor_euro_cotizado_mes','valor_dolar_cotizado_mes','valor_mes',
      'valor_euro_cotizado_dia','valor_dolar_cotizado_dia','valor_dia',
      'valor_euro_cotizado_hora','valor_dolar_cotizado_hora','valor_hora'
    );
  }
  
  private static function calcular_meses_dias_horas($horas_totales,$mesas_totales,$valores){
    $horas_totales = ($horas_totales ?? 0) + ($mesas_totales ?? 0)*$valores['horas_dia'];//1 mesa = 1 dia
    $meses = 0;
    if($valores['horas_mes'] > 0){
      $meses = intdiv($horas_totales,$valores['horas_mes']);
      $horas_totales = $horas_totales%$valores['horas_mes'];
    }
    $dias = 0;
    if($valores['horas_dia'] > 0){
      $dias = intdiv($horas_totales,$valores['horas_dia']);
      $horas_totales = $horas_totales%$valores['horas_dia'];
    }
    $horas = $horas_totales;
    return compact('meses','dias','horas');
  }
  
  private static function calcular_total($meses_dias_horas,$factor_porcentaje,$valor_mes,$valor_dia,$valor_hora){
    $total = bcmul_precise($valor_mes,$meses_dias_horas['meses']);
    $total = bcadd_precise($total,bcmul_precise($valor_dia,$meses_dias_horas['dias']));
    $total = bcadd_precise($total,bcmul_precise($valor_hora,$meses_dias_horas['horas']));
    return bcmul_precise($total,$factor_porcentaje);
  }
  
  public function recalcular($año_mes,$id_casino,$version,$tipo,$accessors){
    extract($accessors);
    $devengar = $RD('devengar',0);
    
    $valor_dolar = $COT('valor_dolar');//@RETORNADO
    $valor_euro  = $COT('valor_euro');//@RETORNADO
    $devengado_fecha_cotizacion   = $COT('devengado_fecha_cotizacion');//@RETORNADO
    $determinado_fecha_cotizacion = $COT('determinado_fecha_cotizacion');//@RETORNADO
    $devengado_cotizacion_dolar   = $COT('devengado_cotizacion_dolar','0');//@RETORNADO
    $devengado_cotizacion_euro    = $COT('devengado_cotizacion_euro','0');//@$RETORNADO
    $determinado_cotizacion_dolar = $COT('determinado_cotizacion_dolar','0');//@RETORNADO
    $determinado_cotizacion_euro  = $COT('determinado_cotizacion_euro','0');//@RETORNADO
    
    $dias_mes  = $RD('dias_mes',0);//@RETORNADO
    $horas_dia = $RD('horas_dia',0);//@RETORNADO
    $porcentaje = bcadd($RD('porcentaje','0.0000'),'0',4);//@RETORNADO
    $valores = self::calcular_valores($valor_dolar,$valor_euro,$dias_mes,$horas_dia,$porcentaje);
    $devengado_valor_cotizado = self::calcular_valores_cotizados(
      $devengado_cotizacion_dolar,$devengado_cotizacion_euro,$valores
    );
    $determinado_valor_cotizado = self::calcular_valores_cotizados(
      $determinado_cotizacion_dolar,$determinado_cotizacion_euro,$valores
    );
        
    $accesors_diario = [
      'R' => AUX::make_accessor($R('diario',[])),
      'A' => AUX::make_accessor($A('diario',[])),
      'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
    ];
    $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
    $diario = $this->recalcular_diario(
      $año_mes,$id_casino,$version,$tipo,
      $accesors_diario,
      $valores
    )['diario'] ?? [];//@RETORNADO
        
    $horas = 0;//@RETORNADO
    $mesas = 0;//@RETORNADO
    if($version == 'mensual' || $version == 'antiguo'){
      $horas = $R('horas',0);
      $mesas = $R('mesas',0);
    }
    else if($version == 'diario'){
      foreach($diario as $d){
        $horas+=$d['horas_diarias'] ?? 0;
        $mesas+=$d['mesas_diarias'] ?? 0;
      }
    }
    
    $meses_dias_horas = self::calcular_meses_dias_horas($horas,$mesas,$valores);
    $total_dolar = self::calcular_total(
      $meses_dias_horas,
      $valores['factor_porcentaje'],
      $valores['valor_dolar'],
      $valores['valor_dolar_dia'],
      $valores['valor_dolar_hora']
    );
    $total_euro = self::calcular_total(
      $meses_dias_horas,
      $valores['factor_porcentaje'],
      $valores['valor_euro'],
      $valores['valor_euro_dia'],
      $valores['valor_euro_hora']
    );
    $devengado_total = self::calcular_total(
      $meses_dias_horas,
      $valores['factor_porcentaje'],
      $devengado_valor_cotizado['valor_mes'],
      $devengado_valor_cotizado['valor_dia'],
      $devengado_valor_cotizado['valor_hora']
    );
    $determinado_total = self::calcular_total(
      $meses_dias_horas,
      $valores['factor_porcentaje'],
      $determinado_valor_cotizado['valor_mes'],
      $determinado_valor_cotizado['valor_dia'],
      $determinado_valor_cotizado['valor_hora']
    );
    
    $ret = array_merge(compact(
      'tipo',
      'horas','mesas',
      'devengar',
      'devengado_fecha_cotizacion','determinado_fecha_cotizacion',
      'total_dolar','total_euro',
      'devengado_total','determinado_total',
      'diario'
    ),$valores);
    
    foreach($devengado_valor_cotizado as $k => $v){
      $ret['devengado_'.$k] = $v;
    }
    foreach($determinado_valor_cotizado as $k => $v){
      $ret['determinado_'.$k] = $v;
    }
    
    $ret['devengado_deduccion'] = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $ret['determinado_ajuste'] = bcadd($RD('determinado_ajuste','0.00'),'0',22);//@RETORNADO
    if($version == 'antiguo'){
      $ret['devengado_total'] = $R('devengado_total',$ret['devengado_total']);
      $ret['determinado_total'] = $R('determinado_total',$ret['determinado_total']);
    }
        
    $ret['devengado']   = bcsub($ret['devengado_total'],$ret['devengado_deduccion'],22);
    $ret['determinado'] = bcadd($ret['determinado_total'],$ret['determinado_ajuste'],22);
    
    return $ret;
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$version,$tipo,
    $accessors,$valores
  ){
    extract($accessors);
    
    $año_mes = explode('-',$año_mes);
    
    $diario = [];
    
    $horas = 0;
    $mesas = 0;
    
    $año_mes_str = $año_mes[0].'-'.$año_mes[1].'-';
    $cotizaciones = $COT(null,[]);
    foreach($cotizaciones as $dia => $cot){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = $año_mes_str.str_pad($dia,2,'0',STR_PAD_LEFT);
      $horas_diarias = $D('horas_diarias',0);
      $mesas_diarias = $D('mesas_diarias',0);
      $horas+=$horas_diarias;
      $mesas+=$mesas_diarias;
      
      $cotizacion_dolar = $cot['dolar'] ?? '0';
      $cotizacion_euro  = $cot['euro'] ?? '0';
      
      $meses_dias_horas = self::calcular_meses_dias_horas($horas,$mesas,$valores);
      $valores_cotizados = self::calcular_valores_cotizados($cotizacion_dolar,$cotizacion_euro,$valores);
      $total_dolar = self::calcular_total(
        $meses_dias_horas,
        $valores['factor_porcentaje'],
        $valores['valor_dolar'],
        $valores['valor_dolar_dia'],
        $valores['valor_dolar_hora']
      );
      $total_euro = self::calcular_total(
        $meses_dias_horas,
        $valores['factor_porcentaje'],
        $valores['valor_euro'],
        $valores['valor_euro_dia'],
        $valores['valor_euro_hora']
      );
      $total = self::calcular_total(
        $meses_dias_horas,
        $valores['factor_porcentaje'],
        $valores_cotizados['valor_mes'],
        $valores_cotizados['valor_dia'],
        $valores_cotizados['valor_hora']
      );
      
      $diario[$dia] = array_merge(compact(
          'dia','fecha','horas_diarias','mesas_diarias','horas','mesas',
          'total_dolar','total_euro','total'
        ),
        $valores,
        $valores_cotizados
      );
    }
    
    return compact('diario');
  }
  
  public function guardar($id_canon,$id_canon_anterior,$datos){
    foreach(($datos['canon_fijo_mesas_adicionales'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo']     = $tipo;
      unset($d['id_canon_fijo_mesas_adicionales']);
      $diario = $d['diario'] ?? [];
      unset($d['diario']);
      $id_canon_fijo_mesas_adicionales = DB::table('canon_fijo_mesas_adicionales')
      ->insertGetId($d);
      foreach($diario as $d){
        $d['id_canon_fijo_mesas_adicionales'] = $id_canon_fijo_mesas_adicionales;
        DB::table('canon_fijo_mesas_adicionales_diario')
        ->insert($d);
      }
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
          'dia' => $dia,
          'dolar' => ($datadia['cotizacion_dolar'] ?? null),
          'euro' =>  ($datadia['cotizacion_euro'] ?? null)
        ];
      }
    }
    
    return $ret;
  }
  
  public function datosCanon(){
    return [
      'canon_fisico' => "SUM(subcanon.determinado+subcanon.determinado_ajuste) as canon_fijo_mesas_adicionales¡canon_fisico",
      'canon_online' => '0 as canon_fijo_mesas_adicionales¡canon_online',
      'ganancia_fisico' => '0 as canon_fijo_mesas_adicionales¡ganancia_fisico',
      'ganancia_online' => '0 as canon_fijo_mesas_adicionales¡ganancia_online',
      'ganancia' => '0 as canon_fijo_mesas_adicionales¡ganancia',
      'ganancia_CCO' => '0 as canon_fijo_mesas_adicionales¡ganancia_CCO',
      'ganancia_BPLAY' => '0 as canon_fijo_mesas_adicionales¡ganancia_BPLAY'
    ];
  }
}
