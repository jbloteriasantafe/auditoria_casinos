<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteTemporalProducido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ajuste_temporal_producido';
  protected $primaryKey = 'id_ajuste_temporal_producido';
  protected $visible = array('id_ajuste_temporal_producido','producido_sistema','producido_calculado','diferencia',
                              'id_producido', 'id_maquina','id_tipo_ajuste',
                              'coinin_ini','coinin_fin' ,'coinout_ini' ,'coinout_fin',
                              'jackpot_ini' ,'jackpot_fin','progresivo_ini','progresivo_fin',
                              'id_detalle_producido','id_detalle_contador_final','id_detalle_contador_inicial',
                              'id_contador_horario_ini','id_contador_horario_fin'
                              );
  public $timestamps = false;

  public function producido(){
    return $this->belongsTo('App\Producido','id_producido','id_producido');
  }

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }
  public function tipo_ajuste(){
    return $this->belongsTo('App\TipoAjuste','id_tipo_ajuste','id_tipo_ajuste');
  }
}
