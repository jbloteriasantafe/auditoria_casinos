<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class EstadoSesionBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_sesion_bingo';
  protected $primaryKey = 'id_estado_sesion';
  protected $visible = array('id_estado_sesion','descripcion');
  public $timestamps = false;
}
