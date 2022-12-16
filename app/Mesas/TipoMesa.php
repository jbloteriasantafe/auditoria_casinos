<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoMesaObserver extends \App\Observers\EntityObserver
{
   public function creating(TipoMesa $model)
   {
      $model->descripcion = strtoupper($model->descripcion);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('descripcion', $entidad->descripcion),
     );
     return $detalles;
   }
}

class TipoMesa extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'tipo_mesa';
  protected $primaryKey = 'id_tipo_mesa';
  protected $visible = array('id_tipo_mesa','descripcion');

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_juego_mesa','id_juego_mesa');
  }
  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_tipo_mesa;
  }
  public static function boot(){
    parent::boot();
    TipoMesa::observe(new TipoMesaObserver());
  }
}
