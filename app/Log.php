<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
  protected $connection = 'mysql';
  protected $table = 'log';
  protected $primaryKey = 'id_log';
  protected $visible = array('id_log','fecha','accion','tabla','id_entidad');
  public $timestamps = false;

  public function detalles(){
    return $this->hasMany('App\DetalleLog','id_log','id_log');
  }

  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }

}
