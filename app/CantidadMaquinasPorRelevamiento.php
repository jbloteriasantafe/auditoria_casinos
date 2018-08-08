<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CantidadMaquinasPorRelevamiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'cantidad_maquinas_por_relevamiento';
  protected $primaryKey = 'id_cantidad_maquinas_por_relevamiento';
  protected $visible = array('id_cantidad_maquinas_por_relevamiento','cantidad','id_sector','fecha_desde','fecha_hasta','id_tipo_cantidad_maquinas_por_relevamiento');
  public $timestamps = false;

  public function tipo_cantidad_maquinas_por_relevamiento(){
    return $this->belongsTo('App\TipoCantidadMaquinasPorRelevamiento','id_tipo_cantidad_maquinas_por_relevamiento','id_tipo_cantidad_maquinas_por_relevamiento');
  }

  public function sector(){
    return $this->belongsTo('App\Sector','id_sector','id_sector');
  }

}
