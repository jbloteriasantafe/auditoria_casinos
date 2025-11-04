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
  
  public function totalesCanon_query($discriminar_adicionales){
    return 'SELECT
      sc.id_canon,
      "Paños" as concepto,
      1 as es_fisico,
      sc.bruto as beneficio,
      IF(sc.devengar,sc.devengado_total,NULL) as bruto,
      IF(sc.devengar,sc.devengado_deduccion,NULL) as deduccion,
      IF(sc.devengar,sc.devengado,NULL) as devengado,
      sc.determinado as determinado
    FROM canon_fijo_mesas AS sc';
  }
  
  public function recalcular($año_mes,$id_casino,$version,$tipo,$accessors){
    extract($accessors);
    $año_mes_arr = explode('-',$año_mes);
    $dias = count($año_mes_arr) >= 2? 
      cal_days_in_month(CAL_GREGORIAN,intval($año_mes_arr[1]),intval($año_mes_arr[0]))
    : 0;
    
    $valor_dolar = $COT('valor_dolar');//@RETORNADO
    $valor_euro  = $COT('valor_euro');//@RETORNADO
    
    $devengar = $RD('devengar',1);
    
    $dias_valor = $RD('dias_valor',0);//@RETORNADO
    $valores = self::calcular_valores($valor_dolar,$valor_euro,$dias_valor);
    $devengado_valor_cotizado = self::calcular_valores_cotizados(
      $COT('devengado_cotizacion_dolar','0'),
      $COT('devengado_cotizacion_euro','0'),
      $valores
    );
    $determinado_valor_cotizado = self::calcular_valores_cotizados(
      $COT('determinado_cotizacion_dolar','0'),
      $COT('determinado_cotizacion_euro','0'),
      $valores
    );
    
    $dias_lunes_jueves = 0;//@RETORNADO
    $dias_viernes_sabados = 0;//@RETORNADO
    $dias_domingos = 0;//@RETORNADO
    $dias_todos = 0;//@RETORNADO
    //Originalmente eran dos parametros distintos... pero la verdad para que el total devengado y el ultimo dia del mes
    //den igual con igual cotizacion es necesario que sean iguales... y tiene sentido la verdad
    $dias_fijos = $dias_valor;//@RETORNADO
    
    if($año_mes !== null){ 
      $wdmin_wdmax_count_arr = [
        'dias_lunes_jueves'    => [1,4,0],
        'dias_viernes_sabados' => [5,6,0],
        'dias_domingos'        => [0,0,0],
        'dias_todos'           => [0,6,0],
      ];
      
      $calcular = $D('calcular_dias_lunes_jueves',true)
      || $D('calcular_dias_viernes_sabados',true)
      || $D('calcular_dias_domingos',true)
      || $D('calcular_dias_todos',true);
      //@SPEED: unset K si no hay que calcular?
      if($calcular){
        for($d=1;$d<=$dias;$d++){
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
    
    $meses_dias  = self::calcular_meses_dias($mesas_dias,$valores,'1');
    $total_dolar = self::calcular_total($meses_dias,$valores['valor_dolar'],$valores['valor_dolar_diario']);
    $total_euro  = self::calcular_total($meses_dias,$valores['valor_euro'],$valores['valor_euro_diario']);
    $devengado_total   = self::calcular_total($meses_dias,$devengado_valor_cotizado['valor'],$devengado_valor_cotizado['valor_diario']);
    $determinado_total = self::calcular_total($meses_dias,$determinado_valor_cotizado['valor'],$determinado_valor_cotizado['valor_diario']);
    $devengado_total_dolar_cotizado = self::calcular_total($meses_dias,$devengado_valor_cotizado['valor_dolar_cotizado'],$devengado_valor_cotizado['valor_dolar_cotizado_diario']);
    $devengado_total_euro_cotizado = self::calcular_total($meses_dias,$devengado_valor_cotizado['valor_euro_cotizado'],$devengado_valor_cotizado['valor_euro_cotizado_diario']);
    $determinado_total_dolar_cotizado = self::calcular_total($meses_dias,$determinado_valor_cotizado['valor_dolar_cotizado'],$determinado_valor_cotizado['valor_dolar_cotizado_diario']);
    $determinado_total_euro_cotizado = self::calcular_total($meses_dias,$determinado_valor_cotizado['valor_euro_cotizado'],$determinado_valor_cotizado['valor_euro_cotizado_diario']);
    
    $accesors_diario = [
      'R' => AUX::make_accessor($R('diario',[])),
      'A' => AUX::make_accessor($A('diario',[])),
      'COT' => AUX::make_accessor($COT('canon_cotizacion_diaria',[])),
    ];
    $accesors_diario['RA'] = AUX::combine_accessors($accesors_diario['R'],$accesors_diario['A']);
    $factor_ajuste_diario_fijas = $tipo == 'Fijas'? bcdiv($dias_valor,$dias,12) : '1';
    $diario = $this->recalcular_diario(
      $año_mes,$id_casino,$version,$tipo,
      $accesors_diario,
      $mesas_lunes_jueves,
      $mesas_viernes_sabados,
      $mesas_domingos,
      $mesas_todos,
      $mesas_fijos,
      $mesas_dias,
      $factor_ajuste_diario_fijas,
      $valores
    )['diario'] ?? [];//@RETORNADO
    
    $bruto = '0';
    $mesas_habilitadas_acumuladas = 0;
    if($version == 'mensual' || $version == 'antiguo'){
      $bruto = bcadd($R('bruto',$this->bruto($tipo,$año_mes,$id_casino)->bruto),'0',2);//@RETORNADO
    }
    else if($version == 'diario'){
      foreach($diario as $d){
        $bruto = bcadd_precise($bruto,$d['bruto'] ?? '0');
      }
    }
    
    $devengado_fecha_cotizacion = $COT('devengado_fecha_cotizacion');//@RETORNADO
    $determinado_fecha_cotizacion = $COT('determinado_fecha_cotizacion');//@RETORNADO
    $ret = array_merge(compact(
      'tipo',
      'dias_lunes_jueves','mesas_lunes_jueves','dias_viernes_sabados','mesas_viernes_sabados',
      'dias_domingos','mesas_domingos','dias_todos','mesas_todos','dias_fijos','mesas_fijos',
      'mesas_dias',
      'factor_ajuste_diario_fijas',
      'total_dolar',
      'total_euro',
      'devengar',
      'devengado_fecha_cotizacion','devengado_total',
      'determinado_fecha_cotizacion','determinado_total',
      'devengado_total_dolar_cotizado','devengado_total_euro_cotizado',
      'determinado_total_dolar_cotizado','determinado_total_euro_cotizado',
      'bruto',
      'diario'
    ),$valores);
    
    foreach($devengado_valor_cotizado as $k => $v){
      $ret['devengado_'.$k] = $v;
    }
    foreach($determinado_valor_cotizado as $k => $v){
      $ret['determinado_'.$k] = $v;
    }
    
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);//@RETORNADO
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',16);//@RETORNADO
    if($version == 'antiguo'){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
    $devengado   = bcsub($devengado_total,$devengado_deduccion,16);
    $determinado = bcadd($determinado_total,$determinado_ajuste,16);
    
    $ret['devengado_total'] = $devengado_total;
    $ret['devengado_deduccion'] = $devengado_deduccion;
    $ret['devengado'] = $devengado;
    
    $ret['determinado_total'] = $determinado_total;
    $ret['determinado_ajuste'] = $determinado_ajuste;
    $ret['determinado'] = $determinado;
    
    return $ret;
  }
  
  private static function calcular_valores($valor_dolar,$valor_euro,$dias_valor){
    $factor_dias_valor = $dias_valor != 0? bcdiv('1',$dias_valor,12) : '0.000000000000';//Un error de una milesima de peso en 1 billon
    $valor_euro_diario = bcmul_precise($valor_euro,$factor_dias_valor);
    $valor_dolar_diario = bcmul_precise($valor_dolar,$factor_dias_valor);
    return compact(
      'valor_dolar','valor_euro','dias_valor',
      'factor_dias_valor','valor_dolar_diario','valor_euro_diario'
    );
  }
  
  private static function calcular_valores_cotizados($cotizacion_dolar,$cotizacion_euro,$valores){        
    $valor_euro_cotizado = bcmul_precise($valores['valor_euro'],$cotizacion_euro);
    $valor_dolar_cotizado = bcmul_precise($valores['valor_dolar'],$cotizacion_dolar);
    $valor = bcadd_precise($valor_euro_cotizado,$valor_dolar_cotizado);
    
    $valor_euro_cotizado_diario = bcmul_precise($valor_euro_cotizado,$valores['factor_dias_valor']);
    $valor_dolar_cotizado_diario = bcmul_precise($valor_dolar_cotizado,$valores['factor_dias_valor']);
    $valor_diario = bcmul_precise($valor,$valores['factor_dias_valor']);
    
    return compact(
      'cotizacion_dolar','cotizacion_euro',
      'valor_euro_cotizado','valor_dolar_cotizado','valor',
      'valor_euro_cotizado_diario','valor_dolar_cotizado_diario','valor_diario'
    );
  }
  
  private static function calcular_meses_dias($mesas_dias,$valores,$factor_ajuste_diario_fijas){
    $mesas_dias = bcmul_precise($mesas_dias,$factor_ajuste_diario_fijas);
    $meses = '0';
    $dias  = '0';
    if($valores['dias_valor'] > 0){
      $meses = bcdiv($mesas_dias,$valores['dias_valor'],0);
      $dias  = bcsub_precise($mesas_dias,bcmul_precise($meses,$valores['dias_valor']));
    }
    return compact('meses','dias');
  }
  
  private static function calcular_total($meses_dias,$valor,$valor_diario){
    $total = bcmul_precise($valor,$meses_dias['meses']);
    return bcadd_precise($total,bcmul_precise($valor_diario,$meses_dias['dias']));
  }
  
  private function recalcular_diario(
    $año_mes,$id_casino,$version,$tipo,
    $accessors,
    $mesas_lunes_jueves,
    $mesas_viernes_sabados,
    $mesas_domingos,
    $mesas_todos,
    $mesas_fijos,
    $mesas_dias,
    $factor_ajuste_diario_fijas,
    $valores
  ){
    static $cotizaciones = [];//voy guardando por si cambia alguna ya cambia todas...
    extract($accessors);
    
    $año_mes_str = substr($año_mes,0,strlen('XXXX-XX-'));
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
    $cotizaciones = $COT(null,[]);
    $dias_restantes = count($cotizaciones);
    foreach($cotizaciones as $dia => $cot){
      $D = AUX::make_accessor($R($dia,[]));
      $fecha = $año_mes_str.str_pad($dia,2,'0',STR_PAD_LEFT);
      $cotizacion_dolar = $cot['dolar'] ?? '0';
      $cotizacion_euro  = $cot['euro'] ?? '0';
      
      $bruto = $this->bruto($tipo,$fecha,$id_casino,true);
      
      $idx_dia_semana = (new \DateTime($fecha))->format('w');
      $dia_semana = $dias_semana[$idx_dia_semana];
      $mesas_habilitadas = $mesas_semana[$idx_dia_semana];
      $mesas_usadas_peso = $D('mesas_usadas_peso',$bruto->mesas_peso ?? 0);
      $bruto_peso = $D('bruto_peso',$bruto->bruto_peso ?? '0');
      $mesas_usadas_dolar = $D('mesas_usadas_dolar',$bruto->mesas_dolar ?? 0);
      $bruto_dolar = $D('bruto_dolar',$bruto->bruto_dolar ?? '0');
      $bruto_dolar_cotizado = bcmul($bruto_dolar,$cotizacion_dolar,4);
      $mesas_usadas = bcadd_precise($mesas_usadas_peso,$mesas_usadas_dolar);
      $bruto = bcadd($bruto_peso,$bruto_dolar_cotizado,4);
      
      $mesas_habilitadas_acumuladas += $mesas_habilitadas;
      
      $valores_cotizados = self::calcular_valores_cotizados($cotizacion_dolar,$cotizacion_euro,$valores);          
      //Para el ultimo dia, evito la aproximación porque la cantidad de mesas 
      //(F*MesasAcumuladas) deberia redondear a MesasDias
      $dias_restantes--;
      $meses_dias  = self::calcular_meses_dias(
        $dias_restantes == 0? $mesas_dias : $mesas_habilitadas_acumuladas,
        $valores,
        $dias_restantes == 0?        '1' : $factor_ajuste_diario_fijas
      );
      $total_dolar = self::calcular_total($meses_dias,$valores['valor_dolar'],$valores['valor_dolar_diario']);
      $total_euro  = self::calcular_total($meses_dias,$valores['valor_euro'],$valores['valor_euro_diario']);
      $total = self::calcular_total($meses_dias,$valores_cotizados['valor'],$valores_cotizados['valor_diario']);
      
      $aux = compact(
        'dia','fecha',
        'cotizacion_euro',
        'cotizacion_dolar',
        'dia_semana',
        'mesas_habilitadas',
        'mesas_usadas_peso',
        'bruto_peso',
        'mesas_usadas_dolar',
        'bruto_dolar',
        'bruto_dolar_cotizado',
        'mesas_usadas',
        'bruto',
        'dias_valor',
        'factor_dias_valor',
        'mesas_habilitadas_acumuladas',
        'factor_ajuste_diario_fijas',
        'total_dolar',
        'total_euro',
        'total'
      );
        
      $diario[$dia] = array_merge($aux,$valores,$valores_cotizados);
    }
    
    return compact('diario');
  }
    
  public function guardar($id_canon,$id_canon_anterior,$datos){
    foreach(($datos['canon_fijo_mesas'] ?? []) as $tipo => $d){
      $d['id_canon'] = $id_canon;
      $d['tipo'] = $tipo;
      unset($d['id_canon_fijo_mesas']);
      $diario = $d['diario'];
      unset($d['diario']);
      $id_canon_fijo_mesas = DB::table('canon_fijo_mesas')
      ->insertGetId($d);
      foreach($diario as $d){
        $d['id_canon_fijo_mesas'] = $id_canon_fijo_mesas;
        DB::table('canon_fijo_mesas_diario')
        ->insert($d);
      }
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
          'dolar' => ($datadia['cotizacion_dolar'] ?? null),
          'euro'  => ($datadia['cotizacion_euro'] ?? null)
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
      return ((object)['dia' => ($diario? intval($año_mes_arr[2]) : 0),'mesas_peso' => $v,'bruto_peso' => $v,'mesas_dolar' => $v,'bruto_dolar' => $v,'cotizacion_dolar' => $v,'bruto_dolar_cotizado' => $v,'mesas' => $v,'bruto' => $v]);
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
          SUM(IF(idm.id_moneda = 1,idm.utilidad,0)) as bruto_peso,
          SUM(IF(idm.id_moneda = 2,idm.utilidad,0)) as bruto_dolar,
          MAX(IF($diario,$cot_valor,NULL)) as cotizacion_dolar,
          SUM(IF(idm.id_moneda = 2,$cot_valor*idm.utilidad,0)) as bruto_dolar_cotizado,
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
          COUNT(distinct IF(idm.id_moneda = 1,$codigo,NULL)) as mesas_peso,
          COUNT(distinct IF(idm.id_moneda = 2,$codigo,NULL)) as mesas_dolar,
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
        $resultado[$d]->mesas_peso = $mesas[$d]->mesas_peso;
        $resultado[$d]->mesas_dolar = $mesas[$d]->mesas_dolar;
        $resultado[$d]->mesas     = $mesas[$d]->mesas;
      }
    }
    else{
      if($resultado !== null && $mesas !== null && array_key_exists(0,$resultado) && array_key_exists(0,$mesas)){
        $resultado[0]->mesas_peso = $mesas[0]->mesas_peso;
        $resultado[0]->mesas_dolar = $mesas[0]->mesas_dolar;
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
  
  public function datosCanon(){
    return [
      'canon_fisico' => 'SUM(subcanon.determinado+subcanon.determinado_ajuste) as canon_fijo_mesas¡canon_fisico',
      'canon_online' => '0 as canon_fijo_mesas¡canon_online',
      'ganancia_fisico' => 'SUM(subcanon.bruto) as canon_fijo_mesas¡ganancia_fisico',
      'ganancia_online' => '0 as canon_fijo_mesas¡ganancia_online',
      'ganancia' => 'SUM(subcanon.bruto) as canon_fijo_mesas¡ganancia',
      'ganancia_yoy' => 'SUM(subcanon_yoy.bruto) as canon_fijo_mesas¡ganancia_yoy',
      'ganancia_CCO' => '0 as canon_fijo_mesas¡ganancia_CCO',
      'ganancia_BPLAY' => '0 as canon_fijo_mesas¡ganancia_BPLAY',
      'determinado_fecha_cotizacion' => 'MAX(subcanon.determinado_fecha_cotizacion) as canon_fijo_mesas¡determinado_fecha_cotizacion',
      'determinado_fecha_cotizacion_yoy' => 'MAX(subcanon_yoy.determinado_fecha_cotizacion) as canon_fijo_mesas¡determinado_fecha_cotizacion_yoy',
      'determinado_cotizacion_euro' => 'MAX(subcanon.determinado_cotizacion_euro) as canon_fijo_mesas¡determinado_cotizacion_euro',
      'determinado_cotizacion_euro_yoy' => 'MAX(subcanon_yoy.determinado_cotizacion_euro) as canon_fijo_mesas¡determinado_cotizacion_euro_yoy',
      'determinado_cotizacion_dolar' => 'MAX(subcanon.determinado_cotizacion_dolar) as canon_fijo_mesas¡determinado_cotizacion_dolar',
      'determinado_cotizacion_dolar_yoy' => 'MAX(subcanon_yoy.determinado_cotizacion_dolar) as canon_fijo_mesas¡determinado_cotizacion_dolar_yoy',
      'valor_euro' => 'MAX(subcanon.valor_euro) as canon_fijo_mesas¡valor_euro',
      'valor_euro_yoy' => 'MAX(subcanon_yoy.valor_euro) as canon_fijo_mesas¡valor_euro_yoy',
      'valor_dolar' => 'MAX(subcanon.valor_dolar) as canon_fijo_mesas¡valor_dolar',
      'valor_dolar_yoy' => 'MAX(subcanon_yoy.valor_dolar) as canon_fijo_mesas¡valor_dolar_yoy',
    ];
  }
}
