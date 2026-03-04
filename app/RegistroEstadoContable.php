<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroEstadoContable extends Model
{
  protected $connection = 'mysql';
  protected $table = 'registroEstadoContable';
  protected $primaryKey = 'id_registroEstadoContable';
  protected $visible = array('id_registroEstadoContable', 'fecha_EstadoContable', 'casino', 'usuario',
                             'activo_corriente', 'activo_corriente_reexpresado',
                             'activo_nocorriente', 'activo_nocorriente_reexpresado',
                             'pasivo_corriente', 'pasivo_corriente_reexpresado',
                             'pasivo_nocorriente', 'pasivo_nocorriente_reexpresado',
                             'ingresos', 'ingresos_reexpresado',
                             'costos', 'costos_reexpresado',
                             'gastos_comercio', 'gastos_comercio_reexpresado',
                             'gastos_adm', 'gastos_adm_reexpresado',
                             'recpam', 'recpam_reexpresado',
                             'otros', 'otros_reexpresado',
                             'imp_ganancias', 'imp_ganancias_reexpresado',
                             'fecha_toma', 'valido');
  public $timestamps = false;
  protected $appends = array('archivos_count');

  public function casinoEstadoContable(){
    return $this->belongsTo('App\Casino','casino','id_casino');
  }

  public function getArchivosCountAttribute(){
    return $this->archivos()->count();
  }

  public function archivos(){
    return $this->morphMany('App\Registro_archivo', 'fileable', 'fileable_type', 'fileable_id');
  }
}
