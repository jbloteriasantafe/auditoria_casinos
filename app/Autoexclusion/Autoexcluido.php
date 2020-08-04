<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;

class Autoexcluido extends Model
{
  protected $connection = 'mysql';
  protected $table = 'ae_datos';
  protected $primaryKey = 'id_autoexcluido';
  protected $visible = array('id_autoexcluido','apellido','nombres',
                              'nombre_localidad','nombre_provincia','nro_domicilio',
                              'nro_dni', 'telefono', 'correo',
                              'domicilio', 'id_sexo','fecha_nacimiento',
                              'id_ocupacion','id_estado_civil','id_capacitacion'
                              );
  protected $fillable = ['apellido','nombres',
                              'nombre_localidad','nombre_provincia','nro_domicilio',
                              'nro_dni', 'telefono', 'correo',
                              'domicilio', 'id_sexo',
                              'fecha_nacimiento','id_ocupacion',
                              'id_estado_civil','id_capacitacion'];

  protected $appends = ['es_primer_ae','estado_transicionable'];

  public $timestamps = false;

  public function getEsPrimerAeAttribute(){
    $ae = Autoexcluido::where('nro_dni','=',$this->nro_dni)->orderBy('id_autoexcluido','asc')->first();
    return $ae->id_autoexcluido == $this->id_autoexcluido;
  }

  //Estado basado en fecha no en el que esta seteado
  public function getEstadoTransicionableAttribute(){
    $estado = $this->estado;
    $primero = $this->es_primer_ae;
    $factual = date('Y-m-d');
    if($primero){
      if($estado->id_nombre_estado == 3 || $estado->id_nombre_estado == 6) return 1;//Vigente
      if($factual > $estado->fecha_cierre_ae) return 5;//Vencido
      if($factual > $estado->fecha_vencimiento) return 2;//Renovado
      if($factual > $estado->fecha_renovacion) return 4;//Fin por AE
      return 1;//Vigente
    }
    if($estado->id_nombre_estado == 3 || $estado->id_nombre_estado == 6) return 2;//Renovado
    if($factual > $estado->fecha_cierre_ae) return 5; //Vencido
    return 2; //Renovado
  }

  public function contacto(){
    return $this->hasOne('App\Autoexclusion\ContactoAE','id_autoexcluido','id_autoexcluido');
  }
  public function estado(){
    return $this->hasOne('App\Autoexclusion\EstadoAE','id_autoexcluido','id_autoexcluido');
  }
  public function importacion(){
    return $this->hasOne('App\Autoexclusion\ImportacionAE','id_autoexcluido','id_autoexcluido');
  }
  public function encuesta(){
    return $this->hasOne('App\Autoexclusion\Encuesta','id_autoexcluido','id_autoexcluido');
  }

  public function ocupacion(){
    return $this->belongsTo('App\Autoexclusion\OcupacionAE','id_ocupacion','id_ocupacion');
  }
  public function estadoCivil(){
    return $this->belongsTo('App\Autoexclusion\EstadoCivilAE','id_estado_civil','id_estado_civil');
  }
  public function capacitacion(){
    return $this->belongsTo('App\Autoexclusion\CapacitacionAE','id_capacitacion','id_capacitacion');
  }
}
