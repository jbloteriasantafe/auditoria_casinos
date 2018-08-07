<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoRelevamiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_relevamiento';
  protected $primaryKey = 'id_estado_relevamiento';
  protected $visible = array('id_estado_relevamiento','descripcion');
  public $timestamps = false;

  public function relevamientos(){
    return $this->HasMany('App\Relevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function relevamientos_movimientos(){
    return $this->HasMany('App\RelevamientoMovimiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function relevamientos_progresivos(){
    return $this->hasMany('App\RelevamientoProgresivo','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function logs_movimientos(){
    return $this->hasMany('App\LogMovimiento', 'id_estado_relevamiento', 'id_estado_relevamiento');
  }

  public function logs_islas(){
    return $this->hasMany('App\LogIsla', 'id_estado_relevamiento', 'id_estado_relevamiento');
  }

}
