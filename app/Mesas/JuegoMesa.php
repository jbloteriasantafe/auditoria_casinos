<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class JuegoMesa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'juego_mesa';
    protected $primaryKey = 'id_juego_mesa';
    protected $visible = array('id_juego_mesa','id_tipo_mesa','nombre_juego');
    public $timestamps = false;

    public function mesas(){
      return $this->hasMany('App\Mesas\MesaDePanio', 'id_juego_mesa', 'id_juego_mesa');
    }

    public function tipo_mesa(){
      return $this->belongsTo('App\Mesas\TipoMesa', 'id_tipo_mesa', 'id_tipo_mesa');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_juego_mesa;
    }

}
