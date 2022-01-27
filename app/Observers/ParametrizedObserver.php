<?php
namespace App\Observers;
class ParametrizedObserver extends EntityObserver{
    private $attrs;
    public function __construct(){
        $this->attrs = func_get_args();
    }
    public function getDetalles($entidad){ 
        $ret = [];
        foreach($this->attrs as $k){
            $ret[] = [$k,$entidad->{$k}];
        }
        return $ret;
    }
}
/*
{//Si se asigna esta clase por si sola no guarda nada... Es para subclasearla tipo
class ArchivoObserver extends Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('nombre_archivo');
  }
}
*/