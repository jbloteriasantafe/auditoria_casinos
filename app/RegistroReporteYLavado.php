<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroReporteYLavado extends Model
{
    protected $table = 'registroReporteYLavado';
    protected $primaryKey = 'id_registroReporteYLavado';
    public $timestamps = false;

    protected $fillable = [
        'fecha_ReporteYLavado',
        'fecha_toma',
        'reporte_sistematico',
        'reporte_operaciones',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoReporteYLavado()
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
