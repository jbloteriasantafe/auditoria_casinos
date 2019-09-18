<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class PartidaBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_partida';
  protected $primaryKey = 'id_partida';
  protected $visible = array('id_partida','id_usuario','id_sesion',
                              'num_partida','hora_inicio','valor_carton',
                              'serie_inicio', 'serie_fin', 'carton_inicio_i',
                              'carton_fin_i', 'carton_inicio_f', 'carton_inicio_f',
                              'cartones_vendidos', 'bola_linea', 'bola_bingo',
                              'premio_linea', 'premio_bingo', 'pozo_dot',
                              'pozo_extra','carton_fin_f'
                              );
  protected $fillable = ['id_partida','id_usuario','id_sesion',
                              'num_partida','hora_inicio','valor_carton',
                              'serie_inicio', 'serie_fin', 'carton_inicio_i',
                              'carton_fin_i', 'carton_inicio_f', 'carton_inicio_f',
                              'cartones_vendidos', 'bola_linea', 'bola_bingo',
                              'premio_linea', 'premio_bingo', 'pozo_dot',
                              'pozo_extra','carton_fin_f'];

  public $timestamps = false;

  public function sesionBingo(){
    return $this->belongsTo('App\Bingo\SesionBingo','id_sesion','id_sesion');
  }
  public function usuario(){
    return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
  }
  public function detallesPartida(){
    return $this->hasMany('App\Bingo\DetallePartidaBingo','id_partida','id_partida');
  }
}
