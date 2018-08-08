<?php

namespace App\Observers;

use App\Permiso;

class PermisoObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('descripcion',$entidad->descripcion)
      );
      return $detalles;
    }

}
