<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\TomaRelevMovObserver;

/*
*
* Detalle de los relevamientos movimientos
*/
class TomaRelModificada extends Model
{
  protected $connection = 'mysql';
  protected $table = 'toma_relev_mov';
  protected $primaryKey = 'id_toma_relev_mov';
  protected $visible = array('id_toma_relev_mov','id_relevamiento_movimiento',
                            'mac','valcont1','valcont2','valcont3','valcont4','valcont5',
                            'valcont6','valcont7','valcont8', 'juego', 'apuesta_max',
                            'cant_lineas', 'porcentaje_devolucion', 'denominacion',
                             'cant_creditos', 'observaciones_modif','nro_admnin',
                             'modelo', 'nro_serie','nro_isla','marca','id_modificador',
                             'check1','check2', 'check3', 'check4','check5',
                             'check6', 'check7','check8', 'fecha_modif'
                           );
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
