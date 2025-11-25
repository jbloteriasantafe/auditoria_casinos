<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DenunciasAlea_plataforma extends Model
{
    protected $table = 'denunciasAlea_plataforma';
    protected $primaryKey = 'id_denunciasAlea_plataforma';
    public $timestamps = false;

    protected $fillable = [
        'plataforma',

    ];
}
