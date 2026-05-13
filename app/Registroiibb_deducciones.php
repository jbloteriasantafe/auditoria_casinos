<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registroiibb_deducciones extends Model
{
    protected $table = 'registroiibb_deducciones';
    protected $primaryKey = 'id_registroiibb_deducciones';
    public $timestamps = false;

    protected $fillable = [
        'id_registroiibb',
        'label',
        'monto',
        'valido',
    ];
}
