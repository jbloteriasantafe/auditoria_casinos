<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LogMovimiento;
use App\Nota;
use App\Maquina;
use App\Expediente;
use App\RelevamientoMovimiento;
use App\EstadoMovimiento;
use Illuminate\Support\Facades\DB;
use Response;
use PDF;
use Dompdf\Dompdf;
use View;
use App\TomaRelevamientoMovimiento;
use App\DetalleRelevamientoProgresivo;

class TomaRelevamientoMovimientoController extends Controller
{

  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new TomaRelevamientoMovimientoController();
    }
    return self::$instance;
  }

  //cuando el fiscalizador carga los relevamientos, se crea una toma_relev_mov
  //por cada maquina y se asocia a su respectivo relevamiento_movimiento
  public function crearTomaRelevamiento( $id_maquina ,
  $id_relevamiento,
  $contadores,
  $juego ,
  $apuesta_max,
  $cant_lineas,
  $porcentaje_devolucion,
  $denominacion ,
  $cant_creditos,
  $fecha_sala,
  $observaciones, $mac,
  $sectorRelevadoCargar,
  $islaRelevadaCargar){
    $mtm = Maquina::find($id_maquina);

    $toma = new TomaRelevamientoMovimiento;

    //los datos que obtiene de maquina no se si son realmente utiles
    $toma->nro_admin = $mtm->nro_admin;
    $toma->modelo = $mtm->modelo;
    $toma->nro_serie = $mtm->nro_serie;
    $toma->nro_isla = $mtm->isla->nro_isla;
    $toma->marca = $mtm->marca;
    $toma->mac = $mac;
    $toma->nro_isla_relevada = $islaRelevadaCargar;
    $toma->descripcion_sector_relevado = $sectorRelevadoCargar;
    $toma->toma_reingreso = 0; //Mas de una toma esta DEPRECADO!

    foreach($contadores as $idx => $cont){
      if(isset($cont['valor'])){
        $toma['vcont'.($idx+1)] = $cont['valor'];
      }
    }

    $toma->juego= $juego;
    $toma->apuesta_max= $apuesta_max;
    $toma->cant_lineas= $cant_lineas;
    $toma->porcentaje_devolucion= $porcentaje_devolucion;
    $toma->denominacion= $denominacion;
    $toma->cant_creditos= $cant_creditos;
    $toma->observaciones = $observaciones;
    $toma->save();
    //$toma->maquina()->associate($id_maquina);
    $toma->relevamiento_movimiento()->associate($id_relevamiento);
    $toma->save();
    foreach($mtm->progresivos as $prog){
      foreach($prog->pozos as $p){
        $detProg = new DetalleRelevamientoProgresivo;
        $detProg->id_toma_relev_mov = $toma->id_toma_relev_mov;
        $detProg->id_relevamiento_progresivo = null;
        $detProg->id_pozo = $p->id_pozo;
        $detProg->id_tipo_causa_no_toma_progresivo = null;
        $detProg->nivel1 = null;
        $detProg->nivel2 = null;
        $detProg->nivel3 = null;
        $detProg->nivel4 = null;
        $detProg->nivel5 = null;
        $detProg->nivel6 = null;
        $detProg->save();
      }
    }
    return $toma;
  }

  public function limpiarToma($id_toma_relev_mov){
    $toma = TomaRelevamientoMovimiento::find($id_toma_relev_mov);
    DB::transaction(function() use(&$toma){
      if(is_null($toma)) return null;
      $toma->vcont1 = null;
      $toma->vcont2 = null;
      $toma->vcont3 = null;
      $toma->vcont4 = null;
      $toma->vcont5 = null;
      $toma->vcont6 = null;
      $toma->vcont7 = null;
      $toma->vcont8 = null;
      $toma->juego = null;
      $toma->apuesta_max = null;
      $toma->cant_lineas = null;
      $toma->porcentaje_devolucion = null;
      $toma->denominacion = null;
      $toma->cant_creditos = null;
      $toma->observaciones = null;
      $toma->mac = null;
      $toma->nro_isla_relevada = null;
      $toma->descripcion_sector_relevado = null;
      $toma->save();
      foreach($toma->detalles_relevamiento_progresivo as $detProg){
        $detProg->id_tipo_causa_no_toma_progresivo = null;
        $detProg->nivel1 = null;
        $detProg->nivel2 = null;
        $detProg->nivel3 = null;
        $detProg->nivel4 = null;
        $detProg->nivel5 = null;
        $detProg->nivel6 = null;
        $detProg->save();
      }
    });
    return $toma;
  }


}
