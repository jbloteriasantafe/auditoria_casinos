<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LayoutTotalIsla extends Model
{
    protected $connection = 'mysql';
    protected $table = 'layout_total_isla';
    protected $primaryKey = 'id_layout_total';
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
}
