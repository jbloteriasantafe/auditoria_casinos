<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroJackpotsPagados extends Model
{
    protected $table = 'registroJackpotsPagados';
    protected $primaryKey = 'id_registroJackpotsPagados';
    public $timestamps = false;

    protected $fillable = [
        'fecha_JackpotsPagados',
        'fecha_toma',
        'importe',
        'importe_usd',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoJackpotsPagados()
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
