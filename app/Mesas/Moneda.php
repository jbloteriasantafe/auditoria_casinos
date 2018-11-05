<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Moneda extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'moneda';
  protected $primaryKey = 'id_moneda';
  protected $visible = array('id_moneda','descripcion','siglas');

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_moneda','id_moneda');
  }

  public function fichas(){
    return $this->hasMany('App\Mesas\Ficha','id_moneda','id_moneda');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_moneda;
  }
}
