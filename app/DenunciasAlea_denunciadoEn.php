<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DenunciasAlea_denunciadoEn extends Model
{
    protected $table = 'denunciasAlea_denunciadoEn';
    protected $primaryKey = 'id_denunciasAlea_denunciadoEn';
    public $timestamps = false;

    protected $fillable = [
        'lugar',

    ];
}
