<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\NivelProgresivoObserver;

class NivelProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'nivel_progresivo';
  protected $primaryKey = 'id_nivel_progresivo';
  protected $visible = array('id_nivel_progresivo','nro_nivel','nombre_nivel','porc_oculto','porc_visible','base','maximo');
  public $timestamps = false;

  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

  public function pozos(){
    return $this->belongsToMany('App\Pozo','pozo_tiene_nivel_progresivo','id_nivel_progresivo' , 'id_pozo');
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
