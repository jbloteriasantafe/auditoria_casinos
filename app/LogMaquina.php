<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogMaquina extends Model
{
  protected $connection = 'mysql';
  protected $table = 'log_maquina';
  protected $primaryKey = 'id_log_maquina';
  protected $visible = array('id_log_maquina','id_maquina','id_tipo_movimiento','id_estado_maquina', 'razon', 'fecha', 'juega_progresivo', 'denominacion', 'nro_isla', 'sector', 'nombre_juego', 'porcentaje_devolucion');
  public $timestamps = false;

  public function Maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

  public function tipo_movimiento(){
    return $this->belongsTo('App\TipoMovimiento','id_tipo_movimiento','id_tipo_movimiento');
  }

  public function estado_maquina(){
    return $this->belongsTo('App\EstadoMaquina','id_estado_maquina','id_estado_maquina');
  }


  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_log_maquina;
  }

}
