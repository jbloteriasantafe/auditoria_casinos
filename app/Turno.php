<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
  protected $connection = 'mysql';
  protected $table = 'turno';
  protected $primaryKey = 'id_turno';
  protected $visible = array('id_turno','id_layout_parcial','id_casino', 'dia_desde', 'dia_hasta' , 'entrada','salida' , 'nro_turno');
  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  
  // public function nro_turno($casino, $date){//dependiendo del datetime y el casino, devuelvo el casino que corresponde
  //     $day_of_week = date("w");
  //     $this_hour = date("H:i:s");
  //     $turnos = Turno::where('id_casino',$casino)->get();
  //     $bandera = false;
  //     $i=0;
  //     while ($bandera != true) {
  //       if(($day_of_week >= $turnos[$i]->dia_desde && $day_of_week <= $turnos[$i]->dia_hasta) && ($this_hour >= $turnos[$i]->entrada && $day_of_week <= $turnos[$i]->salidas)){
  //         $bandera = true;
  //         $turno= $turnos[$i];
  //       }
  //       $i++;
  //     }
  //
  //     return $numero;
  // }
}
