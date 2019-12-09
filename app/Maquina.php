<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\MaquinaObserver;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maquina extends Model
{

  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'maquina';
  protected $primaryKey = 'id_maquina';
  protected $visible = array('id_maquina','nro_admin','marca','id_casino','marca_juego' ,'modelo','desc_marca','id_unidad_medida','nro_serie','mac','juega_progresivo','denominacion','id_isla','id_formula','id_gli_hard','id_gli_soft','id_tipo_maquina','id_tipo_gabinete','created_at','updated_at','deleted_at','id_tabla_pago','id_juego','id_estado_maquina','porcentaje_devolucion','id_formula','id_pack');
  public $timestamps = true;
  protected $dates = ['deleted_at'];

  public function isla(){
    return $this->belongsTo('App\Isla','id_isla','id_isla');
  }
  public function nro_admin(){
    return $this->nro_admin;
  }
  //Deprecado, usar relacion ->juegos->glisofts o juego_activo->glisofts
  public function gliSoftOld(){
    return $this->belongsTo('App\GliSoft','id_gli_soft','id_gli_soft');
  }
  public function gliHard(){
    return $this->belongsTo('App\GliHard','id_gli_hard','id_gli_hard');
  }
  public function formula(){
    return $this->belongsTo('App\Formula','id_formula','id_formula');
  }
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function tipoMaquina(){
    return $this->belongsTo('App\TipoMaquina','id_tipo_maquina','id_tipo_maquina');
  }
  public function unidad_medida(){
    return $this->belongsTo('App\UnidadMedida','id_unidad_medida','id_unidad_medida');
  }
  public function tipoGabinete(){
    return $this->belongsTo('App\TipoGabinete','id_tipo_gabinete','id_tipo_gabinete');
  }

  public function tipoMoneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public function movimientos(){
      return $this->hasMany('App\Movimiento','id_maquina','id_maquina');
  }
  public function expedientes(){
     return $this->belongsToMany('App\Expediente','maquina_tiene_expediente','id_maquina','id_expediente');
  }
  public function eventualidades(){
     return $this->belongsToMany('App\Eventualidad','maquina_tiene_eventualidad','id_maquina','id_eventualidad');
  }
  public function notas(){
     return $this->belongsToMany('App\Nota','maquina_tiene_nota','id_maquina','id_nota');
  }
  public function juegos(){
        return $this->belongsToMany('App\Juego','maquina_tiene_juego','id_maquina','id_juego')->withPivot('denominacion' , 'porcentaje_devolucion','id_pack','habilitado');
        // return $this->belongsToMany('App\modelo a donde voy ','tabla intermedia','id donde estoy','id donde voy')->withPivot('denominacion', 'porcentaje_devolucion');
  }

  public function progresivos(){
    return $this->belongsToMany('App\Progresivo','maquina_tiene_progresivo','id_maquina','id_progresivo');
  }

  public function juego_activo(){
    return $this->belongsTo('App\Juego','id_juego','id_juego');
  }
  public function log_maquinas(){
    return $this->hasMany('App\LogMaquina','id_maquina','id_maquina');
  }
  public function estado_maquina(){
    return $this->belongsTo('App\EstadoMaquina','id_estado_maquina','id_estado_maquina');
  }
  public function pozo(){
    return $this->belongsTo('App\Pozo','id_pozo','id_pozo');
  }
  public function maquinas_a_pedido(){
      return $this->hasMany('App\MaquinaAPedido','id_maquina','id_maquina');
  }
  public function detalles_producido(){
      return $this->hasMany('App\DetalleProducido','id_maquina','id_maquina');
  }
  public function detalles_contadores_horarios(){
      return $this->hasMany('App\DetalleContadorHorario','id_maquina','id_maquina');
  }
  public function detalles_relevamientos(){
      return $this->hasMany('App\DetalleRelevamiento','id_maquina','id_maquina');
  }
  public function detalles_relevamientos_progresivos(){
    return $this->hasMany('App\DetalleRelevamientoProgresivo','id_maquina','id_maquina');
  }

  public function relevamiento_movimiento(){
    return $this->hasMany('App\RelevamientoMovimiento', 'id_maquina','id_maquina');
  }

  public function movimientos_islas(){
    return $this->HasMany('App\MovimientoIsla','id_maquina','id_maquina');
  }

  public function ajuste_temporal_producido(){
    return $this->HasMany('App\AjusteTemporalProducido','id_maquina','id_maquina');
  }


  public static function boot(){
    parent::boot();
    Maquina::observe(new MaquinaObserver());
  }
  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_maquina;
  }

  // obtenerPorcentajeDevolucion obtiene el porcentaje de devolucion del juego activo
  // sino existe devuelve vacio
  public function obtenerPorcentajeDevolucion(){

    $id_juego = $this->juego_activo->id_juego;
    $resultado = $this->juegos->where('id_juego' , $id_juego)->first();

    if(isset($resultado)){
      return  $resultado->pivot->porcentaje_devolucion;
    }
    return null;
  }

  // obtenerDenominacion obtiene la denominacion del juego activo o "" sino existe
  public function obtenerDenominacion(){

    $id_juego = $this->juego_activo->id_juego;
    $resultado = $this->juegos->where('id_juego' , $id_juego)->first();

    if(isset($resultado)){
      return  $resultado->pivot->denominacion;
    }
    return null;
  }

}
