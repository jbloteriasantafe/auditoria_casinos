<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\GliHardObserver;

class GliHard extends Model
{
  protected $connection = 'mysql';
  protected $table = 'gli_hard';
  protected $primaryKey = 'id_gli_hard';
  protected $visible = array('id_gli_hard','nro_archivo');
  public $timestamps = false;

  public function archivo(){
      return $this->belongsTo('App\Archivo','id_archivo','id_archivo');
  }

  public function casinos(){
    return $this->belongsToMany('App\Casino', 'casino_tiene_gli_hard', 'id_gli_hard', 'id_casino');
  }

  public function maquinas(){
    return $this->HasMany('App\Maquina','id_gli_hard','id_gli_hard');
  }

  public function expedientes(){
     return $this->belongsToMany('App\Expediente','expediente_tiene_gli_hard','id_gli_hard','id_expediente');
  }

  public static function boot(){
        parent::boot();
        GliHard::observe(new GliHardObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_gli_hard;
  }

}
