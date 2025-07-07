<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cache extends Model
{
  protected $connection = 'mysql';
  protected $table = 'cache';
  protected $primaryKey = 'id_cache';
  protected $visible = array('id_cache','codigo','subcodigo','data','dependencias','creado');
  public $timestamps = false;
}
