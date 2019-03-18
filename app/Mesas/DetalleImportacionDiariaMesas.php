<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class DetalleImportacionDiariaMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_importacion_diaria_mesas';
  protected $primaryKey = 'id_detalle_importacion_diaria_mesas';
  protected $visible = array('id_detalle_importacion_diaria_mesas',
                             'id_importacion_diaria_mesas',
                             'id_mesa_de_panio',
                             'id_moneda',
                             'fecha',
                             'utilidad',
                             'droop',
                             'id_juego_mesa',
                             'nro_mesa',//es el nro_admin de la mesa
                             'nombre_juego',
                             'codigo_moneda',
                             'diferencia_cierre',//UTILIDAD IMPORTADA - UTILIDAD CALCULADA
                             'reposiciones',
                             'retiros',
                             'tipo_mesa',
                             'hold',
                             'utilidad_calculada',
                             'id_cierre_mesa',
                             'id_ultimo_cierre',
                             'saldo_fichas',
                             'cotizacion',
                             'conversion'
                           );

                           //win es utilidad y drop es el total que tuvo la mesas
  protected $appends = array('hold','conversion');

  public function getHoldAttribute(){
      if($this->droop != 0){
        return round(($this->utilidad * 100)/$this->droop,2);
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

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function juego(){
    return $this->belongsTo('App\Mesas\JuegoMesa','id_juego_mesa','id_juego_mesa');
  }

  public function importacion_diaria_mesas(){
    return $this->belongsTo('App\Mesas\ImportacionDiariaMesas','id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function cierre(){
    return $this->belongsTo('App\Mesas\Cierre','id_cierre_mesa','id_cierre_mesa');
  }

  public function cierre_anterior(){
    return $this->belongsTo('App\Mesas\Cierre','id_ultimo_cierre','id_cierre_mesa');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_importacion_diaria_mesas;
  }
}
