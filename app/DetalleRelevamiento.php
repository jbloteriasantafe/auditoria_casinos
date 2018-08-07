<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\DetalleRelevamientoObserver;

class DetalleRelevamiento extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_relevamiento';
  protected $primaryKey = 'id_detalle_relevamiento';
  protected $visible = array('id_detalle_relevamiento','cont1','cont2','cont3','cont4','cont5','cont6','cont7','cont8','producido_calculado_relevado','id_unidad_medida' , 'id_maquina','codigo');
  public $timestamps = false;

  public function relevamiento(){
    return $this->belongsTo('App\Relevamiento','id_relevamiento','id_relevamiento');
  }

  public function maquina(){
    return $this->belongsTo('App\Maquina','id_maquina','id_maquina');
  }

  public function tipo_causa_no_toma(){
    return $this->belongsTo('App\TipoCausaNoToma','id_tipo_causa_no_toma','id_tipo_causa_no_toma');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_detalle_relevamiento;
  }
}
