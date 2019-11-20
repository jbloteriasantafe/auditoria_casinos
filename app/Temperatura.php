<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Temperatura extends Model
{
  protected $connection = 'mysql';
  protected $table = 'temperatura';
  protected $primaryKey = 'id_temperatura';
  protected $visible = array(
    'id_temperatura',
    'descripcion'
    );
  public $timestamps = false;
}
