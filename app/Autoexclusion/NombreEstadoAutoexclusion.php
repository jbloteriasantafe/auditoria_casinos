<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class NombreEstadoAutoexclusion extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_nombre_estado';
  protected $primaryKey = 'id_nombre_estado';
  protected $visible = array('id_nombre_estado','descripcion');
  public $timestamps = false;

  public function estados(){
    return $this->hasMany('App\Autoexclusion\EstadoAE','id_nombre_estado','id_nombre_estado');
  }
}
