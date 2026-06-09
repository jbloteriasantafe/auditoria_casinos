<?php

namespace App\Eventualidades;

use App\Casino;
use App\Procedimiento;
use App\Turno;
use App\Usuario;
use Illuminate\Database\Eloquent\Model;

class Eventualidad extends Model
{
    protected $table = 'eventualidades';
    protected $primaryKey = 'id_eventualidades';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'fecha_carga',
        'id_turno',
        'horario',
        'id_casino',
        'id_usuario_generador',
        'otros_fiscalizadores',
        'estado_eventualidad',
        'menores',
        'fumadores',
        'boletin_adjunto',
        'observaciones',
        'id_archivo'
    ];

    public function casino()
    {
        return $this->belongsTo(Casino::class, 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'id_usuario');
    }

    public function turno()
    {
        return $this->belongsTo(Turno::class,'id_turno');
    }
    public function observaciones()
    {
    return $this->hasMany(Observacion::class, 'id_eventualidades', 'id_eventualidades');
    }

    public function procedimientosRealizados()
    {
        return $this->belongsToMany(Procedimiento::class,
                'eventualidad_tiene_procedimiento',
                'id_eventualidades', 'id_procedimiento')
            ->withPivot('estado','observacion')
            ->orderBy('procedimiento.orden');
    }
}
