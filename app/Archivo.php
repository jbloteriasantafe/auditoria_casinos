<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ArchivoObserver;
use Illuminate\Support\Facades\Storage;

class Archivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'archivo';
  protected $primaryKey = 'id_archivo';
  protected $visible = array('id_archivo','nombre_archivo','archivo');
  public $timestamps = false;

  public function gliSoft(){
    return $this->hasOne('App\GliSoft','id_archivo','id_archivo');
  }

  public function gliHard(){
    return $this->hasOne('App\GliHard','id_archivo','id_archivo');
  }

  public function nota(){
    return $this->hasOne('App\Nota','id_archivo','id_archivo');
  }

  public function eventualidad(){
    return $this->hasOne('App\Eventualidad','id_archivo','id_archivo');
  }

  public static function boot(){
        parent::boot();
        Archivo::observe(new ArchivoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_archivo;
  }

  public function setearDatosArchivo($file){
    $this->archivo = base64_encode(file_get_contents($file->getRealPath()));
    $this->nombre_archivo= date("Y-m-d_H-i-s_") . $file->getClientOriginalName();
    return;
  }

  public function save(array $options = Array()){
    $data = $this->archivo;
    $this->archivo = null;
    parent::save($options);
    Storage::put($this->nombre_archivo,base64_decode($data));
    $this->archivo = $data;
  }

  public function getArchivoAttribute($archivo){
    //Si es nulo y existe un archivo con el mismo nombre, lo retornamos
    if(is_null($archivo) &&
      !is_null($this->nombre_archivo) &&
      Storage::exists($this->nombre_archivo)){
        $contenido = Storage::get($this->nombre_archivo);
        return base64_encode($contenido);
    }
    return $archivo;
  }

  public function setArchivoAttribute($archivo){
    $this->attributes['archivo'] = $archivo;
  }

  public function cargarArchivoGuardado(){
    //Si es nulo me fijo si el archivo existe, y lo cargo
    if(is_null($this->archivo) &&
      !is_null($this->nombre_archivo) &&
      Storage::exists($this->nombre_archivo)){
        $contenido = Storage::get($this->nombre_archivo);
        $this->archivo = base64_encode($contenido);
    }
  }

}
