<?php

namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Canon\CanonController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
require_once(app_path('BC_extendido.php'));

class CanonVariableController extends Controller
{
  public $table = 'canon_variable';
  public $id    = 'id_canon_variable';
  public $valorPorDefecto = '{"1":{"Maquinas":{"alicuota":"21","devengado_deduccion":"250000"},"Bingo":{"alicuota":"35"}},"2":{"Maquinas":{"alicuota":"25","devengado_deduccion":"500000"},"Bingo":{"alicuota":"55"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}},"3":{"Maquinas":{"alicuota":"20.56","devengado_apostado_porcentaje_aplicable":"19","devengado_apostado_porcentaje_impuesto_ley":"0.95","devengado_deduccion":"1000000"},"Bingo":{"alicuota":"78.5"},"JOL":{"alicuota":"15","devengado_deduccion":"100000"}}}';
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function validar(){
    return [
      'canon_variable' => 'array',
      'canon_variable.*.devengado_apostado_sistema' => ['nullable',AUX::numeric_rule(2)],
      'canon_variable.*.devengado_apostado_porcentaje_aplicable' => ['nullable',AUX::numeric_rule(4)],
      'canon_variable.*.devengado_apostado_porcentaje_impuesto_ley' => ['nullable',AUX::numeric_rule(4)],
      'canon_variable.*.devengado_bruto' => ['nullable',AUX::numeric_rule(2)],
      'canon_variable.*.devengado_deduccion' => ['nullable',AUX::numeric_rule(2)],
      'canon_variable.*.determinado_impuesto' => ['nullable',AUX::numeric_rule(2)],
      'canon_variable.*.determinado_bruto' => ['nullable',AUX::numeric_rule(2)],
      'canon_variable.*.determinado_ajuste' => ['nullable',AUX::numeric_rule(22)],
      'canon_variable.*.alicuota' => ['nullable',AUX::numeric_rule(4)]
    ];
  }
  
  public function es($tipo,$concepto){    
    if($concepto == '') return true;
    
    $concepto = strtoupper($concepto);
    
    if(in_array($tipo,['JOL','jol','Jol'])){
      return in_array($concepto,['JOL','ONLINE']);
    }
    
    if(in_array($tipo,['BINGO','bingo','Bingo'])){
      return in_array($concepto,['BINGO','FíSICO','FÍSICO']);
    }
    
    if(in_array($tipo,['MAQUINAS','maquinas','Maquinas','MAQUINA','Maquina','maquina','MTM','mtm','Mtm'])){
      return in_array($concepto,['MAQUINA','FíSICO','FÍSICO']);
    }
    
    return false;
  }
  
