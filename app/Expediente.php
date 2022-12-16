<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpedienteObserver extends Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('nro_exp_org','nro_exp_interno','nro_exp_control');
  }
}

class Expediente extends Model
{
  protected $connection = 'mysql';
  protected $table = 'expediente';
  protected $primaryKey = 'id_expediente';
  protected $visible = array('id_expediente', 'nro_exp_org','nro_exp_interno','nro_exp_control','fecha_iniciacion','iniciador','concepto','ubicacion_fisica','fecha_pase','remitente','destino','nro_folios','tema','anexo','nro_cuerpos','concatenacion');
  protected $appends = array('concatenacion');
  public $timestamps = false;

  public function casinos(){
    return $this->belongsToMany('App\Casino','expediente_tiene_casino','id_expediente','id_casino');
  }
  public function resoluciones(){
    return $this->hasMany('App\Resolucion','id_expediente','id_expediente');
  }
  public function disposiciones(){
    return $this->hasMany('App\Disposicion','id_expediente','id_expediente');
  }
  public function log_movimientos(){
    return $this->hasMany('App\LogMovimiento','id_expediente','id_expediente');
  }
  public function maquinas(){
     return $this->belongsToMany('App\Maquina','maquina_tiene_expediente','id_expediente','id_maquina');
  }

  public function gli_hards(){
     return $this->belongsToMany('App\GliHard','expediente_tiene_gli_hard','id_expediente','id_gli_hard');
  }

  public function gli_softs(){
     return $this->belongsToMany('App\GliSoft','expediente_tiene_gli_sw','id_expediente','id_gli_soft');
  }

  public function notas(){
    return $this->HasMany('App\Nota' , 'id_expediente', 'id_expediente');
  }

  public function tipo_movimiento(){
    return $this->belongsTo('App\TipoMovimiento', 'id_tipo_movimiento', 'id_tipo_movimiento');
  }

  public function getConcatenacionAttribute(){
    return $this->nro_exp_org . '-' . $this->nro_exp_interno . '-' . $this->nro_exp_control;
  }

  public static function boot(){
        parent::boot();
        Expediente::observe(new ExpedienteObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_expediente;
  }

}
