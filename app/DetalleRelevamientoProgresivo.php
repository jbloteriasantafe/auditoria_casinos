<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleRelevamientoProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento_progresivo';
  protected $primaryKey = 'id_detalle_relevamiento_progresivo';
  protected $visible = array('id_detalle_relevamiento_progresivo', 'id_relevamiento_progresivo','id_pozo', 'nivel1', 'nivel2', 'nivel3', 'nivel4','nivel5','nivel6');
  public $timestamps = false;

  public function relevamiento_progresivo(){
    return $this->belongsTo('App\RelevamientoProgresivo','id_relevamiento_progresivo','id_relevamiento_progresivo');
  }

  public function pozo(){
    return $this->belongsTo('App\Pozo','id_pozo','id_pozo');
  }
}