  public function totales($id_canon){
    return DB::table('canon_variable')
    ->select('tipo',
      DB::raw('SUM(determinado_subtotal) as beneficio'),//Con el impuesto restado
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
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
      
    $accesors_diario = [
      'R' => AUX::make_accessor($R('diario',[])),
      'A' => AUX::make_accessor($A('diario',[])),
      'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
    ];
    $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $año_mes_arr = explode('-',$año_mes);
    $dias = empty($año_mes)? 0 : cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
    $determinado_impuesto       = bcadd($R('determinado_impuesto','0.00'),'0',14);//@RETORNADO
    $diario = $this->recalcular_diario(
      $año_mes,$id_casino,$es_antiguo,$tipo,$dias,
      $factor_apostado_porcentaje_aplicable,$factor_apostado_porcentaje_impuesto_ley,$factor_alicuota,
      $determinado_impuesto,
      $accesors_diario
    )['diario'] ?? [];//@RETORNADO
    
    $devengado_apostado_sistema = '0';
    $devengado_base_imponible   = '0';
    $devengado_impuesto         = '0';
    $devengado_bruto            = '0';
    $determinado_bruto          = '0';
    $devengado_bruto            = '0';
    $determinado_bruto          = '0';
    $devengado_subtotal         = '0';
    $determinado_subtotal       = '0';
    $devengado_total            = '0';
    $determinado_total          = '0';
        
    if(empty($diario)){
      $bruto = $this->bruto($tipo,$año_mes,$id_casino)->bruto;
      $apostado = $this->apostado($tipo,$año_mes,$id_casino)->apostado;
      
      $aux = array_intersect_key($this->calcular_devengado(
        '0',
        $R('devengado_apostado_sistema',$apostado ?? '0'),'0',
        $R('devengado_bruto',$bruto ?? '0'),'0',
        $factor_apostado_porcentaje_impuesto_ley,
        $factor_apostado_porcentaje_aplicable,
        $factor_alicuota
      ),array_flip([
        'devengado_apostado_sistema','devengado_base_imponible',
        'devengado_impuesto','devengado_bruto',
        'devengado_subtotal','devengado_total'
      ]));
      
      foreach($aux as $varname => $varvalue) $$varname = $varvalue;
      
      $aux = array_intersect_key($this->calcular_determinado(
        '0',
        $R('determinado_impuesto','0'),
        $R('determinado_bruto',$bruto ?? '0'),'0',
        $factor_alicuota
      ),array_flip([
        'determinado_impuesto','determinado_bruto',
        'determinado_subtotal','determinado_total'
      ]));
      
      foreach($aux as $varname => $varvalue) $$varname = $varvalue;
    }
    else{
      foreach($diario as $d){
        foreach([
            'devengado_apostado_sistema','devengado_base_imponible',
            'devengado_impuesto','devengado_bruto','determinado_bruto',
            'devengado_subtotal','determinado_subtotal','devengado_total','determinado_total'
        ] as $var){
          $$var = bcadd_precise($$var,$d[$var] ?? '0');
        }        
      }
    }

    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',20);
    
    $devengado = bcsub($devengado_total,$devengado_deduccion,20);
    $determinado = bcadd($determinado_total,$determinado_ajuste,20);
        
    $ret = compact('tipo',
      'alicuota','devengar',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'devengado_bruto','devengado_impuesto','devengado_subtotal','devengado_total','devengado_deduccion',
      'devengado',
      'determinado_impuesto','determinado_bruto','determinado_subtotal','determinado_total','determinado_ajuste',
      'determinado',
      'diario','errores','canon_cotizacion_diaria'
    );
    
    return $ret;
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$es_antiguo,$tipo,$dias,
    $factor_apostado_porcentaje_aplicable,$factor_apostado_porcentaje_impuesto_ley,$factor_alicuota,
    $determinado_impuesto_total,
    $accessors
  ){
    extract($accessors);
    
    $año_mes_str = substr($año_mes,0,strlen('XXXX-XX-'));
    
    $diario = [];
    $devengado_impuesto_total = '0';
    
    for($dia=1;$dia<=$dias;$dia++){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = $año_mes_str.str_pad($dia,2,'0',STR_PAD_LEFT);
      $apostado = $this->apostado($tipo,$fecha,$id_casino,true);
      $bruto    = $this->bruto($tipo,$fecha,$id_casino,true);
      
      $devengado_apostado_sistema_ARS = bcadd($D('devengado_apostado_sistema_ARS',$apostado->apostado_ARS),'0',2);//@RETORNADO    
      $devengado_apostado_sistema_USD = bcadd($D('devengado_apostado_sistema_USD',$apostado->apostado_USD),'0',2);//@RETORNADO    
      $devengado_bruto_ARS = bcadd($D('devengado_bruto_ARS',$bruto->bruto_ARS),'0',2);//@RETORNADO    
      $devengado_bruto_USD = bcadd($D('devengado_bruto_USD',$bruto->bruto_USD),'0',2);//@RETORNADO    
      $determinado_bruto_ARS = bcadd($D('determinado_bruto_ARS',$bruto->bruto_ARS),'0',2);
      $determinado_bruto_USD = bcadd($D('determinado_bruto_USD',$bruto->bruto_USD),'0',2);
      
      $cotizacion_USD = AUX::get_cotizacion_sesion($fecha,2) ?? '0';//@RETORNADO              
                  
      $diario[$dia] = array_merge(
        compact('dia','fecha'),
        $this->calcular_devengado(
          $cotizacion_USD,
          $devengado_apostado_sistema_ARS,$devengado_apostado_sistema_USD,
          $devengado_bruto_ARS,$devengado_bruto_USD,
          $factor_apostado_porcentaje_impuesto_ley,
          $factor_apostado_porcentaje_aplicable,
          $factor_alicuota
        ),
        $this->calcular_determinado(
          $cotizacion_USD,
          '0',
          $determinado_bruto_ARS,$determinado_bruto_USD,
          $factor_alicuota
        )
      );
      
      $devengado_impuesto_total = bcadd($devengado_impuesto_total,$diario[$dia]['devengado_impuesto'],16);
    }
    
    $determinado_impuesto_total_calculado = '0';
    $devengado_impuesto_total_nulo = bccomp_precise($devengado_impuesto_total,'0') == 0;
    $didx_impuesto_mas_grande = null;
    foreach($diario as $didx => &$d){
      if($devengado_impuesto_total_nulo){
        $d['determinado_impuesto'] = '0';
      }
      else {
        $d['determinado_impuesto'] = bcdiv(
          bcmul_precise($d['devengado_impuesto'],$determinado_impuesto_total),
          $devengado_impuesto_total,
          16
        );
      }
      foreach($this->calcular_determinado(
        $d['cotizacion_USD'],
        $d['determinado_impuesto'],
        $d['determinado_bruto_ARS'],$d['determinado_bruto_USD'],
        $d['factor_alicuota']
      ) as $k => $v){
        $d[$k] = $v;
      }
      
      $determinado_impuesto_total_calculado = bcadd($determinado_impuesto_total_calculado,$d['determinado_impuesto'],16);
      $didx_impuesto_mas_grande = (
        $didx_impuesto_mas_grande === null
      || (bccomp($d['determinado_impuesto'],$diario[$didx_impuesto_mas_grande]['determinado_impuesto'],16) > 0)
      )?
        $didx
      : $didx_impuesto_mas_grande;
    }
    
    //Sumo el error global al que tiene el impuesto mas grande (para minimizar el error local)
    $error = bcsub($determinado_impuesto_total,$determinado_impuesto_total_calculado,16);
    {
      $d = &$diario[$didx_impuesto_mas_grande];
      $d['determinado_impuesto'] = bcadd($d['determinado_impuesto'],$error,16);
      foreach($this->calcular_determinado(
        $d['cotizacion_USD'],
        $d['determinado_impuesto'],
        $d['determinado_bruto_ARS'],$d['determinado_bruto_USD'],
        $d['factor_alicuota']
      ) as $k => $v){
        $d[$k] = $v;
      }
    }
    
    return compact('diario');
  }
  
  private function calcular_determinado(
    $cotizacion_USD,
    $determinado_impuesto,
    $determinado_bruto_ARS,$determinado_bruto_USD,
    $factor_alicuota
  ){
    $determinado_bruto_USD_cotizado = bcmul($determinado_bruto_USD,$cotizacion_USD,4);//2+2 @RETORNADO
    $determinado_bruto = bcadd($determinado_bruto_ARS,$determinado_bruto_USD_cotizado,4);//@RETORNADO
    
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,16);
    $determinado_total = bcmul($determinado_subtotal,$factor_alicuota,22);
    
    return compact(
      'cotizacion_USD',
      'determinado_bruto_ARS','determinado_bruto_USD','determinado_bruto_USD_cotizado','determinado_bruto',
      'factor_alicuota',
      'determinado_impuesto','determinado_subtotal',
      'determinado_total'
    );
  }
  
