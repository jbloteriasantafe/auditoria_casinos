<?php

namespace App\Observers;

use App\TomaRelevMov;

class TomaRelevMovObserver extends EntityObserver
{
    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('vcont1', $entidad->vcont1),
        array('vcont2', $entidad->vcont2),
        array('vcont3', $entidad->vcont3),
        array('vcont4', $entidad->vcont4),
        array('vcont5', $entidad->vcont5),
        array('vcont6', $entidad->vcont6),
        array('vcont7', $entidad->vcont7),
        array('vcont8', $entidad->vcont8),
        array('juego', $entidad->juego),
        array('apuesta_max', $entidad->apuesta_max),
        array('cant_lineas', $entidad->cant_lineas),
        array('porcent_devolucion', $entidad->porcent_devolucion),
        array('denominacion', $entidad->denominacion),
        array('cant_creditos', $entidad->cant_creditos),
        array('observaciones', $entidad->observaciones)
      );
      return $detalles;
    }

}
