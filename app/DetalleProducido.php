<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleProducido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_producido';
  protected $primaryKey = 'id_detalle_producido';
  protected $visible = array('id_detalle_producido','apuesta','premio','valor');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }
  public function producido(){
    return $this->belongsTo('App\Producido','id_producido','id_producido');
  }
  public function tipo_ajuste(){
    return $this->belongsTo('App\TipoAjuste','id_tipo_ajuste','id_tipo_ajuste');
  }
  public function ajuste_producido(){
    return $this->belongsTo('App\AjusteProducido','id_ajuste_producido','id_ajuste_producido');
  }

}
