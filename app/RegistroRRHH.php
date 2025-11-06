<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroRRHH extends Model
{
    protected $table = 'registroRRHH';
    protected $primaryKey = 'id_registroRRHH';
    public $timestamps = false;

    protected $fillable = [
        'fecha_RRHH',
        'archivo',
        'casino',
        'usuario',
        'fecha_toma',
        'personal_inicio',
        'altas_mes',
        'bajas',
        'personal_final',
        'personal_nomina',
        'diferencia',
        'tercerizados',
        'total_personal',
        'ofertado_adjudicado',
        'ludico',
        'no_ludico',
        'total_tipo',
        'porcentaje_ludico',
        'porcentaje_no_ludico',
        'porcentaje_total',
        'ludico_viviendo',
        'no_ludico_viviendo',
        'porcentaje_ludico_viviendo',
        'porcentaje_no_ludico_viviendo',
        'porcentaje_total_viviendo',
        'diferencia_nomina_ddjj',
    ];



    public function casinoRRHH()
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
