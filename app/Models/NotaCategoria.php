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
     * Nombre legible por ID. No filtra por activo=1: una nota vieja con id_categoria
     * desactivada igual debe mostrar la descripción histórica. La selección en el alta
     * sigue restringiéndose a categorías activas (ver activasPorRama).
     */
    public static function nombrePorId($id)
    {
        $cat = static::where('id', $id)->first();
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
