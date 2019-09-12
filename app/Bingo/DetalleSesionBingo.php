<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class DetalleSesionBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_sesion_bingo';
  protected $primaryKey = 'id_detalle_sesion';
  protected $visible = array('id_detalle_sesion','id_sesion','valor_carton','serie_inicio',
                              'serie_fin','carton_inicio','carton_fin'
                              );
  protected $fillable = ['id_detalle_sesion','id_sesion','valor_carton','serie_inicio',
                              'serie_fin','carton_inicio','carton_fin'];

  public $timestamps = false;

  public function sesionBingo()
  {
    return $this->belongsTo('App\Bingo\SesionBingo','id_sesion','id_sesion');
  }

}
