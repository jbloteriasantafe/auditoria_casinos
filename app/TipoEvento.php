<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoEvento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'tipo_evento';
  protected $primaryKey = 'id_tipo_evento';
  protected $visible = array('id_tipo_evento','descripcion','color_text','color_back');
  public $timestamps = false;

  public function eventualidades(){
    return $this->hasMany('App\Evento','id_tipo_evento','id_tipo_evento');
  }

  public static function boot(){
        parent::boot();

  }

  public function fondo(){
    return $this->color_back;
  }

  public function texto(){
    return $this->color_text;
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_tipo_evento;
  }

}
