<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Sector extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'sector';
  protected $primaryKey = 'id_sector';
  protected $visible = array('id_sector','descripcion','id_casino','cantidad_maquinas','deleted_at');
  public $timestamps = false;
  protected $appends = array('cantidad_maquinas');

  public function getCantidadMaquinasAttribute()
  {
      $res = 0;
      foreach($this->islas as $isla){
          $res = $res + $isla->cantidad_maquinas;
      }
      return $res;
  }

  public function islas(){
    return $this->HasMany('App\Isla','id_sector','id_sector')->orderBy('nro_isla');
  }

  public function cantidad_maquinas_por_relevamiento(){
    return $this->HasMany('App\CantidadMaquinasPorRelevamiento','id_sector','id_sector');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function relevamientos(){
    return $this->HasMany('App\Relevamiento','id_sector','id_sector');
  }

  public function relevamientos_progresivos(){
    return $this->hasMany('App\RelevamientoProgresivo','id_relevamiento_progresivo','id_relevamiento_progresivo');
  }

  public function layouts_parcial(){
    return $this->HasMany('App\LayoutParcial','id_sector','id_sector');
  }

  public static function boot(){
        parent::boot();
        Sector::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_sector;
  }

}
