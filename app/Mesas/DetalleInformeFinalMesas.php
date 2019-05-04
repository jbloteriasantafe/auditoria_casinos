<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

//se van a crear 13, uno de los cuales va a repetir
//los valores 'cuota' son los montos/utilidades del mes pasados
//a sus valores en EUR/USD --
class DetalleInformeFinalMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_informe_final_mesas';
  protected $primaryKey = 'id_detalle_informe_final_mesas';
  protected $visible = array( 'id_detalle_informe_final_mesas',
                              'id_informe_final_mesas',
                              'total_pagado',
                              'impuestos',
                              'fecha_cobro',
                              'total_mes_anio_anterior',//utilidad
                              'total_mes_actual',//utilidad
                              'cotizacion_euro_anterior',
                              'cotizacion_dolar_actual',
                              'cotizacion_euro_actual',
                              'cotizacion_dolar_anterior',
                              'id_casino',
                              'id_mes_casino',
                              'cuota_dolar_actual',//*se calcula
                              'cuota_euro_actual',//*se calcula
                              'cuota_dolar_anterior',//*se calcula
                              'cuota_euro_anterior',//*se calcula
                              'variacion_euro',//*se calcula
                              'variacion_dolar',//*se calcula
                              'siglas_mes',
                              'nro_cuota'
                           );
  protected $appends = array('cuota_euro_actual','cuota_dolar_actual',
                            'cuota_euro_anterior','cuota_dolar_anterior',
                            'variacion_euro','variacion_dolar',
                            'siglas_mes','nro_cuota'
                          );
  public function getNroCuotaAttribute(){
    return $this->mes_casino->nro_cuota;
  }

  public function getSiglasMesAttribute(){
    return $this->mes_casino->siglas;
  }
  public function getCuotaDolarActualAttribute(){
    $div1 = $this->total_mes_actual/2;
    //dd($this->total_mes_actual,$div1);
    $div2 = $div1/$this->cotizacion_dolar_actual;
   return round($div2,2);
  }

  public function getCuotaEuroActualAttribute(){
   return round(($this->total_mes_actual/2)/$this->cotizacion_euro_actual,2);
  }

  public function getCuotaEuroAnteriorAttribute(){
    if($this->total_mes_anio_anterior != null && $this->total_mes_anio_anterior != 0){
      $div1 = $this->total_mes_anio_anterior/2;
      $div2 = $div1/$this->cotizacion_euro_anterior;
      return round($div2,2);
    }
   return 0;
  }

  public function getCuotaDolarAnteriorAttribute(){
    if($this->total_mes_anio_anterior != null && $this->total_mes_anio_anterior != 0){
      return round(($this->total_mes_anio_anterior/2)/$this->cotizacion_dolar_anterior,2);
    }
    return 0;
  }

  public function getVariacionEuroAttribute(){
    $variacion_total = 0;
    if($this->cuota_euro_anterior != 0 && $this->cuota_euro_actual != 0){
      $variacion_total = (($this->cuota_euro_actual/ $this->cuota_euro_anterior)*100)-100;
    }
    return round($variacion_total,2);
  }

  public function getVariacionDolarAttribute(){
    $variacion_total = 0;
    if($this->cuota_dolar_anterior != 0 && $this->cuota_dolar_actual != 0){
      $variacion_total = (($this->cuota_dolar_actual / $this->cuota_dolar_anterior)*100)-100;
    }
    return round($variacion_total,2);
  }

  public function informe_final_mesas(){
    return $this->belongsTo('App\Mesas\InformeFinalMesas','id_informe_final_mesas','id_informe_final_mesas');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function mes_casino(){
    return $this->belongsTo('App\MesCasino','id_mes_casino','id_mes_casino')->withTrashed();
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_informe_final_mesas;
  }
}
