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
                              'total_peso',
                              'medio_total_euro',
                              'medio_total_dolar',
                          );

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleInformeFinalMesas','id_informe_final_mesas','id_informe_final_mesas');
  }

  public function informe_anterior(){
    $informe = InformeFinalMesas::where('id_casino','=',$this->id_casino)
    ->where('anio_inicio','<',$this->anio_inicio)
    ->orderBy('anio_inicio','desc')->orderBy('id_informe_final_mesas','desc')->first();
    return $informe;
  }

  public function informe_proximo(){
    $informe = InformeFinalMesas::where('id_casino','=',$this->id_casino)
    ->where('anio_inicio','>',$this->anio_inicio)
    ->orderBy('anio_inicio','asc')->orderBy('id_informe_final_mesas','asc')->first();
    return $informe;
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_informe_final_mesas;
  }
}
