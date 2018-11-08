<?php

namespace App\Observers\Mesas;
use App\Mesas\Apertura;
use App\Observers\EntityObserver;

class AperturaObserver extends EntityObserver
{

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('fecha', $entidad->fecha),
       array('hora', $entidad->hora),
       array('total_pesos_fichas_a', $entidad->total_pesos_fichas_c),
       array('id_mesa_de_panio', $entidad->id_mesa_de_panio),
     );
     return $detalles;
   }
}
