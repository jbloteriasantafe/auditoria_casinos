<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class Ficha extends Model
{
    protected $connection = 'mysql';
    protected $table = 'ficha';
    protected $primaryKey = 'id_ficha';
    protected $visible = array('id_ficha','valor_ficha','desde','hasta');
    public $timestamps = false;

    public function detalles_cierre(){
      return $this->hasMany('App\Mesas\DetalleCierre', 'id_ficha', 'id_ficha');
    }

    public function detalles_apertura(){
      return $this->hasMany('App\Mesas\DetalleApertura', 'id_ficha', 'id_ficha');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_ficha;
    }

}
