<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MesaObserver extends \App\Observers\EntityObserver
{
   public function creating(Mesa $mesa)
   {
      $mesa->nombre = strtoupper($mesa->nombre);
   }

   public function getDetalles($entidad){
     $detalles = array(//para cada modelo poner los atributos más importantes
       array('nro_mesa', $entidad->nro_mesa),
       array('nombre', $entidad->nombre),
       array('descripcion', $entidad->descripcion),
       array('id_juego_mesa', $entidad->id_juego_mesa),
       array('id_moneda', $entidad->id_moneda),
       array('id_sector_mesas', $entidad->id_sector_mesas),
     );
     return $detalles;
   }
}

class Mesa extends Model
{
  use SoftDeletes;
  protected $connection = 'mysql';
  protected $table = 'mesa_de_panio';
  protected $primaryKey = 'id_mesa_de_panio';
  protected $visible = array('id_mesa_de_panio','nro_mesa','nombre','descripcion',
                             'id_juego_mesa','id_casino','id_moneda','id_sector_mesas',
                             'multimoneda','codigo_mesa','nro_admin','codigo_sector','nombre_sector');


  protected $fillable = ['nro_mesa','nombre','descripcion','nro_admin',
                             'id_juego_mesa','id_casino','id_moneda','id_sector_mesas','multimoneda'];


  protected $appends = array('codigo_mesa','codigo_sector','nombre_sector');


  public function getCodigoMesaAttribute(){
      if(isset($this->juego)){
        $j =$this->juego;
        if($this->nro_admin < 10){
        return $j->siglas.'-'.'0'.$this->nro_admin;
      }else {
        return $j->siglas.'-'.$this->nro_admin;
      }
      }else{
        return $this->nro_admin;
      }
    }
    public function getCodigoSectorAttribute(){
          if(isset($this->sector)){
            $j =$this->sector;
            return $j->descripcion.'-'.$this->codigo_mesa;
          }else{
            return $this->codigo_mesa;
          }
        }

        public function getNombreSectorAttribute(){
     if(isset($this->sector)){
       $j =$this->sector;
       return $j->descripcion;
     }else{
       return 's/s';
     }
   }
  public function sector(){
    return $this->belongsTo('App\Mesas\SectorMesas','id_sector_mesas','id_sector_mesas');
  }

  public function juego(){
    return $this->belongsTo('App\Mesas\JuegoMesa','id_juego_mesa','id_juego_mesa');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function aperturas_a_pedido(){
    return $this->hasMany('App\Mesas\AperturaAPedido','id_mesa_de_panio','id_mesa_de_panio');
  }

  //ELIMINAR EL ATRIBUTO DE LA BD!!! en enlace que esta funcionando es por juego()->tipo_mesa()
  /*
  public function tipo_mesa(){
    return $this->belongsTo('App\Mesas\TipoMesa','id_tipo_mesa','id_tipo_mesa');
  }*/

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_mesa_de_panio;
  }
  public static function boot(){
    parent::boot();
    Mesa::observe(new MesaObserver());
  }
}
