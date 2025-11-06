<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPozosAcumuladosLinkeados extends Model
{
    protected $table = 'registroPozosAcumuladosLinkeados';
    protected $primaryKey = 'id_registroPozosAcumuladosLinkeados';
    public $timestamps = false;

    protected $fillable = [
        'fecha_PozosAcumuladosLinkeados',
        'fecha_toma',
        'importe',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoPozosAcumuladosLinkeados()
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
