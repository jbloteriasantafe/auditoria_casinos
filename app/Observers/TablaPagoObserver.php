<?php

namespace App\Observers;

use App\TablaPago;

class TablaPagoObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('codigo',$entidad->codigo)
      );
      return $detalles;
    }

}
