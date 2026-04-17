<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class NombreEstadoAutoexclusion extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_nombre_estado';
  protected $primaryKey = 'id_nombre_estado';
  protected $visible = array('id_nombre_estado','descripcion','deprecado');
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

  public function estados(){
    return $this->hasMany('App\Autoexclusion\EstadoAE','id_nombre_estado','id_nombre_estado');
  }
}
