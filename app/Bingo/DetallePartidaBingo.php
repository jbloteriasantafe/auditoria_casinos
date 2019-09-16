<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class DetallePartidaBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_partida_bingo';
  protected $primaryKey = 'id_detalle_partida';
  protected $visible = array('id_detalle_partida','id_partida','id_premio','carton'
                              );
  protected $fillable = ['id_detalle_partida','id_partida','id_premio','carton'];

  public $timestamps = false;

  public function partidaBingo()
  {
    return $this->belongsTo('App\Bingo\PartidaBingo','id_partida','id_partida');
  }
  public function premio()
  {
    return $this->belongsTo('App\Bingo\PremioBingo','id_premio','id_premio');
  }
}
