<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroAutDirectores_director extends Model
{
    protected $table = 'registroAutDirectores_director';
    protected $primaryKey = 'id_registroAutDirectores_director';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'cuit',
        'habilitado',
        'fecha_toma',
        'casino',
        'usuario'
    ];



    public function casinoAutDirectores_director()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
