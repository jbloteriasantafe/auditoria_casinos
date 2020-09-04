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

  public function getEstadoTransicionableAttribute(){
    $estado = $this->estado;
    //Si esta finalizado no puede cambiar
    if($estado->id_nombre_estado == 4) return 4;
    //Si esta vencido no puede cambiar
    if($estado->id_nombre_estado == 5) return 5;

    $factual = date('Y-m-d');
    $primero = $this->es_primer_ae;
    //A los estados res983 los trato como si fueran pendiente o vigente.
    $pdte_val = ($estado->id_nombre_estado == 3 || $estado->id_nombre_estado == 6);
    $vigente  = ($estado->id_nombre_estado == 1 || $estado->id_nombre_estado == 7);
    if($primero){
      //Validar
      if($pdte_val) return 1;//Vigente
      //Si esta renovado y paso la fecha de cierre permito vencer
      if($estado->id_nombre_estado == 2 && $factual > $estado->fecha_cierre_ae) return 5;
      //Si esta vigente y paso la fecha del vencimiento permito renovar
      if($vigente && $factual > $estado->fecha_vencimiento) return 2;
      //Si esta vigente y paso la fecha de renovacion pero no la de vencimiento, permito finalizar
      if($vigente && $factual > $estado->fecha_renovacion)  return 4;//Fin por AE
      //No deberia llegar aca pero lo dejo en el que estaba supongo...
      return $estado->id_nombre_estado;
    }
    if($pdte_val) return 1;//Vigente
    //@HACK: Si esta renovado lo paso a vigente por mala migracion
    if($estado->id_nombre_estado == 2 || $estado->id_nombre_estado == 7) return 1;
    if($vigente && $factual > $estado->fecha_cierre_ae) return 5; //Vencido
    return $estado->id_nombre_estado;
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
