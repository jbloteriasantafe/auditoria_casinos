<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PruebaProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'prueba_progresivo';
  protected $primaryKey = 'id_prueba_progresivo';
  protected $visible = array('id_prueba_progresivo','fecha','id_progresivo','id_maquina');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsToMany('App\Maquina','id_maquina','id_maquina');
  }
  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

}
