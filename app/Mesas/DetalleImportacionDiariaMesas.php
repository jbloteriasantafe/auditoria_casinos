<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

//Los cierres se tratan de buscar dinamicamente, una vez encontrados se setean para evitar
//problemas en el futuro por si cambian el juego/nro de mesa 
//(los cierres estan enlazados directamente con las mesas y la "relacion dinamica" de los detalles con los cierres se romperia!) 
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
                             'reposiciones',
                             'retiros',
                             'utilidad',
                             'saldo_fichas',
                             'ajuste_fichas',
                             'id_cierre_mesa',//Se setea una vez validada la importacion
                             'id_cierre_mesa_anterior',//Se setea una vez validada la importacion
                             'observacion',
                             'hold',//dinamico
                             'conversion',//dinamico
                             'cierre',//dinamico
                             'cierre_anterior',//dinamico
                             'saldo_fichas_relevado',//dinamico
                             'diferencia_saldo_fichas'//dinamico
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

  //Si en algun momento se quisiera que se actualice de todos modos, llamar el metodo con el valor en true
  public function getCierreAttribute($actualizar_igual = false){
    $imp = $this->importacion_diaria_mesas;
    //Si esta validado, retorno lo que esta
    if(!$actualizar_igual && $imp->validado){
      return is_null($this->id_cierre_mesa)? null : Cierre::withTrashed()->find($this->id_cierre_mesa);
    }
    //Si no esta validado y ya esta seteado, lo retorno
    if(!$actualizar_igual && !is_null($this->id_cierre_mesa)){
      $cierre = Cierre::find($this->id_cierre_mesa);
      //Si lo borraron, sigo de largo para actualizarlo al mas actual
      if(!is_null($cierre)) return $cierre;
    }
    //Si no esta validado, y no esta seteado, trato de buscarle un cierre (y lo seteo)
    $mesa = $this->mesa();
    if(is_null($mesa)) return null;
    $fecha = $imp->fecha;
    $id_moneda = $imp->id_moneda;
    $cierre =  Cierre::where([['fecha','=',$fecha],['id_moneda','=',$id_moneda],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]])
    ->whereNull('deleted_at')->first();

    if(!is_null($cierre)){
      $this->id_cierre_mesa = $cierre->id_cierre_mesa;
      $this->save();//@SLOW si se actualizan muchos cierres al mismo tiempo
    }
    return $cierre;
  }
  public function getCierreAnteriorAttribute($actualizar_igual = false){
    $imp = $this->importacion_diaria_mesas;

    if(!$actualizar_igual && $imp->validado){
      return is_null($this->id_cierre_mesa_anterior)? null : Cierre::withTrashed()->find($this->id_cierre_mesa_anterior);
    }

    if(!$actualizar_igual && !is_null($this->id_cierre_mesa_anterior)){
      $cierre = Cierre::find($this->id_cierre_mesa_anterior);
      if(!is_null($cierre)) return $cierre;
    }

    $mesa = $this->mesa();
    if(is_null($mesa)) return null;
    $id_moneda = $imp->id_moneda;
    $fecha = date("Y-m-d", strtotime($imp->fecha . " -1 days"));
    $cierre =  Cierre::where([['fecha','=',$fecha],['id_moneda','=',$id_moneda],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]])
    ->whereNull('deleted_at')->orderBy('fecha','desc')->first();

    if(!is_null($cierre)){
      $this->id_cierre_mesa_anterior = $cierre->id_cierre_mesa;
      $this->save();
    }
    return $cierre;
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
