<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroGanancias_periodo extends Model
{
    protected $table = 'registroGanancias_periodo';
    protected $primaryKey = 'id_registroGanancias_periodo';
    public $timestamps = false;

    protected $fillable = [
        'casino',
        'usuario',
        'fecha_toma',
        'periodo_fiscal',
        'fecha_presentacion',
        'saldo',
        'forma_pago',
        'observaciones',


    ];



    public function casinoGanancias_periodo()
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
