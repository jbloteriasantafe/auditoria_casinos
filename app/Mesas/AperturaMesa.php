<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class AperturaMesa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'apertura_mesa';
    protected $primaryKey = 'id_apertura_mesa';
    protected $visible = array('id_apertura_mesa','hora','total_fichas_a',
                              'total_anticipos_a','id_fiscalizador','id_tipo_cierre',
                              'id_mesa_de_panio','fecha');
    public $timestamps = false;

    public function fiscalizador(){
      return $this->belongsTo('App\Usuario', 'id_fiscalizador', 'id_usuario');
    }

    public function mesa(){
      return $this->belongsTo('App\Mesas\MesaDePanio', 'id_mesa_de_panio', 'id_mesa_de_panio');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_cierre_mesa;
    }

}
