<?php

namespace App\Http\Controllers\Mesas;
use Illuminate\Support\Facades\Storage;
use File;

class CarpetasHelper {
  private function CREAR_CARPETA_SI_NO_EXISTE($carpeta){
    if(!File::exists($carpeta)){
      return File::makeDirectory($carpeta);
    }
    
    if(File::isDirectory($carpeta)){
      return true;
    }
    
    File::delete($carpeta);
    return File::makeDirectory($carpeta);
  }
  
  private function build_path($path,$file){
    if($file !== null)
      return "$path/$file";
    return $path;
  }
  
  public function MESAS($file = null){
    return $this->build_path(
      Storage::getAdapter()->applyPathPrefix('Mesas'),
      $file
    );
  }
  
  public function APUESTAS($file = null){
    return $this->build_path(
      $this->MESAS('RelevamientosApuestas'),
      $file
    );
  }
  
  public function APERTURAS($file = null){
    return $this->build_path(
      $this->MESAS('RelevamientosAperturas'),
      $file
    );
  }
  
  public function __construct() {
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->MESAS());
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->APUESTAS());
    $this->CREAR_CARPETA_SI_NO_EXISTE($this->APERTURAS());
  }
}
