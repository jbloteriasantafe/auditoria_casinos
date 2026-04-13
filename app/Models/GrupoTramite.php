<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoTramite extends Model
{
    protected $table = 'grupos_tramites';
    
    protected $fillable = [
        'nro_nota',
        'anio',
        'id_casino',
        'id_plataforma',
        'titulo',
        'fecha_inicio_evento',
        'fecha_fin_evento',
        'id_tipo_evento',
        'id_categoria',
        'tipo_solicitud',
        'id_grupo_padre',
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

    public function esPlataforma()
    {
        return !is_null($this->id_plataforma);
    }

}
