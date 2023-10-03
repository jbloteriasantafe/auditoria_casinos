<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
                             'cierres',//dinamico
                             'saldo_fichas_relevado',//dinamico
                             'diferencia_saldo_fichas'//dinamico
                           );
  protected $appends = array('hold','conversion','cierres','saldo_fichas_relevado','diferencia_saldo_fichas');

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

  //Si en algun momento se quisiera que se actualice de todos modos, llamar el metodo con el valor en true
  public function getCierresAttribute(){
    $imp = $this->importacion_diaria_mesas;
    
    if($imp->validado){//Si esta validado, retorno lo que esta    
      return [
        is_null($this->id_cierre_mesa_anterior)? null 
        : Cierre::withTrashed()->find($this->id_cierre_mesa_anterior),
        is_null($this->id_cierre_mesa)? null 
        : Cierre::withTrashed()->find($this->id_cierre_mesa),
      ];
    }
    
    //Si no esta validado SIEMPRE lo actualizo al consultarse
    $cierres = null;
    {
      $mesa = $this->mesa();
      if(is_null($mesa)) return [null,null];
      
      $cierres = Cierre::where([
        ['fecha','<=',$imp->fecha],
        ['id_moneda','=', $imp->id_moneda],
        ['id_mesa_de_panio','=',$mesa->id_mesa_de_panio]
      ])->whereNull('deleted_at')
      ->orderBy('fecha','desc')
      ->orderBy('hora_inicio','desc')
      //Si termina despues del dia se le da prioridad
      ->orderBy(DB::raw('IF(hora_inicio > hora_fin,CONCAT(9,hora_fin),hora_fin)'),'desc')
      ->take(2)->get()->reverse()->values();
    }
    
    $cierres_a_guardar = [];
    switch($cierres->count()){
      case 2:{
        if($cierres[1]->fecha == $imp->fecha){
          $cierres_a_guardar = [$cierres[0],$cierres[1]];
        }
        else{
          //El ultimo es anterior a la fecha,
          //por lo que no esta informado en la importacion
          //El anterior es el ultimo informado y el "actual" tambien
          $cierres_a_guardar = [$cierres[1],$cierres[1]];
        }
      }break;
      case 1:{//Primer cierre de una mesa nueva
        $cierres_a_guardar = [$cierres[0],$cierres[0]];
      }break;
      default:{//No hay cierres informados de la mesa
        $cierres_a_guardar = [null,null];
      }break;
    }
    
    $this->id_cierre_mesa_anterior = is_null($cierres_a_guardar[0])? 
      null 
      : $cierres_a_guardar[0]->id_cierre_mesa;
    $this->id_cierre_mesa          = is_null($cierres_a_guardar[1])? 
      null 
      : $cierres_a_guardar[1]->id_cierre_mesa;
    
    $this->save();
    
    return $cierres_a_guardar;
  }

  public function getSaldoFichasRelevadoAttribute(){   
    $fichas = array_map(function($c){
      if(is_null($c)) return 0;
      return $c->total_pesos_fichas_c;
    },$this->cierres);
    
    return $fichas[1]-$fichas[0];
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
