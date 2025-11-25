<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DenunciasAlea_estado extends Model
{
    protected $table = 'denunciasAlea_estado';
    protected $primaryKey = 'id_denunciasAlea_estado';
    public $timestamps = false;

    protected $fillable = [
        'estado',

    ];
}
