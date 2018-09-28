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

   public function crearRelevamientoMovimiento($id_log_mov, $id_maq){
     $mtmm = Maquina::find($id_maq);
     $relevMov = new RelevamientoMovimiento;
     $relevMov->log_movimiento()->associate($id_log_mov);
     $relevMov->maquina()->associate($id_maq);
     $relevMov->nro_admin = $mtmm[0]->nro_admin;
     $relevMov->estado_relevamiento()->associate(1);//generado
     $relevMov->save();
     return $relevMov;
   }

   public function maquinasEnviadasAFiscalizar($id_log_mov){
     $logMov = LogMovimiento::find($id_log_mov);
     $fiscalizaciones = FiscalizacionMov::where([
                                          ['id_log_movimiento','=',$id_log_mov],
                                          ['id_estado_relevamiento','=',1]])
                                          ->get();

      if(count($fiscalizaciones) == 0){
        return 0;
        //$val  = new Validator();
        //$validator->errors()->add('sin_relevamientos', 'El movimiento no posee maquinas enviadas a fiscalizar.');
        //return ['errors' => $val->messages()->toJson()];
      }
      $tipoMovimiento = $logMov->tipo_movimiento->descripcion;
      $casino = $logMov->casino;
      $relevamientos = array();
      $count = 0;
      foreach ($fiscalizaciones as $fiscalizacion) {
        foreach($fiscalizacion->relevamientos_movimientos as $relev_mov){
          $relevamientos[] = $this->generarPlanillaMaquina($relev_mov,$tipoMovimiento, $casino, $fiscalizacion->fecha_envio_fiscalizar,$fiscalizacion->id_estado_relevamiento,$count++,$fiscalizacion->es_reingreso);
        }
     }
     $view = View::make('planillaMovimientos', compact('relevamientos'));
     $dompdf = new Dompdf();
     $dompdf->set_paper('A4', 'portrait');
     $dompdf->loadHtml($view->render());
     $dompdf->render();
     $font = $dompdf->getFontMetrics()->get_font("helvetica", "regular");
     $dompdf->getCanvas()->page_text(20, 815, $casino->codigo, $font, 10, array(0,0,0));
     $dompdf->getCanvas()->page_text(515, 815, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, 10, array(0,0,0));

     return $dompdf->stream('planilla.pdf', Array('Attachment'=>0));
   }

   public function generarPlanillaMaquina($relev_mov, $tipo_movimiento, $casino , $fecha_envio, $estado_relevamiento,$nro,$es_toma_2){
     $rel= new \stdClass();

     $rel->toma=$es_toma_2;
     $rel->nro= $nro;
     $maquina = $relev_mov->maquina;

     //campos que solo se deberían chequear y si estan mal agregarlo en observaciones
     $rel->tipo_movimiento = $tipo_movimiento; //guarda la descripcion del tipo de movimiento
     $rel->fecha_relev_sala = $relev_mov->fecha_relev_sala;
     $rel->nro_admin = $maquina->nro_admin;
     $isla = Isla::find($maquina->id_isla);
     if($isla != null)
     {
       $rel->nro_isla = $isla->nro_isla;
     }else{
       $rel->nro_isla = "";
     }
     $rel->nro_serie =  $maquina->nro_serie;
     $rel->marca =  $maquina->marca;
     $rel->modelo =  $maquina->modelo;
     $rel->casinoCod = $casino->codigo;
     $rel->casinoNom = $casino->nombre;
     $rel->juego = $maquina->juego_activo->nombre_juego;
     $rel->porcentaje_devolucion = $maquina->porcentaje_devolucion;
     $rel->denominacion = $maquina->denominacion;

     $formula = $maquina->formula;
     $rel->nom_cont1 = $formula->cont1;
     $rel->nom_cont2 = $formula->cont2;
     $rel->nom_cont3 = $formula->cont3;
     $rel->nom_cont4 = $formula->cont4;
     $rel->nom_cont5 = $formula->cont5;
     $rel->nom_cont6 = $formula->cont6;
     $rel->nom_cont7 = $formula->cont7;
     $rel->nom_cont8 = $formula->cont8;

     //verifico si es la segunda toma que se hace
     if($es_toma_2){ //existe una toma para el relevamiento
         $toma_relev = $this->obtenerTomaRelevamiento($maquina->id_maquina,$relev_mov->id_log_movimiento);
         $rel->toma1_cont1 = $toma_relev->vcont1;
         $rel->toma1_cont2 = $toma_relev->vcont2;
         $rel->toma1_cont3 = $toma_relev->vcont3;
         $rel->toma1_cont4 = $toma_relev->vcont4;
         $rel->toma1_cont5 = $toma_relev->vcont5;
         $rel->toma1_cont6 = $toma_relev->vcont6;
         $rel->toma1_cont7 = $toma_relev->vcont7;
         $rel->toma1_cont8 = $toma_relev->vcont8;
         $juego = Juego::find($toma_relev->juego);
         $rel->toma1_juego = $juego->nombre_juego;
         $rel->toma1_apuesta_max = $toma_relev->apuesta_max;
         $rel->toma1_cant_lineas = $toma_relev->cant_lineas;
         $rel->toma1_porcentaje_devolucion = $toma_relev->porcentaje_devolucion;
         $rel->toma1_denominacion = $toma_relev->denominacion;
         $rel->toma1_cant_creditos = $toma_relev->cant_creditos;
         $rel->toma1_mac = $toma_relev->mac;


     }else{
       $rel->toma1_cont1 = null;
       $rel->toma1_cont2 = null;
       $rel->toma1_cont3 = null;
       $rel->toma1_cont4 = null;
       $rel->toma1_cont5 = null;
       $rel->toma1_cont6 = null;
       $rel->toma1_cont7 = null;
       $rel->toma1_cont8 = null;
       $rel->toma1_juego = null;
       $rel->toma1_apuesta_max = null;
       $rel->toma1_cant_lineas = null;
       $rel->toma1_porcentaje_devolucion = null;
       $rel->toma1_denominacion = null;
       $rel->toma1_cant_creditos = null;
       $rel->toma1_mac=null;

     }
    $rel->fecha = $fecha_envio;

    //Si la ficha ya se cargó, entoces se imprime con los datos de la toma:
    $toma_relev = TomaRelevamientoMovimiento::where('id_relevamiento_movimiento','=',$relev_mov->id_relev_mov)->get()->first();


    if ($estado_relevamiento > 2  && $estado_relevamiento != 5) {

      $rel->toma2_cont1 = $toma_relev->vcont1;
      $rel->toma2_cont2 = $toma_relev->vcont2;
      $rel->toma2_cont3 = $toma_relev->vcont3;
      $rel->toma2_cont4 = $toma_relev->vcont4;
      $rel->toma2_cont5 = $toma_relev->vcont5;
      $rel->toma2_cont6 = $toma_relev->vcont6;
      $rel->toma2_cont7 = $toma_relev->vcont7;
      $rel->toma2_cont8 = $toma_relev->vcont8;
      $game = Juego::find($toma_relev->juego);
      $rel->toma2_juego = $game->nombre_juego;
      $rel->toma2_apuesta_max = $toma_relev->apuesta_max;
      $rel->toma2_cant_lineas = $toma_relev->cant_lineas;
      $rel->toma2_porcentaje_devolucion = $toma_relev->porcentaje_devolucion;
      $rel->toma2_denominacion = $toma_relev->denominacion;
      $rel->toma2_cant_creditos = $toma_relev->cant_creditos;
      $rel->toma2_observ = $toma_relev->observaciones;
      $rel->toma2_mac= $toma_relev->mac;

    }else{
      $rel->toma2_cont1 = null;
      $rel->toma2_cont2 = null;
      $rel->toma2_cont3 = null;
      $rel->toma2_cont4 = null;
      $rel->toma2_cont5 = null;
      $rel->toma2_cont6 = null;
      $rel->toma2_cont7 = null;
      $rel->toma2_cont8 = null;
      $rel->toma2_juego = null;
      $rel->toma2_apuesta_max = null;
      $rel->toma2_cant_lineas = null;
      $rel->toma2_porcentaje_devolucion = null;
      $rel->toma2_denominacion = null;
      $rel->toma2_cant_creditos = null;
       $rel->toma2_observ =null;
       $rel->toma2_mac= null;
    }

     return $rel;
   }

   //obtiene la toma relevamiento asociada a la primera toma de la maquina
   //id_maquina, para mostrarla junto con la 2da toma.
   public function obtenerTomaRelevamiento($id_maquina,$id_log_mov){
     $relevamiento = DB::table('relevamiento_movimiento')
                     ->select('toma_relev_mov.*')
                     ->join('toma_relev_mov', 'toma_relev_mov.id_relevamiento_movimiento','=','relevamiento_movimiento.id_relev_mov')
                     ->join('fiscalizacion_movimiento','fiscalizacion_movimiento.id_fiscalizacion_movimiento','=','relevamiento_movimiento.id_fiscalizacion_movimiento')
                     ->where('relevamiento_movimiento.id_maquina','=', $id_maquina)
                     ->where('relevamiento_movimiento.id_log_movimiento','=', $id_log_mov)
                     ->where('fiscalizacion_movimiento.es_reingreso','=','true')
                     ->get()->first();

     return $relevamiento;
   }


   //guarda la toma del relevamiento por maquina, sea que la haya modificado o es nueva
   public function cargarTomaRelevamiento( $id_maquina , $contadores, $juego , $apuesta_max, $cant_lineas, $porcentaje_devolucion, $denominacion ,
    $cant_creditos, $fecha_sala, $observaciones, $id_fiscalizacion_movimiento, $id_cargador, $id_fisca, $mac){

     $relevamiento = RelevamientoMovimiento::where([['id_fiscalizacion_movimiento','=',$id_fiscalizacion_movimiento],['id_maquina','=',$id_maquina]])->get()->first();
     $relevamiento->estado_relevamiento()->associate(3);//finalizado
     $relevamiento->fecha_relev_sala = $fecha_sala;
     $relevamiento->fecha_carga =  date('Y-m-d h:i:s', time());
     $relevamiento->fiscalizador()->associate($id_fisca);
     $relevamiento->cargador()->associate($id_cargador);
     $relevamiento->save();


     if(!isset($relevamiento->toma_relevamiento_movimiento)){
         TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($id_maquina ,
         $relevamiento->id_relev_mov,
         $contadores,
         $juego ,
         $apuesta_max,
         $cant_lineas,
         $porcentaje_devolucion,
         $denominacion ,
         $cant_creditos,
         $fecha_sala,
         $observaciones,$mac);
       }else{
         TomaRelevamientoMovimientoController::getInstancia()->editarTomaRelevamiento(
         $relevamiento->toma_relevamiento_movimiento,
         $contadores,
         $juego ,
         $apuesta_max,
         $cant_lineas,
         $porcentaje_devolucion,
         $denominacion ,
         $cant_creditos,
         $fecha_sala,
         $observaciones, $mac);

       }
   }

   //guarda la toma del relevamiento por maquina, sea que la haya modificado o es nueva
   public function cargarTomaRelevamientoEv( $id_maquina , $contadores, $juego , $apuesta_max, $cant_lineas, $porcentaje_devolucion, $denominacion ,
    $cant_creditos, $fecha_sala, $observaciones, $id_cargador, $id_fisca, $mac, $id_log_movimiento){

     $relevamiento = RelevamientoMovimiento::where([['id_log_movimiento','=',$id_log_movimiento],['id_maquina','=',$id_maquina]])->get()->first();
     $relevamiento->estado_relevamiento()->associate(3);//finalizado
     $relevamiento->fecha_relev_sala = $fecha_sala;
     $relevamiento->fecha_carga =  date('Y-m-d h:i:s', time());
     $relevamiento->fiscalizador()->associate($id_fisca);
     $relevamiento->cargador()->associate($id_cargador);
     $relevamiento->save();


     if(!isset($relevamiento->toma_relevamiento_movimiento)){
         TomaRelevamientoMovimientoController::getInstancia()->crearTomaRelevamiento($id_maquina ,
         $relevamiento->id_relev_mov,
         $contadores,
         $juego ,
         $apuesta_max,
         $cant_lineas,
         $porcentaje_devolucion,
         $denominacion ,
         $cant_creditos,
         $fecha_sala,
         $observaciones,$mac);
       }else{
         TomaRelevamientoMovimientoController::getInstancia()->editarTomaRelevamiento(
         $relevamiento->toma_relevamiento_movimiento,
         $contadores,
         $juego ,
         $apuesta_max,
         $cant_lineas,
         $porcentaje_devolucion,
         $denominacion ,
         $cant_creditos,
         $fecha_sala,
         $observaciones, $mac);

       }
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


   public function generarRelevamientoEventualidad($request){
     $relevamiento = $this->crearRelevamientoMovimiento($request->id_log_movimiento, $request->id_maquina);
     $relevamiento->estado_relevamiento()->associate(3);//finalizado
     $relevamiento->fecha_relev_sala = $request['fecha_sala'];
     $relevamiento->fecha_carga =  date('Y-m-d h:i:s', time());
     $relevamiento->fiscalizador()->associate($request['id_fiscalizador']);
     $relevamiento->cargador()->associate($request['id_cargador']);
     $relevamiento->save();
     TomaRelevamientoMovimientoController::getInstancia()
                                          ->crearTomaRelevamiento($request->id_maquina ,
                                                   $relevamiento->id_relev_mov,
                                                   $request->contadores,
                                                   $request->juego,
                                                   $request->apuesta_max,
                                                   $request->cant_lineas,
                                                   $request->porcentaje_devolucion,
                                                   $request->denominacion ,
                                                   $request->cant_creditos,
                                                   $request->fecha_sala,
                                                  $request->observaciones,
                                                $request->mac);
    return $relevamiento->id_relev_mov;

   }


   public function relevamientosIntervencionesMTM($id_mtm,$nro, $id_log_movimiento, $tipo_movimiento,$tipo, $cas){
     $rel= new \stdClass();
     $rel->nro= $nro;

     $maquina = Maquina::find($id_mtm);
     $relevamiento = RelevamientoMovimiento::where([['id_log_movimiento','=',$id_log_movimiento],['id_maquina','=',$id_mtm]])->get()->first();
     //campos que solo se deberían chequear y si estan mal agregarlo en observaciones
     $rel->tipo_movimiento = $tipo_movimiento; //guarda la descripcion del tipo de movimiento
     $rel->nro_admin = $maquina->nro_admin;
     $isla = Isla::find($maquina->id_isla);
     if($isla != null)
     {
       $rel->nro_isla = $isla->nro_isla;
     }else{
       $rel->nro_isla = "";
     }
     $rel->nro_serie =  $maquina->nro_serie;
     $rel->marca =  $maquina->marca;
     $rel->modelo =  $maquina->modelo;
     $rel->casinoCod = $cas->codigo;
     $rel->casinoNom = $cas->nombre;
     $rel->juego = $maquina->juego_activo->nombre_juego;
     $rel->porcentaje_devolucion = $maquina->porcentaje_devolucion;
     $rel->denominacion = $maquina->denominacion;

     if(isset($maquina->formula)){
       $formula = $maquina->formula;
       $rel->nom_cont1 = $formula->cont1;
       $rel->nom_cont2 = $formula->cont2;
       $rel->nom_cont3 = $formula->cont3;
       $rel->nom_cont4 = $formula->cont4;
       $rel->nom_cont5 = $formula->cont5;
       $rel->nom_cont6 = $formula->cont6;
       $rel->nom_cont7 = $formula->cont7;
       $rel->nom_cont8 = $formula->cont8;
     }else {
       $formula = $maquina->formula;
       $rel->nom_cont1 = "s/f";
       $rel->nom_cont2 = "s/f";
       $rel->nom_cont3 = "s/f";
       $rel->nom_cont4 = "s/f";
       $rel->nom_cont5 = "s/f";
       $rel->nom_cont6 = "s/f";
       $rel->nom_cont7 = "s/f";
       $rel->nom_cont8 = "s/f";
     }


     if($tipo == 1){

       $rel->toma1_cont1 = null;
       $rel->toma1_cont2 = null;
       $rel->toma1_cont3 = null;
       $rel->toma1_cont4 = null;
       $rel->toma1_cont5 = null;
       $rel->toma1_cont6 = null;
       $rel->toma1_cont7 = null;
       $rel->toma1_cont8 = null;
       $rel->toma1_juego = null;
       $rel->toma1_apuesta_max = null;
       $rel->toma1_cant_lineas = null;
       $rel->toma1_porcentaje_devolucion = null;
       $rel->toma1_denominacion = null;
       $rel->toma1_cant_creditos = null;
       $rel->toma1_mac=null;
       $rel->toma1_observ = null;
       $rel->fecha_relev_sala = null;
     }else{
       $toma_relev = $relevamiento->toma_relevamiento_movimiento;//$this->obtenerTomaRelevamiento($maquina->id_maquina,$relev_mov->id_log_movimiento);
       $rel->toma1_cont1 = $toma_relev->vcont1;
       $rel->toma1_cont2 = $toma_relev->vcont2;
       $rel->toma1_cont3 = $toma_relev->vcont3;
       $rel->toma1_cont4 = $toma_relev->vcont4;
       $rel->toma1_cont5 = $toma_relev->vcont5;
       $rel->toma1_cont6 = $toma_relev->vcont6;
       $rel->toma1_cont7 = $toma_relev->vcont7;
       $rel->toma1_cont8 = $toma_relev->vcont8;
       $juego = Juego::find($toma_relev->juego);
       $rel->toma1_juego = $juego->nombre_juego;
       $rel->toma1_apuesta_max = $toma_relev->apuesta_max;
       $rel->toma1_cant_lineas = $toma_relev->cant_lineas;
       $rel->toma1_porcentaje_devolucion = $toma_relev->porcentaje_devolucion;
       $rel->toma1_denominacion = $toma_relev->denominacion;
       $rel->toma1_cant_creditos = $toma_relev->cant_creditos;
       $rel->toma1_mac = $toma_relev->mac;
       $rel->toma1_observ = $toma_relev->observaciones;
       $rel->fecha_relev_sala = $relevamiento->fecha_relev_sala;
     }
     return $rel;
   }
}
