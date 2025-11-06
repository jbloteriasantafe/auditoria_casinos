<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroContribEnteTuristico extends Model
{
    protected $table = 'registroContribEnteTuristico';
    protected $primaryKey = 'id_registroContribEnteTuristico';
    public $timestamps = false;

    protected $fillable = [
        'fecha_ContribEnteTuristico',
        'fecha_toma',
        'monto_pagado',
        'fecha_venc',
        'fecha_pres',
        'base_imponible',
        'alicuota',
        'impuesto_determinado',
        'observaciones',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoContribEnteTuristico()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }

    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }


}
