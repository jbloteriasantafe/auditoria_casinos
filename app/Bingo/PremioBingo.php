<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class PremioBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'premio_bingo';
  protected $primaryKey = 'id_premio';
  protected $visible = array('id_premio','nombre_premio','porcentaje','bola_tope','tipo_premio');
  public $timestamps = false;
}
