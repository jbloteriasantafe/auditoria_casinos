<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class ImportacionDiariaMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_diaria_mesas';
  protected $primaryKey = 'id_importacion_diaria_mesas';
  protected $visible = array('id_importacion_diaria_mesas',
                             'nombre_csv',
                             'fecha',
                             'validado',
                             'id_casino',
                             'id_moneda',
                             'droop',
                             'reposiciones',
                             'retiros',
                             'utilidad',
                             'saldo_fichas',
                             'cotizacion',
                             'observacion',
                             'hold',//dinamico
                             'conversion_total',//dinamico
                             'saldo_fichas_relevado',//dinamico
                             'diferencia_saldo_fichas',//dinamico
                             'ajuste_fichas',//dinamico
                           );
  protected $appends = array('hold','conversion_total','saldo_fichas_relevado','diferencia_saldo_fichas','ajuste_fichas');

  public function getHoldAttribute(){
     if($this->droop != 0){
       return round(($this->utilidad * 100)/$this->droop,2);
     }else{
       return '--';
     }
  }

  public function getConversionTotalAttribute(){
     if($this->cotizacion != 0 && $this->cotizacion != null){
       return round($this->cotizacion * $this->utilidad,3);
     }else{
       return '--';
     }
  }

  public function getSaldoFichasRelevadoAttribute(){
    $saldo = 0;
    foreach($this->detalles as $d) $saldo+=$d->saldo_fichas_relevado;
    return $saldo;
  }

  public function getAjusteFichasAttribute(){
    $ajuste = 0;
    foreach($this->detalles as $d) $ajuste+=$d->ajuste_fichas;
    return $ajuste;
  }

  public function getDiferenciaSaldoFichasAttribute(){
    return $this->saldo_fichas - $this->saldo_fichas_relevado + $this->ajuste_fichas;
  }

  public function actualizarCierres($actualizar_igual = false){
    foreach($this->detalles as $d){
      $d->getCierreAttribute($actualizar_igual);
      $d->getCierreAnteriorAttribute($actualizar_igual);
    }
  }

  public function moneda(){
    return $this->belongsTo('App\Mesas\Moneda','id_moneda','id_moneda');
  }

  public function casino(){
    return $this->belongsTo('App\Casino','id_casino','id_casino');
  }

  public function detalles(){
    return $this->hasMany('App\Mesas\DetalleImportacionDiariaMesas','id_importacion_diaria_mesas','id_importacion_diaria_mesas');
  }

  public function getTableName(){
    return $this->table;
  }
  public function getId(){
    return $this->id_importacion_diaria_mesas;
  }
}
