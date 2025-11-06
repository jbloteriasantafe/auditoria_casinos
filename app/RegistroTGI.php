<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroTGI extends Model
{
    protected $table = 'registroTGI';
    protected $primaryKey = 'id_registrotgi';
    public $timestamps = false;

    protected $fillable = [
        'fecha_tgi',
        'fecha_toma',
        'archivo',
        'casino',
        'usuario',
    ];



    public function casinoTGI()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }


    public function pagos()
    {
        return $this->hasMany(RegistroTGI_partida_pago::class, 'registroTGI', 'id_registrotgi');
    }
    public function archivos()
    {
        return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
    }


    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
