<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroAportesPatronales extends Model
{
    protected $table = 'registroAportesPatronales';
    protected $primaryKey = 'id_registroAportesPatronales';
    public $timestamps = false;

    protected $fillable = [
        'fecha_AportesPatronales',
        'fecha_toma',
        'fecha_pres',
        'fecha_pago',
        'cant_empleados',
        'monto_pagado',
        'observaciones',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoAportesPatronales()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }
    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
