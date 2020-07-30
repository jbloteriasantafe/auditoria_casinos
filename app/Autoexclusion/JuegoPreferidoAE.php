<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class JuegoPreferidoAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_juego_preferido';
  protected $primaryKey = 'id_juego_preferido';
  protected $visible = array('id_juego_preferido','descripcion');
  public $timestamps = false;

  public function encuestas(){
    return $this->hasMany('App\Autoexclusion\Encuesta','id_juego_preferido','id_juego_preferido');
  }
}