<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoAjuste extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_ajuste';
  protected $primaryKey = 'id_tipo_ajuste';
  protected $visible = array('id_tipo_ajuste','descripcion');
  public $timestamps = false;

  public function detalles_producidos(){
    return $this->HasMany('App\DetalleProducido','id_tipo_ajuste','id_tipo_ajuste');
  }
  public function ajuste_temporal_producido(){
    return $this->HasMany('App\AjusteTemporalProducido','id_tipo_ajuste','id_tipo_ajuste');
  }

}
