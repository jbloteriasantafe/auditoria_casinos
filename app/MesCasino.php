<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MesCasino extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'mes_casino';
  protected $primaryKey = 'id_mes_casino';
  protected $visible = array('id_mes_casino',
                              'nombre_mes',
                              'nro_cuota',
                              'dia_inicio',
                              'dia_fin',
                              'id_casino',
                              'nro_mes',
                              'siglas',
                            );
  public $timestamps = false;
  protected $appends = array('siglas');

  public function getSiglasAttribute(){
    $siglas = ['ENE','FEB','MAR','ABR', 'MAY', 'JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
    if ($this->dia_inicio != 1 || $this->nro_cuota == 13) {
      //sale con fritas
      return $siglas[$this->nro_mes-1].' '.$this->dia_inicio.' al '.$this->dia_fin;
    }else{
      return $siglas[$this->nro_mes-1];
    }
  }
  
  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles_informe_final_mesas(){
    return $this->hasMany('App\Mesas\DetalleInformeFinalMesas','id_mes_casino','id_mes_casino');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_mes_casino;
  }

}
