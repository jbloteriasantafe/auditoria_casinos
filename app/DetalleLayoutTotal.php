<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleLayoutTotal extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_layout_total';
  protected $primaryKey = 'id_detalle_layout_total';
  protected $visible = array('id_detalle_layout_total','id_layout_total','nro_admin', 'descripcion_sector' ,'nro_isla','co','pb','id_maquina','id_estado_maquina');
  public $timestamps = false;

  public function layout_total(){
    return $this->belongsTo('App\LayoutTotal','id_layout_total','id_layout_total');
  }

}
