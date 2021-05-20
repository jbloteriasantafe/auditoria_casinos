<?php
namespace App\Autoexclusion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Autoexcluido extends Model
{
  use SoftDeletes;

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

  public function getEsPrimerAeAttribute(){
    $ae = Autoexcluido::where('nro_dni','=',$this->nro_dni)->orderBy('id_autoexcluido','asc')->first();
    return $ae->id_autoexcluido == $this->id_autoexcluido;
  }

  /*
            +----------+
            ¦ Pdte Val ¦
            +----------+
                 ¦validado        En cualquier momento, si HOY > fecha_cierre_ae
     #######+----------+#############################################    ¦
     #  +-->¦ Vigente  ¦                                            #    ¦
     #  ¦   +----------+                                            #    ¦
     #  ¦        ¦Primer AE?   HOY > fecha_vencimiento?             #    ¦
     #  +----no--o--si-------------o--si-------+                    #    ¦
     #  ¦                          ¦           ¦                    #    ¦
     #  ¦                          no      +--------+               #    ¦
     #  ¦                          ¦       ¦Renovado¦               #    ¦
     #  ¦                          ¦       +--------+               #    ¦
     #  ¦                          ¦                                #    ¦
     #  +----------------------no--o HOY > fecha_renovacion?        #    ¦
     #                             ¦    (y el usuario pidio el Fin) #    ¦
     #                             si                               #    ¦
     #                             ¦                                #    ¦
     #                             ¦                                #    ¦
     #                       +-----------+                          #    ¦
     ########################¦Fin. por AE¦###########################    ¦
                             +-----------+                               ¦
                                   ¦HOY > fecha_vencimiento?             ¦
                               +-------+                                 ¦
                               ¦Vencido¦                                 ¦
                               +-------+                                 ¦
                                   ¦                                     ¦
                                   +-------------------------------------+
  */

  //Las transiciones automaticas se manejan en AutoexclusionController::actualizarVencidosRenovados
  //Aca solo se obtiene a que estado "se puede" pasar
  //La validación y la finalización son por pedido del administrador en ambos casos (desde el frontend)
  //Las relaciones entre las fechas es,
  //Primer AE
  //fecha_ae < fecha_renovacion (5 meses mas) < fecha_vencimiento (1 mes mas) < fecha_cierre_ae (6 meses mas)
  //AE repetido
  //fecha_ae < fecha_cierre_ae (12 meses mas)
  public function getEstadoTransicionableAttribute(){
    $estado = $this->estado;
    //Si esta vencido no puede cambiar
    if($estado->id_nombre_estado == 5) return 5;//Vencido

    //A los estados res983 los trato como si fueran pendiente o vigente.
    $pdte_val = ($estado->id_nombre_estado == 3 || $estado->id_nombre_estado == 6);
    //Validar
    if($pdte_val) return 1;//Vigente

    $factual = date('Y-m-d');
    //Si la fecha paso la de cierre, esta vencido
    if($factual > $estado->fecha_cierre_ae) return 5;

    $primero = $this->es_primer_ae;
    if(!$primero){
      //@HACK: Si esta renovado lo paso a vigente por mala migracion
      if($estado->id_nombre_estado == 2 || $estado->id_nombre_estado == 7) return 1;
      return $estado->id_nombre_estado;
    }

    $vigente  = ($estado->id_nombre_estado == 1 || $estado->id_nombre_estado == 7);
    if($vigente){
      //Si esta vigente y paso la fecha del vencimiento permito renovar
      if($factual > $estado->fecha_vencimiento) return 2;//Renovado
      //Si esta vigente y paso la fecha de renovacion pero no la de vencimiento, permito finalizar
      if($factual > $estado->fecha_renovacion)  return 4;//Fin por AE
    }

    //Si esta finalizado por AE y paso la fecha de vencimiento, esta vencido
    if($estado->id_nombre_estado == 4 && $factual > $estado->fecha_vencimiento) return 5;//Vencido

    //No deberia llegar aca pero lo dejo en el que estaba supongo...
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

  public function sexo(){
    return $this->belongsTo('App\Autoexclusion\SexoAE','id_sexo','id_sexo');
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
