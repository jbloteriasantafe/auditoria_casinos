<?php

namespace App\Observers;

use App\LogMovimiento;

class LogMovimientoObserver extends EntityObserver
{
    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
          array('id_estado_movimiento', $entidad->id_estado_movimiento),
          array('id_estado_relevamiento', $entidad->id_estado_relevamiento),
          array('id_tipo_movimiento', $entidad->id_tipo_movimiento)
      );
      return $detalles;
    }

}
