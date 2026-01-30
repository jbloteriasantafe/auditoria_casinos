<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * GrupoTramite - Contenedor padre para agrupar notas relacionadas
 * 
 * Permite que notas MKT y FISC estén bajo un mismo identificador.
 * Almacena los datos comunes (nro_nota, casino, título, fechas).
 */
class GrupoTramite extends Model
{
    protected $table = 'grupos_tramites';
    
    protected $fillable = [
        'nro_nota',
        'anio',
        'id_casino',
        'titulo',
        'fecha_inicio_evento',
        'fecha_fin_evento',
        'id_tipo_evento',
        'id_categoria',
        'tipo_solicitud',
    ];

    protected $dates = [
        'fecha_inicio_evento',
        'fecha_fin_evento',
    ];

    /**
     * Todas las notas hijas de este grupo
     */
    public function notas()
    {
        return $this->hasMany(NotaIngreso::class, 'id_grupo');
    }

    /**
     * Nota de Marketing (si existe)
     */
    public function notaMkt()
    {
        return $this->hasOne(NotaIngreso::class, 'id_grupo')->where('tipo_rama', 'MKT');
    }

    /**
     * Nota de Fiscalización (si existe)
     */
    public function notaFisc()
    {
        return $this->hasOne(NotaIngreso::class, 'id_grupo')->where('tipo_rama', 'FISC');
    }

    /**
     * Casino asociado
     */
    public function casino()
    {
        return $this->belongsTo(\App\Casino::class, 'id_casino');
    }

    /**
     * Tipo de Evento
     */
    public function tipoEvento()
    {
        return $this->belongsTo(NotaTipoEvento::class, 'id_tipo_evento', 'idtipoevento');
    }

    /**
     * Categoría
     */
    public function categoria()
    {
        return $this->belongsTo(NotaCategoria::class, 'id_categoria', 'idcategoria');
    }

    /**
     * Helper: Indica si tiene ambas ramas (MKT y FISC)
     */
    public function tieneAmbasRamas()
    {
        return $this->notas()->count() >= 2;
    }

    /**
     * Helper: Obtener ramas existentes
     */
    public function getRamasAttribute()
    {
        return $this->notas()->pluck('tipo_rama')->toArray();
    }
}
