<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RelevamientoApuestas extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'relevamiento_apuestas_mesas';
  protected $primaryKey = 'id_relevamiento_apuestas';
  protected $visible = array('id_relevamiento_apuestas','fecha','hora_ejecucion',
                              'hora_propuesta','id_turno', 'id_fiscalizador',
                              'id_cargador','id_casino','id_estado_relevamiento',
                              'observaciones_validacion','observaciones','es_backup',
                              'nro_turno','created_at','deleted_at'
                            );
  public $timestamps = false;


  protected $fillable = ['fecha','hora_ejecucion',
                         'hora_propuesta','id_turno', 'id_fiscalizador',
                         'id_cargador','id_casino'];



  public function turno(){
    return $this->belongsTo('App\Turno','id_turno','id_turno');
  }


  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fiscalizador','id_usuario');
  }

  public function cargador(){
    return $this->belongsTo('App\Usuario','id_cargador','id_usuario');
  }
  public function controlador(){
    return $this->belongsTo('App\Usuario','id_controlador','id_usuario');
  }
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleRelevamientoApuestas','id_relevamiento_apuestas','id_relevamiento_apuestas');
  }

  public function estado(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_relevamiento_apuestas;
  }
}
