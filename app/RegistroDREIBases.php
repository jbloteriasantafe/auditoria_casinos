<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroDREIBases extends Model
{
    protected $table = 'registroDREI_bases';
    protected $primaryKey = 'id_registroDREI_bases';
    public $timestamps = false;

    protected $fillable = [
        'id_registroDREI',
        'label',
        'base_imponible',
        'alicuota',
        'impuesto_determinado',
        'valido',
    ];
}
