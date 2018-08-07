<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\MovimientoIsla;

/**
* se crea en isla controller en cada funcion que da de baja o alta una maquina de una isla
*/
class MovimientoIslaController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new MovimientoIslaController();
    }
    return self::$instance;
  }

  public function guardar($id_isla, $id_maquina){
    $movimiento = new MovimientoIsla;
    $movimiento->isla()->associate($id_isla);
    $movimiento->maquina()->associate($id_maquina);
    $movimiento->fecha = date("Y-m-d");
    $movimiento->save();
  }

}
