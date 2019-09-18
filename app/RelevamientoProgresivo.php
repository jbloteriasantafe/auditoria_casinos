<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RelevamientoProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'relevamiento_progresivo';
  protected $primaryKey = 'id_relevamiento_progresivo';
  protected $visible = array(
  'id_relevamiento_progresivo',
  'nro_relevamiento_progresivo',
  'subrelevamiento',
  'id_usuario_cargador',
  'id_usuario_fiscalizador',
  'backup',
  'fecha_generacion',
  'fecha_ejecucion',
  'fecha_carga',
  'observacion_carga',
  'observacion_validacion',
  'id_estado_relevamiento');
  public $timestamps = false;

  public function sector(){
    return $this->belongsTo('App\Sector','id_sector','id_sector');
  }

  public function usuario_cargador(){
    return $this->belongsTo('App\Usuario','id_usuario_cargador','id_usuario');
  }

  public function usuario_fiscalizador(){
    return $this->belongsTo('App\Usuario','id_usuario_fiscalizador','id_usuario');
  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleRelevamientoProgresivo','id_relevamiento_progresivo','id_relevamiento_progresivo');
  }

}
