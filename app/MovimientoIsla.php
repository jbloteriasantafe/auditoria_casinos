<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MovimientoIsla extends Model
{
  protected $connection = 'mysql';
  protected $table = 'movimiento_isla';
  protected $primaryKey = 'id_movimiento_isla';
  protected $visible = array('id_movimiento_isla','id_isla','id_maquina','fecha');
  public $timestamps = false;

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_movimiento_isla;
  }

}
