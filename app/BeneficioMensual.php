<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BeneficioMensual extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio_mensual';
  protected $primaryKey = 'id_beneficio_mensual';
  protected $visible = array('id_beneficio_mensual','id_actividad','id_casino','id_tipo_moneda','anio_mes','canon','bruto','iea');
  public $timestamps = false;

  public function actividad(){
    return $this->belongsTo('App\Actividad','id_actividad','id_actividad');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

}
