<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroDeudaEstado extends Model
{
    protected $table = 'registroDeudaEstado';
    protected $primaryKey = 'id_registroDeudaEstado';
    public $timestamps = false;

    protected $fillable = [
        'fecha_DeudaEstado',
        'fecha_toma',
        'fecha_consulta',
        'registra_incumplimiento',
        'incumplimiento',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoDeudaEstado()
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
