<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleRelevamientoProgresivo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento_progresivo';
  protected $primaryKey = 'id_detalle_relevamiento_progresivo';
  //SI agregan niveles ASEGURARSE de agregarlo a $visible
  protected $visible = array('id_detalle_relevamiento_progresivo', 'id_relevamiento_progresivo','id_pozo','id_tipo_causa_no_toma_progresivo', 'nivel1', 'nivel2', 'nivel3', 'nivel4','nivel5','nivel6','id_toma_relev_mov');
  public $timestamps = false;

  public function relevamiento_progresivo(){
    return $this->belongsTo('App\RelevamientoProgresivo','id_relevamiento_progresivo','id_relevamiento_progresivo');
  }

  //Un detalle puede ser por un relevamiento normal o de una toma de un movimiento
  public function toma_relevamiento_movimiento(){
    return $this->belongsTo('App\TomaRelevamientoMovimiento','id_toma_relev_mov','id_toma_relev_mov');
  }

  public function pozo(){
    return $this->belongsTo('App\Pozo','id_pozo','id_pozo');
  }

  public function tipo_causa_no_toma(){
    return $this->belongsTo('App\TipoCausaNoTomaProgresivo','id_tipo_causa_no_toma_progresivo','id_tipo_causa_no_toma_progresivo');
  }

  public function table(){
    return $this->table;
  }

  public function getMaxLvlAttribute(){
    $maxlvl = -1;
    foreach($this->visible as $att){
      if(substr($att,0,strlen('nivel')) == 'nivel'){
        $lvl = substr($att,strlen('nivel'));
        $maxlvl = ($lvl > $maxlvl)? $lvl : $maxlvl;
      }
    }
    return $maxlvl;
  }
}
