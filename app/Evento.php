<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'evento';
  protected $primaryKey = 'id_evento';
  protected $visible = array('id_evento','fecha_inicio','fecha_fin',
  'hora_inicio','hora_fin','titulo','descripcion','id_casino','id_tipo_evento',
  'realizado');

  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function tipo_evento(){
      return $this->belongsTo('App\TipoEvento','id_tipo_evento','id_tipo_evento');
  }

  public function fiscalizacion(){
      return $this->belongsTo('App\FiscalizacionMov','id_fiscalizacion_movimiento','id_fiscalizacion_movimiento');
  }

  public function desde(){
    return $this->hora_inicio;
  }

  public function hasta(){
    return $this->hora_fin;
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_evento;
  }

}
