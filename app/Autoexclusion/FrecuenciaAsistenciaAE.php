<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class FrecuenciaAsistenciaAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_frecuencia_asistencia';
  protected $primaryKey = 'id_frecuencia';
  protected $visible = array('id_frecuencia','nombre');
  public $timestamps = false;
  
  public static function boot(){
    parent::boot();
    self::observe(new \App\Observers\FullObserver());
  }
  
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->{$this->primaryKey};
  }

  public function encuestas(){
    return $this->hasMany('App\Autoexclusion\Encuesta','id_frecuencia_asistencia','id_frecuencia');
  }
}
