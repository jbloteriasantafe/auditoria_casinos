<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class DetalleSesionBingoRe extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_sesion_bingo_re';
  protected $primaryKey = 'id_detalle_sesion_re';
  protected $visible = array('id_detalle_sesion_re','id_detalle_sesion','id_sesion_re','valor_carton','serie_inicio',
                              'serie_fin','carton_inicio','carton_fin'
                              );
  protected $fillable = ['id_detalle_sesion_re','id_detalle_sesion','id_sesion_re','valor_carton','serie_inicio',
                              'serie_fin','carton_inicio','carton_fin'];

  public $timestamps = false;

  public function sesionBingoRe()
  {
    return $this->belongsTo('App\Bingo\SesionBingoRe','id_sesion_re','id_sesion_re');
  }
  
}
