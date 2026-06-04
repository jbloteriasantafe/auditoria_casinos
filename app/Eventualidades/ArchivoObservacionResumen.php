<?php

namespace App\Eventualidades;

use Illuminate\Database\Eloquent\Model;

class ArchivoObservacionResumen extends Model
{
    protected $table      = 'archivo_observacion_resumen';
    protected $primaryKey = 'id_archivo_observacion_resumen';
    public    $timestamps = true;

    protected $fillable = [
        'id_observacion_resumen_diario',
        'filename',
    ];

    public function observacion()
    {
        return $this->belongsTo(ObservacionResumenDiario::class, 'id_observacion_resumen_diario');
    }
}
