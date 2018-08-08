<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoGabinete extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_gabinete';
  protected $primaryKey = 'id_tipo_gabinete';
  protected $visible = array('id_tipo_gabinete','descripcion');
  public $timestamps = false;

  public function maquinas(){
    return $this->HasMany('App\Maquina','id_tipo_gabinete','id_tipo_gabinete');
  }
}
