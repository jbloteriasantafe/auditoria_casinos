<style>
table td {
  padding: 0.1em;
}
table th {
  padding: 0.1em;
  background: #e8e8e8;
}
th.euro {
  background: #8e8ef9;
}
th.dolar {
  background: #aff98e;
}
.celda-vacia {
  border: 0 !important;
  background: white;
}
.celda-oscura {
  background: grey;
  border: 1px solid black !important;
}
.border-bottom {
  border-bottom: 1px solid black !important;
}

.mes {
  width: 9em;
}
</style>

<?php
  $año = $parametros['año'] ?? null;
  $casino = $parametros['casino'] ?? '';
  $_m = !empty($casino)? intval(substr($fecha_inicio[$casino],strlen('XXXX-'),strlen('XX'))) : '';
  $data_rel = [];
  $totales = [];
  foreach(($data[$casino] ?? []) as $_a => $_){
    $valores = [
      'valor_euro' => null,
      'valor_dolar' => null,
      'valor_euro_yoy' => null,
      'valor_dolar_yoy' => null
    ];
    
    $total = [
      'bruto_yoy' => null,
      'bruto' => null,
      'bruto_euro_yoy' => null,
      'bruto_dolar_yoy' => null,
      'bruto_euro' => null,
      'bruto_dolar' => null,
    ];
    
    $data_rel2 = [];
    $_a2 = $_a;
    for($_rm=0;$_rm<13;$_rm++){
      $d = $dataf($casino,$_a2,$_m);
      
      if($_rm == 0 || $_rm == 12){//@TODO: Para el primer y ultimo falta calcular el parcial... necesito canon diario
        foreach($total as $k => $_){
          $d->{$k} = null;
        }
        $d->variacion_euro = null;
        $d->variacion_dolar = null;
      }
      
      $data_rel2[] = $d;
      $_m++;
      if($_m > 12){
        $_a2++;
        $_m=1;
      }
      
      foreach($total as $k => $v){
        $total[$k] = bcadd_precise($v ?? '0',$d->{$k} ?? '0');
      }
      
      foreach($valores as $k => $v){
        if(!isset($d->{$k})) continue;
        if($v === null){
          $valores[$k] = $d->{$k};
          continue;
        }
        $valores[$k] = $v !== $d->{$k}? -1 : $v;
      }
    }
    $total = (object) $total;
    
    $total->variacion_euro = bcsub_precise(
      @bcdiv(bcmul_precise('100',$total->bruto_euro),$total->bruto_euro_yoy,3) ?? '100',
      '100'
    );
    $total->variacion_dolar = bcsub_precise(
      @bcdiv(bcmul_precise('100',$total->bruto_dolar),$total->bruto_dolar_yoy,3) ?? '100',
      '100'
    );
    
    $total->valor_euro  = $valores['valor_euro'] == -1? null : $valores['valor_euro'];
    $total->valor_dolar = $valores['valor_dolar'] == -1? null : $valores['valor_dolar'];
    $total->valor_euro_yoy  = $valores['valor_euro_yoy'] == -1? null : $valores['valor_euro_yoy'];
    $total->valor_dolar_yoy = $valores['valor_dolar_yoy'] == -1? null : $valores['valor_dolar_yoy'];
    $total->valor_euro_base = null;
    $total->valor_dolar_base = null;
    
    $totales[$_a] = $total;
    $data_rel[$_a] = $data_rel2;
  }
  
  $T = null;
  $D = null;
  if(intval($año)){
    $D = $data_rel[$año-1];
    $T = $totales[$año-1];
  }
?>

