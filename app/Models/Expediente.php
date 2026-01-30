<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $table = 'expedientes_notas';
    protected $fillable = ['id_nota_ingreso', 'tipo_rama', 'nro_expediente_manual', 'estado_actual'];

    public function nota()
    {
        return $this->belongsTo(NotaIngreso::class, 'id_nota_ingreso');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'id_expediente_nota');
    }

    public function disposicion()
    {
        return $this->hasOne(Disposicion::class, 'id_expediente_nota');
    }
}
