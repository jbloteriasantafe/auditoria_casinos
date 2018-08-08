<?php

namespace App\Observers;

use App\Archivo;

class ArchivoObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('nombre_archivo',$entidad->nombre_archivo)
      );
      return $detalles;
    }

}
