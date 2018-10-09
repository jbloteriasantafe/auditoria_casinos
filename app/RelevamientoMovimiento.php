<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\RelevamientoMovimientoObserver;

class RelevamientoMovimiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'relevamiento_movimiento';
  protected $primaryKey = 'id_relev_mov';
  protected $visible = array('id_relev_mov','id_fiscalizacion_movimiento',
  'id_fisca','id_cargador', 'id_maquina', 'fecha_relev_sala','fecha_fecha_carga',
   'id_estado_relevamiento','id_log_movimiento','nro_admin');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function log_movimiento(){
    return $this->belongsTo('App\LogMovimiento','id_log_movimiento','id_log_movimiento');
  }
  public function fiscalizacion(){
    return $this->belongsTo('App\FiscalizacionMov','id_fiscalizacion_movimiento','id_fiscalizacion_movimiento');
  }

  public function toma_relevamiento_movimiento(){
      return $this->hasOne('App\TomaRelevamientoMovimiento','id_relevamiento_movimiento','id_relev_mov');
  }

  public function toma_relevamiento_modificada(){
      return $this->hasOne('App\TomaRelModificada','id_relevamiento_movimiento','id_relev_mov');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fisca','id_usuario');
  }

  public function cargador(){
    return $this->belongsTo('App\Usuario','id_cargador','id_usuario');
  }
  public static function boot(){
        parent::boot();
        Disposicion::observe(new RelevamientoMovimientoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_relev_mov;
  }

}
