<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\RolObserver;

class Rol extends Model
{
  protected $connection = 'mysql';
  protected $table = 'rol';
  protected $primaryKey = 'id_rol';
  public $timestamps = false;
  protected $visible = array('id_rol','descripcion');

    public function usuarios(){
      return $this->belongsToMany('App\Usuario','usuario_tiene_rol','id_rol','id_usuario');
    }

    public function permisos(){
      return $this->belongsToMany('App\Permiso','rol_tiene_permiso','id_rol','id_permiso');
    }

    public static function boot(){
          parent::boot();
          Rol::observe(new RolObserver());
    }

    public function getTableName(){
      return $this->table;
    }

    public function getId(){
      return $this->id_rol;
    }

}
