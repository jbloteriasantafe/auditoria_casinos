<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
//Hay valores que se calculan  dinamicamente pero una vez validada la importacion se setean
//Esto es porque las categorias y numeros de las mesas pueden cambiar. Entonces 1 mes puede 
//"asignarle" un cierre y otro mes puede "asignarle" otro cierre si el ADM cambia la mesa.
//Para evitar esto, al principio se le asigna por fecha, nombre de juego y numero de mesa
//Pero luego una vez validado se lo "concreta" como una relacion en ids. Entonces si 1 año
//despues vas a la misma importacion, te deberia devolver los mismos valores (mientras no se toquen los cierres)
class DetalleImportacionDiariaMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'detalle_importacion_diaria_mesas';
  protected $primaryKey = 'id_detalle_importacion_diaria_mesas';
  protected $visible = array('id_detalle_importacion_diaria_mesas',
                             'id_importacion_diaria_mesas',
                             'siglas_juego',
                             'nro_mesa',//es el nro_admin de la mesa
                             'droop',
                             'utilidad',
                             'reposiciones',
                             'retiros',
                             'saldo_fichas',
                             'utilidad_calculada',
                             'hold',
                             'id_cierre_mesa',//Se setea una vez validada la importacion
                             'id_cierre_mesa_anterior',//Se setea una vez validada la importacion
                             'ajuste_fichas',
                             'observacion',
                             'cierre',
                             'cierre_anterior',
                             'saldo_fichas_relevado',
                             'diferencia_saldo_fichas'
                           );
  protected $appends = array('hold','conversion','cierre','cierre_anterior','saldo_fichas_relevado','diferencia_saldo_fichas');

  public function getHoldAttribute(){
      if($this->droop != 0){
        return round(($this->utilidad * 100)/$this->droop,2);
      }else{
        return '--';
      }
  }

  public function getConversionAttribute(){
    $cotizacion = $this->importacion_diaria_mesas->cotizacion;
    if(empty($cotizacion)) return '--';
    
    return round($this->importacion_diaria_mesas->cotizacion * $this->utilidad,3);
  }
  public function importacion_diaria_mesas(){
    return $this->belongsTo('App\Mesas\ImportacionDiariaMesas','id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function juego_mesa(){
    $imp = $this->importacion_diaria_mesas;
    $id_casino = $imp->id_casino;
    $id_moneda = $imp->id_moneda;
    $fecha     = $imp->fecha;
    $siglas_juego = $this->siglas_juego;
    
    $juego =  JuegoMesa::withTrashed()->where('juego_mesa.id_casino',$id_casino)
    ->where(function($q) use ($siglas_juego){
      return $q->where('juego_mesa.siglas','like',$siglas_juego)->orWhere('juego_mesa.nombre_juego','like',$siglas_juego);
    })
    ->where(function ($q) use ($fecha){
      return $q->whereNull('juego_mesa.deleted_at')->orWhere('juego_mesa.deleted_at','>',$fecha);
    })->first();
    return $juego;
  }

  public function mesa(){
    $juego = $this->juego_mesa();
    if(is_null($juego)) return null;

    $imp       = $this->importacion_diaria_mesas;
    $fecha     = $imp->fecha;
    $id_moneda = $imp->id_moneda;
    
    $mesa = $juego->mesas()
    ->where('mesa_de_panio.nro_admin','=',$this->nro_mesa)
    ->where(function($q) use ($fecha){
      return $q->whereNull('mesa_de_panio.deleted_at')->orWhere('mesa_de_panio.deleted_at','>',$fecha);
    })
    ->where(function($q) use ($id_moneda){//Multimoneda o coincide la moneda
      return $q->whereNull('mesa_de_panio.id_moneda')->orWhere('mesa_de_panio.id_moneda','=',$id_moneda);
    })->first();
    return $mesa;
  }

  public function getCierreAttribute(){
    $imp = $this->importacion_diaria_mesas;
    if($imp->validado) return is_null($this->id_cierre_mesa)? null : Cierre::withTrashed()->find($this->id_cierre_mesa);

    $mesa = $this->mesa();
    if(is_null($mesa)) return null;
    $fecha = $imp->fecha;
    $id_moneda = $imp->id_moneda;

    return Cierre::where([['fecha','=',$fecha],['id_moneda','=',$id_moneda],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]])
    ->whereNull('deleted_at')->first();
  }
  public function getCierreAnteriorAttribute(){
    $imp = $this->importacion_diaria_mesas;
    if($imp->validado) return is_null($this->id_cierre_mesa_anterior)? null : Cierre::withTrashed()->find($this->id_cierre_mesa_anterior);

    $mesa = $this->mesa();
    if(is_null($mesa)) return null;
    $fecha = $imp->fecha;
    $id_moneda = $imp->id_moneda;

    return Cierre::where([['fecha','<',$fecha],['id_moneda','=',$id_moneda],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]])
    ->whereNull('deleted_at')
    ->orderBy('fecha','desc')->first();
  }

  public function getSaldoFichasRelevadoAttribute(){
    $fichas = $this->cierre;
    $fichas = is_null($fichas)? 0 : $fichas->total_pesos_fichas_c;
    $fichas_anterior = $this->cierre_anterior;
    $fichas_anterior = is_null($fichas_anterior)? 0 : $fichas_anterior->total_pesos_fichas_c;
    $saldo_relevado = $fichas - $fichas_anterior;
    return $saldo_relevado;
  }    

  public function getDiferenciaSaldoFichasAttribute(){
    $ajuste_fichas = $this->ajuste_fichas? $this->ajuste_fichas : 0;
    return $this->saldo_fichas - $this->saldo_fichas_relevado + $ajuste_fichas;
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_importacion_diaria_mesas;
  }
}
