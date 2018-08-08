<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampoConDiferencia extends Model
{
  protected $connection = 'mysql';
  protected $table = 'campo_con_diferencia';
  protected $primaryKey = 'id_campo_con_diferencia';
  protected $visible = array('id_campo_con_diferencia','id_detalle_layout_parcial','columna','valor');
  public $timestamps = false;

  public function detalle_layout_parcial(){
    return $this->belongsTo('App\DetalleLayoutParcial','id_detalle_layout_parcial','id_detalle_layout_parcial');
  }

}
