<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoMovimiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_movimiento';
  protected $primaryKey = 'id_tipo_movimiento';
  protected $visible = array('id_tipo_movimiento','descripcion');
  public $timestamps = false;

  public function notas(){
    return $this->hasMany('App\Nota','id_tipo_movimiento','id_tipo_movimiento');
  }
  public function expedientes(){
    return $this->hasMany('App\Expediente', 'id_tipo_movimiento', 'id_tipo_movimiento');
  }

  public function log_movimientos(){
    return $this->hasMany('App\LogMovimiento', 'id_tipo_movimiento', 'id_tipo_movimiento');
  }

  public function log_maquinas(){
    return $this->hasMany('App\LogMaquina','id_tipo_movimiento','id_tipo_movimiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tipo_movimiento;
  }
}
