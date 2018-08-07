<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\CasinoObserver;

class Casino extends Model
{
  protected $connection = 'mysql';
  protected $table = 'casino';
  protected $primaryKey = 'id_casino';
  protected $visible = array('id_casino','nombre','codigo');
  public $timestamps = false;

  public function unidades_medida(){
    return $this->belongsToMany('App\UnidadMedida','casino_tiene_unidad_medida','id_casino','id_unidad_medida');
  }
  public function usuarios(){
    return $this->belongsToMany('App\Usuario','usuario_tiene_casino','id_casino','id_usuario');
  }
  public function expedientes(){
    return $this->belongsToMany('App\Casino','expediente_tiene_casino','id_casino','id_expediente');
  }
  public function logs_movimientos(){
    return $this->hasMany('App\LogMovimiento','id_casino','id_casino');
  }
  public function eventualidades(){
    return $this->hasMany('App\Eventualidad','id_casino','id_casino');
  }
  public function eventos(){
    return $this->hasMany('App\Evento','id_casino','id_casino');
  }
  public function notas(){
    return $this->hasMany('App\Nota','id_casino','id_casino');
  }
  public function gliSofts(){
    return $this->belongsToMany('App\GliSoft', 'casino_tiene_gli_soft', 'id_casino' , 'id_gli_soft');
  }
  public function gliHards(){
    return $this->belongsToMany('App\GliHard', 'casino_tiene_gli_hard', 'id_casino' , 'id_gli_hard');
  }
  public function sectores(){
    return $this->HasMany('App\Sector','id_casino','id_casino');
  }
  public function beneficios(){
    return $this->HasMany('App\Beneficio','id_casino','id_casino');
  }
  public function producidos(){
    return $this->HasMany('App\Producido','id_casino','id_casino');
  }
  public function contadores_horarios(){
    return $this->HasMany('App\ContadorHorario','id_casino','id_casino');
  }

  public static function boot(){
        parent::boot();
        Casino::observe(new CasinoObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_casino;
  }

}
