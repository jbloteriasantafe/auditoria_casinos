<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroTGI_partida extends Model
{
    protected $table = 'registroTGI_partida';
    protected $primaryKey = 'id_registroTGI_partida';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'partida',
        'estado',
        'casino',
        'usuario',
    ];


    public function casinoTGI_partida()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
