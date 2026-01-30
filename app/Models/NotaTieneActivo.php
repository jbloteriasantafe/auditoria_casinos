<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaTieneActivo extends Model
{
    protected $table = 'nota_tiene_activos';
    protected $fillable = ['id_nota_ingreso', 'tipo_activo', 'id_activo'];

    public function nota()
    {
        return $this->belongsTo(NotaIngreso::class, 'id_nota_ingreso');
    }
}
