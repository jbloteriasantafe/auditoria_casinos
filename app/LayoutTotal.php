<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LayoutTotal extends Model
{
  protected $connection = 'mysql';
  protected $table = 'layout_total';
  protected $primaryKey = 'id_layout_total';
  protected $visible = array('id_layout_total', 'nro_layout_total' ,'fecha','fecha_generacion','fecha_ejecucion', 'backup', 'id_casino','turno' ,'id_estado_relevamiento' , 'observacion_fiscalizacion' , 'observacion_validacion');
  public $timestamps = false;

  public function detalles(){
    return $this->hasMany('App\DetalleLayoutTotal','id_layout_total','id_layout_total');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');

  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function usuario_cargador(){
    return $this->belongsTo('App\Usuario','id_usuario_cargador','id_usuario');
  }

  public function usuario_fiscalizador(){
    return $this->belongsTo('App\Usuario','id_usuario_fiscalizador','id_usuario');
  }

  public function islas(){
    return $this->belongsToMany('App\Isla','layout_total_isla','id_layout_total','id_isla')->withPivot('maquinas_observadas');
  }
  
  public function observaciones_islas(){
    return $this->hasMany('App\LayoutTotalIsla','id_layout_total','id_layout_total');
  }

}
