<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documento adjunto de una nota.
 * Nivel intermedio del modelo Tipo -> Documento -> Versión:
 * un tipo (solicitud, diseno, bases, varios, informe, anexo) puede tener
 * VARIOS documentos, y cada documento conserva sus versiones.
 */
class NotaArchivoDocumento extends Model
{
    protected $table = 'nota_archivos_documentos';

    public $timestamps = false;

    protected $fillable = [
        'id_nota_ingreso',
        'tipo_archivo',
        'nombre',
        'orden',
        'created_at',
        'created_by',
    ];

    protected $dates = ['created_at'];

    public function versiones()
    {
        return $this->hasMany(NotaArchivoVersion::class, 'id_documento')->orderBy('version', 'desc');
    }

    public function nota()
    {
        return $this->belongsTo(NotaIngreso::class, 'id_nota_ingreso');
    }

    /**
     * Devuelve (o crea si no existe) el documento por defecto ("Documento 1")
     * de un tipo para una nota. Lo usan los flujos de subida que todavía no
     * eligen documento explícito (wizard / modal agregar de un solo input).
     */
    public static function obtenerOCrearPorDefecto($idNota, $tipo, $userId = null)
    {
        $doc = self::where('id_nota_ingreso', $idNota)
            ->where('tipo_archivo', $tipo)
            ->orderBy('orden')
            ->orderBy('id')
            ->first();

        if (!$doc) {
            $doc = self::create([
                'id_nota_ingreso' => $idNota,
                'tipo_archivo' => $tipo,
                'nombre' => 'Documento 1',
                'orden' => 0,
                'created_at' => \Carbon\Carbon::now(),
                'created_by' => $userId,
            ]);
        }

        return $doc;
    }

    /**
     * Siguiente número de orden para un nuevo documento de un tipo dado.
     */
    public static function siguienteOrden($idNota, $tipo)
    {
        $max = self::where('id_nota_ingreso', $idNota)
            ->where('tipo_archivo', $tipo)
            ->max('orden');
        return ($max ?? -1) + 1;
    }
}
