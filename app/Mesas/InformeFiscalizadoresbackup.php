<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InformeFiscalizadoresback extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'informe_fiscalizadores';
  protected $primaryKey = 'id_informe_fiscalizadores';
  protected $visible = array('id_informe_fiscalizadores','fecha','id_casino',
                              'cumplio_minimo', 'cant_cierres',
                              'cant_aperturas','cant_mesas_abiertas',
                              'cant_mesas_totales','cant_mesas_con_diferencia',
                              'cantidad_abiertas_con_minimo','id_apuesta_minima_juego',
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
    return $this->belongsTo('App\Mesas\ApuestaMinimaJuego','id_apuesta_minima_juego','id_apuesta_minima');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_informe_fiscalizadores;
  }

}
