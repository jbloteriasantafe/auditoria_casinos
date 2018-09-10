<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class DetalleCierre extends Model
{
    protected $connection = 'mysql';
    protected $table = 'detalle_cierre';
    protected $primaryKey = 'id_detalle_cierre';
    protected $visible = array('id_detalle_cierre','id_ficha','monto_ficha',
                               'valor_ficha','id_cierre_mesa');
    public $timestamps = false;

    public function cierre(){
      return $this->belongsTo('App\Mesas\CierreMesa', 'id_cierre_mesa', 'id_cierre_mesa');
    }

    public function ficha(){
      return $this->belongsTo('App\Mesas\Ficha', 'id_ficha', 'id_ficha');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_detalle_cierre;
    }

}