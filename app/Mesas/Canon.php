<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
//los valores bases son los que pueden ser menor al monto pagado,
//ya que el base incial pudo haber aumentado, y despues el casino tuvo perdidas,
//pero el canon no puede bajar.
class Canon extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'canon_mesas';
  protected $primaryKey = 'id_canon_mesas';
  protected $visible = array('id_canon_mesas','periodo_anio_inicio','id_casino',
                              'valor_base_dolar','valor_base_euro','periodo_anio_fin',
                              'created_at', 'valor_real',
                              'updated_at','deleted_at','nombre',
                              'valor_real_dolar','valor_real_euro',
                              );
  public $timestamps = false;
  protected $fillable = ['periodo_anio_fin','periodo_anio_inicio',
                        'id_casino','valor_base_dolar','valor_real_dolar',
                        'valor_real_euro','valor_base_euro'
                        ];


  protected $appends = array('nombre');

  public function getNombreAttribute(){
    return $this->casino->nombre;
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_canon_mesas;
  }
}
