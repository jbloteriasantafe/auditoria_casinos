<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CampoModificado extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'campo_modificado_mesas';
  protected $primaryKey = 'id_campo_modificado';
  protected $visible = array('id_campo_modificado','id_importacion_diaria_mesas',
                              'id_entidad','valor_anterior',
                              'nombre_entidad','nombre_del_campo',
                              'created_at', 'id_entidad_extra',
                              'updated_at','deleted_at','nombre_entidad_extra',
                              'accion'
                              );
  public $timestamps = false;

  protected $fillable = ['id_entidad','nombre_entidad','id_entidad_extra',
                          'valor_anterior','nombre_entidad_extra','accion',
                          'id_importacion_diaria_mesas','nombre_del_campo'
                        ];


  public function importacion(){
    return $this->belongsTo('App\Mesas\ImportacionDiaria',
                  'id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_campo_modificado;
  }
}
