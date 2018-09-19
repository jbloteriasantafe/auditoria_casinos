<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\RelevamientoObserver;

class Relevamiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'relevamiento';
  protected $primaryKey = 'id_relevamiento';
  protected $visible = array('id_relevamiento','nro_relevamiento','subrelevamiento',
  'backup','fecha','fecha_generacion','fecha_ejecucion','fecha_carga','tecnico',
  'observacion_carga','observacion_validacion','truncadas','mtms_habilitadas_hoy');
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
    return $this->HasMany('App\DetalleRelevamiento','id_relevamiento','id_relevamiento');
  }

  public function mtm_a_pedido(){
    return $this->hasMany('App\MaquinaAPedido','id_relevamiento','id_relevamiento');
  }

  public static function boot(){
    parent::boot();
    Relevamiento::observe(new RelevamientoObserver());
  }

  public function delete(){
    foreach($this->detalles as $detalle){
      $detalle->delete();
    }
    parent::delete();
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_relevamiento;
  }

}
