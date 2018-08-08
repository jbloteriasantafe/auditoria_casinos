<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\EstadoMovimiento;
use Illuminate\Support\Facades\DB;
use Response;

class EstadoMovimientoController extends Controller
{
  private static $instance;

  public static function getInstancia(){
    if (!isset(self::$instance)) {
      self::$instance = new LogMovimientoController();
    }
    return self::$instance;
  }


  public function buscarEstadoMovimiento($estado){

    $estados = array();
    $auxiliar=DB::table('estado_movimiento')
              ->where('estado_movimiento.descripcion', '=', $estado)
              ->get()
              ->toArray();
    $estados=array_merge($estados,$auxiliar);
    $estado= $estados->first();
              return $estado->id_estado_movimiento;
  }

}
