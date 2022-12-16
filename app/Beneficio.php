<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Beneficio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'beneficio';
  protected $primaryKey = 'id_beneficio';
  protected $visible = array('id_beneficio','fecha','coinin','coinout','valor','porcentaje_devolucion','cantidad_maquinas','promedio_por_maquina','observacion','validado','md5');
  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function ajuste_beneficio(){
    return $this->belongsTo('App\AjusteBeneficio','id_beneficio','id_beneficio');
  }

  public static function boot(){
    parent::boot();
    Beneficio::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_beneficio;
  }
}
