<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LayoutTotalCodigo extends Model
{
  protected $connection = 'mysql';
  protected $table = 'layout_total_codigo';
  protected $primaryKey = 'id_layout_total_codigo';
  protected $visible = array('id_layout_total_codigo','codigo','descripcion');
  public $timestamps = false;

  public function layouts(){
    return $this->HasMany('App\DetalleLayoutTotal','codigo','co');
  }
  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_estado_movimiento;
  }
}
