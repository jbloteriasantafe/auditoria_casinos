<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectorMesas extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'sector_mesas';
  protected $primaryKey = 'id_sector_mesas';
  protected $visible = array('id_sector_mesas','descripcion','id_casino');
  protected $appends = array('lista_mesas');
  protected $fillable = ['descripcion','id_casino'];

  public function getListaMesasAttribute(){
    if(isset($this->mesas)){
      $mesas = "";
      $total = count($this->mesas);
      $aux = 1;
      foreach ($this->mesas as $m) {
        if($aux == $total){
          $mesas = $mesas . $m->nro_mesa;
        }else{
          $mesas = $mesas . $m->nro_mesa. " - ";
        }
        $aux++;
      }
      return $mesas;
    }else{
      return "Sin mesas";
    }
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function mesas(){
    return $this->HasMany('App\Mesas\Mesa','id_sector_mesas','id_sector_mesas');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_sector_mesas;
  }
}