@if($año === null || empty($casino))
@else
<style>
.bruto_anterior,
.bruto_actual {
  width: 12em;
}
.cotizacion_euro_anterior,
.cotizacion_dolar_anterior,
.cotizacion_euro_actual,
.cotizacion_dolar_actual {
  width: 9em;
}
.valor_euro_anterior,
.valor_dolar_anterior,
.valor_euro_actual,
.valor_dolar_actual {
  width: 9.5em;
}
.variacion_euro,.variacion_dolar {
  width: 9em;
}
tbody.marcar-primer-penultimo tr:nth-child(1) td,
tbody.marcar-primer-penultimo tr:nth-child(1) th {
  background: orange;
}
tbody.marcar-primer-penultimo tr:nth-last-child(2) td,
tbody.marcar-primer-penultimo tr:nth-last-child(2) th {
  background: orange;
}
</style>
<div style="width: 100%;display: flex;align-items: baseline;">
<table style="table-layout: fixed;">
  <colgroup>
    <col class="mes">
    <col class="bruto_anterior">
    <col class="bruto_actual">
    <col class="cotizacion_euro_anterior">
    <col class="cotizacion_dolar_anterior">
    <col class="cotizacion_euro_actual">
    <col class="cotizacion_dolar_actual">
    <col class="valor_euro_anterior">
    <col class="valor_dolar_anterior">
    <col class="valor_euro_actual">
    <col class="valor_dolar_actual">
    <col class="variacion_euro">
    <col class="variacion_dolar">
  </colgroup>
  <thead>
    <tr>
      <th class="{{$abbr_casinos[$casino]}}" colspan="13">Actualización {{$casino}} {{$año}}</th>
    </tr>
  </thead>
  <thead>
    <tr>
      <th class="celda_especial" rowspan="2">Mes</th>
      <th>{{$año-1-1}}/{{$año-1}}</th>
      <th>{{$año-1}}/{{$año}}</th>
      <th colspan="2">Cotizaciones {{$año-1-1}}/{{$año-1}}</th>
      <th colspan="2">Cotizaciones {{$año-1}}/{{$año}}</th>
      <th colspan="2">{{$año-1-1}}/{{$año-1}}</th>
      <th colspan="2">{{$año-1}}/{{$año}}</th>
      <th colspan="2">Variación</th>
    </tr>
    <tr>
      <th>Rdo. Bruto</th>
      <th>Rdo. Bruto</th>
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
    </tr>
  </thead>
  <tbody class="marcar-primer-penultimo">
    @foreach($D as $d)
    <tr>
      <th>{{$meses_calendario[$d->mes]}}</th>
      <td>{{$formatear_decimal($d->bruto_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($d->bruto ?? null)}}</td>
      <td>{{$formatear_decimal($d->cotizacion_euro_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($d->cotizacion_dolar_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($d->cotizacion_euro ?? null)}}</td>
      <td>{{$formatear_decimal($d->cotizacion_dolar ?? null)}}</td>
      <td>{{$formatear_decimal($d->bruto_euro_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($d->bruto_dolar_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($d->bruto_euro ?? null)}}</td>
      <td>{{$formatear_decimal($d->bruto_dolar ?? null)}}</td>
      <td>{{$formatear_porcentaje($d->variacion_euro ?? null)}}</td>
      <td>{{$formatear_porcentaje($d->variacion_dolar ?? null)}}</td>
    </tr>
    @endforeach
    <tr>
      <th class="celda_especial">Total</th>
      <td>{{$formatear_decimal($T->bruto_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto ?? null)}}</td>
      <td style="border-top: 1px solid black;border-bottom: 0;" colspan="4">&nbsp;</td>
      <td>{{$formatear_decimal($T->bruto_euro_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_dolar_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_euro ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_dolar ?? null)}}</td>
      <td>{{$formatear_porcentaje($T->variacion_euro ?? null)}}</td>
      <td>{{$formatear_porcentaje($T->variacion_dolar ?? null)}}</td>
    </tr>
  </tbody>
  <tbody>
    <tr>
      <td class="celda-vacia" colspan="13">&nbsp;</td>
    </tr>
  </tbody>
  <thead>
    <th>Actualización</th>
    <th>Valores {{$año-1}}/{{$año}}</th>
    <th>Montos {{$año-1-1}}/{{$año-1}}</th>
    <th>Montos {{$año-1}}/{{$año}}</th>
    <th>% Variac.</th>
    <th>Valores Base {{$año-1}}/{{$año}}</th>
    <th>Valores Base {{$año}}/{{$año+1}}</th>
    <th>Valores {{$año}}/{{$año+1}}</th>
    <th class="celda-vacia" colspan="5">&nbsp;</th>
  </thead>
  <tbody>
    <tr>
      <th class="euro">Euro</th>
      <td>{{$formatear_decimal($T->valor_euro_yoy)}}</td>
      <td>{{$formatear_decimal($T->bruto_euro_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_euro)}}</td>
      <td>{{$formatear_porcentaje($T->variacion_euro ?? null)}}</td>
      <td>{{$formatear_decimal($T->valor_euro_base)}}</td>
      <td>{{$formatear_decimal(bcmul(
        $T->valor_euro_base,
        bcadd_precise('1',bcdiv($T->variacion_euro,'100',5)),
        2
      ))}}</td>
      <td>{{$formatear_decimal($T->valor_euro)}}</td>
      <td class="celda-vacia" colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <th class="dolar">Dólar</th>
      <td>{{$formatear_decimal($T->valor_dolar_yoy)}}</td>
      <td>{{$formatear_decimal($T->bruto_dolar_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_dolar ?? null)}}</td>
      <td>{{$formatear_porcentaje($T->variacion_dolar ?? null)}}</td>
      <td>{{$formatear_decimal($T->valor_dolar_base)}}</td>
      <td>{{$formatear_decimal(bcmul(
        $T->valor_dolar_base,
        bcadd_precise('1',bcdiv($T->variacion_dolar,'100',5)),
        2
      ))}}</td>
      <td>{{$formatear_decimal($T->valor_dolar)}}</td>
      <td class="celda-vacia" colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td class="celda-vacia" colspan="13">&nbsp;</td>
    </tr>
  </tbody>
</table>
</div>
@endif
