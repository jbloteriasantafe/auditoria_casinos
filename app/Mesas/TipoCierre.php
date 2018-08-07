<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class TipoCierre extends Model
{
    protected $connection = 'mysql';
    protected $table = 'tipo_cierre';
    protected $primaryKey = 'id_tipo_cierre';
    protected $visible = array('id_tipo_cierre','descripcion');
    public $timestamps = false;

    public function cierres(){
      return $this->hasMany('App\Mesas\Cierre', 'id_tipo_cierre', 'id_tipo_cierre');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_tipo_cierre;
    }

}
