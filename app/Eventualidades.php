<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Eventualidades extends Model
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
        'procedimientos',
        'id_usuario_generador',
        'otros_fiscalizadores',
        'estado_eventualidad',
        'menores',
        'fumadores',
        'boletin_adjunto',
        'observaciones',
        'id_archivo'
    ];

    protected $casts = [
        'procedimientos' => 'array',
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
    return $this->hasMany(ObservacionEventualidades::class, 'id_eventualidades', 'id_eventualidades');
    }


}
