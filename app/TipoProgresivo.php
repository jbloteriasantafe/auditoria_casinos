<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\TipoProgresivoObserver;

class TipoProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_progresivo';
  protected $primaryKey = 'id_tipo_progresivo';
  protected $visible = array('id_tipo_progresivo','descripcion');
  public $timestamps = false;

  public function progresivos(){
     return $this->HasMany('App\Progresivo','id_tipo_progresivo','id_tipo_progresivo');
  }

  public static function boot(){
        parent::boot();
        TipoProgresivo::observe(new TipoProgresivoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tipo_progresivo;
  }
  
}
