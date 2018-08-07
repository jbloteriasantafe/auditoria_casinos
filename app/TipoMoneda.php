<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoMoneda extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_moneda';
  protected $primaryKey = 'id_tipo_moneda';
  protected $visible = array('id_tipo_moneda','descripcion');
  public $timestamps = false;

  public function contadores(){
    return $this->HasMany('App\ContadorHorario','id_tipo_moneda','id_tipo_moneda');
  }
  public function producidos(){
    return $this->HasMany('App\Producido','id_tipo_moneda','id_tipo_moneda');
  }
  public function beneficios(){
    return $this->HasMany('App\Beneficio','id_tipo_moneda','id_tipo_moneda');
  }
  public function beneficios_mensuales(){
    return $this->HasMany('App\BeneficioMensual','id_tipo_moneda','id_tipo_moneda');
  }

}
