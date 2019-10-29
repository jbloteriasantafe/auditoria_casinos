<?php

namespace App\Observers\Mesas;
use App\Mesas\SectorMesas;
use App\Observers\EntityObserver;

class SectorMesasObserver extends EntityObserver
{
   public function creating(SectorMesas $model)
   {
       $model->descripcion = strtoupper($model->descripcion);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('descripcion', $entidad->descripcion),
     );
     return $detalles;
   }
}
