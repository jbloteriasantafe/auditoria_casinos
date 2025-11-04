<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroIva extends Model
{
    protected $table = 'registroIva';
    protected $primaryKey = 'id_registroIva';
    public $timestamps = false;

    protected $fillable = [
        'fecha_iva',
        'fecha_presentacion',
        'fecha_toma',
        'saldo',
        'observacion',
        'casino',
        'usuario'
    ];



    public function casinoIva()
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
