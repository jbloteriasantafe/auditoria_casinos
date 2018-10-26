<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Observers\DisposicionObserver;

class Disposicion extends Model
{
  protected $connection = 'mysql';
  protected $table = 'disposicion';
  protected $primaryKey = 'id_disposicion';
  protected $visible = array('id_disposicion','nro_disposicion','nro_disposicion_anio','descripcion');
  public $timestamps = false;

  public function expediente(){
    return $this->belongsTo('App\Expediente','id_expediente','id_expediente');
  }
  public function nota(){
    return $this->belongsTo('App\Nota','id_nota','id_nota');
  }
  public static function boot(){
        parent::boot();
        Disposicion::observe(new DisposicionObserver());
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_disposicion;
  }

}
