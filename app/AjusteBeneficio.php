<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AjusteBeneficio extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ajuste_beneficio';
  protected $primaryKey = 'id_ajuste_beneficio';
  protected $visible = array('id_ajuste_beneficio','valor','id_beneficio');
  public $timestamps = false;

  public function beneficio(){
    return $this->hasOne('App\Beneficio','id_beneficio','id_beneficio');
  }
}
