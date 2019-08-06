<?php

namespace App\Observers;

use App\Http\Controllers\LogController;
use App\Http\Controllers\DetalleLogController;
//Por cada clase del modelo hay que hacer un observer que extienda a este,
//y además sobreescribir el método getDetalles
class EntityObserver
{

    public function created($entidad){
      $this->guardarLog($entidad,'Alta');
    }

    public function updating($entidad){
      $this->guardarLog($entidad,'Modificación');
    }

    public function deleted($entidad){
      $this->guardarLog($entidad,'Eliminación');
    }

    private function guardarLog($entidad,$accion){
      $log = LogController::getInstancia()->guardarLog($accion,$entidad->getTableName(),$entidad->getId());
      DetalleLogController::getInstancia()->guardarDetalleLog($this->getDetalles($entidad),$log->id_log);
    }

    public function getDetalles($entidad){
      //hacer override y retornar vector de (campo,valor)
      return null;
    }

}
