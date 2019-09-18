<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class EstadoSesionBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_estado_sesion';
  protected $primaryKey = 'id_estado_sesion';
  protected $visible = array('id_estado_sesion','descripcion');
  public $timestamps = false;
}
