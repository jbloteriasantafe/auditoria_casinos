<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class DetalleRelevamientoApuestas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento_apuestas';
  protected $primaryKey = 'id_detalle_relevamiento_apuestas';

  protected $visible = array('id_detalle_relevamiento_apuestas','id_tipo_mesa',
                              'nro_mesa','nombre_juego','posiciones',
                              'minimo','maximo','codigo_mesa','id_estado_mesa',
                              'id_relevamiento_apuestas','id_mesa_de_panio',
                              'id_moneda','multimoneda','descripcion'
                            );
  public $timestamps = false;

  protected $appends = array('descripcion');

  public function getDescripcionAttribute(){
    if(isset($this->moneda)){
      return $this->moneda->descripcion;
    }
    else{
      return null;
    }
  }


  protected $fillable = ['id_detalle_relevamiento_apuestas','id_tipo_mesa',
                              'nro_mesa','nombre_juego', 'id_estado_mesa',
                              'posiciones','minimo','maximo','codigo_mesa',
                              'id_juego_mesa','id_relevamiento_apuestas','id_mesa_de_panio'];



  public function relevamiento(){
    return $this->belongsTo('App\Mesas\RelevamientoApuestas','id_relevamiento_apuestas','id_relevamiento_apuestas');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function estado(){
    return $this->belongsTo('App\Mesas\EstadoMesa','id_estado_mesa','id_estado_mesa');
  }

  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
  }

  public function juego(){
    return $this->belongsTo('App\Mesas\JuegoMesa','id_juego_mesa','id_juego_mesa');
  }

  public function moneda(){
    return $this->belongsto('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_relevamiento_apuestas;
  }
}
