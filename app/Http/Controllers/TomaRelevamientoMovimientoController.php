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
  $islaRelevadaCargar, $es_toma_2){
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
    $toma->toma_reingreso = $es_toma_2;//pone uno si es que habia antes una sola toma relev mov

    //hay muchos if porque no encontré una forma mejor y rapida ->cami
    if(isset($contadores[0]['valor'])){
      $toma->vcont1= $contadores[0]['valor'];
    }
    if(isset($contadores[1]['valor'])){
        $toma->vcont2= $contadores[1]['valor'];
    }
    if(isset($contadores[2]['valor'])){
      $toma->vcont3= $contadores[2]['valor'];
    }
    if(isset($contadores[3]['valor'])){
      $toma->vcont4= $contadores[3]['valor'];
    }
    if(isset($contadores[4]['valor'])){
      $toma->vcont5= $contadores[4]['valor'];
    }
    if(isset($contadores[5]['valor'])){
      $toma->vcont6= $contadores[5]['valor'];
    }
    if(isset($contadores[6]['valor'])){
      $toma->vcont7= $contadores[6]['valor'];
    }
    if(isset($contadores[7]['valor'])){
      $toma->vcont8= $contadores[7]['valor'];
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

  }

  //permite editar la toma relevamiento
  public function editarTomaRelevamiento(
  $tomas,
  $contadores,
  $juego ,
  $apuesta_max,
  $cant_lineas,
  $porcentaje_devolucion,
  $denominacion ,
  $cant_creditos,
  $fecha_sala,
  $observaciones,
  $mac,
  $sectorRelevadoCargar,
  $islaRelevadaCargar,
  $es_toma_reingreso){


    //detecto que la toma que esta modificando es la primera porque el rel tiene cargada una sola
      if(count($tomas) == 1){
        $toma = $tomas[0];
        $this->completar($toma, $contadores,$juego ,$apuesta_max,
        $cant_lineas,$porcentaje_devolucion,$denominacion ,$cant_creditos,
        $fecha_sala,$observaciones, $mac,$sectorRelevadoCargar,$islaRelevadaCargar,$es_toma_reingreso);
      }else{//debe ser la otra
        foreach ($tomas as $toma) {
          if($toma->toma_reingreso == 1){//la busco distinguiendo su atributo
            $this->completar($toma, $contadores,$juego ,$apuesta_max,
            $cant_lineas,$porcentaje_devolucion,$denominacion ,$cant_creditos,
            $fecha_sala,$observaciones, $mac,$sectorRelevadoCargar,$islaRelevadaCargar,$es_toma_reingreso);
          }
        }
      }
    }


  private function completar($toma, $contadores,$juego ,$apuesta_max,
  $cant_lineas,$porcentaje_devolucion,$denominacion ,$cant_creditos,
  $fecha_sala,$observaciones, $mac,$sectorRelevadoCargar,$islaRelevadaCargar,$es_toma_reingreso){

    if(isset($contadores[0]['valor'])){
    $toma->vcont1= $contadores[0]['valor'];
    }
    if(isset($contadores[1]['valor'])){
        $toma->vcont2= $contadores[1]['valor'];
    }
    if(isset($contadores[2]['valor'])){
      $toma->vcont3= $contadores[2]['valor'];
    }
    if(isset($contadores[3]['valor'])){
      $toma->vcont4= $contadores[3]['valor'];
    }
    if(isset($contadores[4]['valor'])){
      $toma->vcont5= $contadores[4]['valor'];
    }
    if(isset($contadores[5]['valor'])){
      $toma->vcont6= $contadores[5]['valor'];
    }
    if(isset($contadores[6]['valor'])){
      $toma->vcont7= $contadores[6]['valor'];
    }
    if(isset($contadores[7]['valor'])){
      $toma->vcont8= $contadores[7]['valor'];
    }
    $toma->juego= $juego;
    $toma->apuesta_max= $apuesta_max;
    $toma->cant_lineas= $cant_lineas;
    $toma->porcentaje_devolucion= $porcentaje_devolucion;
    $toma->denominacion= $denominacion;
    $toma->cant_creditos= $cant_creditos;
    $toma->observaciones = $observaciones;
    $toma->mac = $mac;
    $toma->nro_isla_relevada = $islaRelevadaCargar;
    $toma->descripcion_sector_relevado = $sectorRelevadoCargar;

    $toma->save();

  }













}
