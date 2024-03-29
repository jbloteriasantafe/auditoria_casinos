<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TablaPagoObserver extends Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('codigo');
  }
}

class TablaPago extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tabla_pago';
  protected $primaryKey = 'id_tabla_pago';
  protected $visible = ['id_tabla_pago','codigo','id_juego','porcentaje_devolucion']; //'denominacion_base','porc_devolucion_min', 'porc_devolucion_max'
  public $timestamps = false;

  public function juego(){
    return $this->belongsTo('App\Juego','id_juego','id_juego');
  }

  //devuelve todas las maquinas que tengan como denominacion activa la tabla de pago que responde al método
  public function maquinas(){
      return $this->belongsToMany('App\Juego', 'denominacion_activa' , 'id_tabla_pago' , 'id_maquina');
  }
  public static function boot(){
        parent::boot();
        TablaPago::observe(new TablaPagoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tabla_pago;
  }

}
