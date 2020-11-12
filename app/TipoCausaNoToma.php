<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoCausaNoToma extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_causa_no_toma';
  protected $primaryKey = 'id_tipo_causa_no_toma';
  protected $visible = array('id_tipo_causa_no_toma','descripcion','codigo','deprecado');
  public $timestamps = false;

  public function detalles(){
    return $this->HasMany('App\DetalleRelevamiento','id_tipo_causa_no_toma','id_tipo_causa_no_toma');
  }
}
