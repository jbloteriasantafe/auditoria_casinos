<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaArchivoVersion extends Model
{
    protected $table = 'nota_archivos_versiones';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id_nota_ingreso',
        'id_documento',
        'tipo_archivo',
        'version',
        'path_archivo',
        'nombre_original',
        'created_at',
        'created_by'
    ];

    protected $dates = ['created_at'];

    public function nota()
    {
        return $this->belongsTo(NotaIngreso::class, 'id_nota_ingreso');
    }

    public function documento()
    {
        return $this->belongsTo(NotaArchivoDocumento::class, 'id_documento');
    }

    /**
     * Siguiente versión. Si se pasa $idDocumento, cuenta por documento
     * (modelo nuevo Tipo->Documento->Versión); si no, cae al comportamiento
     * viejo por (nota, tipo) para retrocompatibilidad.
     */
    public static function getNextVersion($idNota, $tipoArchivo, $idDocumento = null)
    {
        $q = self::query();
        if ($idDocumento) {
            $q->where('id_documento', $idDocumento);
        } else {
            $q->where('id_nota_ingreso', $idNota)->where('tipo_archivo', $tipoArchivo);
        }
        return (($q->max('version')) ?? 0) + 1;
    }

    /**
     * Todas las versiones de un (nota, tipo) — todos los documentos de ese tipo.
     */
    public static function getVersions($idNota, $tipoArchivo)
    {
        return self::where('id_nota_ingreso', $idNota)
            ->where('tipo_archivo', $tipoArchivo)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Versiones de UN documento específico.
     */
    public static function getVersionsByDocumento($idDocumento)
    {
        return self::where('id_documento', $idDocumento)
            ->orderBy('version', 'desc')
            ->get();
    }
}
