<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RelevamientoAmbiental extends Model
{
  protected $connection = 'mysql';
  protected $table = 'relevamiento_ambiental';
  protected $primaryKey = 'id_relevamiento_ambiental';
  protected $visible = array(
    'id_relevamiento_ambiental',
    'nro_relevamiento_ambiental',
    'fecha_generacion',
    'fecha_ejecucion',
    'id_casino',
    'id_usuario_fiscalizador',
    'id_usuario_cargador',
    'id_estado_relevamiento',
    'id_tipo_relev_ambiental',
    'observacion_carga');
  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
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
    return $this->HasMany('App\DetalleRelevamientoAmbiental','id_relevamiento_ambiental','id_relevamiento_ambiental');
  }

  public function generalidades(){
    return $this->HasMany('App\DatoGeneralidad','id_relevamiento_ambiental','id_relevamiento_ambiental');
  }
}
