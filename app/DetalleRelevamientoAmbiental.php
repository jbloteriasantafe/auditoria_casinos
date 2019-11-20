<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleRelevamientoAmbiental extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento_ambiental';
  protected $primaryKey = 'id_detalle_relevamiento_ambiental';
  protected $visible = array(
    'id_detalle_relevamiento_ambiental',
    'id_relevamiento_ambiental',
    'id_isla',
    'id_mesa_de_panio',
    'nro_islote',
    'turno1',
    'turno2',
    'turno3',
    'turno4',
    'turno5',
    'turno6',
    'turno7',
    'turno8'
    );
  public $timestamps = false;

  public function relevamiento_ambiental(){
    return $this->belongsTo('App\RelevamientoAmbiental','id_relevamiento_ambiental','id_relevamiento_ambiental');
  }

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

}
