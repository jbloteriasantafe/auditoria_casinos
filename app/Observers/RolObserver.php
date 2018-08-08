<?php

namespace App\Observers;

use App\Rol;

class RolObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('descripcion',$entidad->descripcion)
      );
      return $detalles;
    }

}
