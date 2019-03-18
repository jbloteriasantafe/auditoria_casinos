<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ComandoEnEspera extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'comando_a_ejecutar';
  protected $primaryKey = 'id_comando_a_ejecutar';
  protected $visible = array('id_comando_a_ejecutar','nombre_comando','fecha_a_ejecutar';

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_comando_a_ejecutar;
  }
}
