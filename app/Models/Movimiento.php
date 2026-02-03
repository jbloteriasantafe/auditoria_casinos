<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos_expedientes';
    protected $fillable = ['id_expediente_nota', 'id_usuario', 'fecha_movimiento', 'accion', 'comentario', 'archivo_adjunto'];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente_nota');
    }
    
    // Asumimos que existe un modelo Usuario o User en App o App\Models
    public function usuario() 
    {
        return $this->belongsTo(\App\Usuario::class, 'id_usuario');
    }
}
