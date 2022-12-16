<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonedaObserver extends \App\Observers\EntityObserver
{
   public function creating(Moneda $model)
   {
     $model->descripcion = strtoupper($model->descripcion);
     $model->siglas = strtoupper($model->siglas);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('descripcion', $entidad->descripcion),
       array('siglas', $entidad->siglas),
     );
     return $detalles;
   }
}

class Moneda extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'moneda';
  protected $primaryKey = 'id_moneda';
  protected $visible = array('id_moneda','descripcion','siglas');

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_moneda','id_moneda');
  }

  public function fichas(){
    return $this->hasMany('App\Mesas\Ficha','id_moneda','id_moneda')->orderBy('ficha.valor_ficha','desc');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_moneda;
  }
  public static function boot(){
    parent::boot();
    Moneda::observe(new MonedaObserver());
  }
}
