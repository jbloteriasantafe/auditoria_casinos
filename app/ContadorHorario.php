<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContadorHorario extends Model
{
  protected $connection = 'mysql';
  protected $table = 'contador_horario';
  protected $primaryKey = 'id_contador_horario';
  protected $visible = array('id_contador_horario','fecha','cerrado',  'id_tipo_moneda','md5');
  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function detalles(){
    return $this->HasMany('App\DetalleContadorHorario','id_contador_horario','id_contador_horario');
  }
  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public static function boot(){
    parent::boot();
    ContadorHorario::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_contador_horario;
  }
}
