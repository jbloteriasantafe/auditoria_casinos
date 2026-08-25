<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registro_archivo extends Model
{
    protected $table = 'registro_archivo';
    protected $primaryKey = 'id_registro_archivo';
    public $timestamps = false;

    protected $fillable = [
        'path',
        'fileable_id',
        'fileable_type',
        'usuario',
        'fecha_toma',
    ];


    /**
     * Subir y borrar archivos también son movimientos del documento, así que se anotan en su
     * historial. Va acá y no en cada guardarX/eliminarArchivo porque los adjuntos de los 27
     * documentos pasan todos por este modelo.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($archivo) {
            $archivo->anotarEnHistorialDelDocumento('subio_archivo');
        });

        static::deleted(function ($archivo) {
            $archivo->anotarEnHistorialDelDocumento('borro_archivo');
        });
    }

    private function anotarEnHistorialDelDocumento($accion)
    {
        $tipo = DocumentoHistorial::tipoDe($this->fileable_type);
        if (!$tipo) return;

        DocumentoHistorial::create([
            'tipo'         => $tipo,
            'id_documento' => $this->fileable_id,
            'accion'       => $accion,
            'detalle'      => $this->nombreVisible(),
            'usuario'      => session('id_usuario'),
            'fecha'        => date('Y-m-d H:i:s'),
        ]);
    }

    /** El nombre sin el prefijo que se le agrega al guardar para que no se pisen entre sí. */
    public function nombreVisible()
    {
        $nombre = basename((string)$this->path);
        return preg_replace('/^\d+_[A-Za-z0-9]{6}_/', '', $nombre);
    }

    public function fileable()
    {
        return $this->morphTo('fileable', 'fileable_type', 'fileable_id');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
