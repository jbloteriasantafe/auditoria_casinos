<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Response;
use App\Archivo;
use App\Casino;
use Illuminate\Support\Facades\DB;

use Validator;

use App\Maquina;
use App\LogMaquina;


class LogMaquinaController extends Controller
{
  private static $atributos = [

  ];

  private static $instance;

  public static function getInstancia() {
      if(!isset(self::$instance)){
          self::$instance = new LogMaquinaController();
      }
      return self::$instance;
  }



public function guardarLogMaquina(Request $req){

  /*Validator::make($request->all(), [
    'id_fiscalizador' => 'nullable|exists:usuario ,id_usuario',
    'observaciones' => 'nullable|alpha| max:100',
    'fecha' => 'required|date',
    'sector' => 'nullable|alpha| max:100',
    'islas' => 'nullable|alpha| max:100',
    'maquinas' => 'nullable|alpha| max:100',
    'file' => 'sometimes|mimes:pdf',
    'id_tipo_movimiento' => 'required |exists:tipo_movimiento ,id_tipo_movimiento',

  ], array(), self::$atributos)->after(function ($validator){
      //$validator->getData()['descripcion'] get campo de validador
  })->validate();
*/

}

  public function registrarMovimiento($id_maquina, $razon,$id_tipo_movimiento){
    $maquina = Maquina::find($id_maquina);
    $log = new LogMaquina;
    $log->maquina()->associate($id_maquina);
    $log->tipo_movimiento()->associate($id_tipo_movimiento);
    $log->razon=$razon;
    $log->fecha = date("Y-m-d");
    $log->juega_progresivo = $maquina->juega_progresivo;
    $log->denominacion = $maquina->denominacion;
    if(!empty($maquina->isla)){//En caso de ser "desasociar isla" isla no existe
      $log->nro_isla = $maquina->isla->nro_isla;
      if(!empty($maquina->isla->sector)){
        $log->sector = $maquina->isla->sector->descripcion;

      }
    }
    $log->nombre_juego = $maquina->juego_activo->nombre_juego;
    $log->porcentaje_devolucion = $maquina->porcentaje_devolucion;
    $log->estado_maquina()->associate($maquina->id_estado_maquina);
    $log->save();

  }

}
