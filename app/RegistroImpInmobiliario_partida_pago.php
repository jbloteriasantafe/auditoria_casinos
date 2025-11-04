<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroImpInmobiliario_partida_pago extends Model
{
    protected $table = 'registroImpInmobiliario_partida_pago';
    protected $primaryKey = 'id_registroImpInmobiliario_partida_pago';
    public $timestamps = false;

    protected $fillable = [
        'partida',
        'cuota',
        'registroImpInmobiliario',
        'observacion',
        'total',
        'fecha_pres',

    ];

    public function partidaImpInmobiliario(){
      return $this->belongsTo(RegistroImpInmobiliario_partida::class,'partida','id_registroImpInmobiliario_partida');
    }

    public function registro(){
      return $this->belongsTo(RegistroImpInmobiliario::class,'registroImpInmobiliario','id_registroImpInmobiliario');
    }
}
