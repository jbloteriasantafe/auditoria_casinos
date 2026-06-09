<?php

namespace App\Eventualidades;

use App\Usuario;
use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observacion_eventualidades';
    protected $primaryKey = 'id_observacion_eventualidades';
    // La tabla tiene created_at/updated_at; con timestamps=false quedaban en NULL y la fecha
    // de creación no se mostraba. Habilitado para registrar la fecha de cada observación.
    public $timestamps = true;

    protected $fillable = [
        'id_eventualidades',
        'observacion',
        'id_usuario_generador',
        'id_archivo'
    ];

    public function eventualidad()
    {
        return $this->belongsTo(Eventualidad::class, 'id_eventualidades');
    }
    public function usuario()
    {
        // La FK al autor es id_usuario_generador (la tabla NO tiene columna id_usuario).
        return $this->belongsTo(Usuario::class, 'id_usuario_generador');
    }


}
