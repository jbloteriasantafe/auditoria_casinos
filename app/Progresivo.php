<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ProgresivoObserver;
use Illuminate\Database\Eloquent\SoftDeletes;

class Progresivo extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'progresivo';
  protected $primaryKey = 'id_progresivo';
  protected $visible = array(
    'id_progresivo',
    'nombre',
    'porc_recup',
    'id_casino',
    'es_individual',
    'deleted_at'
  );
  public $timestamps = false;


  public function pozos(){
    return $this->hasMany('App\Pozo','id_progresivo','id_progresivo');
  }

  public function maquinas(){
        return $this->belongsToMany('App\Maquina','maquina_tiene_progresivo','id_progresivo','id_maquina');
  }

  public function casino(){
        return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public static function boot(){
    parent::boot();
    Progresivo::observe(new ProgresivoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_progresivo;
  }

}
