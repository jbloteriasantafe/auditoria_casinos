<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    use SoftDeletes;

    protected $table = 'movimientos_expedientes';
    protected $fillable = ['id_expediente_nota', 'id_usuario', 'fecha_movimiento', 'accion', 'comentario', 'archivo_adjunto'];
    protected $dates = ['deleted_at'];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente_nota');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Usuario::class, 'id_usuario');
    }
}
