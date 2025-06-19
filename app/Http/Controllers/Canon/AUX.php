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
}
