<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DatoGeneralidad extends Model
{
  protected $connection = 'mysql';
  protected $table = 'dato_generalidad';
  protected $primaryKey = 'id_dato_generalidad';
  protected $visible = array(
    'id_dato_generalidad',
    'tipo_generalidad',
    'id_relevamiento_ambiental',
    'turno1',
    'turno2',
    'turno3',
    'turno4',
    'turno5',
    'turno6',
    'turno7',
    'turno8'
    );
  public $timestamps = false;
}
