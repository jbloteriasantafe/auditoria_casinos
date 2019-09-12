<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class Canon extends Model
{
  protected $connection = 'mysql';
  protected $table = 'canon_bingo';
  protected $primaryKey = 'id_canon';
  protected $visible = array('id_canon','fecha_inicio','porcentaje', 'id_casino');
  public $timestamps = false;
}
