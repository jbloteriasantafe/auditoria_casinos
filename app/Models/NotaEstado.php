<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaEstado extends Model
{
    use SoftDeletes;

    protected $table = 'nota_estados';

    protected $fillable = [
        'descripcion', 'orden', 'color', 'activo'
    ];

    // Constantes de estado (deben coincidir con nota_estados.descripcion)
    const CARGA_INICIAL           = 'CARGA INICIAL';
    const CONTROL_INICIADO        = 'CONTROL INICIADO';
    const VISTO_CON_OBSERVACIONES = 'VISTO CON OBSERVACIONES';
    const OBS_COMUNICADO          = 'OBS/COMUNICADO';
    const OBS_CORREGIDA           = 'OBS/CORREGIDA';
    const APROBADO_FUNCIONARIO    = 'APROBADO POR FUNCIONARIO';
    const APROBADO_NOTA           = 'APROBADO - NOTA/DISPOSICION';
    const VENCIDO                 = 'VENCIDO';
    const CON_INFORME             = 'CON INFORME';
    const CON_INFORME_NEGATIVO    = 'CON INFORME NEGATIVO';
    const PENDIENTE_ADJUNTOS      = 'PENDIENTE_ADJUNTOS';
    const RECHAZADO               = 'RECHAZADO';

    /**
     * Todos los estados activos ordenados
     */
    public static function activos()
    {
        return static::where('activo', 1)->orderBy('orden')->get();
    }

    /**
     * Clase CSS por descripción de estado
     */
    public static function colorPorDescripcion($descripcion)
    {
        $estado = static::where('descripcion', $descripcion)->where('activo', 1)->first();
        return $estado ? $estado->color : 'default';
    }

    /**
     * Mapea el nombre de color de la BD (default/info/warning/success/danger/...) a estilo CSS del badge.
     * Fuente única de colores: la columna `color` de nota_estados (así un estado nuevo NO necesita tocar código).
     */
    public static function cssPorColor($color)
    {
        switch ($color) {
            case 'danger':        return 'background:#dc3545;color:#fff;';
            case 'success':       return 'background:#28a745;color:#fff;';
            case 'warning':       return 'background:#f0ad4e;color:#fff;';
            case 'warning-white': return 'background:#f0ad4e;color:#fff;';
            case 'warning-black': return 'background:#f0ad4e;color:#000;';
            case 'info':          return 'background:#5bc0de;color:#fff;';
            default:              return 'background:#5bc0de;color:#fff;';
        }
    }

    /**
     * Estilo CSS del badge para una descripción de estado, leyendo la columna `color` de la BD.
     * (Cacheado por request para no consultar por cada fila.)
     */
    public static function estilo($descripcion)
    {
        static $mapa = null;
        if ($mapa === null) {
            $mapa = [];
            foreach (static::all() as $e) {
                $mapa[$e->descripcion] = $e->color;
            }
        }
        $color = isset($mapa[$descripcion]) ? $mapa[$descripcion] : 'default';
        return self::cssPorColor($color);
    }

    /**
     * Transiciones permitidas para FUNCIONARIO_1
     */
    public static function transicionesFuncionario1()
    {
        return [
            self::CONTROL_INICIADO => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
            self::OBS_CORREGIDA    => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
        ];
    }

    /**
     * Transiciones permitidas para FUNCIONARIO_2 (igual que F1 + CON INFORME NEGATIVO)
     */
    public static function transicionesFuncionario2()
    {
        return [
            self::CONTROL_INICIADO     => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
            self::OBS_CORREGIDA        => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
            self::CON_INFORME_NEGATIVO => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
        ];
    }

    /**
     * Alias para compatibilidad — devuelve transiciones de funcionario1
     */
    public static function transicionesFuncionario()
    {
        return self::transicionesFuncionario1();
    }

    /**
     * Transiciones permitidas para usuarios regulares (casino/plataforma)
     */
    public static function transicionesRegular()
    {
        return [
            self::VISTO_CON_OBSERVACIONES => [self::OBS_CORREGIDA],
        ];
    }

    /**
     * Transiciones para el rol JUEGO_RESPONSABLE: se mueve libremente entre
     * CARGA INICIAL, CONTROL INICIADO, VISTO CON OBSERVACIONES y RECHAZADO.
     */
    public static function transicionesJuegoResponsable()
    {
        $libres = [self::CARGA_INICIAL, self::CONTROL_INICIADO, self::VISTO_CON_OBSERVACIONES, self::RECHAZADO];
        $mapa = [];
        foreach ($libres as $desde) {
            $mapa[$desde] = array_values(array_filter($libres, function ($x) use ($desde) {
                return $x !== $desde;
            }));
        }
        return $mapa;
    }

    /**
     * Verifica si una transición es válida para un nivel dado
     */
    public static function transicionPermitida($estadoActual, $nuevoEstado, $nivel)
    {
        if ($nivel === 'admin') return true;

        if ($nivel === 'funcionario1') {
            $mapa = self::transicionesFuncionario1();
        } elseif ($nivel === 'funcionario2') {
            $mapa = self::transicionesFuncionario2();
        } elseif ($nivel === 'funcionario') {
            // Fallback genérico: usar funcionario1
            $mapa = self::transicionesFuncionario1();
        } elseif ($nivel === 'juego_responsable') {
            $mapa = self::transicionesJuegoResponsable();
        } else {
            $mapa = self::transicionesRegular();
        }

        return isset($mapa[$estadoActual]) && in_array($nuevoEstado, $mapa[$estadoActual]);
    }
}
