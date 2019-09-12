<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class ReporteEstado extends Model
{
  protected $connection = 'mysql';
  protected $table = 'reporte_estado_bingo';
  protected $primaryKey = 'id_reporte_estado';
  protected $visible = array('id_reporte_estado','fecha_sesion','importacion','relevamiento','sesion_cerrada','id_casino');
  public $timestamps = false;

}
