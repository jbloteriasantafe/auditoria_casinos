<?php

namespace App\Mesas;

use Illuminate\Database\Eloquent\Model;

class ImportacionDiariaMesas extends Model
{
  protected $connection = 'mysql';
  protected $table = 'importacion_diaria_mesas';
  protected $primaryKey = 'id_importacion_diaria_mesas';
  protected $visible = array('id_importacion_diaria_mesas',
                             'fecha',
                             'nombre_csv',
                             'id_casino',
                             'id_moneda',
                             'total_diario',
                             'diferencias',
                             'observacion',
                             'validado',
                             'cotizacion',
                             'utilidad_diaria_calculada',
                             'saldo_diario_fichas',
                             'total_diario_retiros',
                             'total_diario_reposiciones',
                             'utilidad_diaria_total',
                             'hold_diario',
                             'conversion_total',
                             'saldo_fichas_relevado',
                             'diferencia_saldo_fichas',
                             'ajuste_fichas'
                           );
  protected $appends = array('hold_diario','conversion_total','saldo_fichas_relevado','diferencia_saldo_fichas','ajuste_fichas');

  public function getHoldDiarioAttribute(){
     if($this->total_diario != 0){
       return round(($this->utilidad_diaria_total * 100)/$this->total_diario,2);
     }else{
       return '--';
     }

  }

  public function getConversionTotalAttribute(){
     if($this->cotizacion != 0 && $this->cotizacion != null){
       return round($this->cotizacion * $this->utilidad_diaria_total,3);
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
    return $this->saldo_diario_fichas - $this->saldo_fichas_relevado + $this->ajuste_fichas;
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

  public function detallesConDiferencias(){
    return $this->detalles()->where('diferencia_cierre','<>',0)
    ->get()
    ->sortBy('codigo_mesa')
    ->values();
  }

}
