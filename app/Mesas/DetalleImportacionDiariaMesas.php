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
                             'droop',//TOTAL
                             'droop_tarjeta',//Subtotal en tarjeta
                             'reposiciones',
                             'retiros',
                             'utilidad',
                             'saldo_fichas',
                             'propina',
                             'ajuste_fichas',
                             'id_cierre_mesa',//Se setea una vez validada la importacion
                             'id_cierre_mesa_anterior',//Se setea una vez validada la importacion
                             'observacion',
                             'sumar_propina',
                             'cotizacion_diaria',//dinamico
                             'hold',//dinamico
                             'conversion',//dinamico
                             'cierre',//dinamico
                             'cierre_anterior',//dinamico
                             'saldo_fichas_relevado',//dinamico
                             'diferencia_saldo_fichas'//dinamico
                           );
  protected $appends = array('hold','conversion','cierre','cierre_anterior','saldo_fichas_relevado','diferencia_saldo_fichas');

  public function getCotizacionDiariaAttribute(){
    return $this->importacion_diaria_mesas->cotizacion_diaria;
  }

  public function getHoldAttribute(){
      if($this->droop != 0){
        return round(($this->utilidad * 100)/$this->droop,2);
      }else{
        return '--';
      }
  }

  public function getConversionAttribute(){
    $cotizacion = $this->cotizacion_diaria;
    if(empty($cotizacion)) return '--';
    return round($cotizacion * $this->utilidad,3);
  }

  public function importacion_diaria_mesas(){
    return $this->belongsTo('App\Mesas\ImportacionDiariaMesas','id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function juego_mesa(){
    $mesa = $this->mesa();
    return is_null($mesa)? null : $mesa->juego;
  }

  public function mesa(){
    $imp       = $this->importacion_diaria_mesas;
    $fecha     = $imp->fecha;
    $id_moneda = $imp->id_moneda;
    $id_casino = $imp->id_casino;
    $siglas_juego = $this->siglas_juego;
    
    $mesa = Mesa::withTrashed()->where('mesa_de_panio.id_casino',$id_casino)
    ->where('mesa_de_panio.nro_admin','=',$this->nro_mesa)
    ->where('mesa_de_panio.nombre','LIKE',$siglas_juego.'%')
    ->where(function($q) use ($id_moneda){//Multimoneda o coincide la moneda
      return $q->whereNull('mesa_de_panio.id_moneda')->orWhere('mesa_de_panio.id_moneda','=',$id_moneda);
    })
    ->where(function($q) use ($fecha){
      return $q->whereNull('mesa_de_panio.deleted_at')->orWhere('mesa_de_panio.deleted_at','>',$fecha);
    })
    ->orderBy('mesa_de_panio.id_mesa_de_panio','desc')->first();
    
    return $mesa;
  }

  private function getCierre($fecha){;
    $mesa = $this->mesa();
    if(is_null($mesa)) return null;
    $imp = $this->importacion_diaria_mesas;
    $id_moneda = $imp->id_moneda;
    $cierres =  Cierre::where([['fecha','=',$fecha],['id_moneda','=',$id_moneda],['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]])
    ->whereNull('deleted_at')->orderBy('fecha','desc')->orderBy('hora_inicio','desc')->get()->toArray();
    usort($cierres,function($c1,$c2){
      $c1_pasa_el_dia = $c1['hora_inicio'] > $c1['hora_fin'];
      $c2_pasa_el_dia = $c2['hora_inicio'] > $c2['hora_fin'];
      if( $c1_pasa_el_dia && !$c2_pasa_el_dia) return  1;//C1 cierra despues, se le da prioridad
      if(!$c1_pasa_el_dia &&  $c2_pasa_el_dia) return -1;//C2 cierra despues, se le da prioridad
      if( $c1_pasa_el_dia ==  $c2_pasa_el_dia){//Si ambos terminan en el mismo dia, nos quedamos con el que cerro mas tarde
        if($c2['hora_fin'] > $c1['hora_fin']) return -1;//C2 cierra despues, se le da prioridad
        if($c2['hora_fin'] < $c1['hora_fin']) return  1;//C1 cierra despues, se le da prioridad
      }
      return 0;//Son iguales, indiferente
    });
    return count($cierres) > 0? 
      Cierre::find($cierres[count($cierres)-1]['id_cierre_mesa']) 
    : null;
  }

  //Si en algun momento se quisiera que se actualice de todos modos, llamar el metodo con el valor en true
  public function getCierreAttribute($actualizar = false){
    $imp = $this->importacion_diaria_mesas;
    //Si esta validado, retorno lo que esta
    if(!$actualizar && $imp->validado){
      return is_null($this->id_cierre_mesa)? null : Cierre::withTrashed()->find($this->id_cierre_mesa);
    }
    //Si no esta validado y ya esta seteado, lo retorno
    if(!$actualizar && !is_null($this->id_cierre_mesa)){
      $cierre = Cierre::find($this->id_cierre_mesa);
      //Si lo borraron, sigo de largo para actualizarlo al mas actual
      if(!is_null($cierre)) return $cierre;
    }
    //Si no esta validado, y no esta seteado, trato de buscarle un cierre (y lo seteo)
    $cierre = $this->getCierre($imp->fecha);
    if(!is_null($cierre)){
      $this->id_cierre_mesa = $cierre->id_cierre_mesa;
      $this->save();//@SLOW si se actualizan muchos cierres al mismo tiempo
    }
    return $cierre;
  }
  public function getCierreAnteriorAttribute($actualizar = false){
    $imp = $this->importacion_diaria_mesas;

    if(!$actualizar && $imp->validado){
      return is_null($this->id_cierre_mesa_anterior)? null : Cierre::withTrashed()->find($this->id_cierre_mesa_anterior);
    }

    if(!$actualizar && !is_null($this->id_cierre_mesa_anterior)){
      $cierre = Cierre::find($this->id_cierre_mesa_anterior);
      if(!is_null($cierre)) return $cierre;
    }

    $cierre = $this->getCierre(date("Y-m-d", strtotime($imp->fecha . " -1 days")));
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
    $propina = ($this->propina && $this->sumar_propina)? $this->propina : 0;
    return $this->saldo_fichas - $this->saldo_fichas_relevado + $ajuste_fichas + $propina;
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_detalle_importacion_diaria_mesas;
  }
}
