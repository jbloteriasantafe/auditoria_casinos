<?php

namespace App\Observers\Mesas;
use App\Mesas\TipoMesa;
use App\Observers\EntityObserver;

class TipoMesaObserver extends EntityObserver
{
   public function creating(TipoMesa $model)
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
