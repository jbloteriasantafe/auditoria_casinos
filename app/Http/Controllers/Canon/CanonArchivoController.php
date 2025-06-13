<?php

namespace App\Http\Controllers\Canon;

use App\Http\Controllers\Canon\CanonController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Archivo;

class CanonArchivoController extends Controller
{
  public $table = 'canon_archivo';
  public $id    = 'id_canon_archivo';
  private static $instance;

  public static function getInstancia(){
    self::$instance = self::$instance ?? (new self()); 
    return self::$instance;
  }
    
  public function validar(){
    return [
      'canon_archivo' => 'array',
      'canon_archivo.*.descripcion' => ['nullable','string','max:256'],
      'canon_archivo.*.id_archivo'  => ['nullable','integer','exists:archivo,id_archivo'],
      'canon_archivo.*.file'        => 'file',
    ];
  }
  
  public function recalcular($id_casino,$año_mes,$principal,$R){
    return ['canon_archivo' => $R('canon_archivo',[])];
  }
  
  public function confluir($data){
    return [];
  }
  
  public function obtener($id_canon){
    $ret = [];
    $ret['canon_archivo'] = DB::table('canon_archivo as ca')
    ->select('ca.id_canon','ca.descripcion','a.id_archivo','a.nombre_archivo')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('ca.id_canon',$id_canon)
    ->orderBy('id_archivo','asc')
    ->get()
    ->transform(function(&$adj){
      $adj->link = '/canon/archivo?id_canon='.urlencode($adj->id_canon)
      .'&nombre_archivo='.urlencode($adj->nombre_archivo);
      return $adj;
    });
    return $ret;
  }
    
  public function procesar_para_salida($data){
    $ret = [];
    
    foreach(['id_archivo','id_canon','id_canon_archivo'] as $k){
      foreach(($data['canon_archivo'] ?? []) as $tipo => $_){
        unset($data['canon_archivo'][$tipo][$k]);
      }
    }
    
    $ret['canon_archivo'] = $data['canon_archivo'] ?? [];
    
    return $ret;
  }
  
  public function guardar($id_canon,$id_canon_anterior,$datos){
    $archivos_existentes = $id_canon_anterior === null? 
      collect([])
    : DB::table('canon_archivo as ca')
    ->select('ca.descripcion','ca.type','a.*')
    ->join('archivo as a','a.id_archivo','=','ca.id_archivo')
    ->where('id_canon',$id_canon_anterior)
    ->get()
    ->keyBy('id_archivo');
    
    $archivos_enviados = collect($datos['canon_archivo'] ?? [])->groupBy('id_archivo');
    
    $archivos_resultantes = [];
    foreach($archivos_enviados as $id_archivo_e => $archivos_e){
      if($id_archivo_e !== ''){//Es "existente"
        //Se recibio un id archivo que no estaba antes
        if(!$archivos_existentes->has($id_archivo_e)) continue;
        
        $archivo_bd = $archivos_existentes[$id_archivo_e];
        
        $archivo = null;//Por si me mando varios con el mismo id_archivo, busco el que tenga mismo nombre de archivo
        foreach($archivos_e as $ae){
          if($ae['nombre_archivo'] == $archivo_bd->nombre_archivo){
            $archivo = $ae;
            break;
          }
        }
        
        if($archivo === null) continue;//No encontre, lo ignoro
                    
        //El archivo se repite para el nuevo canon pero posiblemente con otra descripcion
        $archivos_resultantes[] = [
          'id_archivo'  => $archivo_bd->id_archivo,
          'id_canon'    => $id_canon,
          'descripcion' => ($archivo['descripcion'] ?? ''),
          'type'        => $archivo_bd->type,
        ];
      }
      else{//Archivos nuevos
        foreach($archivos_e as $a){
          $file=$a['file'] ?? null;
          if($file === null) continue;
          
          $archivo_bd = new Archivo;
          $data = base64_encode(file_get_contents($file->getRealPath()));
          $nombre_archivo = $file->getClientOriginalName();
          $archivo_bd->nombre_archivo = $nombre_archivo;
          $archivo_bd->archivo = $data;
          $archivo_bd->save();
          
          $archivos_resultantes[] = [
            'id_archivo' => $archivo_bd->id_archivo,
            'id_canon' => $id_canon,
            'descripcion' => ($a['descripcion'] ?? ''),
            'type' => $file->getMimeType() ?? 'application/octet-stream'
          ];
        } 
      }
    }
    
    DB::table('canon_archivo')
    ->insert($archivos_resultantes);
  }
}
