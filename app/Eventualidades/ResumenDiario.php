<?php

namespace App\Eventualidades;

use App\Casino;
use App\Usuario;
use Illuminate\Database\Eloquent\Model;

class ResumenDiario extends Model
{
    protected $table      = 'resumen_diario';
    protected $primaryKey = 'id_resumen_diario';
    public    $timestamps = true;

    protected $fillable = [
        'id_casino',
        'fecha',
        'estado',
        'id_usuario_visador',
        'fecha_visado',
    ];

    public function casino()
    {
        return $this->belongsTo(Casino::class, 'id_casino');
    }

    public function visador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_visador');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionResumenDiario::class, 'id_resumen_diario');
    }
}
