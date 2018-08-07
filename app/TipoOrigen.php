<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoOrigen extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_origen';
  protected $primaryKey = 'id_tipo_origen';
  protected $visible = array('id_tipo_origen','descripcion');
  public $timestamps = false;

  public function movimientos(){
    return $this->HasMany('App\Movimiento','id_tipo_origen','id_tipo_origen');
  }

}
