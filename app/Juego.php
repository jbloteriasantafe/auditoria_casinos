<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\JuegoObserver;

class Juego extends Model
{
  protected $connection = 'mysql';
  protected $table = 'juego';
  protected $primaryKey = 'id_juego';
  protected $visible = array('id_juego','nombre_juego', 'id_progresivo','id_gli_soft','cod_identificacion','cod_juego');
  public $timestamps = false;
  protected $appends = array('cod_identificacion');

  public function getCodIdentificacionAttribute(){
    if($this->id_gli_soft != null){
      return GliSoft::find($this->id_gli_soft)->nro_archivo;}
      return null;
  }

  // El modelo viejo tenia una relacion Juego n->1 GLISoft
  // Por lo que con una columna en el juego foranea id_gli_soft era suficiente
  // Pero habia casos que el juego tenia muchos glisoft, y el glisoft tenia muchos juegos
  // Por lo que hay que expandir sobre ese modelo a una tabla intermedia.
  // En principio habria que migrar todas las foraneas de juego a la intermedia juego_glisoft
  // pero por ahora voy a hacerlo asi
  public function gliSoftOld(){
    return $this->belongsTo('App\GliSoft','id_gli_soft','id_gli_soft');
  }

  public function gliSoft(){
    return $this->belongsToMany('App\GliSoft','juego_glisoft','id_juego','id_gli_soft');
  }

  public function agregarGliSofts($garray,$ids=False){
    $arr = [];
    if(!$ids){
      foreach($garray as $g) $arr[] = $g->id_gli_soft;
    }
    else $arr = $garray;

    $this->gliSoft()->syncWithoutDetaching($arr);
  }
  public function setearGliSofts($garray,$ids=False){
    $arr = [];
    if(!$ids){
      foreach($garray as $g) $arr[] = $g->id_gli_soft;
    }
    else $arr = $garray;

    $this->gliSoft()->sync($arr);
  }

  public function tablasPago(){
    return $this->hasMany('App\TablaPago','id_juego','id_juego');
  }

  public function maquinas_juegos(){
     return $this->belongsToMany('App\Maquina','maquina_tiene_juego','id_juego','id_maquina')->withPivot('denominacion' , 'porcentaje_devolucion');;
  }

  public function casinos(){
    return $this->belongsToMany('App\Casino','casino_tiene_juego','id_juego','id_casino');
 }

  public function progresivo(){
    return $this->belongsTo('App\Progresivo','id_progresivo','id_progresivo');
  }

  public function maquinas(){//En realidad obtiene las maquinas que lo tienen como activo.
    return $this->hasMany('App\Maquina','id_juego','id_juego');
  }

  public function pack(){
    return $this->belongsToMany('App\PackJuego','pack_tiene_juego','id_juego','id_pack');
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
