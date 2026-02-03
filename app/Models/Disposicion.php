<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposicion extends Model
{
    protected $table = 'disposiciones';
    protected $fillable = ['id_expediente_nota', 'nro_disposicion_manual', 'cuerpo_considerandos', 'archivo_pdf_firmado'];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'id_expediente_nota');
    }
}
