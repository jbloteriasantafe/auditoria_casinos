<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\ProducidoObserver;

class Producido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'producido';
  protected $primaryKey = 'id_producido';
  protected $visible = array('id_producido','fecha','validado','beneficio_calculado','id_tipo_moneda','apuesta','premio','valor','md5');
  public $timestamps = false;
  protected $appends = array('beneficio_calculado');

  public function getBeneficioCalculadoAttribute(){
    $beneficio = Beneficio::where([['fecha',$this->fecha],['id_tipo_moneda',$this->id_tipo_moneda],['id_casino',$this->id_casino]])->first();
    if($beneficio!=""){
      $ajuste = ($beneficio->ajuste_beneficio != null) ? $beneficio->ajuste_beneficio->valor : 0;
    }else{
      $ajuste =0;
    }
    return DetalleProducido::where('id_producido','=',$this->id_producido)->sum('valor') + $ajuste;
  }

  public function recalcular($col){
    return DetalleProducido::where('id_producido','=',$this->id_producido)->sum($col);
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function ajustes_producido(){
    return $this->hasManyThrough('App\AjusteProducido','App\DetalleProducido','id_producido','id_detalle_producido');
  }

  public function detalles(){
    return $this->HasMany('App\DetalleProducido','id_producido','id_producido');
  }
  public function tipo_moneda(){
    return $this->belongsTo('App\TipoMoneda','id_tipo_moneda','id_tipo_moneda');
  }

  public static function boot(){
    parent::boot();
    Producido::observe(new ProducidoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_producido;
  }


}
