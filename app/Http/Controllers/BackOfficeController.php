<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Validator;

//Mismo que en CanonController
function formatear_decimal(string $val) : string {//number_format castea a float... lo hacemos a pata...
  $negativo = ($val[0] ?? false) == '-'? '-' : '';
  $val = strlen($negativo)? substr($val,1) : $val;
  
  $parts   = explode('.',$val);
  $entero  = $parts[0] ?? '';
  $decimal = $parts[1] ?? null;
  $entero_separado = [];
  for($i=0;$i<strlen($entero);$i++){
    $bucket = intdiv($i,3);
    if($i%3 == 0) $entero_separado[$bucket] = '';
    $entero_separado[$bucket] = $entero[strlen($entero)-1-$i] . $entero_separado[$bucket];
  }

  $newval = implode('.',array_reverse($entero_separado));
  $decimal = is_null($decimal)? null : rtrim($decimal,'0');
  if(!is_null($decimal) && strlen($decimal) > 0){
    $newval .= ','.$decimal;
  }
  return $negativo.$newval;
}

class BackOfficeController extends Controller {
  //Por algun motivo, las vistas pueden ser exponencialmente mas lentas que una
  //query directa, por eso no queryiero una vista sino una raw query
  //Esto puede evitarse usando el ALGORITHM MERGE de MySQL 8.0
  //Pero para ser rapido en 5.7 hago esto... Octavio 2023-09-12
  //https://dev.mysql.com/doc/refman/8.0/en/derived-table-optimization.html
  //https://stackoverflow.com/questions/62832483/mysql-view-is-very-slow-why

  private $selectComunVals_internal_cache = [];
  private function selectComunVals_internal($tabla,$tabla_valor,$columna_valor,$id_valor){
    $k = implode('|',func_get_args());
    if(!array_key_exists($k,$this->selectComunVals_internal_cache)){
      $this->selectComunVals_internal_cache[$k] = DB::table($tabla)
      ->selectRaw("$tabla_valor.$id_valor as id,$tabla_valor.$columna_valor as valor")->distinct()
      ->join($tabla_valor,"$tabla_valor.$id_valor",'=',"$tabla.$id_valor")
      ->orderby("$tabla_valor.$columna_valor",'asc')
      ->get();
    }
    return $this->selectComunVals_internal_cache[$k];
  }
  
  private function selectCasinoVals($tabla){
    return $this->selectComunVals_internal($tabla,'casino','nombre','id_casino');
  }
  private function selectTipoMonedaVals($tabla){
    return $this->selectComunVals_internal($tabla,'tipo_moneda','descripcion','id_tipo_moneda');
  }
  private function selectMonedaVals($tabla){
    return $this->selectComunVals_internal($tabla,'moneda','siglas','id_moneda');
  }
  
