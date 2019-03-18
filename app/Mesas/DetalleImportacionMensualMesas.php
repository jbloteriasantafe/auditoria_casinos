<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class DetalleImportacionMensualMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_importacion_mensual_mesas';
  protected $primaryKey = 'id_detalle_importacion_mensual_mesas';
  protected $visible = array('id_detalle_importacion_mensual_mesas',
                             'id_importacion_mensual_mesas',
                             'fecha_dia',
                             'total_diario',
                             'utilidad',
                             'droop',
                             'hold',
                             'cotizacion',
                             'retiros_dia',
                             'reposiciones_dia',
                             'utilidad_calculada_dia',
                             'saldo_fichas_dia',
                             'diferencias',
                             'conversion'
                           );

  protected $appends = array('hold','conversion');

  public function getHoldAttribute(){
       if($this->droop != 0){
         return round(($this->utilidad * 100)/$this->total_diario,2);
       }else{
         return '--';
       }

  }

  public function getConversionAttribute(){
      if($this->cotizacion != 0 && $this->cotizacion != null){
        return round($this->cotizacion * $this->utilidad,3);
      }else{
        return '--';
      }

  }


  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function importacion_mensual_mesas(){
    return $this->belongsTo('App\Mesas\ImportacionMensualMesas','id_importacion_mensual_mesas','id_importacion_mensual_mesas');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_importacion_mensual_mesas;
  }
}
