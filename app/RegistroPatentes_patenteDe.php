<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPatentes_patenteDe extends Model
{
    protected $table = 'registroPatentes_patenteDe';
    protected $primaryKey = 'id_registroPatentes_patenteDe';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'nombre',
        'estado',
        'usuario',
        'casino',

    ];

    public function CasinoPatentes_patenteDe(){
      return $this->belongsTo(Casino::class,'casino','id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
