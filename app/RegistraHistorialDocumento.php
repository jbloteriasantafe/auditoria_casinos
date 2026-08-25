<?php

namespace App;

/**
 * Anota en documento_historial quién creó / modificó / validó cada documento contable.
 *
 * Va por eventos de Eloquent en vez de llamadas sueltas en cada guardarX/actualizarX: son 27
 * documentos con dos métodos cada uno, y cualquier alta nueva se olvidaría de anotar. Con esto,
 * cualquier ->save() queda registrado.
 *
 * OJO: los update() masivos del query builder NO disparan eventos. Es lo que queremos, por ejemplo
 * en vencerSegurosPasados(), que vence seguros de a montones y no es alguien tocando el documento.
 */
trait RegistraHistorialDocumento
{
    public static function bootRegistraHistorialDocumento()
    {
        static::created(function ($modelo) {
            $modelo->anotarEnHistorial('creo');
        });

        static::updated(function ($modelo) {
            $cambios = array_keys($modelo->getDirty());
            // Validar toca únicamente la columna 'valido': se distingue para que el historial
            // diga "validó" y no "modificó", que es lo que le sirve al que audita.
            if ($cambios === ['valido']) {
                $modelo->anotarEnHistorial($modelo->valido ? 'valido' : 'desvalido');
                return;
            }
            $modelo->anotarEnHistorial('modifico');
        });
    }

    public function anotarEnHistorial($accion, $detalle = null)
    {
        $tipo = DocumentoHistorial::tipoDe(get_class($this));
        if (!$tipo) return; // modelo sin slug: no se anota en vez de romper el guardado

        DocumentoHistorial::create([
            'tipo'         => $tipo,
            'id_documento' => $this->getKey(),
            'accion'       => $accion,
            'detalle'      => $detalle,
            'usuario'      => session('id_usuario'),
            'fecha'        => date('Y-m-d H:i:s'),
        ]);
    }
}
