<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ObservacionEventualidades extends Model
{
    protected $table = 'observacion_eventualidades';
    protected $primaryKey = 'id_observacion_eventualidades';
    public $timestamps = false;

    protected $fillable = [
        'id_eventualidades',
        'observacion',
        'id_usuario_generador',
        'id_archivo'
    ];

    public function eventualidad()
    {
        return $this->belongsTo(Eventualidades::class, 'id_eventualidades');
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class,'id_usuario');
    }


}
