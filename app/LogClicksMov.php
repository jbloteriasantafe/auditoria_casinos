<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
*/
class LogClicksMov extends Model
{
  protected $connection = 'mysql';
  protected $table = 'log_clicks_mov';
  protected $primaryKey = 'id_log_clicks_mov';
  protected $visible = array('id_log_clicks_mov','id_log_movimiento','fecha');
  public $timestamps = false;


  public function log_movimiento(){
    return $this->belongsTo('App\LogMovimiento','id_log_movimiento','id_log_movimiento');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_log_clicks_mov;
  }

}
