<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\LogIsla;
use App\EstadoRelevamiento;
use Illuminate\Support\Facades\DB;

class LogIslaController extends Controller
{
  private static $instance;

  public static function getInstancia() {
    if (!isset(self::$instance)) {
      self::$instance = new LogIslaController();
    }
    return self::$instance;
  }

  public function guardar($id_isla, $id_estado_relevamiento){
    $log = new LogIsla;
    $log->isla()->associate($id_isla);
    $log->estado_relevamiento()->associate($id_estado_relevamiento);
    $log->fecha = date("Y-m-d");
    $log->save();
  }

  public function obtenerHistorial($id_isla){
    $historial = DB::table('log_isla')
                    ->select('fecha','id_log_isla','id_estado_relevamiento')
                    ->where('log_isla.id_isla','=',$id_isla)
                    ->take(3)
                    ->get();
    $estados = EstadoRelevamiento::whereIn('id_estado_relevamiento',[4,5])->get();

    return ['historial'=>$historial, 'estados'=>$estados];
  }

}
