<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApuestaMinimaJuego extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'apuesta_minima_juego';
  protected $primaryKey = 'id_apuesta_minima';
  protected $visible = array('id_apuesta_minima','descripcion','id_juego_mesa',
                              'cantidad_requerida','apuesta_minima'
                              );
  public $timestamps = false;

  public function juego(){
    return $this->belongsTo('App\Mesas\JuegoMesa','id_juego_mesa','id_juego_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_apuesta_minima;
  }
}
