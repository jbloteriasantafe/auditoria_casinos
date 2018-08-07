<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class CierreApertura extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cierre_apertura';
    protected $primaryKey = 'id_cierre_apertura';
    protected $visible = array('id_cierre_apertura','id_cierre_mesa','id_apertura_mesa',
                              'id_estado_relevamiento','id_mesa_de_panio','id_juego_mesa',
                              'id_controlador');
    public $timestamps = false;

    public function controlador(){
      return $this->belongsTo('App\Usuario', 'id_controlador', 'id_usuario');
    }

    public function mesa(){
      return $this->belongsTo('App\Mesas\MesaDePanio', 'id_mesa_de_panio', 'id_mesa_de_panio');
    }

    public function cierre(){
      return $this->belongsTo('App\Mesas\CierreMesa', 'id_cierre_mesa', 'id_cierre_mesa');
    }

    public function apertura(){
      return $this->belongsTo('App\Mesas\AperturaMesa', 'id_apertura_mesa', 'id_apertura_mesa');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_cierre_mesa;
    }

}
