<?php

namespace App\Observers;

use App\Casino;

class CasinoObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('nombre',$entidad->nombre)
      );
      return $detalles;
    }

}
