<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RegistroPromoTickets extends Model
{
    protected $table = 'registroPromoTickets';
    protected $primaryKey = 'id_registroPromoTickets';
    public $timestamps = false;

    protected $fillable = [
        'fecha_PromoTickets',
        'fecha_toma',
        'importe',
        'importe_usd',
        'cantidad',
        'archivo',
        'casino',
        'usuario'
    ];



    public function casinoPromoTickets()
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
