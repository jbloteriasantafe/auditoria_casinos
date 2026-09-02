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
  
  public static function boot(){
    parent::boot();
    self::observe(new \App\Observers\FullObserver());
  }
  
  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->{$this->primaryKey};
  }

  public function getEsPrimerAeAttribute(){
    $primer_ae = Autoexcluido::where('nro_dni','=',$this->nro_dni)
    ->join('ae_estado','ae_estado.id_autoexcluido','=','ae_datos.id_autoexcluido')
    ->orderBy('fecha_ae','asc')
    ->first();
    //Si no hay autoexclusión, la que estamos actualmente es la primera
    return $primer_ae === null || ($primer_ae->id_autoexcluido == $this->id_autoexcluido);
  }

  /* Res 983
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

                            
   /* Res Nueva
            +----------+
            ¦ Pdte Val ¦
            +----------+
                 ¦validado        En cualquier momento, si HOY > fecha_cierre_ae
     #######+----------+####################################          ¦
     #           ¦                                         #          ¦
     #           ¦Primer AE?  fecha_cierre_ae = +180 dias  #          ¦
     #  +----no--o--si-------------------+                 #          ¦  
     #  ¦                                ¦                 #          ¦
     #  ¦                                ¦                 #          ¦
     #  ¦fecha_cierre_ae = +365 dias     ¦                 #          ¦
     #  ¦                                ¦                 #          ¦
     #  ¦                    +-------+   ¦                 #          ¦
     #  +--------------------¦Vigente¦---+                 #          ¦
     #                       +-------+                     #          ¦
     #                                                     #          ¦
     #######################################################          ¦
                             +-------+                                ¦
                             ¦Vencido¦                                ¦
                             +-------+                                ¦
                                 ¦                                    ¦
                                 +------------------------------------+

  */

  //Las transiciones automaticas se manejan en AutoexclusionController::actualizarVencidosRenovados
  //Aca solo se obtiene a que estado "se puede" pasar
  //La validación y la finalización son por pedido del administrador en ambos casos (desde el frontend)
  //Las relaciones entre las fechas es,
  //Primer AE
  //fecha_ae < fecha_renovacion (5 meses mas) < fecha_vencimiento (1 mes mas) < fecha_cierre_ae (6 meses mas)
  //AE repetido
  //fecha_ae < fecha_cierre_ae (12 meses mas)

  static private $nombres_estado_descripcion = null;
  static private $nombres_estado_id = null;
  public function getEstadoTransicionableAttribute(){
      $hoy = date('Y-m-d');
      self::$nombres_estado_descripcion = self::$nombres_estado_descripcion ?? NombreEstadoAutoexclusion::all()->keyBy('descripcion');
      self::$nombres_estado_id = self::$nombres_estado_id ?? self::$nombres_estado_descripcion->values()->keyBy('id_nombre_estado');
      $estado = $this->estado;
      $resolucion = $estado->resolucion;
      $nombre_estado = self::$nombres_estado_id[$estado->id_nombre_estado]->descripcion;

      if($resolucion == '983'){
          //Si esta vencido solo puede pasar a vencido
          if($nombre_estado == 'Vencido'){
              return self::$nombres_estado_descripcion['Vencido']->id_nombre_estado;
          }
          //Si esta pendiente de validación solo puede pasar a vigente
          if($nombre_estado == 'Pendiente de val.' || $nombre_estado == 'RES.983 - Pendiente'){
              return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
          }
          //Si la fecha supero el vencimiento, puede pasar a vencido
          if($hoy > $estado->fecha_cierre_ae){
              return self::$nombres_estado_descripcion['Vencido']->id_nombre_estado;
          }

          if($this->es_primer_ae){
              if($nombre_estado == 'Vigente' || $nombre_estado == 'RES.983 - Verificado'){
                  //Se le paso la fecha para finalizar, para a renovado
                  if($hoy > $estado->fecha_vencimiento){
                      return self::$nombres_estado_descripcion['Renovado']->id_nombre_estado;
                  }
                  //Si llego a la fecha donde se la habilita para finalizar
                  if($hoy > $estado->fecha_renovacion){
                      return self::$nombres_estado_descripcion['Fin. por AE']->id_nombre_estado;
                  }
                  //Es menor a ambas, es decir que solo sigue vigente
                  return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
              }
              else if($nombre_estado == 'Fin. por AE'){
                  //Si estado Finalizado y para el vencimiento de finalización, se vence la AE
                  if($hoy > $estado->fecha_vencimiento){
                      return self::$nombres_estado_descripcion['Vencido']->id_nombre_estado;
                  }
                  //Si es menor, sigue finalizado (Vigente pero con vencimiento temprano)
                  else{
                      return self::$nombres_estado_descripcion['Fin. por AE']->id_nombre_estado;
                  }
              }
              else if($nombre_estado == 'Renovado'){
                  //No es necesario manejarlo porque se vence con $hoy > $ae->estado_fecha_vencimiento
                  //Mantengo el mismo estado hasta que se venza
                  //Algoritmicamente "Renovado" es lo mismo que "Vigente" solo indica que paso por la fecha intermedia nates
                  return self::$nombres_estado_descripcion['Renovado']->id_nombre_estado;
              }
              else{
                  //No debería llegar aca, lo mantengo como esta
                  error_log(__FILE__.":".__LINE__." Error al modificar el estado AE {$this->id_autoexcluido} R{$resolucion} Estado {$nombre_estado} DNI {$this->nro_dni} Fecha {$estado->fecha_ae}. No se encontro transicion");
                  return self::$nombres_estado_descripcion[$nombre_estado]->id_nombre_estado;
              }
          }
          else{
              if($nombre_estado == 'Vigente' || $nombre_estado == 'RES.983 - Verificado'){
                  return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
              }
              //Si llego aca es porque alguien lo modifico manualmente... pasa a vigente!! que es el estado real que tiene
              else if($nombre_estado == 'Fin. por AE'){
                  return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
              }
              //Si llego aca es porque alguien lo modifico manualmente... pasa a vigente!! que es el estado real que tiene
              else if($nombre_estado == 'Renovado'){
                  return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
              }
              else{
                  //No debería llegar aca, lo mantengo como esta
                  error_log(__FILE__.":".__LINE__." Error al modificar el estado AE {$this->id_autoexcluido} R{$resolucion} Estado {$nombre_estado} DNI {$this->nro_dni} Fecha {$estado->fecha_ae}. No se encontro transicion");
                  return self::$nombres_estado_descripcion[$nombre_estado]->id_nombre_estado;
              }
          }
      }
      else if($resolucion == env('NOMBRE_NUEVA_RESOLUCION')){
          //En la nueva resolución la primer AE es directamente mas corta y las fechas
          //se setean correctamente, asi que puedo verificar contra la fecha final nomas
          if($nombre_estado == 'Vencido'){
              return self::$nombres_estado_descripcion['Vencido']->id_nombre_estado;
          }
          //Si esta pendiente de validación solo puede pasar a vigente
          if($nombre_estado == 'Pendiente de val.' || $nombre_estado == 'RES.983 - Pendiente'){
              return self::$nombres_estado_descripcion['Vigente']->id_nombre_estado;
          }
          //Si la fecha supero el vencimiento, puede pasar a vencido
          if($hoy > $estado->fecha_cierre_ae){
              return self::$nombres_estado_descripcion['Vencido']->id_nombre_estado;
          }
          return self::$nombres_estado_descripcion[$nombre_estado]->id_nombre_estado;
      }
      else{
          error_log(__FILE__.":".__LINE__." Resolucion no implementada: AE {$this->id_autoexcluido} R{$resolucion} Estado {$nombre_estado} DNI {$this->nro_dni} Fecha {$estado->fecha_ae}");
          return self::$nombres_estado_descripcion[$nombre_estado]->id_nombre_estado;
      }
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
