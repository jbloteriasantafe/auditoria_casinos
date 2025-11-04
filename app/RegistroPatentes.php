<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPatentes extends Model
{
    protected $table = 'registroPatentes';
    protected $primaryKey = 'id_registroPatentes';
    public $timestamps = false;

    protected $fillable = [
        'fecha_Patentes',
        'fecha_toma',
        'archivo',
        'casino',
        'usuario',
    ];



    public function casinoPatentes()
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

    public function pagos()
    {
        return $this->hasMany(RegistroPatentes_patenteDe_pago::class, 'registroPatentes', 'id_registroPatentes');
    }

}
