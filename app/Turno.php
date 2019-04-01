<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/*

  Tener en cuenta que 0 no es ningin dia,
  1 es el LUNES,..,7 es el DOMINGO
  para obtener que nro de dia es:
  ->format('w'); ->>retorna 0 si es domingo, el resto de los dias esta OK


*/
class Turno extends Model
{
  protected $connection = 'mysql';
  protected $table = 'turno';
  protected $primaryKey = 'id_turno';
  protected $visible = array('id_turno','id_layout_parcial','id_casino',
  'dia_desde', 'dia_hasta' , 'entrada','salida' , 'nro_turno','hora_propuesta',
    'created_at','deleted_at','updated_at');
  public $timestamps = false;

  protected $fillable = ['nro_turno','dia_desde','dia_hasta','entrada',
                               'salida','id_casino'];

  protected $appends = array('nombre_dia_desde','nombre_dia_hasta');


  public function getNombreDiaDesdeAttribute(){
    return $this->elegirdia($this->dia_desde);
  }
  public function getNombreDiaHastaAttribute(){
    return $this->elegirdia($this->dia_hasta);
  }

  private function elegirdia($dia){
    switch ($dia) {
      case 1:
        return 'Lunes';
      case 2:
        return 'Martes';
      case 3:
        return 'Miercoles';
      case 4:
        return 'Jueves';
      case 5:
        return 'Viernes';
      case 6:
        return 'Sabado';
      case 7:
        return 'Domingo';
      default:

        return 'NoEncontrado';
        break;
    }
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function relevamientos_apuestas(){
    return $this->hasMany('App\Mesas\RelevamientoApuestas','id_turno','id_turno');
  }

    public function getId(){
      return $this->id_turno;
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
