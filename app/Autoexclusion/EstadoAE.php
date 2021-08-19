<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoAE extends Model
{
  use SoftDeletes;
  
  protected $connection = 'mysql';
  protected $table = 'ae_estado';
  protected $primaryKey = 'id_estado';
  protected $visible = array('id_estado','id_nombre_estado','id_casino','id_plataforma',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido','ultima_actualizacion_estado'
                              );
  protected $fillable = ['id_nombre_estado','id_casino','id_plataforma',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido'];

  public function ae(){
    return $this->belongsTo('App\Autoexclusion\Autoexcluido','id_autoexcluido','id_autoexcluido');
  }
  public function nombreEstado(){
    return $this->belongsTo('App\Autoexclusion\NombreEstadoAutoexclusion','id_nombre_estado','id_nombre_estado');
  }
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }
  public function plataforma(){
    return $this->belongsTo('App\Plataforma','id_plataforma','id_plataforma');
  }
}
