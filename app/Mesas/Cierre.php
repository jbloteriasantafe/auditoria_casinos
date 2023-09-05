<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CierreObserver extends \App\Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('fecha','hora_inicio','hora_fin','total_anticipos_c','total_pesos_fichas_c','id_mesa_de_panio');
  }
}

class Cierre extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'cierre_mesa';
  protected $primaryKey = 'id_cierre_mesa';
  protected $visible = array('id_cierre_mesa','fecha','hora_inicio',
                              'hora_fin','total_pesos_fichas_c',
                              'total_anticipos_c', 'id_fiscalizador',
                              'id_mesa_de_panio','id_estado_cierre','id_moneda',
                              'hora_inicio_format','hora_fin_format',
                              'total_pesos','total_cantidad_fichas', 'observacion',
                              'id_estado_cierre','siglas','id_importacion_diaria_cierres'
                            );
  public $timestamps = false;

  protected $fillable = ['fecha','hora_inicio',
                              'hora_fin','total_pesos_fichas_c',
                              'total_anticipos_c', 'id_fiscalizador',
                              'id_tipo_cierre','id_mesa_de_panio',
                              'id_estado_cierre','id_moneda','id_importacion_diaria_cierres'];


  protected $appends = array('hora_inicio_format','hora_fin_format','total_cantidad_fichas','total_pesos','siglas');

  public function getSiglasAttribute(){
    return $this->moneda->siglas;
  }

  public function getTotalPesosAttribute(){
    return $this->total_pesos_fichas_c;
  }


  public function getTotalCantidadFichasAttribute(){
    $suma = 0;
    foreach ($this->detalles as $det) {
      $suma+=$det->cantidad_ficha_cierre;
    }
    return $suma;
  }

  public function getHoraInicioFormatAttribute(){
    if($this->hora_inicio != null){
      $hora = explode(':',$this->hora_inicio);
      $hora_inicio = $hora[0].':'.$hora[1];
      return $hora_inicio;
    }else{
      return '--:--';
    }
  }
  public function getHoraFinFormatAttribute(){
    if($this->hora_fin != null){
      $hora = explode(':',$this->hora_fin);
      $hora_inicio = $hora[0].':'.$hora[1];
      return $hora_inicio;
    }else{
      return '--:--';
    }
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function cierre_apertura(){
    return $this->hasOne('App\Mesas\CierreApertura','id_cierre_mesa','id_cierre_mesa');
  }
  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
  }
  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fiscalizador','id_usuario');
  }

  public function estado_cierre(){
    return $this->belongsTo('App\Mesas\EstadoCierre','id_estado_cierre','id_estado_cierre');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleCierre','id_cierre_mesa','id_cierre_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }
  
  public function importacion(){
    return $this->belongsTo('App\Mesas\ImportacionDiariaCierres','id_cierre_mesa','id_cierre_mesa');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_cierre_mesa;
  }
  public static function boot(){
    parent::boot();
    Cierre::observe(new CierreObserver());
  }
}
