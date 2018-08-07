<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Pozo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'pozo';
  protected $primaryKey = 'id_pozo';
  protected $visible = array('id_pozo','descripcion');
  public $timestamps = false;

  public function niveles_progresivo(){
    return $this->belongsToMany('App\NivelProgresivo','pozo_tiene_nivel_progresivo','id_pozo','id_nivel_progresivo')->withPivot('base');
  }

  public function formatearBase($niveles){
      foreach ($niveles as $nivel){
        $nivel->base = ($nivel->pivot->base != null  ? $nivel->pivot->base : $nivel->base);
      }
      return $niveles;
  }

  public function maquinas(){
    return $this->hasMany('App\Maquina' , 'id_pozo' , 'id_pozo' );
  }

}
