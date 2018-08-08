<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteProducido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ajuste_producido';
  protected $primaryKey = 'id_ajuste_producido';
  protected $visible = array('id_ajuste_producido','producido_sistema','producido_calculado','diferencia');
  public $timestamps = false;

  public function detalle_producido(){
    return $this->hasOne('App\DetalleProducido','id_detalle_producido','id_detalle_producido');
  }

}
