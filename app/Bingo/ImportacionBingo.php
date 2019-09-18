<?php

namespace App\Bingo;

use Illuminate\Database\Eloquent\Model;

class ImportacionBingo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'bingo_importacion';
  protected $primaryKey = 'id_importacion';
  protected $visible = array('id_importacion',
                             'num_partida',
                             'hora_inicio',
                             'fecha',
                             'serieA',
                             'serieB',
                             'carton_inicio_A',
                             'carton_fin_A',
                             'carton_inicio_B',
                             'carton_fin_B',
                             'cartones_vendidos',
                             'valor_carton',
                             'cant_bola',
                             'recaudado',
                             'premio_linea',
                             'premio_bingo',
                             'pozo_dot',
                             'pozo_extra',
                             'id_casino',
                             'id_usuario'
                           );
  public $timestamps = false;
 public function casino(){
       return $this->belongsTo('App\Casino','id_casino','id_casino');
                       }
}
