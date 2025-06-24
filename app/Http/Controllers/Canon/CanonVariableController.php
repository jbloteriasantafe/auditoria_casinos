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
  
  private function apostado($tipo,$año_mes,$id_casino){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.coinin*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
    }
    return null;
  }
  
  public function recalcular($año_mes,$id_casino,$es_antiguo,$tipo,$accessors){
    extract($accessors);
    
    $devengar = $RD('devengar',$es_antiguo? 0 : 1);
    $devengado_apostado_sistema = bcadd($R('devengado_apostado_sistema',$this->apostado($tipo,$año_mes,$id_casino)),'0',2);//@RETORNADO    
    $devengado_apostado_porcentaje_aplicable = bcadd($RD('devengado_apostado_porcentaje_aplicable','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_aplicable = bcdiv($devengado_apostado_porcentaje_aplicable,'100',6);
    
    $devengado_base_imponible = bcmul($devengado_apostado_sistema,$factor_apostado_porcentaje_aplicable,8);//2+6 @RETORNADO
    
    $devengado_apostado_porcentaje_impuesto_ley = bcadd($RD('devengado_apostado_porcentaje_impuesto_ley','0.0000'),'0',4);//@RETORNADO
    $factor_apostado_porcentaje_impuesto_ley = bcdiv($devengado_apostado_porcentaje_impuesto_ley,'100',6);
    
    $devengado_impuesto   = bcmul($devengado_base_imponible,$factor_apostado_porcentaje_impuesto_ley,14);//8+6 @RETORNADO
    $determinado_impuesto =  bcadd($R('determinado_impuesto','0.00'),'0',14);//@RETORNADO
    
    $devengado_bruto   = $R('devengado_bruto',null);//@RETORNADO
    $determinado_bruto = $R('determinado_bruto',null);//@RETORNADO
    if($devengado_bruto === null || $determinado_bruto === null){
      $bruto = $this->bruto($tipo,$año_mes,$id_casino);
      $devengado_bruto   = $devengado_bruto   ?? $bruto;
      $determinado_bruto = $determinado_bruto ?? $bruto;
    }
    
    $devengado_bruto   = bcadd($devengado_bruto,'0',2);
    $determinado_bruto = bcadd($determinado_bruto,'0',2);
    
    $devengado_subtotal   = bcsub($devengado_bruto,$devengado_impuesto,14);//@RETORNADO
    $determinado_subtotal = bcsub($determinado_bruto,$determinado_impuesto,14);//@RETORNADO
    
    $alicuota = bcadd($RD('alicuota','0.0000'),'0',4);//@RETORNADO
    $factor_alicuota = bcdiv($alicuota,'100',6);
    
    $devengado_total   =  bcmul($devengado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $determinado_total =  bcmul($determinado_subtotal,$factor_alicuota,20);//6+14 @RETORNADO
    $devengado_deduccion = bcadd($RAD('devengado_deduccion','0.00'),'0',2);
    $determinado_ajuste  = bcadd($RD('determinado_ajuste','0.00'),'0',20);
    
    if($es_antiguo){
      $devengado_total = $R('devengado_total',$devengado_total);
      $determinado_total = $R('determinado_total',$determinado_total);
    }
    
    $devengado = bcsub($devengado_total,$devengado_deduccion,20);
    $determinado = bcadd($determinado_total,$determinado_ajuste,20);
    
    return compact('tipo',
      'alicuota','devengar',
      'devengado_apostado_sistema','devengado_apostado_porcentaje_aplicable','devengado_base_imponible',
      'devengado_apostado_porcentaje_impuesto_ley',
      'devengado_bruto','devengado_impuesto','devengado_subtotal','devengado_total','devengado_deduccion',
      'devengado',
      'determinado_impuesto','determinado_bruto','determinado_subtotal','determinado_total','determinado_ajuste',
      'determinado'
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
  
  public function obtener($id_canon){
    $ret = [];
    $ret['canon_variable'] = DB::table('canon_variable')
    ->where('id_canon',$id_canon)
    ->get()
    ->keyBy('tipo');
       
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
    return [];
  }
    
  public function bruto($tipo,$año_mes,$id_casino){
    if($año_mes === null || $tipo === null || $id_casino === null) return null;
    $año_mes_arr = explode('-',$año_mes);
    switch($tipo){
      case 'Maquinas':{
        $resultado = DB::table('beneficio as b')
        ->selectRaw('SUM(b.valor*IF(b.id_tipo_moneda = 1,1,CAST(cot.valor AS DECIMAL(20,6)))) as valor')
        ->leftJoin('cotizacion as cot',function($q){
          return $q->where('b.id_tipo_moneda',2)->on('b.fecha','=','cot.fecha');
        })
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      }break;
      case 'Bingo':{
        $resultado = DB::table('bingo_importacion as b')
        ->selectRaw('SUM(b.recaudado-b.premio_bingo-b.premio_linea) as valor')
        ->where('b.id_casino',$id_casino)
        ->whereYear('b.fecha',$año_mes_arr[0])
        ->whereMonth('b.fecha',intval($año_mes_arr[1]))
        ->groupBy(DB::raw('"constant"'))->first();
        
        return $resultado === null? $resultado : $resultado->valor;
      };
      case 'JOL':{
        $JOL_connect_config = CanonValorPorDefectoController::getInstancia()->valorPorDefecto('JOL_connect_config') ?? null;
        $debug = $JOL_connect_config['debug'] ?? false;
        if(empty($JOL_connect_config)) return $debug? '-0.99' : null;
        if(empty($JOL_connect_config['ip_port'])) return $debug? '-0.98' : null;
        if(empty($JOL_connect_config['API-Token']))  return $debug? '-0.97' : null;
        
        set_time_limit(5);
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, "http://{$JOL_connect_config['ip_port']}/api/bruto");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(compact('año_mes','id_casino')));
        //curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_PROXY, NULL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          'API-Token: '.$JOL_connect_config['API-Token']
        ]);
        
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if($result === false){//Error de curl https://curl.se/libcurl/c/libcurl-errors.html
          $ret = $debug? (-curl_errno($ch)).'.99' : null;
          curl_close($ch);
          return $ret;
        }
        curl_close($ch);
        
        if($code != 200){
          return $debug? (-$code).'.98' : null;
        }
                
        return $result;
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
  
  public function datosCanon($tname){
    $attrs_canon = [
      'canon_fisico' => 'SUM(IF(cv.tipo LIKE "jol",0,cv.determinado)) as canon_fisico',
      'canon_online' => 'SUM(IF(cv.tipo LIKE "jol",cv.determinado,0)) as canon_online',
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
