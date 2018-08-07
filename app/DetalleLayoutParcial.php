<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleLayoutParcial extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_layout_parcial';
  protected $primaryKey = 'id_detalle_layout_parcial';
  protected $visible = array('id_detalle_layout_parcial','id_layout_parcial','id_maquina','denominacion_sala','porcentaje_devolucion');
  public $timestamps = false;

  public function layout_parcial(){
    return $this->belongsTo('App\LayoutParcial','id_layout_parcial','id_layout_parcial');
  }

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

  public function tipo_causa_no_toma(){
    return $this->belongsTo('App\TipoCausaNoToma','id_tipo_causa_no_toma','id_tipo_causa_no_toma');
  }

  public function campos_con_diferencia(){
    return $this->HasMany('App\CampoConDiferencia' , 'id_detalle_layout_parcial' , 'id_detalle_layout_parcial');
  }

  // public static function boot(){
  //   parent::boot();
  //   DetalleRelevamiento::observe(new DetalleRelevamientoObserver());
  // }

  public function getTableName(){
    return $this->table;
  }

}
