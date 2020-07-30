<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class OcupacionAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_ocupacion';
  protected $primaryKey = 'id_ocupacion';
  protected $visible = array('id_ocupacion','nombre');
  public $timestamps = false;

  public function autoexcluidos(){
    return $this->hasMany('App\Autoexclusion\Autoexcluido','id_ocupacion','id_ocupacion');
  }
}