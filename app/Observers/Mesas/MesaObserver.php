<?php

namespace App\Observers\Mesas;
use App\Mesas\Mesa;
use App\Observers\EntityObserver;

class MesaObserver extends EntityObserver
{
   public function creating(Mesa $mesa)
   {
      $mesa->nombre = strtoupper($mesa->nombre);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('nro_mesa', $entidad->nro_mesa),
       array('nombre', $entidad->nombre),
       array('descripcion', $entidad->descripcion),
       array('id_tipo_mesa', $entidad->id_tipo_mesa),
       array('id_juego_mesa', $entidad->id_juego_mesa),
       array('id_moneda', $entidad->id_moneda),
       array('id_sector_mesas', $entidad->id_sector_mesas),
     );
     return $detalles;
   }
}
