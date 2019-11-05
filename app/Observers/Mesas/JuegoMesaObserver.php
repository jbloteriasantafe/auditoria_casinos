<?php

namespace App\Observers\Mesas;
use App\Mesas\JuegoMesa;
use App\Observers\EntityObserver;

class JuegoMesaObserver extends EntityObserver
{
   public function creating(JuegoMesa $model)
   {
     $model->nombre_juego = strtoupper($model->nombre_juego);
     $model->siglas = strtoupper($model->siglas);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('nombre_juego', $entidad->nombre_juego),
       array('siglas', $entidad->siglas),
     );
     return $detalles;
   }
}
