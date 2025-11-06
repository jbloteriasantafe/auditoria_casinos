<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroSeguros_tipo extends Model
{
    protected $table = 'registroSeguros_tipo';
    protected $primaryKey = 'id_registroSeguros_tipo';
    public $timestamps = false;

    protected $fillable = [
        'fecha_toma',
        'tipo',
        'usuario',

    ];

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
