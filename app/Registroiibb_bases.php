<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registroiibb_bases extends Model
{
    protected $table = 'registroiibb_bases';
    protected $primaryKey = 'id_registroiibb_bases';
    public $timestamps = false;

    protected $fillable = [
        'base',
        'alicuota',
        'impuesto_determinado',
        'observacion',
    ];




}
