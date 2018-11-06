<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cierre extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'cierre_mesa';
  protected $primaryKey = 'id_cierre_mesa';
  protected $visible = array('id_cierre_mesa','fecha','hora_inicio',
                              'hora_fin','total_pesos_fichas_c',
                              'total_anticipos_c', 'id_fiscalizador',
                              'id_mesa_de_panio','id_estado_cierre'
                            );
  public $timestamps = false;

  protected $fillable = ['fecha','hora_inicio',
                              'hora_fin','total_pesos_fichas_c',
                              'total_anticipos_c', 'id_fiscalizador',
                              'id_tipo_cierre','id_mesa_de_panio',
                              'id_estado_cierre'];


  public function cierre_apertura(){
    return $this->hasOne('App\Mesas\CierreApertura','id_cierre_mesa','id_cierre_mesa');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fiscalizador','id_usuario');
  }

  public function estado_cierre(){
    return $this->belongsTo('App\Mesas\EstadoCierre','id_estado_cierre','id_estado_cierre');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleCierre','id_cierre_mesa','id_cierre_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_cierre_mesa;
  }
}
