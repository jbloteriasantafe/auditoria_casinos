<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

//falta relacionar estado con estado relevamiento

class ImagenesBunker extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'imagenes_bunker';
  protected $primaryKey = 'id_imagenes_bunker';
  protected $visible = array('id_imagenes_bunker','fechas',
                              'mesas','created_at','updated_at',
                              'observaciones', 'id_estado_relevamiento',
                              'mes_anio','id_casino','created_at',
                              'updated_at','deleted_at','nombre');

  protected $appends = array('nombre');



   public function getNombreAttribute(){
     return $this->casino->nombre;
   }

  public function detalles(){
    return $this->HasMany('App\Mesas\DetalleImgBunker','id_imagenes_bunker','id_imagenes_bunker');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function estado(){
    return $this->belongsTo('App\EstadoRelevamiento','id_estado_relevamiento','id_estado_relevamiento');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_imagenes_bunker;
  }
}
