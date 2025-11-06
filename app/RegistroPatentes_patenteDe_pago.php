<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPatentes_patenteDe_pago extends Model
{
    protected $table = 'registroPatentes_patenteDe_pago';
    protected $primaryKey = 'id_registroPatentes_patenteDe_pago';
    public $timestamps = false;

    protected $fillable = [
        'patenteDe',
        'cuota',
        'registroPatentes',
        'observacion',
        'importe',
        'fecha_pres',

    ];

    public function PatenteDe(){
      return $this->belongsTo(RegistroPatentes_patenteDe::class,'patenteDe','id_registroPatentes_patenteDe');
    }

    public function registro(){
      return $this->belongsTo(RegistroPatentes::class,'registroPatentes','id_registroPatentes');
    }
}
