<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Observación que aplica a TODO el registro de "Registros Contables y Premios"
 * de un mes+casino, no a uno de los pseudo-documentos en particular.
 *
 * Va en tabla aparte porque no todos los casinos llevan las mismas secciones
 * (ver prizeSectionsByCasino / seccionesUnificadoPorCasino), así que no hay un
 * pseudo-documento que exista siempre donde colgarla.
 */
class RegistroUnificadoObservacion extends Model
{
    protected $table = 'registroUnificadoObservacion';
    protected $primaryKey = 'id_registroUnificadoObservacion';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'casino',
        'observaciones',
        'usuario',
        'fecha_toma',
    ];

    public function casinoUnificado()
    {
        return $this->belongsTo(Casino::class, 'casino', 'id_casino');
    }
}
