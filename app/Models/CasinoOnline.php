<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CasinoOnline extends Model
{
    protected $connection = 'mysql_online';
    protected $table = 'plataforma';
    protected $primaryKey = 'id_plataforma';
    
    // Allow read only effectively for this module
    protected $fillable = [];
}
