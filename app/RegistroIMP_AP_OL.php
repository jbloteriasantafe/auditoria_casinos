<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroIMP_AP_OL extends Model
{
    protected $table = 'registroIMP_AP_OL';
    protected $primaryKey = 'id_registroIMP_AP_OL';
    public $timestamps = false;

    protected $fillable = [
        'fecha_imp_ap_ol',
        'fecha_toma',
        'qna',
        'fecha_presentacion',
        'fecha_pago',
        'monto_pagado',
        'monto_apuestas',
        'alicuota',
        'impuesto_determinado',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoIMP_AP_OL()
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
