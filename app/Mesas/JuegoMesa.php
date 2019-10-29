<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JuegoMesa extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'juego_mesa';
  protected $primaryKey = 'id_juego_mesa';
  protected $visible = array('id_juego_mesa','id_tipo_mesa','nombre_juego',
                             'siglas','id_casino','posiciones');
  protected $fillable = ['id_tipo_mesa','nombre_juego',
                         'siglas','id_casino','posiciones'];

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_juego_mesa','id_juego_mesa');
  }
  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_juego_mesa;
  }
}
