<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\NivelProgresivoObserver;

class NivelProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'nivel_progresivo';
  protected $primaryKey = 'id_nivel_progresivo';
  protected $visible = array('id_nivel_progresivo',
  'nro_nivel',
  'nombre_nivel',
  'base',
  'porc_oculto',
  'porc_visible',
  'maximo',
  'id_pozo');
  public $timestamps = false;

  public function pozo(){
    return $this->belongsTo('App\Pozo','id_pozo','id_pozo');
  }

  public static function boot(){
    parent::boot();
    NivelProgresivo::observe(new NivelProgresivoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_nivel_progresivo;
  }
}
