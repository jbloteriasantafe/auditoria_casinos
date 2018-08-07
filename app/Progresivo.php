<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ProgresivoObserver;

class Progresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'progresivo';
  protected $primaryKey = 'id_progresivo';
  protected $visible = array('id_progresivo','nombre_progresivo','linkeado','individual','porc_recuperacion','maximo');
  public $timestamps = false;

  public function niveles(){
    return $this->hasMany('App\NivelProgresivo','id_progresivo','id_progresivo');
  }

  public function juegos(){
        return $this->hasMany('App\Juego','id_progresivo','id_progresivo');
  }

  public function pruebas_progresivo(){
    return $this->hasMany('App\PruebaProgresivo','id_progresivo','id_progresivo');
  }

  public function tipoProgresivo(){
      if($this->linkeado == 1 && $this->individual == 0){
        return 'LINKEADO';
      }else{
        return 'INDIVIDUAL';
      }
  }

  public static function boot(){
    parent::boot();
    Progresivo::observe(new ProgresivoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_progresivo;
  }

}
