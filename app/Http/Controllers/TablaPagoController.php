<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TablaPago;

class TablaPagoController extends Controller
{
  private static $instance;

  public static function getInstancia() {
      if (!isset(self::$instance)) {
          self::$instance = new TablaPagoController();
      }
      return self::$instance;
  }

  public function eliminarTablaPago($id){
    $tabla_pago = TablaPago::destroy($id);
    return ['tabla_pago' => $tabla_pago];
  }

  public function guardarTablaPago($tab,$id_juego){
    $tabla = new TablaPago;
    $tabla->codigo = $tab['codigo'];
    $tabla->porcentaje_devolucion = $tab['porcentaje'] ?? null;
    $tabla->juego()->associate($id_juego);
    $tabla->save();
  }

  public function modificarTablaPago($tab){
    $tabla = TablaPago::find($tab['id_tabla_pago']);
    $tabla->codigo = $tab['codigo'];
    $tabla->porcentaje_devolucion = $tab['porcentaje'] ?? null;
    $tabla->save();
  }

}
