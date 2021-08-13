<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class APIToken extends Model
{
  protected $connection = 'mysql';
  protected $table = 'API_token';
  protected $primaryKey = 'id_api_token';
  protected $visible = array('id_api_token','token','ip','id_usuario','id_plataforma');
  public $timestamps = false;

  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }
}
