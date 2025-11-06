<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroDREI extends Model
{
    protected $table = 'registroDREI';
    protected $primaryKey = 'id_registroDREI';
    public $timestamps = false;

    protected $fillable = [
        'fecha_drei',
        'fecha_presentacion',
        'fecha_toma',
        'imp_est_y_garage',
        'com_base_imponible',
        'com_alicuota',
        'com_subt_imp_det',
        'gas_base_imponible',
        'gas_alicuota',
        'gas_imp_det',
        'expl_base_imponible',
        'expl_alicuota',
        'expl_imp_det',
        'apyju_base_imponible',
        'apyju_alicuota',
        'apyju_imp_det',
        'bromatologia',
        'total_imp_det',
        'intereses',
        'deducciones',
        'saldo',
        'monto_pagado',
        'vencimiento_previsto',
        'alicuota_rosario',
        'publicidad',
        'ret_percep_otros_pagos',
        'min_gral',
        'rectificativa_1',
        'rectificativa_2',
        'observacion',
        'casino',
        'usuario',
        'archivo',
    ];



    public function casinoDREI()
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
