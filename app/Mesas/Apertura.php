<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class Apertura extends Model
{
  protected $connection = 'mysql';
  protected $table = 'apertura_mesa';
  protected $primaryKey = 'id_apertura_mesa';
  protected $visible = array('id_apertura_mesa','fecha','hora','total_pesos_fichas_a',
                              'total_anticipos_a', 'id_fiscalizador',
                              'id_mesa_de_panio','id_estado_cierre','id_cargador'
                            );
  public $timestamps = false;


  protected $fillable = ['fecha','hora_inicio',
                              'hora_fin','total_pesos_fichas_c',
                              'total_anticipos_c', 'id_fiscalizador',
                              'id_tipo_cierre','id_mesa_de_panio',
                              'id_estado_cierre'];

  public function cierre_apertura(){
    return $this->hasOne('App\Mesas\CierreApertura','id_apertura_mesa','id_apertura_mesa');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\User','id_fiscalizador','id');
  }

  public function cargador(){
    return $this->belongsTo('App\User','id_cargador','id');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function estado_cierre(){
    return $this->belongsTo('App\Mesas\EstadoCierre','id_estado_cierre','id_estado_cierre');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleApertura','id_apertura_mesa','id_apertura_mesa');
  }


  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_apertura_mesa;
  }
}
