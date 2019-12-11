<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CantidadPersonas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'cantidad_personas';
  protected $primaryKey = 'id_cantidad_personas';
  protected $visible = array(
    'id_cantidad_personas',
    'cantidad_personas',
    'id_isla',
    'id_mesa',
    'id_detalle_relevamiento_ambiental');
  public $timestamps = false;

  public function detalle_relevamiento_ambiental(){
    return $this->belongsTo('App\DetalleRelevamientoAmbiental','id_detalle_relevamiento_ambiental','id_detalle_relevamiento_ambiental');
  }

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesa','id_mesa','id_mesa_de_panio');
  }
}
