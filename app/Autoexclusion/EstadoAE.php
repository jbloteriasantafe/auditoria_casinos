<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class EstadoAE extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_estado';
  protected $primaryKey = 'id_estado';
  protected $visible = array('id_estado','id_nombre_estado','id_casino',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido'
                              );
  protected $fillable = ['id_nombre_estado','id_casino',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido'];

  public $timestamps = false;

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
}
