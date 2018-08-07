<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class MesaDePanio extends Model
{
    protected $connection = 'mysql';
    protected $table = 'mesa_de_panio';
    protected $primaryKey = 'id_mesa_de_panio';
    protected $visible = array('id_mesa_de_panio','nro_mesa','nombre','descripcion','id_tipo_mesa','id_juego_mesa','id_casino');
    public $timestamps = false;

    public function casino(){
      return $this->belongsTo('App\Casino', 'id_casino', 'id_casino');
    }

    public function tipo_mesa(){
      return $this->belongsTo('App\Mesas\TipoMesa', 'id_tipo_mesa', 'id_tipo_mesa');
    }

    public function juego_mesa(){
      return $this->belongsTo('App\Mesas\JuegoMesa', 'id_juego_mesa', 'id_juego_mesa');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_mesa_de_panio;
    }

}
