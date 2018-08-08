<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LayoutParcial extends Model
{
  protected $connection = 'mysql';
  protected $table = 'layout_parcial';
  protected $primaryKey = 'id_layout_parcial';
  protected $visible = array('id_layout_parcial','nro_layout_parcial','sub_control','backup','fecha','fecha_generacion','fecha_ejecucion','fecha_carga','tecnico','observacion_fiscalizacion','observacion_validacion' ,'id_estado_relevamiento' , 'id_usuario_cargador' , 'id_usuario_fiscalizador' , 'id_sector');
  public $timestamps = false;

  public function sector(){
    return $this->belongsTo('App\Sector','id_sector','id_sector');
  }

  public function campos_con_diferencia(){
    return $this->hasManyThrough('App\CampoConDiferencia','App\DetalleLayoutParcial','id_layout_parcial','id_detalle_layout_parcial' , 'id_layout_parcial' , 'id_detalle_layout_parcial');
  }

  public function usuario_cargador(){
    return $this->belongsTo('App\Usuario','id_usuario_cargador','id_usuario');
  }

  public function usuario_fiscalizador(){
    return $this->belongsTo('App\Usuario','id_usuario_fiscalizador','id_usuario');
  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleLayoutParcial','id_layout_parcial','id_layout_parcial');
  }

  public function delete(){
    foreach($this->detalles as $detalle){
      $detalle->delete();
    }
    parent::delete();
  }

}
