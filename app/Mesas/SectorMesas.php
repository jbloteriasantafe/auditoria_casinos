<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectorMesas extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'sector_mesas';
  protected $primaryKey = 'id_sector_mesas';
  protected $visible = array('id_sector_mesas','descripcion','id_casino');



  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_mesa','id_mesa');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_sector_mesas;
  }
}
