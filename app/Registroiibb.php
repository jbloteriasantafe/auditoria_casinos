<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registroiibb extends Model
{
    protected $table = 'registroiibb';
    protected $primaryKey = 'id_registroiibb';
    public $timestamps = false;

    protected $fillable = [
        'fecha_iibb',
        'fecha_presentacion',
        'fecha_toma',
        'diferencia_minimo',
        'deducciones',
        'saldo_a_favor_api_contribuyentes',
        'observacion',
        'casino',
        'usuario',
        'impuesto_total_determinado'
    ];



    public function casinoiibb()
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

    public function bases()
    {
      return $this->hasMany(Registroiibb_bases::class, 'id_registroiibb', 'id_registroiibb');
    }


}
