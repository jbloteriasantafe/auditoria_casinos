<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class CierreMesa extends Model
{
    protected $connection = 'mysql';
    protected $table = 'cierre_mesa';
    protected $primaryKey = 'id_cierre_mesa';
    protected $visible = array('id_cierre_mesa','hora_inicio','hora_fin',
                              'total_fichas_c','total_anticipos_c',
                              'id_fiscalizador','id_tipo_cierre',
                              'id_mesa_de_panio','id_estado_cierre','fecha');
    public $timestamps = false;

    public function fiscalizador(){
      return $this->belongsTo('App\Usuario', 'id_fiscalizador', 'id_usuario');
    }

    public function tipo_cierre(){
      return $this->belongsTo('App\Mesas\TipoCierre', 'id_tipo_cierre', 'id_tipo_cierre');
    }

    public function mesa(){
      return $this->belongsTo('App\Mesas\MesaDePanio', 'id_mesa_de_panio', 'id_mesa_de_panio');
    }

    public function estado_cierre(){
      return $this->belongsTo('App\Mesas\EstadoCierre', 'id_estado_cierre', 'id_estado_cierre');
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_cierre_mesa;
    }

}
