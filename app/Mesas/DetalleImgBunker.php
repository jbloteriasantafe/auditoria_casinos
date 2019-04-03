<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class DetalleImgBunker extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'detalle_img_bunker';
  protected $primaryKey = 'id_detalle_img_bunker';
  protected $visible = array('id_detalle_img_bunker','fecha',
                              'duracion_cd' ,
                              'nombre_cd',
                              'drop_visto' ,
                              'diferencias' ,
                              'minutos_captura' ,
                              'id_imagenes_bunker',
                              'id_mesa_de_panio',
                              'codigo_mesa'

                            );


  public function imagen_bunker(){
    return $this->belongsTo('App\Mesas\DetalleImgBunker',
                            'id_imagenes_bunker','id_imagenes_bunker');
  }

  public function mesa(){
    return $this->belongsTo('App\Mesas\Mesa','id_mesa_de_panio','id_mesa_de_panio');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_img_bunker;
  }
}
