<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPremiosMTM extends Model
{
    protected $table = 'registroPremiosMTM';
    protected $primaryKey = 'id_registroPremiosMTM';
    public $timestamps = false;

    protected $fillable = [
        'fecha_PremiosMTM',
        'fecha_toma',
        'cancel',
        'cancel_usd',
        'progresivos',
        'progresivos_usd',
        'total',
        'total_usd',
        'jackpots',
        'jackpots_usd',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoPremiosMTM()
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


}
