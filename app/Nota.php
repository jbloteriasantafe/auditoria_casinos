<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\NotaObserver;

class Nota extends Model
{
  protected $connection = 'mysql';
  protected $table = 'nota';
  protected $primaryKey = 'id_nota';
  protected $visible = array('id_nota','fecha','detalle','identificacion');
  public $timestamps = false;

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }

  public function disposiciones(){
      return $this->hasMany('App\Disposicion','id_nota','id_nota');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function log_movimiento(){
    return $this->belongsTo('App\LogMovimiento','id_log_movimiento','id_log_movimiento');
  }
  public function archivo(){
      return $this->belongsTo('App\Archivo','id_archivo','id_archivo');
  }

  public static function boot(){
        parent::boot();
        Nota::observe(new NotaObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_nota;
  }

}
