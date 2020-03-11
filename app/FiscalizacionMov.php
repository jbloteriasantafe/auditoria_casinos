<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


/*
* Para poder buscar mas facil todos los relevamientos movimientos
*
*
*/
class FiscalizacionMov extends Model
{
  protected $connection = 'mysql';
  protected $table = 'fiscalizacion_movimiento';
  protected $primaryKey = 'id_fiscalizacion_movimiento';
  protected $visible = array('id_fiscalizacion_movimiento','identificacion_nota','id_log_movimiento','fecha_envio_fiscalizar', 'id_cargador', 'id_fiscalizador', 'id_estado_relevamiento', 'id_nota','es_reingreso');
  public $timestamps = false;

  public function log_movimiento(){
      return $this->belongsTo('App\LogMovimiento','id_log_movimiento','id_log_movimiento');
  }

  public function relevamientos_movimientos(){
    return $this->hasMany('App\RelevamientoMovimiento', 'id_fiscalizacion_movimiento','id_fiscalizacion_movimiento');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fiscalizador','id_usuario');
  }

  public function cargador(){
    return $this->belongsTo('App\Usuario','id_cargador','id_usuario');
  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function nota(){
    return $this->belongsTo('App\Nota','id_nota','id_nota');
  }

  public function evento(){
    return $this->hasOne('App\Evento', 'id_fiscalizacion_movimiento','id_fiscalizacion_movimiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_fiscalizacion_movimiento;
  }

  public function relevamientosCompletados($estado = 3){
    $total = $this->relevamientos_movimientos()->count();
    $completados = $this->relevamientos_movimientos()
    ->where('relevamiento_movimiento.id_estado_relevamiento','=',$estado)->count();
    return $total == $completados;
  }

}
