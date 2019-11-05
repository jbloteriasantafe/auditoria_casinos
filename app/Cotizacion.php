<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cotizacion';
    protected $primaryKey = 'fecha';
    protected $visible = array('fecha','valor');
    public $timestamps = false;

}
