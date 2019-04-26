<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class MinApInforme extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'informe_fiscalizacion_tiene_valor_minimo';
  protected $primaryKey = 'id_informe_fiscalizacion_tiene_valor_minimo';
  protected $visible = array('id_informe_fiscalizacion_tiene_valor_minimo',
                              'id_apuesta_minima_juego',
                              'id_informe_fiscalizadores',
                              'cantidad_cumplieron',
                              'created_at',
                              'updated_at',
                              'deleted_at',
                              'moneda',
                              'cantidad',
                              'valor_minimo',
                              'nombre_juego'
                          );
  protected $fillable = ['id_apuesta_minima_juego',
  'id_informe_fiscalizadores',
  'cantidad_cumplieron'];
  protected $appends = array('moneda','cantidad','valor_minimo',
  'nombre_juego');

  public function getMonedaAttribute(){
    return $this->apuesta_minima_juego->moneda->descripcion;
  }

  public function getCantidadAttribute(){
    return $this->apuesta_minima_juego->cantidad_requerida;
  }

  public function getValorMinimoAttribute(){
    return $this->apuesta_minima_juego->apuesta_minima;
  }

  public function getNombreJuegoAttribute(){
    return $this->apuesta_minima_juego->juego->nombre_juego;
  }

  public function apuesta_minima_juego(){
    return $this->belongsTo('App\Mesas\ApuestaMinimaJuego','id_apuesta_minima_juego','id_apuesta_minima_juego');
  }

  public function informe_fiscalizadores(){
    return $this->belongsTo('App\Mesas\InformeFiscalizadores','id_informe_fiscalizadores','id_informe_fiscalizadores');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_informe_fiscalizacion_tiene_valor_minimo;
  }
}
