<?php

namespace App\Observers;

use App\Disposicion;

class DisposicionObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('nro_disposicion',$entidad->nro_disposicion),
        array('nro_disposicion_anio',$entidad->nro_disposicion_anio)
      );
      return $detalles;
    }

}
