<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EventoControlAmbiental extends Model
{
  protected $connection = 'mysql';
  protected $table = 'evento_control_ambiental';
  protected $primaryKey = 'id_evento_control_ambiental';
  protected $visible = array(
    'id_evento_control_ambiental',
    'descripcion'
    );
  public $timestamps = false;
}
