<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventualidadObserver extends Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('fecha','sector','islas','maquinas','id_log_movimiento','id_archivo');
  }
}

class Eventualidad extends Model
{
  protected $connection = 'mysql';
  protected $table = 'eventualidad';
  protected $primaryKey = 'id_eventualidad';
  protected $visible = array('id_eventualidad','id_archivo','turno', 'fecha_generacion','fecha_toma','sectores','islas','maquinas','observaciones','id_tipo_eventualidad','id_estado_eventualidad','id_casino');
  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function tipo_eventualidad(){
      return $this->belongsTo('App\TipoEventualidad','id_tipo_eventualidad','id_tipo_eventualidad');
  }
  public function archivo(){
      return $this->belongsTo('App\Archivo','id_archivo','id_archivo');
  }
  public function fiscalizadores(){
     return $this->belongsToMany('App\Usuario','fisca_tiene_eventualidad','id_eventualidad','id_fiscalizador');
  }

  public function maquinas(){
     return $this->belongsToMany('App\Maquina','maquina_tiene_eventualidad','id_eventualidad','id_maquina');
  }

  public function estado_eventualidad(){
      return $this->belongsTo('App\EstadoMovimiento','id_estado_eventualidad','id_estado_movimiento');
  }

  public static function boot(){
        parent::boot();
        Eventualidad::observe(new EventualidadObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_eventualidad;
  }

}
