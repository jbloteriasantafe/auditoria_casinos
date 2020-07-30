<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class CapacitacionAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_capacitacion';
  protected $primaryKey = 'id_capacitacion';
  protected $visible = array('id_capacitacion','descripcion');
  public $timestamps = false;

  public function autoexcluidos(){
    return $this->hasMany('App\Autoexclusion\Autoexcluido','id_capacitacion','id_capacitacion');
  }
}