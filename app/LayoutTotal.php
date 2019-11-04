<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\LayoutTotalIsla;
use DB;

class LayoutTotal extends Model
{
  protected $connection = 'mysql';
  protected $table = 'layout_total';
  protected $primaryKey = 'id_layout_total';
  protected $visible = array('id_layout_total', 'nro_layout_total' ,'fecha','fecha_generacion','fecha_ejecucion', 'backup', 'id_casino','turno' ,'id_estado_relevamiento' , 'observacion_fiscalizacion' , 'observacion_validacion');
  public $timestamps = false;
  protected $appends = array('total_activas','total_inactivas');

  public function getTotalActivasAttribute(){
    $activas = DB::table('layout_total_isla')
    ->selectRaw('SUM(IFNULL(layout_total_isla.maquinas_observadas,0)) as total')
    ->where('layout_total_isla.id_layout_total','=',$this->id_layout_total)
    ->groupBy('layout_total_isla.id_layout_total')
    ->get()->first();
    return is_null($activas)? 0 : $activas->total;
  }

  public function getTotalInactivasAttribute(){
    $inactivas = DB::table('detalle_layout_total')
    ->selectRaw('COUNT(id_detalle_layout_total) as total')
    ->where('detalle_layout_total.id_layout_total','=',$this->id_layout_total)
    ->groupBy('detalle_layout_total.id_layout_total')
    ->get()->first();
    return is_null($inactivas)? 0 : $inactivas->total;
  }

  public function detalles(){
    return $this->hasMany('App\DetalleLayoutTotal','id_layout_total','id_layout_total');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');

  }

  public function estado_relevamiento(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function usuario_cargador(){
    return $this->belongsTo('App\Usuario','id_usuario_cargador','id_usuario');
  }

  public function usuario_fiscalizador(){
    return $this->belongsTo('App\Usuario','id_usuario_fiscalizador','id_usuario');
  }

  public function islas(){
    return $this->belongsToMany('App\Isla','layout_total_isla','id_layout_total','id_isla')->withPivot('maquinas_observadas');
  }
  
  public function observaciones_islas(){
    return $this->hasMany('App\LayoutTotalIsla','id_layout_total','id_layout_total');
  }

}
