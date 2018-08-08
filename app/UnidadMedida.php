<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
  protected $connection = 'mysql';
  protected $table = 'unidad_medida';
  protected $primaryKey = 'id_unidad_medida';
  protected $visible = array('id_unidad_medida','descripcion');
  public $timestamps = false;

  public function maquinas(){
    return $this->HasMany('App\Maquina','id_unidad_medida','id_unidad_medida');
  }

  public function casinos(){
    return $this->belongsToMany('App\Casino','casino_tiene_unidad_medida','id_unidad_medida','id_casino');
  }

}
