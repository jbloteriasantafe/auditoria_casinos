<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroDerechoAcceso extends Model
{
    protected $table = 'registroDerechoAcceso';
    protected $primaryKey = 'id_registroDerechoAcceso';
    public $timestamps = false;

    protected $fillable = [
        'fecha_DerechoAcceso',
        'fecha_toma',
        'semana',
        'monto',
        'observaciones',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoDerechoAcceso()
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
