<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaTipoEvento extends Model
{
    use SoftDeletes;

    protected $table = 'nota_tipos_evento';

    protected $fillable = [
        'descripcion', 'tipo_tarea', 'contexto', 'activo'
    ];

    /**
     * Nombre legible por ID. No filtra por activo=1: las notas históricas con
     * id_tipo_evento desactivado deben seguir mostrando la descripción correcta.
     * El select del alta se restringe a activos vía activosPorRama.
     */
    public static function nombrePorId($id)
    {
        $te = static::where('id', $id)->first();
        return $te ? $te->descripcion : null;
    }

    /**
     * Todos los activos, opcionalmente filtrados por rama
     */
    public static function activosPorRama($tipoTarea = null)
    {
        $query = static::where('activo', 1);
        if ($tipoTarea) {
            $query->where('tipo_tarea', $tipoTarea);
        }
        return $query->orderBy('tipo_tarea')->orderBy('id')->get();
    }
}
