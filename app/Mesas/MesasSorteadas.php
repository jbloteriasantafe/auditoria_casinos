<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class MesasSorteadas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'mesas_sorteadas';
  protected $primaryKey = 'id_mesas_sorteadas';
  protected $visible = array('id_mesas_sorteadas','mesas','id_casino','created_at');
  public $timestamps = false;
  protected $fillable = ['mesas','fecha_backup'];
  protected $casts = [
    'mesas' => 'array'
];

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_mesas_sorteadas;
  }
}
