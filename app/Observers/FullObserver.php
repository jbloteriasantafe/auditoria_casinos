<?php
namespace App\Observers;
class FullObserver extends EntityObserver
{
    public function getDetalles($entidad){ 
        $attrs = $entidad->getAttributes();
        $detalles = [];
        foreach($attrs as $k => $v) $detalles[] = [$k,$v];
        return $detalles; 
    }
}
