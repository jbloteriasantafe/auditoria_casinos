<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\MaquinaAPedidoObserver;

class MaquinaAPedido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'maquina_a_pedido';
  protected $primaryKey = 'id_maquina_a_pedido';
  protected $visible = array('id_maquina_a_pedido','fecha' , 'id_maquina','id_relevamiento','created_at');
  public $timestamps = false;

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }
  public function relevamiento(){
      return $this->belongsTo('App\Relevamiento','id_relevamiento','id_relevamiento');
  }

  public static function boot(){
    parent::boot();
    MaquinaAPedido::observe(new MaquinaAPedidoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_maquina_a_pedido;
  }

}
