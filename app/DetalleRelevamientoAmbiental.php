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
    'id_sector',
    'id_turno',
    'total');
  public $timestamps = false;

  public function relevamiento_ambiental(){
    return $this->belongsTo('App\RelevamientoAmbiental','id_relevamiento_ambiental','id_relevamiento_ambiental');
  }

  public function sector(){
    return $this->belongsTo('App\Sector','id_sector','id_sector');
  }

  public function turno(){
    return $this->belongsTo('App\Turno','id_turno','id_turno');
  }
}
