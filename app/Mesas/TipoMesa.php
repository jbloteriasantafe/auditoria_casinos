<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class TipoMesa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_mesa';
    protected $primaryKey = 'id_tipo_mesa';
    protected $visible = array('id_tipo_mesa','descripcion');
    public $timestamps = false;

    public function mesas(){
      return $this->hasMany('App\Mesas\MesaDePanio', 'id_tipo_mesa', 'id_tipo_mesa');
    }

    public function juegos(){
      return $this->hasMany('App\Mesas\JuegoMesa', 'id_tipo_mesa', 'id_tipo_mesa');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_tipo_mesa;
    }

}
