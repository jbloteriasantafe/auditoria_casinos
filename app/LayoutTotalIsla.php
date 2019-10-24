<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class LayoutTotalIsla extends Model
{
    protected $connection = 'mysql';
    protected $table = 'layout_total_isla'; 
    protected $primaryKey = ['id_layout_total', 'id_isla'];
    public $incrementing = false;
    public $timestamps = false;
    protected $visible = array('id_layout_total', 'id_isla', 'maquinas_observadas');

    public function layout_total()
    {
        return $this->belongsTo('App\LayoutTotal', 'id_layout_total', 'id_layout_total');
    }

    public function isla()
    {
        return $this->belongsTo('App\Isla', 'id_isla', 'id_isla');
    }

    //https://stackoverflow.com/questions/36332005/laravel-model-with-two-primary-keys-update
    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::setKeysForSaveQuery($query);
        }

        foreach($keys as $keyName){
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * Get the primary key value for a save query.
     *
     * @param mixed $keyName
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if(is_null($keyName)){
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
