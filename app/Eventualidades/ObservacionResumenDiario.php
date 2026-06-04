<?php

namespace App\Eventualidades;

use App\Usuario;
use Illuminate\Database\Eloquent\Model;

class ObservacionResumenDiario extends Model
{
    protected $table      = 'observacion_resumen_diario';
    protected $primaryKey = 'id_observacion_resumen_diario';
    public    $timestamps = true;

    protected $fillable = [
        'id_resumen_diario',
        'id_usuario_generador',
        'observacion',
        'id_archivo',
    ];

    public function resumen()
    {
        return $this->belongsTo(ResumenDiario::class, 'id_resumen_diario');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_generador');
    }
}
