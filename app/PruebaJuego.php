<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PruebaJuego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'prueba_juego';
  protected $primaryKey = 'id_prueba_juego';
  protected $visible = array('id_prueba_juego','fecha','id_maquina','observacion','id_archivo');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsToMany('App\Maquina','id_maquina','id_maquina');
  }

  public function archivo(){
      return $this->belongsTo('App\Archivo','id_archivo','id_archivo');
  }
}
