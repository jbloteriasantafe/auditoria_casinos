<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleLog extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_log';
  protected $primaryKey = 'id_detalle_log';
  protected $visible = array('id_detalle_log','campo','valor');
  public $timestamps = false;

  public function log(){
    return $this->belongsTo('App\Log','id_log','id_log');
  }
  
}
