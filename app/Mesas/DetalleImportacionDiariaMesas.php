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
                             'siglas_juego',
                             'nro_mesa',//es el nro_admin de la mesa
                             'droop',
                             'utilidad',
                             'reposiciones',
                             'retiros',
                             'saldo_fichas', //NO PUEDE SER NULL -> 0 POR DEFECTO
                             'utilidad_calculada',
                             'diferencia_cierre',//UTILIDAD IMPORTADA - UTILIDAD CALCULADA
                             'hold',
                           );
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
        return round($this->importacion_diaria_mesas->cotizacion * $this->utilidad,3);
      }else{
        return '--';
      }
  }
  public function importacion_diaria_mesas(){
    return $this->belongsTo('App\Mesas\ImportacionDiariaMesas','id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function juego_mesa(){
    $imp = $this->importacion_diaria_mesas;
    $id_casino = $imp->casino->id_casino;
    $fecha = $imp->fecha;
    $siglas_juego = $this->siglas_juego;
    
    $juego =  JuegoMesa::withTrashed()->where('juego_mesa.id_casino',$id_casino)
    ->where(function($q) use ($siglas_juego){
      return $q->where('juego_mesa.siglas','like',$siglas_juego)->orWhere('juego_mesa.nombre_juego','like',$siglas_juego);
    })
    ->where(function ($q) use ($fecha){
      return $q->whereNull('juego_mesa.deleted_at')->orWhere('juego_mesa.deleted_at','<',$fecha);
    })->first();
    return $juego;
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_importacion_diaria_mesas;
  }
}
