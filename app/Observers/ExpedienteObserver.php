<?php

namespace App\Observers;

use App\Expediente;

class ExpedienteObserver extends EntityObserver
{

    public function getDetalles($entidad){
      $detalles = array(//para cada modelo poner los atributos más importantes
        array('nro_exp_org',$entidad->nro_exp_org),
        array('nro_exp_interno',$entidad->nro_exp_interno),
        array('nro_exp_control',$entidad->nro_exp_control)
      );
      return $detalles;
    }

}
