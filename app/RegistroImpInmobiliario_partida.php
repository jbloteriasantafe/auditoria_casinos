<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroImpInmobiliario_partida extends Model
{
    protected $table = 'registroImpInmobiliario_partida';
    protected $primaryKey = 'id_registroImpInmobiliario_partida';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'partida',
        'casino',
        'usuario',
        'estado',

    ];


    public function casinoImpInmobiliario_partida()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
