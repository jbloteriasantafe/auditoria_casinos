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
  
  public function totalesCanon_query($discriminar_adicionales){
    return 'SELECT 
      sc.id_canon,
      CASE
        WHEN tipo = "Maquinas" THEN "MTM"
        WHEN tipo = "Bingo"    THEN "Bingo"
        WHEN tipo = "JOL"      THEN "JOL"
        ELSE tipo
      END as concepto,
      (tipo != "JOL") as es_fisico,
      sc.determinado_subtotal as beneficio,
      IF(sc.devengar,sc.devengado_total,NULL) as bruto,
      IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
      IF(sc.devengar,sc.devengado,NULL) as devengado,
      sc.determinado as determinado
    FROM canon_variable as sc';
  }
  
  public function recalcular($año_mes,$id_casino,$version,$tipo,$accessors){
    extract($accessors);
    
    $devengar = $RD('devengar',1);
    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $determinado_impuesto       = bcadd($R('determinado_impuesto','0.00'),'0',14);//@RETORNADO
    $diario = [];//@RETORNADO
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
    
    if($version == 'mensual' || $version == 'antiguo'){
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
      
      if($version == 'antiguo'){
        $devengado_total = $R('devengado_total',$devengado_total);
        $determinado_total = $R('determinado_total',$determinado_total);
      }
    }
    else if($version == 'diario'){
      $accesors_diario = [
        'R' => AUX::make_accessor($R('diario',[])),
        'A' => AUX::make_accessor($A('diario',[])),
        'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
      ];
      $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
      $diario = $this->recalcular_diario(
        $año_mes,$id_casino,$version,$tipo,
        $factor_apostado_porcentaje_aplicable,$factor_apostado_porcentaje_impuesto_ley,$factor_alicuota,
        $determinado_impuesto,
        $accesors_diario
      )['diario'] ?? [];
      
      foreach($diario as &$d){
        foreach([
          'devengado_apostado_sistema','devengado_base_imponible',
          'devengado_impuesto','devengado_bruto','determinado_bruto',
          'devengado_subtotal','determinado_subtotal','devengado_total','determinado_total'
        ] as $var){
          $$var = bcadd_precise($$var,$d[$var] ?? '0');
        }
        foreach([
          'devengado_subtotal','determinado_subtotal','devengado_total','determinado_total'
        ] as $var){
          $d[$var] = $$var;//Guardo el acumulado
        }
      }
    }

    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',20);
    $devengado = bcsub($devengado_total,$devengado_deduccion,20);
    $determinado = bcadd($determinado_total,$determinado_ajuste,20);
        
    return compact('tipo',
      'alicuota','devengar',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'devengado_bruto','devengado_impuesto','devengado_subtotal','devengado_total','devengado_deduccion',
      'devengado',
      'determinado_impuesto','determinado_bruto','determinado_subtotal','determinado_total','determinado_ajuste',
      'determinado',
      'diario','errores','canon_cotizacion_diaria'
    );
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$version,$tipo,
    $factor_apostado_porcentaje_aplicable,$factor_apostado_porcentaje_impuesto_ley,$factor_alicuota,
    $determinado_impuesto_total,
    $accessors
  ){
    extract($accessors);
    
    $año_mes_str = substr($año_mes,0,strlen('XXXX-XX-'));
    $diario = [];
    $devengado_apostado_sistema_total = '0';
    foreach($COT(null,[]) as $dia => $cot){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = $año_mes_str.str_pad($dia,2,'0',STR_PAD_LEFT);
      $apostado = $this->apostado($tipo,$fecha,$id_casino,true);
      $bruto    = $this->bruto($tipo,$fecha,$id_casino,true);
      
      $devengado_apostado_sistema_peso = bcadd($D('devengado_apostado_sistema_peso',$apostado->apostado_peso),'0',2);//@RETORNADO    
      $devengado_apostado_sistema_dolar = bcadd($D('devengado_apostado_sistema_dolar',$apostado->apostado_dolar),'0',2);//@RETORNADO    
      $devengado_bruto_peso = bcadd($D('devengado_bruto_peso',$bruto->bruto_peso),'0',2);//@RETORNADO    
      $devengado_bruto_dolar = bcadd($D('devengado_bruto_dolar',$bruto->bruto_dolar),'0',2);//@RETORNADO    
      $determinado_bruto_peso = bcadd($D('determinado_bruto_peso',$bruto->bruto_peso),'0',2);
      $determinado_bruto_dolar = bcadd($D('determinado_bruto_dolar',$bruto->bruto_dolar),'0',2);
      
      $cotizacion_dolar = $cot['dolar'] ?? '0';//@RETORNADO              
                  
      $diario[$dia] = array_merge(
        compact('dia','fecha'),
        $this->calcular_devengado(
          $cotizacion_dolar,
          $devengado_apostado_sistema_peso,$devengado_apostado_sistema_dolar,
          $devengado_bruto_peso,$devengado_bruto_dolar,
          $factor_apostado_porcentaje_impuesto_ley,
          $factor_apostado_porcentaje_aplicable,
          $factor_alicuota
        ),
        $this->calcular_determinado(
          $cotizacion_dolar,
          '0',
          $determinado_bruto_peso,$determinado_bruto_dolar,
          $factor_alicuota
        )
      );
      
      $devengado_apostado_sistema_total = bcadd($devengado_apostado_sistema_total,$diario[$dia]['devengado_apostado_sistema'],16);
    }
    
    $determinado_impuesto_total_calculado = '0';
    $devengado_apostado_sistema_total_nulo = bccomp_precise($devengado_apostado_sistema_total,'0') == 0;
    $didx_impuesto_mas_grande = null;
    foreach($diario as $didx => &$d){
      if($devengado_apostado_sistema_total_nulo){
        $d['determinado_impuesto'] = '0';
      }
      else {
        $d['determinado_impuesto'] = bcdiv(
          bcmul_precise($d['devengado_apostado_sistema'],$determinado_impuesto_total),
          $devengado_apostado_sistema_total,
          16
        );
      }
      foreach($this->calcular_determinado(
        $d['cotizacion_dolar'],
        $d['determinado_impuesto'],
        $d['determinado_bruto_peso'],$d['determinado_bruto_dolar'],
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
    if($didx_impuesto_mas_grande !== null && !$devengado_apostado_sistema_total_nulo){
      $d = &$diario[$didx_impuesto_mas_grande];
      $d['determinado_impuesto'] = bcadd($d['determinado_impuesto'],$error,16);
      foreach($this->calcular_determinado(
        $d['cotizacion_dolar'],
        $d['determinado_impuesto'],
        $d['determinado_bruto_peso'],$d['determinado_bruto_dolar'],
        $d['factor_alicuota']
      ) as $k => $v){
        $d[$k] = $v;
      }
    }
    
    return compact('diario');
  }
  
  private function calcular_determinado(
    $cotizacion_dolar,
    $determinado_impuesto,
    $determinado_bruto_peso,$determinado_bruto_dolar,
    $factor_alicuota
  ){
    $determinado_bruto_dolar_cotizado = bcmul($determinado_bruto_dolar,$cotizacion_dolar,4);//2+2 @RETORNADO
    $determinado_bruto = bcadd($determinado_bruto_peso,$determinado_bruto_dolar_cotizado,4);//@RETORNADO
    
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,16);
    $determinado_total = bcmul($determinado_subtotal,$factor_alicuota,22);
    
    return compact(
      'cotizacion_dolar',
      'determinado_bruto_peso','determinado_bruto_dolar','determinado_bruto_dolar_cotizado','determinado_bruto',
      'factor_alicuota',
      'determinado_impuesto','determinado_subtotal',
      'determinado_total'
    );
  }
  
  private function calcular_devengado(
    $cotizacion_dolar,
    $devengado_apostado_sistema_peso,$devengado_apostado_sistema_dolar,
    $devengado_bruto_peso,$devengado_bruto_dolar,
    $factor_apostado_porcentaje_impuesto_ley,
    $factor_apostado_porcentaje_aplicable,
    $factor_alicuota
  ){
    $devengado_apostado_sistema_dolar_cotizado = bcmul($devengado_apostado_sistema_dolar,$cotizacion_dolar,4);//4+2 @RETORNADO
    $devengado_bruto_dolar_cotizado = bcmul($devengado_bruto_dolar,$cotizacion_dolar,4);//2+2 @RETORNADO
    $devengado_apostado_sistema = bcadd($devengado_apostado_sistema_peso,$devengado_apostado_sistema_dolar_cotizado,4);//@RETORNADO
    $devengado_bruto = bcadd($devengado_bruto_peso,$devengado_bruto_dolar_cotizado,4);//@RETORNADO
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,10);//4+6 @RETORNADO
    $devengado_impuesto = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,16);//10+6 @RETORNADO
    $devengado_subtotal = bcsub($devengado_bruto,$devengado_impuesto,16);
    $devengado_total = bcmul($devengado_subtotal,$factor_alicuota,22);
      
    return compact(
      'cotizacion_dolar',
      'devengado_apostado_sistema_peso','devengado_apostado_sistema_dolar','devengado_apostado_sistema_dolar_cotizado','devengado_apostado_sistema',
      'devengado_bruto_peso','devengado_bruto_dolar','devengado_bruto_dolar_cotizado','devengado_bruto',
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
      $diario = $d['diario'];
      unset($d['diario']);
      $id_canon_variable = DB::table('canon_variable')
      ->insertGetId($d);
      foreach($diario as $d){
        $d['id_canon_variable'] = $id_canon_variable;
        DB::table('canon_variable_diario')
        ->insert($d);
      }
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
          'dolar' => ($datadia['cotizacion_dolar'] ?? null),
          'euro' => null
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
      return ((object)['dia' => ($diario? intval($año_mes_arr[2]) : 0),'bruto_peso' => $v,'bruto_dolar' => $v,'cotizacion_dolar' => $v,'bruto_dolar_cotizado' => $v,'bruto' => $v]);
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
          SUM(IF(b.id_tipo_moneda = 1,b.valor,0)) as bruto_peso,
          SUM(IF(b.id_tipo_moneda = 2,b.valor,0)) as bruto_dolar,
          MAX(IF($diario,cot.valor,NULL)) as cotizacion_dolar,
          SUM(IF(b.id_tipo_moneda = 2,cot.valor*b.valor,0)) as bruto_dolar_cotizado,
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
          SUM(b.recaudado-b.premio_bingo-b.premio_linea) as bruto_peso,
          NULL as bruto_dolar,
          NULL as cotizacion_dolar,
          NULL as bruto_dolar_cotizado,
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
      return ((object)['dia' => ($diario? $año_mes_arr[2] : 0),'apostado_peso' => $v,'apostado_dolar' => $v,'cotizacion_dolar' => $v,'apostado_dolar_cotizado' => $v,'apostado' => $v]);
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
          SUM(IF(b.id_tipo_moneda = 1,b.coinin,0)) as apostado_peso,
          SUM(IF(b.id_tipo_moneda = 2,b.coinin,0)) as apostado_dolar,
          MAX(IF($diario,cot.valor,NULL)) as cotizacion_dolar,
          SUM(IF(b.id_tipo_moneda = 2,cot.valor*b.coinin,0)) as apostado_dolar_cotizado,
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
  
  public function datosCanon(){
    return [
      'canon_fisico' => 'SUM(IF(subcanon.tipo LIKE "jol",0,subcanon.determinado+subcanon.determinado_ajuste)) as canon_variable¡canon_fisico',
      'canon_online' => 'SUM(IF(subcanon.tipo LIKE "jol",subcanon.determinado+subcanon.determinado_ajuste,0)) as canon_variable¡canon_online',
      'ganancia_fisico' => 'SUM(IF(subcanon.tipo LIKE "jol",0,subcanon.determinado_subtotal)) as canon_variable¡ganancia_fisico',
      'ganancia_online' => 'SUM(IF(subcanon.tipo LIKE "jol",subcanon.determinado_subtotal,0)) as canon_variable¡ganancia_online',
      'ganancia' => 'SUM(subcanon.determinado_subtotal) as canon_variable¡ganancia',
      'ganancia_CCO' => 'SUM(IF(
        subcanon.tipo LIKE "jol" AND tabla_base.codigo_plataforma LIKE "CCO",
        subcanon.determinado_subtotal,
        0
      )) as canon_variable¡ganancia_CCO',
      'ganancia_BPLAY' => 'SUM(IF(
        subcanon.tipo LIKE "jol" AND tabla_base.codigo_plataforma LIKE "BPLAY",
        subcanon.determinado_subtotal,
        0
      )) as canon_variable¡ganancia_BPLAY'
    ];
  }
}
