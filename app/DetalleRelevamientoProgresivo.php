<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleRelevamientoProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento_progresivo';
  protected $primaryKey = 'id_detalle_relevamiento_progresivo';
  protected $visible = array('id_detalle_relevamiento_progresivo', 'id_relevamiento_progresivo','id_progresivo','id_pozo', 'valor_actual');
  public $timestamps = false;

  public function relevamiento_progresivo(){
    return $this->belongsTo('App\RelevamientoProgresivo','id_relevamiento_progresivo','id_relevamiento_progresivo');
  }

  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

}
