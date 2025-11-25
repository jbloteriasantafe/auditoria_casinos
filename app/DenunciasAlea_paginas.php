<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DenunciasAlea_paginas extends Model
{
    protected $table = 'denunciasAlea_paginas';
    protected $primaryKey = 'id_denunciasAlea_paginas';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'user_pag',
        'plataforma',
        'link_pagina',
        'denunciada',
        'cant_denuncias',
        'estado_denuncia',
        'denunciado_en',
        'usuario'

    ];

    public function plat(){
      return $this->belongsTo('App\DenunciasAlea_plataforma','plataforma','id_denunciasAlea_plataforma');
    }
    public function estado(){
      return $this->belongsTo('App\DenunciasAlea_estado','estado_denuncia','id_denunciasAlea_estado');
    }
    public function lugar(){
      return $this->belongsTo('App\DenunciasAlea_denunciadoEn','denunciado_en','id_denunciasAlea_denunciadoEn');
    }

}
