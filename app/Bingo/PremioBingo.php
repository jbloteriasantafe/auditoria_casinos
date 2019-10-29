<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class PremioBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_premio';
  protected $primaryKey = 'id_premio';
  protected $visible = array('id_premio','nombre_premio','porcentaje','bola_tope','tipo_premio','id_casino');
  public $timestamps = false;
}
