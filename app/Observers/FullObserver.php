<?php
namespace App\Observers;
class FullObserver extends EntityObserver
{//Guarda todos los atributos de la tabla
    public function getDetalles($entidad){ 
        $attrs = $entidad->getAttributes();
        $detalles = [];
        foreach($attrs as $k => $v) $detalles[] = [$k,$v];
        return $detalles; 
    }
}
