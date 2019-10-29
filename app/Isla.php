<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\IslaObserver;
use App\Maquina;
use Illuminate\Database\Eloquent\SoftDeletes;


class Isla extends Model
{
  use SoftDeletes;

  protected $connection = 'mysql';
  protected $table = 'isla';
  protected $primaryKey = 'id_isla';
  protected $visible = array('id_isla','nro_isla','codigo','cantidad_maquinas','id_casino','deleted_at');
  public $timestamps = false;
  protected $appends = array('cantidad_maquinas');

  // Obtiene todas las mtm ACTIVAS
  public function getCantidadMaquinasAttribute(){
      if($this->deleted_at == null){
      return Maquina::where('id_isla','=',$this->id_isla)->whereIn('id_estado_maquina',[1,2,7])->whereNull('deleted_at')->count();
    }else{
      return 0;
    }
  }

  public function maquinas(){
    return $this->HasMany('App\Maquina','id_isla','id_isla');
  }

  public function sector(){
    return $this->belongsTo('App\Sector','id_sector','id_sector');
  }

  public function detalles_relevamientos_producidos(){
    return $this->hasMany('App\DetalleRelevamientoProgresivo','id_isla','id_isla');
  }

  public function movimientos_isla(){
    return $this->HasMany('App\MovimientoIsla','id_isla','id_isla');
  }

  public function logs_isla(){
    return $this->HasMany('App\LogIsla','id_isla','id_isla');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function layouts_totales(){
    return $this->belongsToMany('App\LayoutTotal','layout_total_isla','id_isla','id_layout_total')->withPivot('maquinas_observadas');
  }

  public function observaciones_layout_total(){
    return $this->hasMany('App\LayoutTotalIsla','id_isla','id_isla');
  }

  public static function boot(){
        parent::boot();
        Isla::observe(new IslaObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_isla;
  }

  // public function __construct($nro_isla,$codigo,$id_casino){
  //   $this->nro_isla = $nro_isla;
  //   $this->codigo = $codigo;
  //   $this->casino()->associate($id_casino);
  //   return $this;
  // }

}
