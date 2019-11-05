<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class FichaTieneCasino extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'ficha_tiene_casino';
  protected $primaryKey = 'id_ficha_tiene_casino';
  protected $visible = array('id_ficha','id_casino','id_ficha_tiene_casino',
                              'created_at','updated_at','deleted_at',
                            'id_moneda','valor_ficha','descripcion'
                          );
  protected $fillable = ['id_ficha','id_casino'];
  protected $appends = array('id_moneda','valor_ficha','descripcion');

  public function getIdMonedaAttribute(){
    return $this->ficha->id_moneda;
  }

  public function getValorFichaAttribute(){
    return $this->ficha->valor_ficha;
  }

  public function getDescripcionAttribute(){
    return $this->ficha->moneda->descripcion;
  }

  public function ficha(){
    return $this->belongsTo('App\Mesas\Ficha','id_ficha','id_ficha');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_ficha_tiene_casino;
  }
}
