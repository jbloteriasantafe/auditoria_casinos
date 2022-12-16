<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JuegoMesaObserver extends \App\Observers\EntityObserver
{
   public function creating(JuegoMesa $model)
   {
     $model->nombre_juego = strtoupper($model->nombre_juego);
     $model->siglas = strtoupper($model->siglas);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('nombre_juego', $entidad->nombre_juego),
       array('siglas', $entidad->siglas),
     );
     return $detalles;
   }
}

class JuegoMesa extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'juego_mesa';
  protected $primaryKey = 'id_juego_mesa';
  protected $visible = array('id_juego_mesa','id_tipo_mesa','nombre_juego',
                             'siglas','id_casino','posiciones');
  protected $fillable = ['id_tipo_mesa','nombre_juego',
                         'siglas','id_casino','posiciones'];

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_juego_mesa','id_juego_mesa');
  }
  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_juego_mesa;
  }
  public static function boot(){
    parent::boot();
    JuegoMesa::observe(new JuegoMesaObserver());
  }
}
