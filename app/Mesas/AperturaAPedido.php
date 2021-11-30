<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class AperturaAPedido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'apertura_a_pedido';
  protected $primaryKey = 'id_apertura_a_pedido';
  protected $visible = array('id_apertura_a_pedido','id_mesa_de_panio','fecha_inicio','fecha_fin');
  public $timestamps = false;

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }
  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_apertura_a_pedido;
  }
}
