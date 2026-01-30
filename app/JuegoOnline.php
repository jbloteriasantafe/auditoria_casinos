<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JuegoOnline extends Model
{
    protected $connection = 'mysql_online';
    protected $table = 'juego';
    protected $primaryKey = 'id_juego';
    public $timestamps = false; // Assuming legacy table might not have timestamps or we don't need them for reading

    protected $visible = [
        'id_juego', 
        'nombre_juego', 
        'cod_juego', 
        'porcentaje_devolucion', 
        'proveedor', 
        'codigo_operador',
        'escritorio',
        'movil'
    ];

    public function categoria_juego(){
        return $this->belongsTo('App\CategoriaJuegoOnline','id_categoria_juego','id_categoria_juego');
    }
}
