<?php

namespace App\Observers\Mesas;
use App\Mesas\Cierre;
use App\Observers\EntityObserver;

class CierreObserver extends EntityObserver
{

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('fecha', $entidad->fecha),
       array('hora_inicio', $entidad->hora_inicio),
       array('hora_fin', $entidad->hora_fin),
       array('total_anticipos_c', $entidad->total_anticipos_c),
       array('total_pesos_fichas_c', $entidad->total_pesos_fichas_c),
       array('id_mesa_de_panio', $entidad->id_mesa_de_panio),
     );
     return $detalles;
   }
}
