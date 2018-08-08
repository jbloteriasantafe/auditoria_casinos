<?php

namespace App\Observers;

use App\Resolucion;

class ResolucionObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('nro_resolucion',$entidad->nro_resolucion),
        array('nro_resolucion_anio',$entidad->nro_resolucion_anio)
      );
      return $detalles;
    }

}
