<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

//Necesito un Observer particular para que no logee en cada
//actualización de estado automatica al verificar vencimientos
//dado que estas no son modificaciones echas por el usuario
class EstadoAEObserver extends \App\Observers\FullObserver {
  public function updating($entidad){
    if($entidad->no_logear_update ?? false){
      return;
    }
    $this->guardarLog($entidad,'Modificación');
  }
}

class EstadoAE extends Model
{
  use SoftDeletes;
  
  protected $connection = 'mysql';
  protected $table = 'ae_estado';
  protected $primaryKey = 'id_estado';
  protected $visible = array('id_estado','id_nombre_estado','id_casino','id_plataforma',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido','ultima_actualizacion_estado',
                              'papel_destruido_id_usuario','papel_destruido_datetime'
                              );
  protected $fillable = ['id_nombre_estado','id_casino','id_plataforma',
                              'fecha_ae','fecha_vencimiento',
                              'fecha_renovacion', 'fecha_cierre_ae', 'fecha_revocacion_ae',
                              'id_usuario',  'id_autoexcluido',
                              'papel_destruido_id_usuario','papel_destruido_datetime'
                              ];
                              
  public $no_logear_update = false;

  public static function boot(){
    parent::boot();
    self::observe(new EstadoAEObserver());
  }
  
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->{$this->primaryKey};
  }
  
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
