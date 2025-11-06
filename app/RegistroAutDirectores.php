<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroAutDirectores extends Model
{
    protected $table = 'registroAutDirectores';
    protected $primaryKey = 'id_registroAutDirectores';
    public $timestamps = false;

    protected $fillable = [
        'fecha_AutDirectores',
        'archivo',
        'fecha_toma',
        'casino',
        'usuario'
    ];



    public function casinoAutDirectores()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function autorizaciones(){
      return $this->hasMany(RegistroAutDirectores_autorizacion::class,'registro','id_registroAutDirectores');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }
    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }



}
