<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMesa extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'tipo_mesa';
  protected $primaryKey = 'id_tipo_mesa';
  protected $visible = array('id_tipo_mesa','descripcion');

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_juego_mesa','id_juego_mesa');
  }
  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_tipo_mesa;
  }
}
