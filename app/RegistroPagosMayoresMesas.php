<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPagosMayoresMesas extends Model
{
    protected $table = 'registroPagosMayoresMesas';
    protected $primaryKey = 'id_registroPagosMayoresMesas';
    public $timestamps = false;

    protected $fillable = [
        'fecha_PagosMayoresMesas',
        'fecha_toma',
        'importe_pesos',
        'importe_usd',
        'cant_pagos',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoPagosMayoresMesas()
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
