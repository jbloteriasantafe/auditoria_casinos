<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotaComentario extends Model
{
    protected $connection = 'mysql';
    protected $table = 'nota_comentarios';
    protected $primaryKey = 'id_comentario';
    protected $fillable = ['id_nota', 'id_usuario', 'comentario'];
    public $timestamps = true;

    public function nota(){
        return $this->belongsTo('App\Nota','id_nota','id_nota');
    }

    public function usuario(){
        return $this->belongsTo('App\Usuario','id_usuario','id_usuario');
    }
}
