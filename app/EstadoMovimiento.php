<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoMovimiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_movimiento';
  protected $primaryKey = 'id_estado_movimiento';
  protected $visible = array('id_estado_movimiento','descripcion');
  public $timestamps = false;

  public function log_movimientos(){
    return $this->HasMany('App\LogMovimiento','id_estado_movimiento','id_estado_movimiento');
  }
  public function eventualidades(){
    return $this->HasMany('App\Eventualidad','id_estado_eventualidad','id_estado_movimiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_estado_movimiento;
  }

}
