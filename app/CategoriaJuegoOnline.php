<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoriaJuegoOnline extends Model
{
    protected $connection = 'mysql_online';
    protected $table = 'categoria_juego';
    protected $primaryKey = 'id_categoria_juego';
    public $timestamps = false;
}
