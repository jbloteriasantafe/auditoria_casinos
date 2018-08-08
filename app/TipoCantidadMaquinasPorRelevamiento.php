<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoCantidadMaquinasPorRelevamiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_cantidad_maquinas_por_relevamiento';
  protected $primaryKey = 'id_tipo_cantidad_maquinas_por_relevamiento';
  protected $visible = array('id_tipo_cantidad_maquinas_por_relevamiento','descripcion');
  public $timestamps = false;

  public function cantidades_maquinas_por_relevamientos(){
    return $this->HasMany('App\CantidadMaquinasPorRelevamiento','id_tipo_cantidad_maquinas_por_relevamiento','id_tipo_cantidad_maquinas_por_relevamiento');
  }

}
