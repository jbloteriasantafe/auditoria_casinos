<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPublicoCasino extends Model
{
  protected $connection = 'mysql';
  protected $table = 'registroPublicoCasino';
  protected $primaryKey = 'id_registroPublicoCasino';
  protected $visible = array('id_registroPublicoCasino', 'fecha_mes', 'casino', 
                             'dia_1', 'dia_2', 'dia_3', 'dia_4', 'dia_5', 'dia_6', 'dia_7', 'dia_8', 'dia_9', 'dia_10',
                             'dia_11', 'dia_12', 'dia_13', 'dia_14', 'dia_15', 'dia_16', 'dia_17', 'dia_18', 'dia_19', 'dia_20',
                             'dia_21', 'dia_22', 'dia_23', 'dia_24', 'dia_25', 'dia_26', 'dia_27', 'dia_28', 'dia_29', 'dia_30', 'dia_31');
  public $timestamps = false;

  public function casinoPublicoCasino(){
    return $this->belongsTo('App\Casino','casino','id_casino');
  }
}
