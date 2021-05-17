<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleInformeFinalMesas extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'detalle_informe_final_mesas';
  protected $primaryKey = 'id_detalle_informe_final_mesas';
  protected $visible = array( 'id_detalle_informe_final_mesas',
                              'id_casino',
                              'dia_inicio',
                              'dia_fin',
                              'mes',
                              'anio',
                              'fecha_pago',
                              'fecha_cotizacion',
                              'id_informe_final_mesas',
                              'bruto_peso',
                              'medio_bruto_euro',
                              'medio_bruto_dolar',
                              'cotizacion_dolar_actual',
                              'cotizacion_euro_actual',
                              'total_peso',
                              'medio_total_euro',
                              'medio_total_dolar',
                           );
                           
  public function informe_final_mesas(){
    return $this->belongsTo('App\Mesas\InformeFinalMesas','id_informe_final_mesas','id_informe_final_mesas');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_informe_final_mesas;
  }
}
