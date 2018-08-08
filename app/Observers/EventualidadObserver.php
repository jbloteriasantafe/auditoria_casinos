<?php

namespace App\Observers;

use App\Eventualidad;

class EventualidadObserver extends EntityObserver
{
    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('fecha', $entidad->fecha),
        array('sector', $entidad->sectores),
        array('islas', $entidad->islas),
        array('maquinas', $entidad->maquinas),
        array('id_log_movimiento', $entidad->id_log_movimiento),
        array('id_archivo', $entidad->id_archivo)
      );
      return $detalles;
    }

}
