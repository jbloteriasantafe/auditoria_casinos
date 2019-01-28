<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PackJuego extends Model
{
protected $connection = 'mysql';
  protected $table = 'pack_juego';
  protected $primaryKey = 'id_pack';
  protected $visible = array('id_pack','identificador', 'prefijo');
  public $timestamps = false;

  public function casinos(){
    return $this->belongsToMany('App\Casino','pack_juego_tiene_casino','id_pack','id_casino');
 }

 public function juegos(){
    return $this->belongsToMany('App\Juego','pack_tiene_juego','id_pack','id_juego');
 }

}