  private $vistas = null;
  function __construct(){
    $hoy = date('Y-m');        
    //Directamente vinculado con 'cols', no cambiar el orden si no se cambia el orden de las columnas
    //select, alias, tipo para formateo, tipo de buscador, cantidad de buscadores y valores por defecto, valores (solo select)
    $cols_indexes = ['BO_SELECT','BO_ALIAS','BO_FMT','BO_TIPO','BO_DEFAULTS','BO_VALUES'];
    foreach($cols_indexes as $val => $constant){
      define($constant,$val);
    }
    
    $this->vistas = [
      'beneficio_maquinas_por_moneda' => [
        'cols' => [
          ['b.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('beneficio')],
          ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('beneficio')],
          ['(
              SELECT COUNT(*)
              FROM producido as p
              JOIN detalle_producido as dp ON dp.id_producido = p.id_producido
              WHERE p.fecha = b.fecha AND p.id_tipo_moneda = b.id_tipo_moneda AND p.id_casino = b.id_casino
                AND dp.valor <> 0
            )','maquinas','integer'],
          ['b.coinin','apostado','numeric'],
          ['b.coinout','premio','numeric'],
          ['b.jackpot','premios_mayores','numeric'],
          ['b.valor','beneficio','numeric'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['b.coinin*IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','apostado_ars','numeric'],
          ['b.coinout*IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','premio_ars','numeric'],
          ['b.jackpot*IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','premios_mayores_ars','numeric'],
          ['b.valor*IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','beneficio_ars','numeric'],
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
          'moneda' => 'tm.id_tipo_moneda',
        ],
        'query' => DB::table('beneficio as b')
        ->join('casino as c','c.id_casino','=','b.id_casino')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','b.id_tipo_moneda')
        ->leftJoin('cotizacion as cot','cot.fecha','=','b.fecha'),
        'default_order_by' => [
          'b.fecha' => 'asc'
        ],
      ],
      'beneficio_maquinas' => [
        'cols' => [
          ['b.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('beneficio')],
          ['(
              SELECT COUNT(*)
              FROM producido as p
              JOIN detalle_producido as dp ON dp.id_producido = p.id_producido
              WHERE p.fecha = b.fecha AND p.id_casino = b.id_casino
                AND dp.valor <> 0
            )','maquinas','integer'],
          ['SUM(b.coinin*IF(tm.id_tipo_moneda = 1,1.0,cot.valor))','apostado','numeric'],
          ['SUM(b.coinout*IF(tm.id_tipo_moneda = 1,1.0,cot.valor))','premio','numeric'],
          ['SUM(b.jackpot*IF(tm.id_tipo_moneda = 1,1.0,cot.valor))','premios_mayores','numeric'],
          ['SUM(b.valor*IF(tm.id_tipo_moneda = 1,1.0,cot.valor))','beneficio','numeric'],
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
        ],
        'query' => DB::table('beneficio as b')
        ->join('casino as c','c.id_casino','=','b.id_casino')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','b.id_tipo_moneda')
        ->leftJoin('cotizacion as cot','cot.fecha','=','b.fecha')
        ->groupBy('c.nombre','b.fecha'),
        'default_order_by' => [
          'b.fecha' => 'asc'
        ],
      ],
      'beneficio_mesas_por_moneda' => [
        'cols' => [
          ['idm.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('importacion_diaria_mesas')],
          ['m.siglas','moneda','string','select',[0],$this->selectMonedaVals('importacion_diaria_mesas')],
          ['(
            SELECT COUNT(distinct CONCAT(didm.siglas_juego,didm.nro_mesa))
            FROM detalle_importacion_diaria_mesas as didm
            WHERE didm.id_importacion_diaria_mesas = idm.id_importacion_diaria_mesas
            AND didm.deleted_at IS NULL
            AND (
                 IFNULL(didm.droop,0) <> 0 OR IFNULL(didm.droop_tarjeta,0) <> 0 
              OR IFNULL(didm.reposiciones,0) <> 0 OR IFNULL(didm.retiros,0) <> 0 
              OR IFNULL(didm.utilidad,0) <> 0 OR IFNULL(didm.saldo_fichas,0) <> 0 
              OR IFNULL(didm.propina <> 0,0)
            )
          )','mesas','integer'],
          ['idm.droop','drop','numeric'],
          ['idm.droop_tarjeta','drop_tarjeta','numeric'],
          ['idm.saldo_fichas','saldo_fichas','numeric'],
          ['idm.retiros','retiros','numeric'],
          ['idm.reposiciones','reposiciones','numeric'],
          ['idm.utilidad','utilidad','numeric'],
          ['IF(m.id_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['idm.droop*IF(m.id_moneda = 1,1.0,cot.valor)','drop_ars','numeric'],
          ['idm.droop_tarjeta*IF(m.id_moneda = 1,1.0,cot.valor)','drop_tarjeta_ars','numeric'],
          ['idm.saldo_fichas*IF(m.id_moneda = 1,1.0,cot.valor)','saldo_fichas_ars','numeric'],
          ['idm.retiros*IF(m.id_moneda = 1,1.0,cot.valor)','retiros_ars','numeric'],
          ['idm.reposiciones*IF(m.id_moneda = 1,1.0,cot.valor)','reposiciones_ars','numeric'],
          ['idm.utilidad*IF(m.id_moneda = 1,1.0,cot.valor)','utilidad_ars','numeric'],          
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
          'moneda' => 'm.id_moneda',
        ],
        'query' => DB::table('importacion_diaria_mesas as idm')
        ->join('casino as c','c.id_casino','=','idm.id_casino')
        ->join('moneda as m','m.id_moneda','=','idm.id_moneda')
        ->leftJoin('cotizacion as cot','cot.fecha','=','idm.fecha')
        ->whereNull('idm.deleted_at'),
        'default_order_by' => [
          'idm.fecha' => 'asc'
        ],
      ],
      'beneficio_mesas' => [
        'cols' => [
          ['idm.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('importacion_diaria_mesas')],
          ['(
            SELECT COUNT(distinct CONCAT(didm.siglas_juego,didm.nro_mesa))
            FROM detalle_importacion_diaria_mesas as didm
            JOIN importacion_diaria_mesas as idm2 ON idm2.id_importacion_diaria_mesas = didm.id_importacion_diaria_mesas
            WHERE idm2.id_casino = idm.id_casino AND idm2.fecha = idm.fecha
            AND idm2.deleted_at IS NULL AND didm.deleted_at IS NULL
            AND (
                 IFNULL(didm.droop,0) <> 0 OR IFNULL(didm.droop_tarjeta,0) <> 0 
              OR IFNULL(didm.reposiciones,0) <> 0 OR IFNULL(didm.retiros,0) <> 0 
              OR IFNULL(didm.utilidad,0) <> 0 OR IFNULL(didm.saldo_fichas,0) <> 0 
              OR IFNULL(didm.propina <> 0,0)
            )
          )','mesas','integer'],
          ['SUM(idm.droop*IF(m.id_moneda = 1,1.0,cot.valor))','drop','numeric'],
          ['SUM(idm.droop_tarjeta*IF(m.id_moneda = 1,1.0,cot.valor))','drop_tarjeta','numeric'],
          ['SUM(idm.saldo_fichas*IF(m.id_moneda = 1,1.0,cot.valor))','saldo_fichas','numeric'],
          ['SUM(idm.retiros*IF(m.id_moneda = 1,1.0,cot.valor))','retiros','numeric'],
          ['SUM(idm.reposiciones*IF(m.id_moneda = 1,1.0,cot.valor))','reposiciones','numeric'],
          ['SUM(idm.utilidad*IF(m.id_moneda = 1,1.0,cot.valor))','utilidad','numeric'],          
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
        ],
        'query' => DB::table('importacion_diaria_mesas as idm')
        ->join('casino as c','c.id_casino','=','idm.id_casino')
        ->join('moneda as m','m.id_moneda','=','idm.id_moneda')
        ->leftJoin('cotizacion as cot','cot.fecha','=','idm.fecha')
        ->whereNull('idm.deleted_at')
        ->groupBy('c.nombre','idm.fecha'),
        'default_order_by' => [
          'idm.fecha' => 'asc'
        ],
      ],
      'beneficio_bingos' => [
        'cols' => [
          ['bi.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('bingo_importacion')],
          ['SUM(bi.recaudado)','recaudado_informado','numeric'],
          ['SUM(bi.premio_linea)','premio_linea_informado','numeric'],
          ['SUM(bi.premio_bingo)','premio_bingo_informado','numeric'],
          ['(SUM(bi.recaudado)-SUM(bi.premio_linea)-SUM(bi.premio_bingo))','beneficio_calculado','numeric'],
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
        ],
        'query' => DB::table('bingo_importacion as bi')
        ->join('casino as c','c.id_casino','=','bi.id_casino')
        ->groupBy('c.nombre','bi.fecha'),
        'default_order_by' => [
          'bi.fecha' => 'asc'
        ],
        'count' => DB::table('bingo_importacion as bi')
        ->selectRaw('COUNT(distinct CONCAT(bi.id_casino,"-",bi.fecha)) as count')
        ->join('casino as c','c.id_casino','=','bi.id_casino')
        ->groupBy(DB::raw('"constant"'))
      ],
      'producido_maquinas' => [
        'precols' => 'STRAIGHT_JOIN',
        'cols' => [
          ['p.fecha','fecha','string','input_date_month',[$hoy]],
          ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('producido')],
          ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('producido')],
          ['SUM(dp.valor)','producido','numeric'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)','cotizacion','numeric3d'],
          ['IF(tm.id_tipo_moneda = 1,1.0,cot.valor)*SUM(dp.valor)','cotizado','numeric'],
          ['GROUP_CONCAT(distinct m.nro_admin ORDER BY m.nro_admin)','maquinas','string','input_vals_list',['']],
          ['GROUP_CONCAT(distinct i.nro_isla ORDER BY i.nro_isla)','islas','string','input_vals_list',['']],
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
          'moneda' => 'tm.id_tipo_moneda',
          'maquinas' => 'm.nro_admin',
          'islas' => 'i.nro_isla',
        ],
        'query' => DB::table('producido as p')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
        ->join('casino as c','c.id_casino','=','p.id_casino')
        ->leftJoin('cotizacion as cot','cot.fecha','=','p.fecha')
        ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
        ->join('maquina as m','m.id_maquina','=','dp.id_maquina')
        ->join('isla as i','i.id_isla','=','m.id_isla')
        ->groupBy('p.id_producido'),
        'default_order_by' => [
          'p.fecha' => 'asc'
        ],
        'count' => DB::table('producido as p')
        ->selectRaw('COUNT(distinct p.id_producido) as count')
        ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','p.id_tipo_moneda')
        ->join('casino as c','c.id_casino','=','p.id_casino')
        ->leftJoin('cotizacion as cot','cot.fecha','=','p.fecha')
        ->join('detalle_producido as dp','dp.id_producido','=','p.id_producido')
        ->join('maquina as m','m.id_maquina','=','dp.id_maquina')
        ->join('isla as i','i.id_isla','=','m.id_isla')
        ->groupBy(DB::raw('"constant"'))
      ],
      'totales_diarios_por_moneda'   => $this->vista_totales(false,true),
      'totales_mensuales_por_moneda' => $this->vista_totales(true,true),
      'totales_diarios'   => $this->vista_totales(false,false),
      'totales_mensuales' => $this->vista_totales(true,false),
      'canon' => [
        'cols' => [
          ['DATE_FORMAT(c.año_mes,"%Y-%m")','periodo','string','input_date_month',[null,null]],
          ['cas.nombre','casino','string','select',[0],$this->selectCasinoVals('canon')],
          ['c.devengado','devengado','numeric'],
          ['c.determinado','determinado','numeric'],
          ['(
              c.cargos_adicionales
              +(
                SELECT SUM(mora_provincial)+SUM(mora_nacional)
                FROM canon_pago as cp
                WHERE cp.id_canon = c.id_canon
                GROUP BY "constant"
                LIMIT 1
              )
            )',
            'intereses',
            'numeric'
          ],
          ['c.pago','pago','numeric'],
          ['c.saldo_posterior','saldo_posterior','numeric'],
        ],
        'indirect_where' => [
          'casino' => 'c.id_casino',
          'periodo' => 'c.año_mes',
        ],
        'query' => DB::table('canon as c')
        ->join('casino as cas','cas.id_casino','=','c.id_casino')
        ->whereNull('c.deleted_at'),
        'default_order_by' => [
          'c.año_mes' => 'desc'
        ],
      ],
    ];
  }
  
  private function vista_totales(bool $total_mensual,bool $por_moneda){
    $fechas = DB::raw('(
      SELECT distinct fecha,id_casino,id_tipo_moneda FROM beneficio
      UNION
      SELECT distinct fecha,id_casino,id_moneda as id_tipo_moneda FROM importacion_diaria_mesas
      UNION
      SELECT distinct fecha,id_casino,1 as id_tipo_moneda FROM bingo_importacion
    ) as fechas');
    
    $query = DB::table($fechas)
    ->join('casino as c','c.id_casino','=','fechas.id_casino')
    ->join('tipo_moneda as tm','tm.id_tipo_moneda','=','fechas.id_tipo_moneda')
    ->leftJoin('cotizacion as cot','cot.fecha','=','fechas.fecha')
    ->leftJoin('beneficio as b',function($q){
      return $q->on('fechas.fecha','=','b.fecha')->on('fechas.id_casino','=','b.id_casino')
      ->on('fechas.id_tipo_moneda','=','b.id_tipo_moneda');
    })
    ->leftJoin('importacion_diaria_mesas as idm',function($q){
      return $q->on('fechas.fecha','=','idm.fecha')->on('fechas.id_casino','=','idm.id_casino')
      ->on('fechas.id_tipo_moneda','=','idm.id_moneda')//@HACK: no usan la misma tabla para moneda...
      ->whereNull('idm.deleted_at');
    })
    ->leftJoin(DB::raw('(
      SELECT bi2.fecha,bi2.id_casino,(SUM(bi2.recaudado)-SUM(bi2.premio_linea)-SUM(bi2.premio_bingo)) as beneficio
      FROM bingo_importacion as bi2
      GROUP BY bi2.fecha,bi2.id_casino
    ) as bi'),function($q){
      return $q->on('fechas.fecha','=','bi.fecha')->on('fechas.id_casino','=','bi.id_casino')
      ->where('fechas.id_tipo_moneda','=',1);//Bingo solo tiene pesos... que tenga nulo si es en dolares
    });
    
    $count = DB::table($fechas)
    ->groupBy(DB::raw("'constant'"));
    
    $indirect_where = [
      'casino' => 'fechas.id_casino',
    ];
    
    $cols = [
      ['c.nombre','casino','string','select',[0],$this->selectCasinoVals('producido')]
    ];
    $default_order_by = [];
    
    $beneficios = ['b.valor','idm.utilidad','bi.beneficio'];
    $beneficios_cotizados = array_map(
      function($s) { return "IF(tm.id_tipo_moneda = 1,1.0,cot.valor)*($s)"; },
      $beneficios
    );
    
    if($por_moneda){
      $indirect_where['moneda'] = 'fechas.id_tipo_moneda';
      $cols[] = ['tm.descripcion','moneda','string','select',[0],$this->selectTipoMonedaVals('producido')];
      
      if($total_mensual){
        $año_mes = "CONCAT(LPAD(YEAR(fechas.fecha),4,'0'),'-',LPAD(MONTH(fechas.fecha),2,'0'))";
        array_unshift($cols,[$año_mes,'año_mes','string','input_date_month',['','']]);
        
        $query = $query->groupBy(DB::raw("$año_mes,c.id_casino,tm.id_tipo_moneda"));
        $count = $count->selectRaw("COUNT(distinct CONCAT($año_mes,'-',id_casino,'-',id_tipo_moneda)) as count");
        
        $indirect_where['año_mes'] = 'fechas.fecha';
        
        $default_order_by[$año_mes] = 'desc';
        
        $SUM = function($s) { return "SUM($s)"; };
        $beneficios           = array_map($SUM,$beneficios);
        $beneficios_cotizados = array_map($SUM,$beneficios_cotizados);
      }
      else{
        array_unshift($cols,['fechas.fecha','fecha','string','input_date',['','']]);
        
        $count = $count->selectRaw('COUNT(distinct CONCAT(fechas.fecha,"-",id_casino,"-",id_tipo_moneda)) as count');
        
        $default_order_by['fechas.fecha'] = 'desc';
      }
      
      $cols = array_merge($cols,
        [
          [$beneficios[0],'maq_beneficio','numeric'],
          [$beneficios_cotizados[0],'maq_cotizado','numeric'],
          [$beneficios[1],'mesas_beneficio','numeric'],
          [$beneficios_cotizados[1],'mesas_cotizado','numeric'],
          [$beneficios[2],'bingo_beneficio','numeric'],
          [$beneficios_cotizados[2],'bingo_cotizado','numeric'],
        ]
      );
    }
    else{
      if($total_mensual){
        $año_mes = "CONCAT(LPAD(YEAR(fechas.fecha),4,'0'),'-',LPAD(MONTH(fechas.fecha),2,'0'))";
        array_unshift($cols,[$año_mes,'año_mes','string','input_date_month',['','']]);
        
        $query = $query->groupBy(DB::raw("$año_mes,c.id_casino"));
        $count = $count->selectRaw("COUNT(distinct CONCAT($año_mes,'-',id_casino,'-')) as count");
        
        $indirect_where['año_mes'] = 'fechas.fecha';
        
        $default_order_by[$año_mes] = 'desc';
        
        $SUM = function($s) { return "SUM($s)"; };
        $beneficios           = array_map($SUM,$beneficios);
        $beneficios_cotizados = array_map($SUM,$beneficios_cotizados);
      }
      else{
        array_unshift($cols,['fechas.fecha','fecha','string','input_date',['','']]);
        
        $query = $query->groupBy(DB::raw("fechas.fecha,c.id_casino"));
        $count = $count->selectRaw('COUNT(distinct CONCAT(fechas.fecha,"-",id_casino)) as count');
        
        $default_order_by['fechas.fecha'] = 'desc';
        
        $SUM = function($s) { return "SUM($s)"; };
        $beneficios           = array_map($SUM,$beneficios);
        $beneficios_cotizados = array_map($SUM,$beneficios_cotizados);
      }
      
      $cols = array_merge($cols,
        [
          [$beneficios_cotizados[0],'maq_cotizado','numeric'],
          [$beneficios_cotizados[1],'mesas_cotizado','numeric'],
          [$beneficios_cotizados[2],'bingo_cotizado','numeric'],
        ]
      );
    }
    
    //Si son todos nulos, sigo queriendo que reporte 0, por eso IFNULL
    $cols[] = [implode('+',array_map(function($s){ return "IFNULL($s,0)";},$beneficios_cotizados)),'total','numeric'];
        
    return compact('cols','indirect_where','query','count','default_order_by');
  }
  
  public function index(Request $request){
    $vistas = collect($this->vistas)->map(function($v,$k){
      $columnas = collect($v['cols'])->map(function($c) use($v,$k){
        return collect([
          'nombre' => $c[BO_ALIAS],
          'nombre_fmt' => strtoupper(implode(' ',explode('_',$c[BO_ALIAS]))),
          'tipo' => $c[BO_TIPO] ?? null,
          'default'  => $c[BO_DEFAULTS] ?? [''],
          'valores'  => $c[BO_VALUES] ?? []
        ]);
      });
      
      return collect([
        'nombre' => $k,
        'nombre_fmt' => strtoupper(implode(' ',explode('_',$k))),
        'columnas' => $columnas,
      ]);
    });
            
    return view('seccionBackoffice',compact('vistas'));
  }
  
  public function buscar(Request $request,$para_descargar = false){
    if(!array_key_exists($request->vista,$this->vistas)) return [];
    
    $data = collect($request->all())->map(function($v,$k) use ($request){
      return $this->postprocess_param($request->vista,$k,$v);
    });
        
    $v = $this->vistas[$request->vista];
    $cols = collect($v['cols']);
    
    $QS = [
      clone $v['query'],
      array_key_exists('count',$v)?
         clone $v['count']
      : (clone $v['query'])->selectRaw('COUNT(*) as count')
    ];
  
    foreach($cols as $c){
      $alias = $c[BO_ALIAS];
      if(!isset($data[$alias])) continue;
      $recibido = $data[$alias];
      
      $select = isset($v['indirect_where']) && isset($v['indirect_where'][$alias])?
        $v['indirect_where'][$alias] 
        : $c[BO_SELECT];
      
      $tipo = $c[BO_TIPO] ?? null;
        
      if(is_array($recibido) && $tipo == 'input_vals_list' && !empty($recibido)){
        foreach($QS as &$q)
          $q = $q->whereIn(DB::raw($select),$recibido);
      }
      else if($tipo == 'input_date_month' && !empty($recibido)){
        if(is_array($recibido) && count($recibido) >= 2){
          $d = explode('-',$recibido[0] ?? '1970-01-01');
          $h = explode('-',$recibido[1] ?? date('Y-m-d'));
          foreach($QS as &$q){
            $q = $q->where(function($q) use ($select,$d){
              return $q->whereYear(DB::raw($select),'>',$d[0])
              ->orWhere(function($q) use ($select,$d){
                return $q->whereYear(DB::raw($select),'=',$d[0])
                ->whereMonth(DB::raw($select),'>=',$d[1]);
              });
            })->where(function($q) use ($select,$h){
              return $q->whereYear(DB::raw($select),'<',$h[0])
              ->orWhere(function($q) use ($select,$h){
                return $q->whereYear(DB::raw($select),'=',$h[0])
                ->whereMonth(DB::raw($select),'<=',$h[1]);
              });
            });
          }
        }
        else {
          $m = (is_array($recibido) && count($recibido) == 1)?
            $recibido[0]
          : $recibido;
          $m = array_map(function($s){return intval($s);},explode('-',$m));
          foreach($QS as &$q)
            $q = $q->whereYear(DB::raw($select),'=',$m[0])
                   ->whereMonth(DB::raw($select),'=',$m[1]);
        }
      }
      else if(is_array($recibido) && count($recibido) >= 2){
        if(!is_null($recibido[0])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'>=',$recibido[0]);
        }
        if(!is_null($recibido[1])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'<=',$recibido[1]);
        }
      }
      else if(is_array($recibido) && count($recibido) == 1){
        if(!is_null($recibido[0])){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'=',$recibido[0]);
        }
      }
      else if(!is_array($recibido)){
        if(!is_null($recibido)){
          foreach($QS as &$q)
            $q = $q->where(DB::raw($select),'=',$recibido);
        }
      }
    }
    
