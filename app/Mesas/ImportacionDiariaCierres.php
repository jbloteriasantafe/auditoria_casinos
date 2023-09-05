<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class ImportacionDiariaCierresObserver extends \App\Observers\ParametrizedObserver {
  public function __construct(){
    parent::__construct('id_casino','id_moneda','fecha','nombre_csv','md5');
  }
}

class ImportacionDiariaCierres extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_diaria_cierres';
  protected $primaryKey = 'id_importacion_diaria_cierres';
  protected $visible = [
    'id_importacion_diaria_cierres','id_casino','id_moneda',
    'fecha','nombre_csv','md5'
  ];
  public $timestamps = false;

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function cierres(){
    return $this->hasMany('App\Mesas\Cierre','id_importacion_diaria_cierres','id_importacion_diaria_cierres');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_importacion_diaria_cierres;
  }
  
  public static function boot(){
    parent::boot();
    self::observe(new ImportacionDiariaCierresObserver());
  }
}
