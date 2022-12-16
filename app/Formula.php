<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Formula extends Model
{
  protected $connection = 'mysql';
  protected $table = 'formula';
  protected $primaryKey = 'id_formula';
  protected $visible = array('id_formula' , 'cont1' , 'operador1', 'cont2' , 'operador2', 'cont3' , 'operador3', 'cont4' , 'operador4', 'cont5' , 'operador5', 'cont6' , 'operador6', 'cont7' , 'operador7', 'cont8'  );
  public $timestamps = false;

  public function concatenarFormula(){
    $formula= $this->cont1.$this->operador1.$this->cont2;
    $formula=($this->operador2 != null ? $formula=$formula . $this->operador2 . $this->cont3 :$formula);
    $formula=($this->operador3 != null ? $formula=$formula . $this->operador3 . $this->cont4 :$formula);
    $formula=($this->operador4 != null ? $formula=$formula . $this->operador4 . $this->cont5 :$formula);
    $formula=($this->operador5 != null ? $formula=$formula . $this->operador5 . $this->cont6 :$formula);
    $formula=($this->operador6 != null ? $formula=$formula . $this->operador6 . $this->cont7 :$formula);
    $formula=($this->operador7 != null ? $formula=$formula . $this->operador7 . $this->cont8 :$formula);
    return $formula;
  }

  public function maquinas(){
    return $this->hasMany('App\Maquina', 'id_formula', 'id_formula');
  }

  public static function boot(){
        parent::boot();
        Formula::observe(Observers\ParametrizedObserver::class);
  }

  public function getTableName(){
    return $this->table;
  }

  public function getId(){
    return $this->id_formula;
  }

}
