<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use \DateTime;
class ImportacionMensualMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_mensual_mesas';
  protected $primaryKey = 'id_importacion_mensual_mesas';
  protected $visible = array('id_importacion_mensual_mesas',
                             'fecha_mes',
                             'nombre_csv',
                             'id_casino',
                             'id_moneda',
                             'total_drop_mensual',
                             'cotizacion_dolar',
                             'cotizacion_euro',
                             'diferencias',
                             'validado',
                             'observacion',
                             'mes',
                             'utilidad_calculada',
                             'retiros_mes',
                             'reposiciones_mes',
                             'saldo_fichas_mes',
                             'total_utilidad_mensual',
                             'conversion_total',
                             'descripcion',
                             'nombre',
                             'hold'
                           );
 protected $appends = array('mes','conversion_total','descripcion', 'nombre','hold');

 public function getMesAttribute(){

     if($this->fecha_mes != null){
       setlocale(LC_ALL, 'es_ES.UTF-8');
       $explode = explode('-',$this->fecha_mes);
       $monthNum  = $explode[1];
       $dateObj   = DateTime::createFromFormat('!m', $monthNum);
       $monthName = strftime('%B', $dateObj->getTimestamp());
       return strtoupper($monthName).' - '.$explode[0];
     }else{
       return '--';
     }

  }
  public function getHoldAttribute(){
     if($this->total_drop_mensual != 0){
       return round(($this->total_utilidad_mensual * 100)/$this->total_drop_mensual,2);
     }else{
       return '--';
     }

  }

  public function getNombreAttribute(){
    return $this->casino->nombre;
  }

  public function getDescripcionAttribute(){
    return $this->moneda->descripcion;
  }

  public function getConversionTotalAttribute(){
     $detalles = $this->detalles;
     $total = 0;
     foreach ($detalles as $d) {
      if($d->conversion != '--'){
         $total+= $d->conversion;
      }
     }
     return $total;
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleImportacionMensualMesas','id_importacion_mensual_mesas','id_importacion_mensual_mesas');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_importacion_mensual_mesas;
  }
}
