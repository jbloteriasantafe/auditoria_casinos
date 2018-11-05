<?php

namespace App\Observers\Mesas;
use App\Mesas\Moneda;
use App\Observers\EntityObserver;

class MonedaObserver extends EntityObserver
{
   public function creating(Moneda $model)
   {
     $model->descripcion = strtoupper($model->descripcion);
     $model->siglas = strtoupper($model->siglas);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('descripcion', $entidad->descripcion),
       array('siglas', $entidad->siglas),
     );
     return $detalles;
   }
}
