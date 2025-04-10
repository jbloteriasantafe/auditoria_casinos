<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LogMovimiento;
use App\TomaRelevamientoMovimiento;
use App\Nota;
use App\Juego;
use App\Maquina;
use App\Isla;
use App\Expediente;
use App\RelevamientoMovimiento;
use App\EstadoMovimiento;
use App\FiscalizacionMov;
use App\DetalleRelevamientoProgresivo;
use App\NivelProgresivo;
use App\Archivo;
use Illuminate\Support\Facades\DB;
use Response;
use PDF;
use Dompdf\Dompdf;
use View;


class RelevamientoMovimientoController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new RelevamientoMovimientoController();
    }
    return self::$instance;
  }



  //crea los relevamientos_movimientos cuando el controlador selecciono las maquinas que desea relevar

   public function crearRelevamientoMovimiento($id_log_mov, $maquina){
      $relevMov = null;
      DB::transaction(function() use($id_log_mov,$maquina,&$relevMov){
        $relevMov = new RelevamientoMovimiento;
        $relevMov->log_movimiento()->associate($id_log_mov);
        $relevMov->maquina()->associate($maquina->id_maquina);
        $relevMov->nro_admin = $maquina->nro_admin;
        $relevMov->estado_relevamiento()->associate(1);//generado
        $relevMov->save();
        $toma = TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($maquina->id_maquina,$relevMov->id_relev_mov,[],
        null,null,null,
        null,null,null,
        null,null,null,
        null,null);
      });
      return $relevMov;
   }


   public function generarPlanillaMaquina($relev_mov, $tipo_movimiento, $sentido, $casino){
     $rel= new \stdClass();
     $maquina = $relev_mov->maquina()->withTrashed()->first();
     $rel->tipo_movimiento = $tipo_movimiento;
     $rel->sentido = $sentido === '---'? null : $sentido;
     $rel->fecha_relev_sala = $relev_mov->fecha_relev_sala;
     $rel->nro_admin = $maquina->nro_admin;
     if(!is_null($maquina->deleted_at)) $rel->nro_admin .= ' (ELIM.)';
     $isla = Isla::find($maquina->id_isla);
     $rel->nro_isla = is_null($isla)? '' : $isla->nro_isla;
     $rel->nro_serie =  $maquina->nro_serie;
     $rel->marca =  $maquina->marca;
     $rel->modelo =  $maquina->modelo;
     $rel->casinoCod = $casino->codigo;
     $rel->casinoNom = $casino->nombre;
     $rel->juego = $maquina->juego_activo->nombre_juego;
     $rel->porcentaje_devolucion = $maquina->porcentaje_devolucion;
     $rel->denominacion = $maquina->denominacion;
     $rel->estado = $relev_mov->estado_relevamiento->descripcion;

     $formula = $maquina->formula;
     if(isset($formula)){
      $rel->nom_conts = [];
      for($i=1;$i<=8;$i++){
       $rel->nom_conts[$i] = $formula['cont'.$i];
      }
     }
     else{
      $rel->nom_conts = [];
      for($i=1;$i<=8;$i++){
       $rel->nom_conts[$i] = "s/f";
      }
     }

     $rel->tomas = [];
     $tomas_bd = $relev_mov->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->get();
     $max_lvl_prog = (new DetalleRelevamientoProgresivo)->max_lvl;
     foreach($tomas_bd as $nro_toma => $toma){
        $t = new \stdClass();
        $t->fecha_relev_sala = $relev_mov->fecha_relev_sala;
        $t->conts = [];
        for($i=1;$i<=8;$i++){
          $t->conts[$i] = $toma['vcont'.$i];
        }
        $juego = Juego::find($toma->juego);
        $t->juego = is_null($juego)? '' : $juego->nombre_juego;
        $t->apuesta_max = $toma->apuesta_max;
        $t->cant_lineas = $toma->cant_lineas;
        $t->porcentaje_devolucion = $toma->porcentaje_devolucion;
        $t->denominacion = $toma->denominacion;
        $t->cant_creditos = $toma->cant_creditos;
        $t->mac = $toma->mac;
        $t->nro_isla_relevada = $toma->nro_isla_relevada;
        $t->descripcion_sector_relevado = $toma->descripcion_sector_relevado;
        $t->observ = $toma->observaciones;
        $t->progresivos = $this->obtenerRelevamientosProgresivos($relev_mov,$nro_toma+1);
        $t->max_lvl_prog = $max_lvl_prog;
        $rel->tomas[] = $t;
     }
     //No tiene tomas (no deberia pasar pq se crea una con la fiscalizacion/intervencion) pero le creo una para que complete
     if(count($rel->tomas) == 0){ 
      $t = new \stdClass();
      $t->fecha_relev_sala = '';
      $t->conts = [];
      $t->juego = '';
      $t->apuesta_max = '';
      $t->cant_lineas = '';
      $t->porcentaje_devolucion = '';
      $t->denominacion = '';
      $t->cant_creditos = '';
      $t->mac = '';
      $t->nro_isla_relevada = '';
      $t->descripcion_sector_relevado = '';
      $t->observ = null;
      $t->progresivos = [];
      $t->max_lvl_prog = $max_lvl_prog;
      $rel->tomas[] = $t;
     }

     return $rel;
   }

    public function obtenerRelevamientosProgresivos($relev_mov,$toma = 1){
      $toma_relev = $relev_mov->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')
      ->skip($toma - 1)->first();
      $maquina = $relev_mov->maquina;
      $detalles = is_null($toma_relev)? [] : $toma_relev->detalles_relevamiento_progresivo;
      $progresivos = [];
      foreach($detalles as $d){
        $relprog = new \stdClass();
        $pozo =  $d->pozo()->withTrashed()->first();
        $relprog->pozo = $pozo->descripcion;
        $prog = $pozo->progresivo()->withTrashed()->first();
        $relprog->es_individual = $prog->es_individual;
        $relprog->pozo_unico = $prog->pozos()->count() == 1;
        $relprog->progresivo = $prog->nombre;
        $relprog->niveles = [];
        $relprog->valores_niveles = [];
        $maxlvl = $d->max_lvl;
        for($i = 1;$i<=$maxlvl;$i++){
          $relprog->valores_niveles[$i] = $d['nivel'.$i];
          // Si no hay un nivel me quedo con el ultimo eliminado
          // Puede pasar si es un relevamiento viejo al que se le borro el progresivo
          $nivel = $pozo->niveles()->withTrashed()
          ->where('nro_nivel','=',$i)
          ->orderBy('id_nivel_progresivo','desc')
          ->orderBy('deleted_at','desc')->first();
          if(!is_null($nivel)){
            $relprog->niveles[$nivel->nro_nivel]=$nivel->nombre_nivel;
          }
        }
        $relprog->tipo_causa_no_toma_progresivo = null;
        if(!is_null($d->tipo_causa_no_toma)) $relprog->tipo_causa_no_toma_progresivo = $d->tipo_causa_no_toma->descripcion;
        $progresivos[] = $relprog;
      }
      return $progresivos;
    }

  public function cargarTomaRelevamientoProgs(
    $id_relevamiento, $nro_toma, $id_cargador, $id_fiscalizador, $fecha_sala,
    $mac, $sector_relevado, $isla_relevada, $contadores, $juego, $apuesta_max,
    $cant_lineas, $porcentaje_devolucion, $denominacion, $cant_creditos, 
    $progresivos, $observaciones, $adjunto
  ){
    $relevamiento = RelevamientoMovimiento::find($id_relevamiento);
    $relevamiento->estado_relevamiento()->associate(3);//finalizado
    $relevamiento->fecha_relev_sala = $fecha_sala;
    $relevamiento->fecha_carga =  date('Y-m-d h:i:s', time());
    $relevamiento->fiscalizador()->associate($id_fiscalizador);
    $relevamiento->cargador()->associate($id_cargador);
    $relevamiento->save();

    if($relevamiento->toma_relevamiento_movimiento()->count() == 0){//Si por algun motivo no tiene tomas, le creo una vacia
      TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($relevamiento->id_maquina,$relevamiento->id_relev_mov,[],
      null,null,null,
      null,null,null,
      null,null,null,
      null,null);
    }

    $toma = $relevamiento->toma_relevamiento_movimiento()->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->skip($nro_toma - 1)->first();
    if(is_null($toma)) return -1;

    $toma->mac = $mac;
    $toma->descripcion_sector_relevado = $sector_relevado;
    $toma->nro_isla_relevada = $isla_relevada;
    foreach($contadores as $idx => $c){
      $toma['vcont'.($idx+1)] = $c['valor'];
    }
    $toma->juego = $juego;
    $toma->apuesta_max = $apuesta_max;
    $toma->cant_lineas = $cant_lineas;
    $toma->porcentaje_devolucion = $porcentaje_devolucion;
    $toma->denominacion = $denominacion;
    $toma->cant_creditos = $cant_creditos;
    $toma->observaciones = $observaciones;
    $toma->id_archivo = null;
    if($adjunto){
      $archivo=new Archivo;
      $data=base64_encode(file_get_contents($adjunto->getRealPath()));
      $nombre_archivo=$adjunto->getClientOriginalName();
      $archivo->nombre_archivo=$nombre_archivo;
      $archivo->archivo=$data;
      $archivo->save();
      $toma->id_archivo = $archivo->id_archivo;
    }
    $toma->save();

    $maxlvl = (new DetalleRelevamientoProgresivo)->max_lvl;
    $progresivos = is_null($progresivos)? [] : $progresivos;
    foreach($progresivos as $pozo){
      // No deberia haber multiples relevamientos del mismo pozo para una toma.
      $detalle_prog = $toma->detalles_relevamiento_progresivo()->where('detalle_relevamiento_progresivo.id_pozo','=',$pozo['id_pozo'])->first();
      $causaNoToma = $pozo['id_tipo_causa_no_toma_progresivo'];
      $detalle_prog['id_tipo_causa_no_toma_progresivo'] = $causaNoToma;
      for($i = 1;$i<=$maxlvl;$i++){
        $detalle_prog['nivel'.$i] = null;
      }
      if(is_null($causaNoToma)){
        foreach($pozo['niveles'] as $nivel){
          $nivelbd = NivelProgresivo::find($nivel['id_nivel_progresivo']);
          $nro_nivel = $nivelbd->nro_nivel;
          if($nro_nivel <= $maxlvl){
            $detalle_prog['nivel'.$nro_nivel] = $nivel['val'];
          }
          else{
            throw new Exception('ERROR SE SUPERO LA CANTIDAD DE NIVELES CARGABLES, AGREGAR UN NIVEL A LA TABLA (y al modelo)');
          }
        }
      }
      $detalle_prog->save();
    }
    return 0;
  }

   //el controlador valida la toma, si encuentra un error la marca con error.
   public function validarRelevamientoToma($relevamiento, $validado){
      $toma = TomaRelevamientoMovimiento::where('id_relevamiento_movimiento','=',$relevamiento->id_relev_mov)->get()->first();
      if($validado == 1){
          $relevamiento->estado_relevamiento()->associate(4);//validado
      }else{
        $relevamiento->estado_relevamiento()->associate(6);//Error
      }

      $relevamiento->save();
      $razon = $toma->observaciones;
      return $razon;
   }

 //el controlador valida la toma, si encuentra un error la marca con error.
 // se agrega campo de observacion, es decir , al momento de validar, solo se puede alterar el valor de la
 // observacion el resto son valores de estados
 public function validarRelevamientoTomaConObservacion($relevamiento, $validado, $obsAdmin,$nro_toma = 1){
  $toma = TomaRelevamientoMovimiento::where('id_relevamiento_movimiento','=',$relevamiento->id_relev_mov)
  ->orderBy('toma_relev_mov.id_toma_relev_mov','asc')->skip($nro_toma - 1)->first();

  if($validado == 1){
    $relevamiento->estado_relevamiento()->associate(4);//validado
  }else{
    $relevamiento->estado_relevamiento()->associate(6);//Error
  }

  if ($obsAdmin!="") {
    $toma->observaciones = $toma->observaciones."\n\n***Observaciones Admin****:\n\n".$obsAdmin;
  }

  $relevamiento->save();
  $toma->save();
  return $toma->observaciones;
  }

  public function eliminarRelevamiento($id_relev_mov)
  {
    $rel = RelevamientoMovimiento::find($id_relev_mov);
    $rel->maquina()->dissociate();
    $rel->estado_relevamiento()->dissociate();
    $rel->log_movimiento()->dissociate();
    $rel->fiscalizador()->dissociate();
    $rel->cargador()->dissociate();
    RelevamientoMovimiento::destroy($id_relev_mov);
  }
}
