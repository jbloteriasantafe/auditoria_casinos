<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EstadoMaquina extends Model
{
    protected $connection = 'mysql';
    protected $table = 'estado_maquina';
    protected $primaryKey = 'id_estado_maquina';
    protected $visible = array('id_estado_maquina','descripcion');
    public $timestamps = false;

    public function maquinas(){
      return $this->HasMany('App\Maquina','id_estado_maquina','id_estado_maquina');
    }
    public function log_maquinas(){
      return $this->HasMany('App\LogMaquina','id_estado_maquina','id_estado_maquina');
    }
}
