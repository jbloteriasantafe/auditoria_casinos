<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InformeFinalMesas extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'informe_final_mesas';
  protected $primaryKey = 'id_informe_final_mesas';
  protected $visible = array('id_informe_final_mesas',
                              'id_casino',
                              'anio_inicio',
                              'anio_final',
                              'base_anterior_dolar',
                              'base_anterior_euro',
                              'base_actual_dolar',
                              'base_actual_euro',
                              'base_cobrado_dolar',
                              'base_cobrado_euro',
                              'utilidades_actual',/*calculado*/
                              'utilidades_anterior',/*calculado*/
                              'monto_anterior_dolar',/*calculado*/
                              'monto_anterior_euro',/*calculado*/
                              'monto_actual_euro',/*calculado*/
                              'monto_actual_dolar',/*calculado*/
                              'variacion_total_euro',/*calculado*/
                              'variacion_total_dolar',/*calculado*/
                              'total_peso',
                              'medio_total_euro',
                              'medio_total_dolar',
                          );

  protected $appends = array('monto_anterior_dolar','monto_anterior_euro',
                              'monto_actual_euro','monto_actual_dolar',
                              'variacion_total_euro','variacion_total_dolar',
                              'utilidades_actual','utilidades_anterior'
                            );

  public function getMontoAnteriorDolarAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->cuota_dolar_anterior;
    }
    return round($total,2);
  }

  public function getMontoAnteriorEuroAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->cuota_euro_anterior;
    }
    return round($total,2);
  }
  public function getMontoActualDolarAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->cuota_dolar_actual;
    }
    return round($total,2);
  }

  public function getMontoActualEuroAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->cuota_euro_actual;
    }
    return round($total,2);
  }

  public function getVariacionTotalEuroAttribute(){
    $variacion_total = 0;
    if($this->monto_anterior_euro != 0 && $this->monto_actual_euro != 0){
      $variacion_total = (($this->monto_actual_euro / $this->monto_anterior_euro)*100)-100;
    }
    return round($variacion_total,2);
  }

  public function getVariacionTotalDolarAttribute(){
    $variacion_total = 0;
    if($this->monto_anterior_dolar != 0 && $this->monto_actual_dolar != 0){
      $variacion_total = (($this->monto_actual_dolar / $this->monto_anterior_dolar)*100)-100;
    }
    return round($variacion_total,2);
  }

  public function getUtilidadesActualAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->bruto_peso;
    }
    return round($total,2);
  }

  public function getUtilidadesAnteriorAttribute(){
    $total = 0;
    foreach ($this->detalles as $pago) {
      $total +=$pago->total_mes_anio_anterior;
    }
    return round($total,2);
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleInformeFinalMesas','id_informe_final_mesas','id_informe_final_mesas');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_informe_final_mesas;
  }


}
