<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\EventualidadObserver;

class TipoEventualidad extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_eventualidad';
  protected $primaryKey = 'id_tipo_eventualidad';
  protected $visible = array('id_tipo_eventualidad','descripcion');
  public $timestamps = false;

  public function eventualidades(){
    return $this->hasMany('App\Eventualidad','id_tipo_eventualidad','id_tipo_eventualidad');
  }

  public static function boot(){
        parent::boot();

  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tipo_eventualidad;
  }

}
