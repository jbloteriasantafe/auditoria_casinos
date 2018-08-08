<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Porcentaje extends Model
{
  protected $connection = 'mysql';
  protected $table = 'porcentaje';
  protected $primaryKey = 'id_porcentaje';
  protected $visible = array('id_porcentaje','id_casino','id_actividad','valor');
  public $timestamps = false;

  public function actividad(){
    return $this->belongsTo('App\Actividad','id_actividad','id_actividad');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
}
