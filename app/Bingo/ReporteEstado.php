<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class ReporteEstado extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_reporte_estado';
  protected $primaryKey = 'id_reporte_estado';
  protected $visible = array('id_reporte_estado','fecha_sesion','importacion','relevamiento','sesion_cerrada','sesion_abierta','id_casino','visado','observaciones_visado');
  public $timestamps = false;

}
