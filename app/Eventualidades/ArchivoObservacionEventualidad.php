<?php

namespace App\Eventualidades;

use Illuminate\Database\Eloquent\Model;

class ArchivoObservacionEventualidad extends Model
{
    protected $table      = 'archivo_observacion_eventualidad';
    protected $primaryKey = 'id_archivo_observacion_eventualidad';
    public    $timestamps = true;

    protected $fillable = [
        'id_observacion_eventualidades',
        'filename',
    ];

    public function observacion()
    {
        return $this->belongsTo(Observacion::class, 'id_observacion_eventualidades');
    }
}