  private function calcular_devengado(
    $cotizacion_USD,
    $devengado_apostado_sistema_ARS,$devengado_apostado_sistema_USD,
    $devengado_bruto_ARS,$devengado_bruto_USD,
    $factor_apostado_porcentaje_impuesto_ley,
    $factor_apostado_porcentaje_aplicable,
    $factor_alicuota
  ){
    $devengado_apostado_sistema_USD_cotizado = bcmul($devengado_apostado_sistema_USD,$cotizacion_USD,4);//4+2 @RETORNADO
    $devengado_bruto_USD_cotizado = bcmul($devengado_bruto_USD,$cotizacion_USD,4);//2+2 @RETORNADO
    $devengado_apostado_sistema = bcadd($devengado_apostado_sistema_ARS,$devengado_apostado_sistema_USD_cotizado,4);//@RETORNADO
    $devengado_bruto = bcadd($devengado_bruto_ARS,$devengado_bruto_USD_cotizado,4);//@RETORNADO
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,10);//4+6 @RETORNADO
    $devengado_impuesto = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,16);//10+6 @RETORNADO
    $devengado_subtotal = bcsub($devengado_bruto,$devengado_impuesto,16);
    $devengado_total = bcmul($devengado_subtotal,$factor_alicuota,22);
      
    return compact(
      'cotizacion_USD',
      'devengado_apostado_sistema_ARS','devengado_apostado_sistema_USD','devengado_apostado_sistema_USD_cotizado','devengado_apostado_sistema',
      'devengado_bruto_ARS','devengado_bruto_USD','devengado_bruto_USD_cotizado','devengado_bruto',
      'factor_apostado_porcentaje_impuesto_ley',
      'factor_apostado_porcentaje_aplicable',
      'factor_alicuota',
      'devengado_base_imponible','devengado_impuesto','devengado_subtotal',
      'devengado_total'
    );
  }
  
  public function guardar($id_canon,$id_canon_anterior,$datos){
    foreach(($datos['canon_variable'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo'] = $tipo;
      unset($d['id_canon_variable']);
      DB::table('canon_variable')
      ->insert($d);
    }
    return 1;
  }
  
  public function obtener_diario($id_canon_variable){
    return DB::table('canon_variable_diario')
    ->where('id_canon_variable',$id_canon_variable)
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
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
    
    foreach($ret['canon_variable'] as $tipo => $datatipo){
      $datatipo->diario = $this->obtener_diario($datatipo->id_canon_variable);
    }
       
    return $ret;
  }
    
  public function procesar_para_salida($data){
    $ret = [];
    foreach(['id_canon_variable','id_canon'] as $k){
      foreach(($data['canon_variable'] ?? []) as $tipo => $_){
        unset($data['canon_variable'][$tipo][$k]);
      }
    }
    $ret['canon_variable'] = $data['canon_variable'] ?? [];
    
    return $ret;
  }
  
  public function confluir($data){
    $ret = [];
    $ret['canon_cotizacion_diaria'] = [];
    foreach(($data['canon_variable'] ?? []) as $tipo => $datatipo){
      foreach(($datatipo['diario'] ?? []) as $dia => $datadia){
        $ret['canon_cotizacion_diaria'][$dia] = [
          'dia' => $dia,
          'USD' => ($datadia['cotizacion'] ?? null),
          'EUR' => null
        ];
      }
    }
    
    return [];
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
      return ((object)['dia' => ($diario? intval($año_mes_arr[2]) : 0),'bruto_ARS' => $v,'bruto_USD' => $v,'cotizacion' => $v,'bruto_USD_cotizado' => $v,'bruto' => $v]);
    };
    
    if(array_key_exists($kañomes,$cache[$tipo][$id_casino]) 
    && array_key_exists($dia,$cache[$tipo][$id_casino][$kañomes])){
      return $cache[$tipo][$id_casino][$kañomes][$dia];
    }
    
    $resultado = null;
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->leftJoin('cotizacion as cot',function($j){
          return $j->where('b.id_tipo_moneda',2)
          ->on('cot.fecha','=','b.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]));
        
        $resultado = $resultado->selectRaw("
          IF($diario,DAY(b.fecha),0) as dia,
          SUM(IF(b.id_tipo_moneda = 1,b.valor,0)) as bruto_ARS,
          SUM(IF(b.id_tipo_moneda = 2,b.valor,0)) as bruto_USD,
          MAX(IF($diario,cot.valor,NULL)) as cotizacion,
          SUM(IF(b.id_tipo_moneda = 2,cot.valor*b.valor,0)) as bruto_USD_cotizado,
          SUM(
            IF(b.id_tipo_moneda = 1,
              b.valor,
              IF(b.id_tipo_moneda = 2,
                b.valor*cot.valor,
                NULL
              )
            )
          ) as bruto
        ")
        ->groupBy(DB::raw('IF('.$diario.',DAY(b.fecha),0)'))->get();
      }break;
      case 'Bingo':{
        $resultado = DB::table('bingo_importacion as b')
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]));
        
        $resultado = $resultado->selectRaw("
          IF($diario,DAY(b.fecha),0) as dia,
          SUM(b.recaudado-b.premio_bingo-b.premio_linea) as bruto_ARS,
          NULL as bruto_USD,
          NULL as cotizacion,
          NULL as bruto_USD_cotizado,
          SUM(b.recaudado-b.premio_bingo-b.premio_linea) as bruto
        ")
        ->groupBy(DB::raw('IF('.$diario.',DAY(b.fecha),0)'))->get();
      }break;
      case 'JOL':{
        $JOL_connect_config = CanonValorPorDefectoController::getInstancia()->valorPorDefecto('JOL_connect_config') ?? null;
        $debug = $JOL_connect_config['debug'] ?? false;
        if(empty($JOL_connect_config)){
          return $err_val($debug? '-0.99' : null);
        }
        if(empty($JOL_connect_config['ip_port'])){
          return $err_val($debug? '-0.98' : null);
        }
        if(empty($JOL_connect_config['API-Token'])){
          return $err_val($debug? '-0.97' : null);
        }
        
        set_time_limit(5);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://{$JOL_connect_config['ip_port']}/api/bruto");
        curl_setopt($ch, CURLOPT_POST, 1);
        $desde = "{$año_mes_arr[0]}-{$año_mes_arr[1]}-01";
        $dias_mes = cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));
        $hasta = "{$año_mes_arr[0]}-{$año_mes_arr[1]}-{$dias_mes}";
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(compact('año_mes','id_casino')));
        //curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_PROXY, NULL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'API-Token: '.$JOL_connect_config['API-Token']
        ]);
        
        $resultado = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($resultado === false){//Error de curl https://curl.se/libcurl/c/libcurl-errors.html
          $errno = curl_errno($ch);
          curl_close($ch);
          return $err_val($debug? ('-999999999'.$errno.'.98') : null);
        }
        curl_close($ch);
        
        if($code != 200){
          return $err_val($debug? ('-999999999'.$code.'.99') : null);
        }
        
        $resultado = json_decode($resultado);
      }break;
    }
    
    if($resultado !== null)
      $resultado = $resultado->keyBy('dia');
    
    if($diario) for($d=1;$d<=cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]));$d++){
      $cache[$tipo][$id_casino][$kañomes][$d] = $resultado[$d] ?? $err_val(null);
    }
    else{
      $cache[$tipo][$id_casino][$kañomes][0] = $resultado[0] ?? $err_val(null);
    }
    
    return $cache[$tipo][$id_casino][$kañomes][$dia];
  }
  
  public function apostado($tipo,$año_mes,$id_casino,$diario = false){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    $diario = $diario? 1 : 0;
    
    static $cache = [];
    
    $cache[$tipo] = $cache[$tipo] ?? [];
    $cache[$tipo][$id_casino] = $cache[$tipo][$id_casino] ?? [];

    $kañomes = $año_mes_arr[0].'-'.$año_mes_arr[1];
    $dia = $diario? intval($año_mes_arr[2]) : 0;
        
    $err_val = function($v) use ($diario,$año_mes_arr){
      return ((object)['dia' => ($diario? $año_mes_arr[2] : 0),'apostado_ARS' => $v,'apostado_USD' => $v,'cotizacion' => $v,'apostado_USD_cotizado' => $v,'apostado' => $v]);
    };
    
    if(array_key_exists($kañomes,$cache[$tipo][$id_casino]) 
    && array_key_exists($dia,$cache[$tipo][$id_casino][$kañomes])){
      return $cache[$tipo][$id_casino][$kañomes][$dia];
    }
    
    $resultado = null;
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->leftJoin('cotizacion as cot',function($j){
          return $j->where('b.id_tipo_moneda',2)
          ->on('cot.fecha','=','b.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]));
        
        $resultado = $resultado->selectRaw("
          IF($diario,DAY(b.fecha),0) as dia,
          SUM(IF(b.id_tipo_moneda = 1,b.coinin,0)) as apostado_ARS,
          SUM(IF(b.id_tipo_moneda = 2,b.coinin,0)) as apostado_USD,
          MAX(IF($diario,cot.valor,NULL)) as cotizacion,
          SUM(IF(b.id_tipo_moneda = 2,cot.valor*b.coinin,0)) as apostado_USD_cotizado,
          SUM(
            IF(b.id_tipo_moneda = 1,
              b.coinin,
              IF(b.id_tipo_moneda = 2,
                b.coinin*cot.valor,
                NULL
              )
            )
          ) as apostado
        ")
        ->groupBy(DB::raw('IF('.$diario.',DAY(b.fecha),0)'))->get();
      }break;
    }
    
    if($resultado !== null)
      $resultado = $resultado->keyBy('dia');
    
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
      'canon_fisico' => 'SUM(IF(cv.tipo LIKE "jol",0,cv.determinado+cv.determinado_ajuste)) as canon_fisico',
      'canon_online' => 'SUM(IF(cv.tipo LIKE "jol",cv.determinado+cv.determinado_ajuste,0)) as canon_online',
      'ganancia_fisico' => 'SUM(IF(cv.tipo LIKE "jol",0,cv.determinado_subtotal)) as ganancia_fisico',
      'ganancia_online' => 'SUM(IF(cv.tipo LIKE "jol",cv.determinado_subtotal,0)) as ganancia_online',
      'ganancia' => 'SUM(cv.determinado_subtotal) as ganancia',
      'ganancia_CCO' => 'SUM(IF(
        cv.tipo LIKE "jol" AND "CCO" IN (
          SELECT p.codigo 
          FROM plataforma as p 
          JOIN plataforma_tiene_casino as pc ON pc.id_plataforma = p.id_plataforma
          WHERE pc.id_casino = c.id_casino
        ),
        cv.determinado_subtotal,
        0
      )) as ganancia_CCO',
      'ganancia_BPLAY' => 'SUM(IF(
        cv.tipo LIKE "jol" AND "BPLAY" IN (
          SELECT p.codigo 
          FROM plataforma as p 
          JOIN plataforma_tiene_casino as pc ON pc.id_plataforma = p.id_plataforma
          WHERE pc.id_casino = c.id_casino
        ),
        cv.determinado_subtotal,
        0
      )) as ganancia_BPLAY'
    ];
    
    $tname2 = 't'.uniqid();
    DB::statement("CREATE TEMPORARY TABLE $tname2 AS
      SELECT $tname.casino,$tname.año,$tname.mes,".implode(',',$attrs_canon)."
      FROM $tname
      LEFT JOIN canon as c ON c.id_canon = $tname.id_canon
      LEFT JOIN canon_variable as cv ON cv.id_canon = $tname.id_canon
      LEFT JOIN canon_variable as cv_yoy ON cv_yoy.id_canon = $tname.id_canon_yoy AND cv_yoy.tipo LIKE cv.tipo
      LEFT JOIN canon_variable as cv_mom ON cv_mom.id_canon = $tname.id_canon_mom AND cv_mom.tipo LIKE cv.tipo
      GROUP BY $tname.casino,$tname.año,$tname.mes
    ");
    
    $tables = [$tname2,array_keys($attrs_canon)];
    
    return $tables;
  }
}
