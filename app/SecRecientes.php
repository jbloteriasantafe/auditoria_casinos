<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecRecientes extends Model
{
  protected $connection = 'mysql';
  protected $table = 'sec_recientes';
  protected $primaryKey = 'id_sec_reciente';
  protected $visible = array('id_sec_reciente','orden','seccion', 'ruta', 'id_usuario');
  public $timestamps = false;


  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return [$this->id_usuario, $this->orden];
  }

}
