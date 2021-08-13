<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class SexoAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_sexo';
  protected $primaryKey = 'id_sexo';
  protected $visible = array('id_sexo','descripcion','codigo');
  public $timestamps = false;

  public function autoexcluidos(){
    return $this->hasMany('App\Autoexclusion\Autoexcluido','id_sexo','id_sexo');
  }
}