<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaCategoria extends Model
{
    use SoftDeletes;

    protected $table = 'nota_categorias';

    protected $fillable = [
        'descripcion', 'tipo_tarea', 'activo'
    ];

    /**
     * Nombre legible por ID
     */
    public static function nombrePorId($id)
    {
        $cat = static::where('id', $id)->where('activo', 1)->first();
        return $cat ? $cat->descripcion : null;
    }

    /**
     * Todas las activas, opcionalmente filtradas por rama
     */
    public static function activasPorRama($tipoTarea = null)
    {
        $query = static::where('activo', 1);
        if ($tipoTarea) {
            $query->where('tipo_tarea', $tipoTarea);
        }
        return $query->orderBy('tipo_tarea')->orderBy('id')->get();
    }
}
