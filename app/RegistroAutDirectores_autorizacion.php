<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroAutDirectores_autorizacion extends Model
{
    protected $table = 'registroAutDirectores_autorizacion';
    protected $primaryKey = 'id_registroAutDirectores_autorizacion';
    public $timestamps = false;

    protected $fillable = [
        'fecha_AutDirectores',
        'autoriza',
        'director',
        'observaciones'
    ];





    public function director(){
      return $this->belongsTo(RegistroAutDirectores_director::class,'director', 'id_registroAutDirectores_director');
    }

    public function registro(){
      return $this->belongsTo(RegistroAutDirectores::class,'registro','id_registroAutDirectores');
    }



}
