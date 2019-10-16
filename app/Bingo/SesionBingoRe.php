<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class SesionBingoRe extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_re_sesion';
  protected $primaryKey = 'id_sesion_re';
  protected $visible = array('id_sesion_re','id_sesion','fecha_inicio','hora_inicio',
                              'id_usuario_inicio','id_usuario_fin','pozo_dotacion_inicial', 'pozo_dotacion_final',
                              'pozo_extra_inicial', 'pozo_extra_final',
                              'hora_fin','fecha_fin','fecha_re'
                              );
  protected $fillable = ['fecha_inicio','hora_inicio',
                        'id_usuario_inicio','id_usuario_fin','id_casino',
                        'id_estado', 'pozo_dotacion_inicial', 'pozo_dotacion_final',
                        'pozo_extra_inicial', 'pozo_extra_final','hora_fin','fecha_fin'];

  public $timestamps = false;

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  public function usuarioInicio(){
    return $this->belongsTo('App\Usuario','id_usuario_inicio','id_usuario');
  }
  public function usuarioFin(){
    return $this->belongsTo('App\Usuario','id_usuario_fin','id_usuario');
  }
  public function estadoSesion(){
    return $this->belongsTo('App\Bingo\EstadoSesionBingo','id_estado','id_estado_sesion');
  }
  public function detallesSesion(){
    return $this->hasMany('App\Bingo\DetalleSesionBingoRe','id_sesion_re','id_sesion_re');
  }
  public function sesionBingo(){
    return $this->belongsTo('App\Bingo\SesionBingo','id_sesion_re','id_sesion');
  }

}
