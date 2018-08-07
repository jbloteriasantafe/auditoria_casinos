<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DetalleLog;

class DetalleLogController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new DetalleLogController();
    }
    return self::$instance;
  }

  public function guardarDetalleLog($detalles,$id_log){
    for ($row = 0; $row < count($detalles); $row++){
        $detalleLog = new DetalleLog;
        $detalleLog->campo = $detalles[$row][0];
        $detalleLog->valor = $detalles[$row][1];
        $detalleLog->log()->associate($id_log);
        $detalleLog->save();
    }
  }

}