    $sort_by = [
      'columna' => array_keys($v['default_order_by'])[0],
      'orden'   => array_values($v['default_order_by'])[0],
    ];
    
    if(!empty($request->sort_by) 
    && !empty($request->sort_by['columna']) 
    && !empty($request->sort_by['orden']) 
    && $cols->where(BO_ALIAS,$request->sort_by['columna'])->count() > 0){
      $col = $cols->where(BO_ALIAS,$request->sort_by['columna'])->first()[0];
      $sort_by['columna'] = DB::raw($col);
      $sort_by['orden'] = $request->sort_by['orden'];
    }
    
    $query = $QS[0];
    $count = $QS[1];
        
    $query = $query->orderBy(DB::raw($sort_by['columna']),$sort_by['orden']);
    
    $page_size = is_numeric($request->page_size)? intval($request->page_size) : 10;
    $page      = is_numeric($request->page)? intval($request->page) : 1;
    $OFFSET    = ($page-1)*$page_size;
    
    DB::statement('SET @@group_concat_max_len = 4294967295');//MAXUINT
    $select = $cols->map(function($c){
      return "{$c[BO_SELECT]} as `{$c[BO_ALIAS]}`";
    })->implode(', ');
    $data = $query->selectRaw(($v['precols'] ?? '').' '.$select);
    
