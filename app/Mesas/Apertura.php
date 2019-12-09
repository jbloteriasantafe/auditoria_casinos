<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
class Apertura extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'apertura_mesa';
  protected $primaryKey = 'id_apertura_mesa';
  protected $visible = array('id_apertura_mesa','fecha','hora','total_pesos_fichas_a',
                            'total_anticipos_a', 'id_fiscalizador',
                            'id_mesa_de_panio','id_estado_cierre','id_cargador',
                            'observacion','id_moneda','hora_format','created_at',
                            'deleted_at','total_pesos'
                          );
public $timestamps = false;


protected $fillable = ['fecha','hora_inicio',
                            'hora_fin','total_pesos_fichas_c',
                            'total_anticipos_c', 'id_fiscalizador',
                            'id_tipo_cierre','id_mesa_de_panio',
                            'id_estado_cierre','id_moneda'];

protected $appends = array('hora_format','total_cantidad_fichas','total_pesos');

public function getTotalPesosAttribute(){
  return $this->total_pesos_fichas_a;
}

public function getTotalCantidadFichasAttribute(){
  $suma = 0;
  foreach ($this->detalles as $det) {
    $suma+=$det->cantidad_ficha;
  }
  return $suma;
}

  public function getHoraFormatAttribute(){
    if($this->hora != null){
      $hora = explode(':',$this->hora);
      $hora_inicio = $hora[0].':'.$hora[1];
      return $hora_inicio;
    }else{
      return '--:--';
    }
  }

  public function cierre_apertura(){
    return $this->hasOne('App\Mesas\CierreApertura','id_apertura_mesa','id_apertura_mesa');
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function fiscalizador(){
    return $this->belongsTo('App\Usuario','id_fiscalizador','id_usuario');
  }

  public function cargador(){
    return $this->belongsTo('App\Usuario','id_cargador','id_usuario');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function estado_cierre(){
    return $this->belongsTo('App\Mesas\EstadoCierre','id_estado_cierre','id_estado_cierre');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleApertura','id_apertura_mesa','id_apertura_mesa');
  }

  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
 }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_apertura_mesa;
  }
}
