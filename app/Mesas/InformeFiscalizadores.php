<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/*
Transicion de estados del informe: -son todos internos-
se crea en 0, pasa a 1 cuando se llama a iniciarInformeDiario por 2da vez,
es decir que se crearon mas aperturas
Luego cuando se imprime se actualiza

*/


class InformeFiscalizadores extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'informe_fiscalizadores';
  protected $primaryKey = 'id_informe_fiscalizadores';
  protected $visible = array('id_informe_fiscalizadores','fecha','id_casino',
                              'cumplio_minimo', 'cant_cierres',
                              'cant_aperturas','cant_mesas_abiertas',
                              'cant_mesas_totales','cant_mesas_con_diferencia',
                              'cantidad_abiertas_con_minimo',
                              'id_apuesta_minima_juego',
                              'turnos_sin_minimo',
                              'mesas_relevadas_abiertas',
                              'mesas_importadas_abiertas',
                              'mesas_con_diferencia',
                              'ap_sin_validar',
                              'cie_sin_validar',
                              'aperturas_sorteadas',//% de aperturas relevadas que coinciden con las sorteadas.
                              'created_at','deleted_at','updated_at'
                            );
  public $timestamps = false;


  protected $fillable = ['fecha','cant_cierres',
                          'cumplio_minimo','cant_aperturas',
                          'cant_mesas_totales','cant_mesas_abiertas',
                          'cant_cierres','cant_mesas_con_diferencia'
                          ];


  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function apuesta_minima(){
    return $this->belongsTo('App\Mesas\ApuestaMinimaJuego','id_apuesta_minima_juego','id_apuesta_minima_juego');
  }

  public function minimos(){
    return $this->hasMany('App\Mesas\MinApInforme','id_informe_fiscalizadores','id_informe_fiscalizadores');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_informe_fiscalizadores;
  }

}
