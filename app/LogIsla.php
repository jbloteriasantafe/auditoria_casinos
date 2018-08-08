<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\LogMovimientoObserver;

class LogIsla extends Model
{
  protected $connection = 'mysql';
  protected $table = 'log_isla';
  protected $primaryKey = 'id_log_isla';
  protected $visible = array('id_log_isla','id_isla','id_estado_relevamiento','fecha');
  public $timestamps = false;

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }
  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_log_isla;
  }

}
