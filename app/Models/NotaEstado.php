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
    const PENDIENTE_ADJUNTOS      = 'PENDIENTE_ADJUNTOS';

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
     * Transiciones permitidas para funcionarios: estado_origen => [estados_destino]
     */
    public static function transicionesFuncionario()
    {
        return [
            self::CONTROL_INICIADO => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
            self::OBS_CORREGIDA    => [self::APROBADO_FUNCIONARIO, self::VISTO_CON_OBSERVACIONES],
        ];
    }

    /**
     * Transiciones permitidas para usuarios regulares: estado_origen => [estados_destino]
     */
    public static function transicionesRegular()
    {
        return [
            self::VISTO_CON_OBSERVACIONES => [self::OBS_CORREGIDA],
        ];
    }

    /**
     * Verifica si una transición es válida para un nivel dado
     */
    public static function transicionPermitida($estadoActual, $nuevoEstado, $nivel)
    {
        if ($nivel === 'admin') return true;

        $mapa = ($nivel === 'funcionario')
            ? self::transicionesFuncionario()
            : self::transicionesRegular();

        return isset($mapa[$estadoActual]) && in_array($nuevoEstado, $mapa[$estadoActual]);
    }
}
