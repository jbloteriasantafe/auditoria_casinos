<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class DetalleApertura extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_apertura';
  protected $primaryKey = 'id_detalle_apertura';
  protected $visible = array('id_detalle_apertura','id_ficha','cantidad_ficha',
                              'id_apertura_mesa','id_detalle_cierre'
                            );
  public $timestamps = false;

  public function apertura()
  {
    return $this->belongsTo('App\Mesas\Apertura','id_apertura_mesa','id_apertura_mesa');
  }
  public function ficha(){
    return $this->belongsTo('App\Mesas\Ficha','id_ficha','id_ficha');
  }

  public function detalle_cierre(){
    return $this->belongsTo('App\Mesas\DetalleCierre','id_detalle_cierre','id_detalle_cierre');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_apertura;
  }
}
