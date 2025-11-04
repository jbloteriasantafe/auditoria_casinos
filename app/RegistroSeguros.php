<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroSeguros extends Model
{
    protected $table = 'registroSeguros';
    protected $primaryKey = 'id_registroSeguros';
    public $timestamps = false;

    protected $fillable = [
        'art',
        'archivo',
        'fecha_toma',
        'casino',
        'usuario',
        'compañia',
        'nro_poliza',
        'monto_asegurado',
        'periodo_inicio',
        'periodo_fin',
        'estado',
        'requerimento_anual',
        'observaciones',
        'tipo',
    ];



    public function casinoSeguros()
    {
        return $this->belongsTo(Casino::class,'casino', 'id_casino');
    }

    public function tipoSeguros(){
      return $this->belongsTo(RegistroSeguros_tipo::class,'tipo','id_registroSeguros_tipo');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }
    public function archivos()
        {
            return $this->morphMany(Registro_archivo::class, 'fileable', 'fileable_type', 'fileable_id');
        }



}
