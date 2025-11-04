<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Registro_archivo extends Model
{
    protected $table = 'registro_archivo';
    protected $primaryKey = 'id_registro_archivo';
    public $timestamps = false;

    protected $fillable = [
        'path',
        'fileable_id',
        'fileable_type',
        'usuario',
        'fecha_toma',
    ];


    public function fileable()
    {
        return $this->morphTo('fileable', 'fileable_type', 'fileable_id');
    }

    public function usuario(){
      return $this->belongsTo(Usuario::class,'usuario','id_usuario');
    }



}
