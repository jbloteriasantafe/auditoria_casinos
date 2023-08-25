<?php

namespace App\Http\Controllers\Mesas;
use Illuminate\Support\Facades\Storage;
use File;

class CarpetasHelper {
  private function CREAR_CARPETA_SI_NO_EXISTE($carpeta){
    $existe = File::exists($carpeta);
    $es_car = File::isDirectory($carpeta);
    if($existe &&  $es_car) return;
    if($existe && !$es_car){
      File::delete($carpeta);
      File::makeDirectory($carpeta);
      return;
    }
    if(!$existe){
      File::makeDirectory($carpeta);
      return;
    }
    return;
  }
  
  static private $carpMesas = 'Mesas';
  public function MESAS($file = null){
    $path = Storage::getAdapter()->applyPathPrefix(self::$carpMesas);
    if($file !== null)
      return "$path/$file";
    return $path;
  }
  
  static private $carpApuestas = 'RelevamientosApuestas';
  public function APUESTAS($file = null){
    $path = $this->MESAS(self::$carpApuestas);
    if($file !== null)
      return "$path/$file";
    return $path;
  }
  
  static private $carpAperturas = 'RelevamientosAperturas';
  public function APERTURAS($file = null){
    $path = $this->MESAS(self::$carpAperturas);
    if($file !== null)
      return "$path/$file";
    return $path;
  }
  
  public function __construct() {
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->MESAS());
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->APUESTAS());
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->APERTURAS());
  }
  
  public function borrarArchivoSiExiste($file){
    if(File::exists($file)){
      File::delete($file);
    }
  }
}
