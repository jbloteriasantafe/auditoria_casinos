<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoCausaNoTomaProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_causa_no_toma_progresivo';
  protected $primaryKey = 'id_tipo_causa_no_toma_progresivo';
  protected $visible = array('id_tipo_causa_no_toma_progresivo','descripcion','codigo');
  public $timestamps = false;

  public function detalles(){
    return $this->HasMany('App\DetalleRelevamientoProgresivo','id_tipo_causa_no_toma','id_tipo_causa_no_toma');
  }
}
