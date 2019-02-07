<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class EstadoMesa extends Model
{
  protected $connection = 'mysql';
  protected $table = 'estado_mesa';
  protected $primaryKey = 'id_estado_mesa';
  protected $visible = array('id_estado_mesa','descripcion_mesa','siglas_mesa');
  public $timestamps = false;


  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_estado_mesa;
  }
}
