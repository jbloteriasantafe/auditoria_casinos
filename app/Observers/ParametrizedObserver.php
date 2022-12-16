<?php
namespace App\Observers;
class ParametrizedObserver extends EntityObserver
{
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
