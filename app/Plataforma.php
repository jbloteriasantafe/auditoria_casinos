<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Plataforma extends Model
{
  protected $connection = 'mysql';
  protected $table = 'plataforma';
  protected $primaryKey = 'id_plataforma';
  protected $visible = array('id_plataforma','nombre','codigo');
  public $timestamps = false;

  public function ae_estados(){
    return $this->belongsToMany('App\Autoexclusion\EstadoAE','id_plataforma','id_plataforma');
  }
}
