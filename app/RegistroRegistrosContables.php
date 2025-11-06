<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroRegistrosContables extends Model
{
    protected $table = 'registroRegistrosContables';
    protected $primaryKey = 'id_registroRegistrosContables';
    public $timestamps = false;

    protected $fillable = [
        'fecha_RegistrosContables',
        'fecha_toma',
        'mtm',
        'mtm_usd',
        'mp',
        'mp_usd',
        'bingo',
        'jol',
        'total',
        'total_usd',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoRegistrosContables()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }

    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }


}
