<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleContadorHorario extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_contador_horario';
  protected $primaryKey = 'id_detalle_contador_horario';
  protected $visible = array('id_detalle_contador_horario','isla','coinin','coinout','jackpot','progresivo','id_contador_horario');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }
  public function contador_horario(){
    return $this->belongsTo('App\ContadorHorario','id_contador_horario','id_contador_horario');
  }  

}
