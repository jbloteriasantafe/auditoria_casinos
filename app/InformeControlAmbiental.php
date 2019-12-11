<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InformeControlAmbiental extends Model
{
  protected $connection = 'mysql';
  protected $table = 'informe_control_ambiental';
  protected $primaryKey = 'id_informe_control_ambiental';
  protected $visible = array(
    'id_informe_control_ambiental',
    'nro_informe_control_ambiental',
    'id_casino',
    'id_relevamiento_ambiental_maquinas',
    'id_relevamiento_ambiental_mesas'
  );
  public $timestamps = false;
}
