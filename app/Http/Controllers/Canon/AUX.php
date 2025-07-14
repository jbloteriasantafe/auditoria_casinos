<?php
namespace App\Http\Controllers\Canon;
use Illuminate\Support\Facades\DB;

class AUX {
  public static function formatear_datos($datos){
    $SB = DB::getSchemaBuilder();
    $types = [];
    foreach($datos as $tabla => $d){
      foreach($SB->getColumnListing($tabla) as $cidx => $col){
        $types[$tabla][$col] = $SB->getColumnType($tabla, $col);
      }
    }
        
    foreach($datos as $tabla => $d){
      foreach($d as $rowidx => $row){
        foreach($row as $col => $val){
          switch($types[$tabla][$col] ?? null){
            case 'smallint':
            case 'integer':
            case 'decimal': {
              $datos[$tabla][$rowidx][$col] = self::formatear_decimal((string)$val);//number_format castea a float... lo hacemos a pata...
            }break;
            default:
            case 'string':{
              $datos[$tabla][$rowidx][$col] = trim($val);
            }break;
          }
        }
      }
    }
    
    return $datos;
  }
  
  public static function formatear_decimal(string $val) : string {//number_format castea a float... lo hacemos a pata...
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
  
  public static function numeric_rule(int $digits){
    static $cache = [];
    if($cache[$digits] ?? false) return $cache[$digits];
    $regex = '-?\d+';
    if($digits){
      $digits_regexp = implode('',array_fill(0,$digits,'\d?'));
      $regex .= '\.?'.$digits_regexp;
    }
    $cache[$digits] = 'regex:/^'.$regex.'$/';
    return $cache[$digits];
  }
  
  public static function confluir_datos(array $canon,array $tablas,array $atributos){
    $ret = [];
    foreach($tablas as $tabla){
      foreach($atributos as $attr){
        foreach($canon[$tabla] as $tipo => $data_tabla){
          $data_tabla = (array) $data_tabla;
          if(!isset($data_tabla[$attr])) continue;
          $val = $data_tabla[$attr];
          if(isset($ret[$attr])){//Si es distinto, hay conflicto y pongo en nulo
            $ret[$attr] = $val != $ret[$attr]? null : $val;
          }
          else{
            $ret[$attr] = $val;
          }
        }
      }
    }
    return $ret;
  }
  
  public static function csvstr(array $header,array $filas,string $filename='php://memory') : string{
    $file = fopen($filename, 'a+');//https://stackoverflow.com/questions/13108157/php-array-to-csv
    fputcsv($file, array_values($header));
    foreach ($filas as $f) {
      fputcsv($file, array_values($f));
    }
    rewind($file);        
    return stream_get_contents($file);
  }
  
  public static function ranged_sql($begin,$end){
    $ret = "( SELECT $begin as val ";
    for($i=$begin+1;$i<=$end;$i++){
      $ret.= 'UNION ALL SELECT '.$i.' ';
    }
    return $ret.')';
  }
  
  public static function make_accessor($arr){
    return function($k,$dflt = null) use (&$arr){
      return (!isset($arr[$k]) || $arr[$k] === '' || $arr[$k] === null || $arr[$k] === [])?
        $dflt
      : $arr[$k];
    };
  }
  
  public static function combine_accessors(...$accessors){
    return function($s,$dflt = null) use ($accessors){
      foreach($accessors as $A){
        $ret = $A($s,null);
        if($ret !== null) return $ret;
      }
      return $dflt;
    };
  }
  
  private static $cotizacion_DB = [];
  public static function cotizacion($fecha_cotizacion,$id_tipo_moneda,$id_casino){
    if(empty($fecha_cotizacion) || empty($id_tipo_moneda)) return '0';
    if($id_tipo_moneda == 1){
      return 1;
    }
    
    $cot = (self::$cotizacion_DB[$fecha_cotizacion] ?? [])[$id_tipo_moneda] ?? null;
    
    if($cot === null){      
      $t_fechas_cotizadas = 't'.uniqid();
      $tf = function($sc,$devdet) use ($id_casino) {
        $cas_where = empty($id_casino)? '' : "WHERE c.id_casino <> $id_casino";
        return "(
          SELECT 
            sc.{$devdet}_fecha_cotizacion as fecha,
            sc.{$devdet}_cotizacion_dolar as dolar,
            sc.{$devdet}_cotizacion_euro  as euro
          FROM {$sc} as sc
          JOIN canon as c ON c.id_canon = sc.id_canon AND c.deleted_at IS NULL
          $cas_where
        )";
      };
      //count distinct no cuenta nulos en MySQL por lo menos
      //si hay 1 solo MAX es lo mismo que sacar el valor este
      DB::statement("CREATE TEMPORARY TABLE $t_fechas_cotizadas AS 
      SELECT 
        aux.fecha,
        IF(
          COUNT(distinct aux.dolar) <> 1,
          NULL,
          MAX(aux.dolar)
        ) as dolar,
        IF(
          COUNT(distinct aux.euro) <> 1,
          NULL,
          MAX(aux.euro)
        ) as euro
      FROM
      (
        {$tf('canon_fijo_mesas','devengado')}
        UNION
        {$tf('canon_fijo_mesas','determinado')}
        UNION
        {$tf('canon_fijo_mesas_adicionales','devengado')}
        UNION
        {$tf('canon_fijo_mesas_adicionales','determinado')}
      ) as aux
      GROUP BY aux.fecha
      ORDER BY aux.fecha DESC");
      
      $vals_db = DB::table($t_fechas_cotizadas)
      ->get()
      ->keyBy('fecha');
      
      self::$cotizacion_DB = [];
      foreach($vals_db as $v){
        self::$cotizacion_DB[$v->fecha] = $cotizacion_DB[$v->fecha] ?? [2 => [],3 => []];
        self::$cotizacion_DB[$v->fecha][2] = $v->dolar;
        self::$cotizacion_DB[$v->fecha][3] = $v->euro;
      }
    }
    
    $cot = (self::$cotizacion_DB[$fecha_cotizacion] ?? [])[$id_tipo_moneda] ?? null;
    if($cot === null && $id_tipo_moneda == 2){//Busco en las cotizaciones de los auditores
      $aux = DB::table('cotizacion as cot')
      ->where('fecha',$fecha_cotizacion)
      ->first();
      if($aux !== null){
        self::$cotizacion_DB[$fecha_cotizacion] = self::$cotizacion_DB[$fecha_cotizacion] ?? [];
        self::$cotizacion_DB[$fecha_cotizacion][$id_tipo_moneda] = $aux->valor;
        $cot = $aux->valor;
      }
    }
    
    return $cot ?? '0';
  }
  
  private static $cotizacion_sesion_DB = [];
  public static function get_cotizacion_sesion($fecha_cotizacion,$id_tipo_moneda){
    self::$cotizacion_sesion_DB[$fecha_cotizacion] = self::$cotizacion_sesion_DB[$fecha_cotizacion] ?? [];
    self::$cotizacion_sesion_DB[$fecha_cotizacion][$id_tipo_moneda] = self::$cotizacion_sesion_DB[$fecha_cotizacion][$id_tipo_moneda] ?? null;
    return self::$cotizacion_sesion_DB[$fecha_cotizacion][$id_tipo_moneda];
  }
  public static function set_cotizacion_sesion($fecha_cotizacion,$id_tipo_moneda,$val){
    self::$cotizacion_sesion_DB[$fecha_cotizacion] = self::$cotizacion_sesion_DB[$fecha_cotizacion] ?? [];
    self::$cotizacion_sesion_DB[$fecha_cotizacion][$id_tipo_moneda] = $val;
    return $val;
  }
}
