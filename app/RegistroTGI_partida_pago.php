<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroTGI_partida_pago extends Model
{
    protected $table = 'registroTGI_partida_pago';
    protected $primaryKey = 'id_registroTGI_partida_pago';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'partida',
        'cuota',
        'registroTGI',
        'importe',
        'fecha_vencimiento',
        'fecha_pago',
        'observacion',

    ];

    public function partidaTGI(){
      return $this->belongsTo(RegistroTGI_partida::class,'partida','id_registroTGI_partida');
    }

    public function registro(){
      return $this->belongsTo(RegistroTGI::class,'registroTGI','id_registrotgi');
    }
}
