<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class EstadoCivilAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_estado_civil';
  protected $primaryKey = 'id_estado_civil';
  protected $visible = array('id_estado_civil','descripcion','codigo');
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

  public function autoexcluidos(){
    return $this->hasMany('App\Autoexclusion\Autoexcluido','id_estado_civil','id_estado_civil');
  }
}
