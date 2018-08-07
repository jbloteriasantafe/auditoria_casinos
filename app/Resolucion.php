<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ResolucionObserver;

class Resolucion extends Model
{
  protected $connection = 'mysql';
  protected $table = 'resolucion';
  protected $primaryKey = 'id_resolucion';
  protected $visible = array('id_resolucion','nro_resolucion','nro_resolucion_anio');
  public $timestamps = false;

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }

  public static function boot(){
    parent::boot();
    Resolucion::observe(new ResolucionObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_resolucion;
  }

}
