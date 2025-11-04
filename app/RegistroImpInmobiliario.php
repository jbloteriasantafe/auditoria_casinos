<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroImpInmobiliario extends Model
{
    protected $table = 'registroImpInmobiliario';
    protected $primaryKey = 'id_registroImpInmobiliario';
    public $timestamps = false;

    protected $fillable = [
        'archivo',
        'fecha_ImpInmobiliario',
        'fecha_toma',
        'casino',
        'usuario',
    ];



    public function casinoImpInmobiliario()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function pagos()
    {
        return $this->hasMany(RegistroImpInmobiliario_partida_pago::class, 'registroImpInmobiliario', 'id_registroImpInmobiliario');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }

    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }


}
