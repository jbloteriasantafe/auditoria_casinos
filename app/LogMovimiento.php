<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\LogMovimientoObserver;

class LogMovimiento extends Model
{

  protected $connection = 'mysql';
  protected $table = 'log_movimiento';
  protected $primaryKey = 'id_log_movimiento';

  protected $visible = array('id_log_movimiento','fecha','id_casino',
  'id_expediente','id_estado_movimiento','id_estado_relevamiento',
  'id_tipo_movimiento','carga_finalizada', 'cant_maquinas', 'tipo_carga',
  'islas');

  public $timestamps = false;

  public function controladores(){
     return $this->belongsToMany('App\Usuario','controlador_movimiento','id_log_movimiento','id_controlador_movimiento');
  }
  public function estado_movimiento(){
    return $this->belongsTo('App\EstadoMovimiento','id_estado_movimiento','id_estado_movimiento');
  }
  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function nota(){
    return $this->hasOne('App\Nota','id_log_movimiento','id_log_movimiento');
  }
  public function relevamientos_movimientos(){
    return $this->hasMany('App\RelevamientoMovimiento', 'id_log_movimiento','id_log_movimiento');
  }

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }
  public function fiscalizaciones(){
     return $this->hasMany('App\FiscalizacionMov','id_log_movimiento','id_log_movimiento');
  }
  public function tipo_movimiento(){
    return $this->belongsTo('App\TipoMovimiento','id_tipo_movimiento','id_tipo_movimiento');
  }

  public function log_clicks_movs(){
    return $this->hasMany('App\LogClicksMov', 'id_log_movimiento','id_log_movimiento');
  }


 public static function boot(){
        parent::boot();
        Disposicion::observe(new LogMovimientoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_log_movimiento;
  }

}
