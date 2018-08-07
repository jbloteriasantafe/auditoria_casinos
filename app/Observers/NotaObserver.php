<?php

namespace App\Observers;

use App\Nota;

class NotaObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('fecha',$entidad->fecha)
      );
      return $detalles;
    }

}
