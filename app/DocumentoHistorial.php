<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Quién tocó cada documento contable y cuándo. No guarda QUÉ cambió: solo el movimiento,
 * el usuario y la fecha, que es lo que se muestra en el botón de historial de la tabla.
 *
 * Las filas las escribe sola el trait RegistraHistorialDocumento (eventos de Eloquent),
 * así que no hay que acordarse de llamar a nada en cada guardar/actualizar.
 */
class DocumentoHistorial extends Model
{
    protected $table = 'documento_historial';
    protected $primaryKey = 'id_documento_historial';
    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'id_documento',
        'accion',
        'detalle',
        'usuario',
        'fecha',
    ];

    /**
     * Slug de cada documento => modelo. Es el mismo slug que ya usaban validarDocumento y el
     * data-tipo de los botones de la tabla, así el botón de historial no necesita nada nuevo.
     */
    const TIPOS = [
        'iva'                    => RegistroIva::class,
        'iibb'                   => Registroiibb::class,
        'drei'                   => RegistroDREI::class,
        'tgi'                    => RegistroTGI::class,
        'imp_ap_ol'              => RegistroIMP_AP_OL::class,
        'imp_ap_mtm'             => RegistroIMP_AP_MTM::class,
        'deuda_estado'           => RegistroDeudaEstado::class,
        'pagos_mayores_mesas'    => RegistroPagosMayoresMesas::class,
        'reporte_y_lavado'       => RegistroReporteYLavado::class,
        'registros_contables'    => RegistroRegistrosContables::class,
        'aportes_patronales'     => RegistroAportesPatronales::class,
        'promo_tickets'          => RegistroPromoTickets::class,
        'pozos_acumulados'       => RegistroPozosAcumuladosLinkeados::class,
        'contrib_ente_turistico' => RegistroContribEnteTuristico::class,
        'rrhh'                   => RegistroRRHH::class,
        'ganancias'              => RegistroGanancias::class,
        'ganancias_periodo'      => RegistroGanancias_periodo::class,
        'estado_contable'        => RegistroEstadoContable::class,
        'jackpots_pagados'       => RegistroJackpotsPagados::class,
        'premios_pagados'        => RegistroPremiosPagados::class,
        'premios_mtm'            => RegistroPremiosMTM::class,
        'aut_directores'         => RegistroAutDirectores::class,
        'seguros'                => RegistroSeguros::class,
        'derecho_acceso'         => RegistroDerechoAcceso::class,
        'patentes'               => RegistroPatentes::class,
        'imp_inmobiliario'       => RegistroImpInmobiliario::class,
        'publico_casino'         => RegistroPublicoCasino::class,
    ];

    /** Cómo se lee cada movimiento en el modal. */
    const ETIQUETAS = [
        'creo'          => 'Creó el documento',
        'modifico'      => 'Modificó el documento',
        'valido'        => 'Validó el documento',
        'desvalido'     => 'Quitó la validación',
        'subio_archivo' => 'Subió un archivo',
        'borro_archivo' => 'Borró un archivo',
    ];

    /** Modelo => slug, para que los eventos sepan con qué tipo anotarse. */
    public static function tipoDe($clase)
    {
        $clase = ltrim($clase, '\\');
        foreach (self::TIPOS as $slug => $modelo) {
            if (ltrim($modelo, '\\') === $clase) return $slug;
        }
        return null;
    }

    /** Slug => modelo. Devuelve null si el slug no existe (no se confía en lo que llega). */
    public static function claseDe($tipo)
    {
        return isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo] : null;
    }

    public function usuarioRel()
    {
        return $this->belongsTo(Usuario::class, 'usuario', 'id_usuario');
    }
}
