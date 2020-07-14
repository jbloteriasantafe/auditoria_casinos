<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NombreEstadoAutoexclusion extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_nombre_estado';
  protected $primaryKey = 'id_nombre_estado';
  protected $visible = array('id_nombre_estado','descripcion');
  public $timestamps = false;
}
