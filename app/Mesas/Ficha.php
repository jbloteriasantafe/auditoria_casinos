<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Ficha extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'ficha';
  protected $primaryKey = 'id_ficha';
  protected $visible = array('id_ficha','valor_ficha','id_moneda',);


  public function detalle_cierre(){
    return $this->HasMany('App\Mesas\DetalleCierre','id_ficha','id_ficha');
  }

  public function detalle_apertura(){
    return $this->HasMany('App\Mesas\DetalleApertura','id_ficha','id_ficha');
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_ficha;
  }
}
