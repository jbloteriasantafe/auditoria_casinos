<?php

namespace App;

use App\Casino;
use App\Eventualidades\Eventualidad;
use Illuminate\Database\Eloquent\Model;

class Procedimiento extends Model
{
    protected $connection = 'mysql';
    protected $table      = 'procedimiento';
    protected $primaryKey = 'id_procedimiento';
    public    $timestamps = false;
    protected $fillable   = ['nombre','orden','activo'];

    public function casinos()
    {
        return $this->belongsToMany(Casino::class,
            'casino_tiene_procedimiento', 'id_procedimiento', 'id_casino')
            ->withPivot('activo');
    }

    public function eventualidades()
    {
        return $this->belongsToMany(Eventualidad::class,
            'eventualidad_tiene_procedimiento', 'id_procedimiento', 'id_eventualidades')
            ->withPivot('estado','observacion');
    }
}
