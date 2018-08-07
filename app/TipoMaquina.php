<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoMaquina extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_maquina';
  protected $primaryKey = 'id_tipo_maquina';
  protected $visible = array('id_tipo_maquina','descripcion');
  public $timestamps = false;

  public function maquinas(){
    return $this->HasMany('App\Maquina','id_tipo_maquina','id_tipo_maquina');
  }


}
