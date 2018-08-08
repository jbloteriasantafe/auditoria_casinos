<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\LogClicksMov;


/*
  Se encarga de registrar las veces que el administrador entra a la gestion de isla
*/
class LogClicksMovController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new LogClicksMovController();
    }
    return self::$instance;
  }

  public function guardar($id_log_movimiento){
    $cambiosIsla = new LogClicksMov;
    $cambiosIsla->log_movimiento()->associate($id_log_movimiento);
    $cambiosIsla->fecha = date("Y-m-d");
    $cambiosIsla->save();
  }

  public function eliminar($id_log_movimiento)
  {
    $logsClick = LogClicksMov::where('id_log_movimiento','=',$id_log_movimiento)->get();
    if($logsClick !=null){
      foreach ($logsClick as $log) {
        $log->log_movimiento()->dissociate();
        LogClicksMov::destroy($log->id_log_clicks_mov);
      }
    }
  }

}
