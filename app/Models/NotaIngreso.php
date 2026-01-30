<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaIngreso extends Model
{
    protected $table = 'notas_ingreso';
    protected $fillable = [
        'id_grupo',  // FK al grupo padre
        'id_casino', 
        'fecha_ingreso', 
        'nro_nota', 
        'anio', 
        'titulo', 
        'id_nota_rectificada', 
        'tipo_solicitud',
        'tipo_rama',  // MKT o FISC
        // Adjuntos
        'path_solicitud',   // Solicitud Concesionario (común)
        'path_pautas',      // Legacy - ya no se usa
        'path_diseno',      // Diseño (MKT)
        'path_bases',       // Bases y Condiciones (MKT)
        'path_informe',     // Informe Técnico (común, posterior)
        'path_varios',      // Archivos Varios (FISC - .zip)
        // Evento
        'fecha_inicio_evento',
        'fecha_fin_evento',
        'id_tipo_evento',
        'id_categoria',
        'fecha_referencia'
    ];

    /**
     * Grupo padre al que pertenece esta nota
     */
    public function grupo() {
        return $this->belongsTo(GrupoTramite::class, 'id_grupo');
    }

    public function casino() {
        return $this->belongsTo(\App\Casino::class, 'id_casino', 'id_casino');
    }

    public function expedientes()
    {
        return $this->hasMany(Expediente::class, 'id_nota_ingreso');
    }

    public function activos()
    {
        return $this->hasMany(NotaTieneActivo::class, 'id_nota_ingreso');
    }
}
