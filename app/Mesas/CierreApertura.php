<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CierreApertura extends Model
{
  protected $connection = 'mysql';
  protected $table = 'cierre_apertura';
  protected $primaryKey = 'id_cierre_apertura';
  protected $visible = array('id_cierre_apertura','id_cierre_mesa','id_apertura_mesa',
                             'id_estado_cierre','id_mesa_de_panio','id_juego_mesa',
                             'id_controlador'
                            );
  public $timestamps = false;


  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function controlador(){
    return $this->belongsTo('App\User','id_controlador','id');
  }

  public function estado_cierre(){
    return $this->belongsTo('App\Mesas\EstadoCierre','id_estado_cierre','id_estado_cierre');
  }

  public function apertura(){
    return $this->belongsTo('App\Mesas\Apertura','id_apertura_mesa','id_apertura_mesa');
  }

  public function cierre(){
     return $this->belongsTo('App\Mesas\Cierre','id_cierre_mesa','id_cierre_mesa');
  }

  public function juego(){
    return $this->belongsTo('App\Mesas\JuegoMesa','id_juego_mesa','id_juego_mesa');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_cierre_apertura;
  }
}
