<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\LogMovimientoObserver;

class LogMovimiento extends Model
{

  protected $connection = 'mysql';
  protected $table = 'log_movimiento';
  protected $primaryKey = 'id_log_movimiento';

  protected $visible = array('id_log_movimiento','fecha','id_casino',
  'id_expediente','id_estado_movimiento','id_estado_relevamiento',
  'carga_finalizada', 'cant_maquinas', 'tipo_carga',
  'islas','sentido', 'nro_exp_org','nro_exp_interno','nro_exp_control','nro_disposicion','nro_disposicion_anio');

  public $timestamps = false;

  public function controladores(){
     return $this->belongsToMany('App\Usuario','controlador_movimiento','id_log_movimiento','id_controlador_movimiento');
  }
  public function estado_movimiento(){
    return $this->belongsTo('App\EstadoMovimiento','id_estado_movimiento','id_estado_movimiento');
  }
  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function nota(){
    return $this->hasOne('App\Nota','id_log_movimiento','id_log_movimiento');
  }
  public function relevamientos_movimientos(){
    return $this->hasMany('App\RelevamientoMovimiento', 'id_log_movimiento','id_log_movimiento');
  }

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }
  public function fiscalizaciones(){
     return $this->hasMany('App\FiscalizacionMov','id_log_movimiento','id_log_movimiento');
  }

  public function tipos_movimiento(){
    return $this->belongsToMany('App\TipoMovimiento','logmov_tipomov','id_log_movimiento','id_tipo_movimiento');
  }
  public function tipo_movimiento_str($sep = ', '){
    return implode($sep,$this->tipos_movimiento()->pluck('descripcion')->toArray());
  }
  public function es_intervencion_mtm(){
    $count = 0;
    $es_intervencion_mtm = true;
    foreach($this->tipos_movimiento as $t){
      //ERROR: tipo movimiento deprecado
      if($t->deprecado) return "deprecado";
      $es_intervencion_mtm = $es_intervencion_mtm && $t->es_intervencion_mtm;
      $count += 1;
    }
    //ERROR: no se puede tener mas de 1 tipo mov y no ser todos intervencion mtm
    if($count > 1 && !$es_intervencion_mtm) return "tipos_multiples_invalidos";
    if($this->sentido == '---' && $es_intervencion_mtm) return "intervencion_mtm_sin_sentido";
    return $es_intervencion_mtm;
  }

  public function log_clicks_movs(){
    return $this->hasMany('App\LogClicksMov', 'id_log_movimiento','id_log_movimiento');
  }


 public static function boot(){
        parent::boot();
        Disposicion::observe(new LogMovimientoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_log_movimiento;
  }

  public function relevamientosCompletados($intervencion_mtm,$estado = 3){
    $total = $this->relevamientos_movimientos()->count();
    $completados = $this->relevamientos_movimientos()
    ->where('relevamiento_movimiento.id_estado_relevamiento','=',$estado);
    if($intervencion_mtm){
      $completados = $completados->whereNull('relevamiento_movimiento.id_fiscalizacion_movimiento')->count();
    } 
    else{
      $completados = $completados->whereNotNull('relevamiento_movimiento.id_fiscalizacion_movimiento')->count();
    }
    return $total == $completados;
  }

}
