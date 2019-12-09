<?php

namespace App\Autoexcluido;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_encuesta';
  protected $primaryKey = 'id_encuesta';
  protected $visible = array('id_encuesta','id_juego_preferido','id_frecuencia_asistencia',
                              'veces','tiempo_jugado',
                              'club_jugadores', 'juego_responsable',
                              'decision_ae',  'recibir_informacion',
                              'medio_recibir_informacion', 'id_autoexcluido',
                              'como_asiste'
                              );
  protected $fillable = ['id_juego_preferido','id_frecuencia_asistencia',
                              'veces','tiempo_jugado',
                              'club_jugadores', 'juego_responsable',
                              'decision_ae',  'recibir_informacion',
                              'medio_recibir_informacion', 'id_autoexcluido',
                              'como_asiste'];

  public $timestamps = false;
}
