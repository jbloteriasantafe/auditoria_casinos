<?php

namespace App\Http\Controllers\Canon;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
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
  
  public function es($tipo,$concepto){
    static $tipos = null;
    $tipos = $tipos 
    ?? DB::table('canon_fijo_mesas')->selectRaw('tipo')->distinct()->pluck('tipo')->toArray();
    
    if($concepto == '') return true;
    
    $concepto = strtoupper($concepto);
    
    return in_array($tipo,$tipos)
    &&     in_array($concepto,['','MESA','FÍSICO','FíSICO']);
  }
  
  public function totales($id_canon){
    return DB::table('canon_fijo_mesas')
    ->select('tipo',
      DB::raw('SUM(bruto) as beneficio'),
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
  
  public function recalcular($año_mes,$id_casino,$es_antiguo,$tipo,$accessors){
    extract($accessors);
    $año_mes_arr = explode('-',$año_mes);
    
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
    
    $valor_dolar_diario = bcmul($valor_dolar,$factor_dias_valor,14);//2+12 @RETORNADO
    $valor_euro_diario  = bcmul( $valor_euro,$factor_dias_valor,14);//2+12 @RETONRADO
    
    $devengado_valor_dolar_cotizado = bcmul($devengado_cotizacion_dolar,$valor_dolar,4);//2+2
    $devengado_valor_dolar_diario_cotizado = bcmul($devengado_cotizacion_dolar,$valor_dolar_diario,16);//2+14
    $devengado_valor_euro_cotizado  = bcmul($devengado_cotizacion_euro,$valor_euro,4);//2+2
    $devengado_valor_euro_diario_cotizado  = bcmul($devengado_cotizacion_euro,$valor_euro_diario,16);//4+12
    
    $determinado_valor_dolar_cotizado = bcmul($determinado_cotizacion_dolar,$valor_dolar,4);//2+2
    $determinado_valor_dolar_diario_cotizado = bcmul($determinado_cotizacion_dolar,$valor_dolar_diario,16);//4+12
    $determinado_valor_euro_cotizado  = bcmul($determinado_cotizacion_euro,$valor_euro,4);//2+2
    $determinado_valor_euro_diario_cotizado  = bcmul($determinado_cotizacion_euro,$valor_euro_diario,16);//4+12
    
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
    
    if($mesas_fijos != 0){
      $mesas_lunes_jueves=0;
      $mesas_viernes_sabados=0;
      $mesas_domingos=0;
      $mesas_todos=0;
    }
        
    $mesas_dias = $dias_lunes_jueves*$mesas_lunes_jueves
    +$dias_viernes_sabados*$mesas_viernes_sabados
    +$dias_domingos*$mesas_domingos
    +$dias_todos*$mesas_todos
    +$dias_fijos*$mesas_fijos;//@RETORNADO
    
    $devengado_total_dolar   = '0';//@RETORNADO
    $devengado_total_euro    = '0';//@RETORNADO
    $determinado_total_dolar = '0';//@RETORNADO
    $determinado_total_euro  = '0';//@RETORNADO
    $devengado_total_dolar_cotizado = '0';
    $devengado_total_euro_cotizado  = '0';
    $determinado_total_dolar_cotizado = '0';
    $determinado_total_euro_cotizado  = '0';
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
    
    $bruto = $this->bruto($tipo,$año_mes,$id_casino);
    $mesas_usadas_ARS = $bruto->mesas_ARS;
    $mesas_usadas_USD = $bruto->mesas_USD;
    $mesas_usadas = $bruto->mesas;
    $bruto_ARS = $bruto->bruto_ARS;
    $bruto_USD = $bruto->bruto_USD;
    $bruto_USD_cotizado = $bruto->bruto_USD_cotizado;
    $bruto = $bruto->bruto;
    
    $bruto = bcadd($R('bruto',$this->bruto($tipo,$año_mes,$id_casino)->bruto),'0',2);//@RETORNADO

    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
        
    $devengado   = bcsub($devengado_total,$devengado_deduccion,16);
    $determinado = bcadd($determinado_total,$determinado_ajuste,16);
    
    $accesors_diario = [
      'R' => AUX::make_accessor($R('diario',[])),
      'A' => AUX::make_accessor($A('diario',[])),
      'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
    ];
    $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
    
    $dias = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
    $factor_ajuste_diario_fijas = $tipo == 'Fijas'? bcdiv('30',$dias,12) : '1';
    
    $diario = $this->recalcular_diario(
      $año_mes,$id_casino,$es_antiguo,$tipo,
      $accesors_diario,
      $mesas_lunes_jueves,
      $mesas_viernes_sabados,
      $mesas_domingos,
      $mesas_todos,
      $mesas_fijos,
      $dias,
      $factor_ajuste_diario_fijas,
      $dias_valor,$factor_dias_valor,
      $valor_euro,$valor_dolar,
      $valor_euro_diario,$valor_dolar_diario
    )['diario'] ?? [];//@RETORNADO
    
    $ret = compact(
      'tipo','dias_valor','factor_dias_valor','valor_dolar','valor_euro',
      'valor_dolar_diario','valor_euro_diario',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'mesas_dias','factor_ajuste_diario_fijas',
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
      'determinado_ajuste','determinado',
      
      'bruto',
      'diario',
      'errores'
    );
        
    return $ret;
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$es_antiguo,$tipo,
    $accessors,
    $mesas_lunes_jueves,
    $mesas_viernes_sabados,
    $mesas_domingos,
    $mesas_todos,
    $mesas_fijos,
    $dias,
    $factor_ajuste_diario_fijas,
    $dias_valor,$factor_dias_valor,
    $valor_euro,$valor_dolar,
    $valor_euro_diario,$valor_dolar_diario
  ){
    static $cotizaciones = [];//voy guardando por si cambia alguna ya cambia todas...
    extract($accessors);
    
    $año_mes = explode('-',$año_mes);
    $dias_semana = ['Do','Lu','Ma','Mi','Ju','Vi','Sa'];
    $mesas_semana = [
      $mesas_domingos+$mesas_todos+$mesas_fijos,//Si mesas fijos != 0 las otras son 0 y viceversa
      $mesas_lunes_jueves+$mesas_todos+$mesas_fijos,
      $mesas_lunes_jueves+$mesas_todos+$mesas_fijos,
      $mesas_lunes_jueves+$mesas_todos+$mesas_fijos,
      $mesas_lunes_jueves+$mesas_todos+$mesas_fijos,
      $mesas_viernes_sabados+$mesas_todos+$mesas_fijos,
      $mesas_viernes_sabados+$mesas_todos+$mesas_fijos
    ];
    
    $diario = [];
    $mesas_habilitadas_acumuladas = 0;
    for($dia=1;$dia<=$dias;$dia++){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = implode('-',[$año_mes[0],$año_mes[1],str_pad($dia,2,'0',STR_PAD_LEFT)]);
      $cotizacion_dolar = AUX::get_cotizacion_sesion($fecha,2) ?? '0';
      $cotizacion_euro  = AUX::get_cotizacion_sesion($fecha,3) ?? '0';
      
      $bruto = $this->bruto($tipo,$fecha,$id_casino,true);
      
      $idx_dia_semana = (new \DateTime($fecha))->format('w');
      $dia_semana = $dias_semana[$idx_dia_semana];
      $mesas_habilitadas = $mesas_semana[$idx_dia_semana];
      $mesas_usadas_ARS = $D('mesas_usadas_ARS',$bruto->mesas_ARS ?? 0);
      $bruto_ARS = $D('bruto_ARS',$bruto->bruto_ARS ?? '0');
      $mesas_usadas_USD = $D('mesas_usadas_USD',$bruto->mesas_USD ?? 0);
      $bruto_USD = $D('bruto_USD',$bruto->bruto_USD ?? '0');
      $bruto_USD_cotizado = bcmul($bruto_USD,$cotizacion_dolar,4);
      $mesas_usadas = bcadd_precise($mesas_usadas_ARS,$mesas_usadas_USD);
      $bruto = bcadd($bruto_ARS,$bruto_USD_cotizado,4);
      
      $mesas_habilitadas_acumuladas += $mesas_habilitadas;
      $valor_euro_diario_cotizado  = bcmul_precise($valor_euro_diario,$cotizacion_euro);
      $valor_dolar_diario_cotizado = bcmul_precise($valor_dolar_diario,$cotizacion_dolar);
      $valor_diario = bcadd_precise($valor_euro_diario_cotizado,$valor_dolar_diario_cotizado);
      //@TODO: agregar precision con MOD dias_valor
      $devengado_determinado_acumulado = bcmul_precise($mesas_habilitadas_acumuladas,$valor_diario);
      $devengado_determinado_acumulado = bcmul_precise($factor_ajuste_diario_fijas,$devengado_determinado_acumulado);
      
      $diario[$dia] = compact(
        'dia','fecha',
        'cotizacion_euro',
        'cotizacion_dolar',
        'dia_semana',
        'mesas_habilitadas',
        'mesas_usadas_ARS',
        'bruto_ARS',
        'mesas_usadas_USD',
        'bruto_USD',
        'cotizacion',
        'bruto_USD_cotizado',
        'mesas_usadas',
        'bruto',
        'valor_euro',
        'valor_dolar',
        'dias_valor',
        'factor_dias_valor',
        'valor_euro_diario',
        'valor_dolar_diario',
        'mesas_habilitadas_acumuladas',
        'valor_euro_diario_cotizado',
        'valor_dolar_diario_cotizado',
        'valor_diario',
        'factor_ajuste_diario_fijas',
        'devengado_determinado_acumulado'
      );
    }
    
    return compact('diario');
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
  
  public function obtener_diario($id_canon_fijo_mesas){
    return DB::table('canon_fijo_mesas_diario')
    ->where('id_canon_fijo_mesas',$id_canon_fijo_mesas)
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
    $ret['canon_fijo_mesas'] = DB::table('canon_fijo_mesas')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
    
    foreach($ret['canon_fijo_mesas'] as $tipo => $datatipo){
      $datatipo->diario = $this->obtener_diario($datatipo->id_canon_fijo_mesas);
    }
       
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
    $ret = AUX::confluir_datos(
      $data,
      ['canon_fijo_mesas'],
      [
        'valor_dolar','valor_euro','devengado_fecha_cotizacion',
        'determinado_fecha_cotizacion',
        'devengado_cotizacion_dolar','devengado_cotizacion_euro',
        'determinado_cotizacion_dolar','determinado_cotizacion_euro'
      ]
    );
    
    $ret['canon_cotizacion_diaria'] = [];
    foreach(($data['canon_fijo_mesas'] ?? []) as $tipo => $datatipo){
      foreach(($datatipo['diario'] ?? []) as $dia => $datadia){
        $ret['canon_cotizacion_diaria'][$dia] = [
          'dia' => $dia,
          'USD' => ($datadia['cotizacion_USD'] ?? null),
          'EUR' => ($datadia['cotizacion_EUR'] ?? null)
        ];
      }
    }
    
    return $ret;
  }


  public function bruto($tipo,$año_mes,$id_casino,$diario = false){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    $diario = $diario? 1 : 0;
    
    static $cache = [];
    
    $cache[$tipo] = $cache[$tipo] ?? [];
    $cache[$tipo][$id_casino] = $cache[$tipo][$id_casino] ?? [];

    $kañomes = $año_mes_arr[0].'-'.$año_mes_arr[1];
    $dia = $diario? intval($año_mes_arr[2]) : 0;
        
    $err_val = function($v) use ($diario,$año_mes_arr){
      return ((object)['dia' => ($diario? intval($año_mes_arr[2]) : 0),'mesas_ARS' => $v,'bruto_ARS' => $v,'mesas_USD' => $v,'bruto_USD' => $v,'cotizacion' => $v,'bruto_USD_cotizado' => $v,'mesas' => $v,'bruto' => $v]);
    };
    
    if(array_key_exists($kañomes,$cache[$tipo][$id_casino]) 
    && array_key_exists($dia,$cache[$tipo][$id_casino][$kañomes])){
      return $cache[$tipo][$id_casino][$kañomes][$dia];
    }
    
    $resultado = null;
    switch($tipo){
      case 'Mesas':
      case 'Fijas':
      case 'Diarias': {
        $resultado = DB::table('importacion_diaria_mesas as idm')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('idm.id_moneda',2)->on('idm.fecha','=','cot.fecha');
        })
        ->whereNull('idm.deleted_at')
        ->where('idm.id_casino',$id_casino)
        ->whereYear('idm.fecha',$año_mes_arr[0])
        ->whereMonth('idm.fecha',intval($año_mes_arr[1]));
        
        //@TODO: modularizar con BCMensualesController?
        $mesas = DB::table('importacion_diaria_mesas as idm')
        ->join('detalle_importacion_diaria_mesas as didm','didm.id_importacion_diaria_mesas','=','idm.id_importacion_diaria_mesas')
        ->whereNull('idm.deleted_at')
        ->whereNull('didm.deleted_at')
        ->where('idm.id_casino',$id_casino)
        ->whereYear('idm.fecha',$año_mes_arr[0])
        ->whereMonth('idm.fecha',intval($año_mes_arr[1]))
        ->where(function($q){
        return $q->whereRaw('IFNULL(didm.droop,0) <> 0 OR IFNULL(didm.droop_tarjeta,0) <> 0 OR IFNULL(didm.reposiciones,0) <> 0
          OR IFNULL(didm.retiros,0) <> 0 OR IFNULL(didm.utilidad,0) <> 0 OR IFNULL(didm.saldo_fichas,0) <> 0 OR IFNULL(didm.propina <> 0,0)');
        });
                
        $cot_valor = 'CAST(cot.valor AS DECIMAL(20,6))';
        $resultado = $resultado->selectRaw("
          IF($diario,DAY(idm.fecha),0) as dia,
          SUM(IF(idm.id_moneda = 1,idm.utilidad,0)) as bruto_ARS,
          SUM(IF(idm.id_moneda = 2,idm.utilidad,0)) as bruto_USD,
          MAX(IF($diario,$cot_valor,NULL)) as cotizacion,
          SUM(IF(idm.id_moneda = 2,$cot_valor*idm.utilidad,0)) as bruto_USD_cotizado,
          SUM(
            IF(idm.id_moneda = 1,
              idm.utilidad,
              IF(idm.id_moneda = 2,
                idm.utilidad*$cot_valor,
                NULL
              )
            )
          ) as bruto
        ")
        ->groupBy(DB::raw("IF($diario,DAY(idm.fecha),0)"))->first();
        
        $codigo = 'CONCAT(didm.siglas_juego,didm.nro_mesa)';
        //@HACK: contar doble a las mesas que abren en ARS y USD o cuentan una sola vez?
        $mesas = $mesas->selectRaw("
          IF($diario,DAY(idm.fecha),0) as dia,
          COUNT(distinct IF(idm.id_moneda = 1,$codigo,NULL)) as mesas_ARS,
          COUNT(distinct IF(idm.id_moneda = 2,$codigo,NULL)) as mesas_USD,
          COUNT(distinct IF(idm.id_moneda = 1,$codigo,NULL))+COUNT(distinct IF(idm.id_moneda = 2,$codigo,NULL)) as mesas
        ")
        ->groupBy(DB::raw("IF($diario,DAY(idm.fecha),0)"))->first();
      }break;
    }
    
    if($resultado !== null){
      $resultado = $resultado->keyBy('dia');
    }
    if($mesas !== null){
      $mesas = $mesas->keyBy('dia');
    }
    
    //JOIN
    if($diario) for($d=1;$d<=cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));$d++){
      if($resultado !== null && $mesas !== null && array_key_exists($d,$resultado) && array_key_exists($d,$mesas)){
        $resultado[$d]->mesas_ARS = $mesas[$d]->mesas_ARS;
        $resultado[$d]->mesas_USD = $mesas[$d]->mesas_USD;
        $resultado[$d]->mesas     = $mesas[$d]->mesas;
      }
    }
    else{
      if($resultado !== null && $mesas !== null && array_key_exists(0,$resultado) && array_key_exists(0,$mesas)){
        $resultado[0]->mesas_ARS = $mesas[0]->mesas_ARS;
        $resultado[0]->mesas_USD = $mesas[0]->mesas_USD;
        $resultado[0]->mesas     = $mesas[0]->mesas;
      }
    }
    
    if($diario) for($d=1;$d<=cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));$d++){
      $cache[$tipo][$id_casino][$kañomes][$d] = $resultado[$d] ?? $err_val(null);
    }
    else{
      $cache[$tipo][$id_casino][$kañomes][0] = $resultado[0] ?? $err_val(null);
    }
    
    return $cache[$tipo][$id_casino][$kañomes][$dia];
  }
  
  public function datosCanon($tname){
    $attrs_canon = [
      'canon_fisico' => 'SUM(cfm.determinado+cfm.determinado_ajuste) as canon_fisico',
      'canon_online' => '0 as canon_online',
      'ganancia_fisico' => 'SUM(cfm.bruto) as ganancia_fisico',
      'ganancia_online' => '0 as ganancia_online',
      'ganancia' => 'SUM(cfm.bruto) as ganancia',
      'ganancia_yoy' => 'SUM(cfm_yoy.bruto) as ganancia_yoy',
      'ganancia_CCO' => '0 as ganancia_CCO',
      'ganancia_BPLAY' => '0 as ganancia_BPLAY',
      'determinado_fecha_cotizacion' => 'MAX(cfm.determinado_fecha_cotizacion) as determinado_fecha_cotizacion',
      'determinado_fecha_cotizacion_yoy' => 'MAX(cfm_yoy.determinado_fecha_cotizacion) as determinado_fecha_cotizacion_yoy',
      'determinado_cotizacion_euro' => 'MAX(cfm.determinado_cotizacion_euro) as determinado_cotizacion_euro',
      'determinado_cotizacion_euro_yoy' => 'MAX(cfm_yoy.determinado_cotizacion_euro) as determinado_cotizacion_euro_yoy',
      'determinado_cotizacion_dolar' => 'MAX(cfm.determinado_cotizacion_dolar) as determinado_cotizacion_dolar',
      'determinado_cotizacion_dolar_yoy' => 'MAX(cfm_yoy.determinado_cotizacion_dolar) as determinado_cotizacion_dolar_yoy',
      'valor_euro' => 'MAX(cfm.valor_euro) as valor_euro',
      'valor_euro_yoy' => 'MAX(cfm_yoy.valor_euro) as valor_euro_yoy',
      'valor_dolar' => 'MAX(cfm.valor_dolar) as valor_dolar',
      'valor_dolar_yoy' => 'MAX(cfm_yoy.valor_dolar) as valor_dolar_yoy',
    ];
    
    $tname2 = 't'.uniqid();
    DB::statement("CREATE TEMPORARY TABLE $tname2 AS
      SELECT $tname.casino,$tname.año,$tname.mes,".implode(',',$attrs_canon)."
      FROM $tname
      LEFT JOIN canon_fijo_mesas as cfm ON cfm.id_canon = $tname.id_canon
      LEFT JOIN canon_fijo_mesas as cfm_yoy ON cfm_yoy.id_canon = $tname.id_canon_yoy AND cfm_yoy.tipo LIKE cfm.tipo
      LEFT JOIN canon_fijo_mesas as cfm_mom ON cfm_mom.id_canon = $tname.id_canon_mom AND cfm_mom.tipo LIKE cfm.tipo
      GROUP BY $tname.casino,$tname.año,$tname.mes
    ");
    
    $tables = [$tname2,array_keys($attrs_canon)];
    
    return $tables;
  }
}
