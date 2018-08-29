<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\JuegoObserver;

class Juego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'juego';
  protected $primaryKey = 'id_juego';
  protected $visible = array('id_juego','nombre_juego', 'id_progresivo');
  public $timestamps = false;
  protected $appends = array('cod_identificacion');

  public function getCodIdentifAttribute(){
      return Maquina::where('id_isla','=',$this->id_isla)->whereNull('deleted_at')->count();
  }
  public function gliSoft(){
    return $this->belongsTo('App\GliSoft','id_gli_soft','id_gli_soft');
  }

  public function tablasPago(){
    return $this->hasMany('App\TablaPago','id_juego','id_juego');
  }

  public function maquinas_juegos(){
     return $this->belongsToMany('App\Maquina','maquina_tiene_juego','id_juego','id_maquina')->withPivot('denominacion' , 'porcentaje_devolucion');;
  }

  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

  public function maquinas(){
    return $this->hasMany('App\Maquina','id_juego','id_juego');
  }

  public static function boot(){
    parent::boot();
    Juego::observe(new JuegoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_juego;
  }

}
