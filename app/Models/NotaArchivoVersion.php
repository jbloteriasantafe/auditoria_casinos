<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaArchivoVersion extends Model
{
    protected $table = 'nota_archivos_versiones';
    
    public $timestamps = false;
    
    protected $fillable = [
        'id_nota_ingreso',
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
    
    /**
     * Obtener la siguiente versión para un archivo
     */
    public static function getNextVersion($idNota, $tipoArchivo)
    {
        $maxVersion = self::where('id_nota_ingreso', $idNota)
            ->where('tipo_archivo', $tipoArchivo)
            ->max('version');
        
        return ($maxVersion ?? 0) + 1;
    }
    
    /**
     * Obtener todas las versiones de un archivo
     */
    public static function getVersions($idNota, $tipoArchivo)
    {
        return self::where('id_nota_ingreso', $idNota)
            ->where('tipo_archivo', $tipoArchivo)
            ->orderBy('version', 'desc')
            ->get();
    }
}
