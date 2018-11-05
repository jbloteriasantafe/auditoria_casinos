<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class EstadoCierre extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_cierre';
  protected $primaryKey = 'id_estado_cierre';
  protected $visible = array('id_estado_cierre','descripcion');
  public $timestamps = false;

  public function cierres(){
    return $this->HasMany('App\Mesas\Cierres','id_estado_cierre','id_estado_cierre');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_estado_cierre;
  }
}