    if($para_descargar === false || $para_descargar == 'PAGINA'){
      $data = $data->skip($OFFSET)->take($page_size);
    }
    
    $data = $data->get()->map(function($r,$rk) use ($request){
      return collect($r)->map(function($cv,$ck) use ($request){
        return $this->postprocess($request->vista,$ck,$cv);
      });
    });
    
    if($para_descargar !== false){
      return collect([$cols->pluck(BO_ALIAS)->toArray()])->merge(
        $data->map(function($r){
          return $r->values();
        })
      );
    }
    
    $count = $count->first();
    $count = is_null($count)? 0 : $count->count;
    
    return [
      'current_page' => $page,
      'per_page'     => $page_size,
      'from'         => $OFFSET+1,
      'to'           => $page*$page_size,
      'data'         => $data,
      'total'        => $count,
      'last_page'    => ceil($count/$page_size)
    ];
  }
  
  private function postprocess($vista,$col,$val){
    $col = collect($this->vistas[$vista]['cols'])->where(BO_ALIAS,$col)->first();
    $tipo = $col[BO_TIPO] ?? null;
    if(!is_null($col) && $tipo == 'input_vals_list'){
      $vals  = explode(',',$val);
      $lista = self::colapsarListaDeNumerosAscendentes($vals);
      $count = count($vals);
      $val   = "[$count] $lista";
    }
    return self::val_format($col[BO_FMT] ?? null,$val);
  }
  
  private function postprocess_param($vista,$col,$val){
    $col = collect($this->vistas[$vista]['cols'])->where(BO_ALIAS,$col)->first();
    $tipo = $col[BO_TIPO] ?? null;
    if(!is_null($col) && $tipo == 'input_vals_list'){
      return self::expandirListaDeRangosSeparadaPorComas($val[0]);
    }
    return $val;
  }
  
  public function descargar(Request $request){
    $data = $this->buscar($request,$request->completo == '1'? 'COMPLETO' : 'PAGINA')->toArray();
    
    $f = fopen('php://memory', 'r+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    foreach ($data as $item) {
      fputcsv($f, $item,',','"',"\\");
    }
    rewind($f);
        
    return stream_get_contents($f);
  }
  
  private static function colapsarListaDeNumerosAscendentes(array $lista = null){
    $lista  = $lista ?? [];//null guard
    $rangos = [];
    $r      = [];
    $r_to_str = function($r){ return ($r[0] == $r[1])? $r[0] : "{$r[0]}-{$r[1]}"; };
    
    while(true){
      $val = array_shift($lista);
      if(is_null($val)) break;
      
      if(count($r) >= 2){
        if($r[1] == $val || ($r[1]+1) == $val){
          $r[1] = $val;
          continue;
        }
        $rangos[] = $r_to_str($r);
      }
           
      $r = [$val,$val];
    }
    
    if(count($r) >= 2){
      $rangos[] = $r_to_str($r);
    }
    
    return implode(', ',$rangos);
  }
  
  private static function expandirListaDeRangosSeparadaPorComas($lista_comas){
    if($lista_comas == '') return [];
    $lista_con_rangos = explode(',',$lista_comas);
    $lista_final = [];
    foreach($lista_con_rangos as $v){
      $v = trim($v);
      if(ctype_digit($v)){
        $lista_final[] = intval($v);
        continue;
      }
      $rango = explode('-',$v);
      if(count($rango) != 2) return false;
      
      $v1 = trim($rango[0]);$v2 = trim($rango[1]);
      if(!ctype_digit($v1) || !ctype_digit($v2)) return false;
      $v1 = intval($v1);$v2 = intval($v2);
      $min = min($v1,$v2);
      $max = max($v1,$v2);
      for($i = $min;$i <= $max;$i++){
        $lista_final[] = $i;
      }
    }
    return $lista_final;
  }
  
  private static function val_format($tipo,$val){
    if($val === null || $val === '')
      return '';
    switch($tipo){
      case 'integer':
        return intval($val);
      case 'numeric':
        return formatear_decimal($val);
      case 'numeric3d':
        return formatear_decimal($val);
    }
    return $val;
  }
}
