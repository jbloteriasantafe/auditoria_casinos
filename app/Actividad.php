<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
  protected $connection = 'mysql';
  protected $table = 'actividad';
  protected $primaryKey = 'id_actividad';
  protected $visible = array('id_actividad','descripcion');
  public $timestamps = false;

  public function beneficios_mensuales(){
    return $this->HasMany('App\BeneficioMensual','id_actividad','id_actividad');
  }

  public function procentajes(){
    return $this->HasMany('App\Porcentaje','id_actividad','id_actividad');
  }

}
