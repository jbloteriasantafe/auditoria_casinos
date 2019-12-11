<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Clima extends Model
{
  protected $connection = 'mysql';
  protected $table = 'clima';
  protected $primaryKey = 'id_clima';
  protected $visible = array(
    'id_clima',
    'descripcion'
    );
  public $timestamps = false;
}
