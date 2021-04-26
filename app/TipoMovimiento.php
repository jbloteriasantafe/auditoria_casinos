<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoMovimiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_movimiento';
  protected $primaryKey = 'id_tipo_movimiento';
  protected $visible = array('id_tipo_movimiento','descripcion','puede_reingreso','puede_egreso_temporal','es_intervencion_mtm','deprecado');
  public $timestamps = false;
  protected $appends = array('es_intervencion_mtm');

  public function notas(){
    return $this->hasMany('App\Nota','id_tipo_movimiento','id_tipo_movimiento');
  }
  public function expedientes(){
    return $this->hasMany('App\Expediente', 'id_tipo_movimiento', 'id_tipo_movimiento');
  }

  public function log_movimientos(){
    return $this->belongsToMany('App\LogMovimiento','logmov_tipomov','id_tipo_movimiento','id_log_movimiento');
  }

  public function log_maquinas(){
    return $this->hasMany('App\LogMaquina','id_tipo_movimiento','id_tipo_movimiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tipo_movimiento;
  }

  public function getEsIntervencionMtmAttribute(){
    return $this->puede_egreso_temporal || $this->puede_reingreso;
  }
}
