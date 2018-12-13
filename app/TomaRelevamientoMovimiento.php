<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\TomaRelevMovObserver;

/*
*
* Detalle de los relevamientos movimientos
*/
class TomaRelevamientoMovimiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'toma_relev_mov';
  protected $primaryKey = 'id_toma_relev_mov';
  protected $visible = array('id_toma_relev_mov','id_relevamiento_movimiento',
                            'mac','vcont1','vcont2','vcont3','vcont4','vcont5',
                            'vcont6','vcont7','vcont8', 'juego', 'apuesta_max',
                            'cant_lineas', 'porcentaje_devolucion', 'denominacion',
                             'cant_creditos', 'observaciones','nro_admnin',
                             'modelo', 'nro_serie','nro_isla','marca',
                             'nro_isla_relevada','descripcion_sector_relevado','toma_reingreso');
  public $timestamps = false;

  public function relevamiento_movimiento(){
    return $this->belongsTo('App\RelevamientoMovimiento','id_relevamiento_movimiento','id_relev_mov');
  }
  public static function boot(){
        parent::boot();
        Nota::observe(new TomaRelevMovObserver());
  }
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_toma_relev_mov;
  }

}
