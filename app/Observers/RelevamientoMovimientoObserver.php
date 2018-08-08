<?php

namespace App\Observers;

use App\RelevamientoMovimiento;

class RelevamientoMovimientoObserver extends EntityObserver
{
    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
          array('id_log_movimiento', $entidad->id_log_movimiento),
          array('id_maquina', $entidad->id_maquina),
          array('fecha_envio_fiscalizar_1', $entidad->fecha_envio_fiscalizar_1),
          array('fecha_envio_fiscalizar_2', $entidad->fecha_envio_fiscalizar_2)
      );
      return $detalles;
    }

}
