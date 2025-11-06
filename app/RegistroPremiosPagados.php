<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPremiosPagados extends Model
{
    protected $table = 'registroPremiosPagados';
    protected $primaryKey = 'id_registroPremiosPagados';
    public $timestamps = false;

    protected $fillable = [
        'fecha_PremiosPagados',
        'fecha_toma',
        'importe',
        'importe_usd',
        'cantidad',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoPremiosPagados()
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
