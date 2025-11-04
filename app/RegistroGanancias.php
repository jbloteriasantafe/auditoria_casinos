<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroGanancias extends Model
{
    protected $table = 'registroGanancias';
    protected $primaryKey = 'id_registroGanancias';
    public $timestamps = false;

    protected $fillable = [
        'archivo',
        'casino',
        'usuario',
        'diferencia',
        'fecha_toma',
        'periodo_fiscal',
        'nro_anticipo',
        'anticipo',
        'abonado',
        'computa_contra',
        'fecha_pago',
        'observaciones',

    ];



    public function casinoGanancias()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function archivos()
    {
        return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
