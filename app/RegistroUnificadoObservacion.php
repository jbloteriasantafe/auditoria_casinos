<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Contenedor de lo que aplica a TODO el registro de "Registros Contables y Premios"
 * de un mes+casino y no a un pseudo-documento en particular: la observación y los
 * archivos adjuntos.
 *
 * Va en tabla aparte porque no todos los casinos llevan las mismas secciones
 * (ver prizeSectionsByCasino / seccionesUnificadoPorCasino), así que no hay un
 * pseudo-documento que exista siempre donde colgarlos.
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

    /** Archivos del registro completo (mes+casino), no de una sección puntual. */
    public function archivos()
    {
        return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
    }
}
