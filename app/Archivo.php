<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArchivoObserver extends Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('nombre_archivo');
  }
}

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
  
  public function tomaRelevamientoMovimiento(){
    return $this->hasOne('App\TomaRelevamientoMovimiento','id_archivo','id_archivo');
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

}
