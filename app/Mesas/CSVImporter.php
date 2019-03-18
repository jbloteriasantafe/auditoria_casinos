<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CSVImporter extends Model
{
  protected $connection = 'mysql';
  protected $table = 'filas_csv_mesas_bingos';
  protected $primaryKey = 'id';
  protected $visible = array('id','row_1','row_2','row_3','row_4','row_7','row_8','row_9');

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_ficha;
  }
}
