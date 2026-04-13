<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Cache;

class CacheController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new CacheController();
      }
      return self::$instance;
  }
  private function buscar($codigo = null,$subcodigo = null,$antes_de = null,$dependencias = null,$get = true){
    $reglas = [];
    if(!is_null($codigo)) $reglas[] = ['codigo','=',$codigo];
    if(!is_null($subcodigo)) $reglas[] = ['subcodigo','=',$subcodigo];
    if(!is_null($antes_de)) $reglas[] = ['creado','<=',$antes_de];
    if(!is_null($dependencias)) $reglas[] = ['dependencias','LIKE','%|'.implode('|',$dependencias).'|%'];

    $ret = Cache::where($reglas)->orderBy('creado','desc');
    if($get){
      $ret = $ret->get();
    }
    return $ret;
  }
  public function invalidar($codigo = null,$subcodigo = null,$antes_de = null,$dependencias = null){
    $this->buscar($codigo,$subcodigo,$antes_de,$dependencias,false)->delete();
    return $this;
  }
  public function invalidarDependientes($dependencias){
    return $this->invalidar(null,null,null,$dependencias);
  }
  public function agregar($codigo,$subcodigo,$data,$dependencias = []){
    $cache = new Cache;
    $cache->codigo = $codigo;
    $cache->subcodigo = $subcodigo;
    $cache->data = $data;
    $cache->creado = date('Y-m-d H:i:s');
    $cache->dependencias = '|'.implode('|',$dependencias).'|';
    $cache->save();
    return $this;
  }
  public function buscarUltimoDentroDeSegundos($codigo,$subcodigo,$segundos){
    $ahora = date('Y-m-d H:i:s');
    $antes_de = date('Y-m-d H:i:s',strtotime($ahora) - $segundos);
    $this->invalidar($codigo,$subcodigo,$antes_de);
    return $this->buscar($codigo,$subcodigo)->first();
  }
}
