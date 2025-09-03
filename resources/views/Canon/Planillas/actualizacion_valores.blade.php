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
  $_m = !empty($casino)? intval($primer_mes) : '';
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
@elseif($año == 'evolucion_cotizacion')
<?php 
  $años_por_fila = 3;
  $width_año = $años_por_fila? ((100-4)/$años_por_fila) : 0;
?>
<style>
  .euro,.dolar {
    width: 8em;
  }
  .fecha_cotizacion {
    width: 9em;
  }
</style>
<div style="width: 100%;display: flex;align-items: baseline;">
<table style="table-layout: fixed;">
  <colgroup>
    <?php 
      $columnas_cots = $años_por_fila*3+1;
      $columnas_vars = 13;
      $columnas = max($columnas_cots,$columnas_vars);
      $pad_cots = $columnas-$columnas_cots;
      $pad_vars = $columnas-$columnas_vars;
    ?>
    @if($columnas_cots>$columnas_vars)
    <col class="mes">
    @for($_a=0;$_a<$años_por_fila;$_a++)
    <col class="euro">
    <col class="dolar">
    <col class="fecha_cotizacion">
    @endfor
    @else
    <col class="varcot_año">
    <col class="varcot_euro_periodo">
    <col class="varcot_euro">
    <col class="varcot_euro_var">
    <col class="varcot_dolar_periodo">
    <col class="varcot_dolar">
    <col class="varcot_dolar_var">
    <col class="vacia2">
    <col class="varrec_periodo">
    <col class="varrec_euro">
    <col class="varrec_euro_var">
    <col class="varrec_dolar">
    <col class="varrec_dolar_var">
    @endif
  </colgroup>
  <thead>
    <tr>
      <th colspan="{{$columnas_cots}}">Cotizaciones</th>
      @if($pad_cots>0)
      <th class="celda-vacia" colspan="{{$pad_cots}}">&nbsp;</th>
      @endif
    </tr>
  </thead>
  <?php
    $cas = $abbr_casinos[$casino];
  ?>
  @for($_aabs=$primer_año;$_aabs<=$ultimo_año;$_aabs+=$años_por_fila)
  <thead>
    <tr>
      <th class="celda_especial" rowspan="2">Mes</th>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php 
        $_a2 = $_aabs+$_a;
        $invalido = ($_a2 < $primer_año)
                 || ($_a2 > $ultimo_año);
      ?>
      @if(!$invalido)
      <th class="{{$cas}}" colspan="3">{{$cas}} {{$_a2}}</th>
      @else
      <th class="celda-vacia celda-oscura" colspan="3">&nbsp;</th>
      @endif
      @endfor
      @if($pad_cots>0)
      <th class="celda-vacia" colspan="{{$pad_cots}}">&nbsp;</th>
      @endif
    </tr>
    <tr>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php 
        $_a2 = $_aabs+$_a;
        $invalido = ($_a2 < $primer_año)
                 || ($_a2 > $ultimo_año);
      ?>
      @if(!$invalido)
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th>Fecha Cotización</th>
      @else
      <th class="celda-vacia celda-oscura">&nbsp;</th>
      <th class="celda-vacia celda-oscura">&nbsp;</th>
      <th class="celda-vacia celda-oscura">&nbsp;</th>
      @endif
      @endfor
      @if($pad_cots>0)
      <th class="celda-vacia" colspan="{{$pad_cots}}">&nbsp;</th>
      @endif
    </tr>
  </thead>
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <th>{{$meses_calendario[$_mnum]}}</th>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php 
        $_a2 = $_aabs+$_a;
        $invalido = $_a2 < $primer_año || $_a2 > $ultimo_año
        || ($_a2 == $primer_año && $_mnum < $primer_mes)
        || ($_a2 == $ultimo_año && $_mnum > $ultimo_mes);
        $d = $dataf($casino,$_a2,$_mnum);
      ?>
      @if(!$invalido)
      <td>{{$formatear_decimal($d->cotizacion_euro ?? null)}}</td>
      <td>{{$formatear_decimal($d->cotizacion_dolar ?? null)}}</td>
      <td>{{$d->fecha_cotizacion ?? '-'}}</td>
      @else
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      @endif
      @endfor
      @if($pad_cots>0)
      <td class="celda-vacia" colspan="{{$pad_cots}}">&nbsp;</td>
      @endif
    </tr>
    @endfor
    <tr>
      <?php
        $bb = ($_aabs+$años_por_fila>$ultimo_año)? 'border-bottom' : '';
      ?>
      <td class="{{$bb}}" colspan="{{$columnas_cots}}">&nbsp;</td>
      @if($pad_cots>0)
      <td class="celda-vacia {{$bb}}" colspan="{{$pad_cots}}">&nbsp;</td>
      @endif
    </tr>
  </tbody>
  @endfor
  <?php
    $filas_cuerpo = ($ultimo_año-$primer_año+1)*2;
  ?>
  <thead>
    <tr>
      <th colspan="7">Variación de cotización de Moneda Extranjera</th>
      <th class="celda-vacia" rowspan="2" style="border-right: 1px solid black !important;">&nbsp;</th>
      <th colspan="5">Variación de recaudación</th>
      @if($pad_vars>0)
      <th class="celda-vacia" colspan="{{$pad_vars}}">&nbsp;</th>
      @endif
    </tr>
    <tr>
      <th class="celda_especial">Año</th>
      <th class="euro" colspan="2">Euro</th>
      <th>%</th>
      <th class="dolar" colspan="2">Dólar</th>
      <th>%</th>
      <th class="celda_especial">Período</th>
      <th class="euro" colspan="2">Euro</th>
      <th class="dolar" colspan="2">Dólar</th>
      @if($pad_vars>0)
      <th class="celda-vacia" colspan="{{$pad_vars}}">&nbsp;</th>
      @endif
    </tr>
  </thead>
  <tbody>
    <?php
      $mes_cotizacion = intval($primer_mes);
      $mes_cotizacion = ($mes_cotizacion+1)%12;
      $mes_cotizacion = $mes_cotizacion==0? 1 : $mes_cotizacion;
      $mes_cotizacion_str = str_pad($mes_cotizacion,2,'0',STR_PAD_LEFT);
      $primer_cotizacion_euro = null;
      $ultima_cotizacion_euro = null;
      $primer_cotizacion_dolar = null;
      $ultima_cotizacion_dolar = null;
      $primer_bruto_euro = null;
      $ultimo_bruto_euro = null;
      $primer_bruto_dolar = null;
      $ultimo_bruto_dolar = null;
      $fdiv = function($a,$b){
        if($b != 0) return $a/$b;
        if($a > 0)  return INF;
        if($a < 0)  return -INF;
        return null;
      };
    ?>
    @foreach($totales as $_aabs => $T)
    @continue($_aabs == 0)
    <?php
      $d1 = $dataf($casino,$_aabs,$mes_cotizacion);
      $d2 = $dataf($casino,$_aabs+1,$mes_cotizacion);
      $fcot1 = $mes_cotizacion_str.'/'.substr($_aabs,2,2);
      $fcot2 = $mes_cotizacion_str.'/'.substr($_aabs+1,2,2);
      $primer_cotizacion_euro = bccomp_precise($primer_cotizacion_euro ?? '0','0') == 0?
       $d1->cotizacion_euro
      : $primer_cotizacion_euro;
      $primer_cotizacion_dolar = bccomp_precise($primer_cotizacion_dolar ?? '0','0') == 0?
       $d1->cotizacion_dolar
      : $primer_cotizacion_dolar;
      $ultima_cotizacion_euro = bccomp_precise($d1->cotizacion_euro ?? '0','0') == 0? 
       $ultima_cotizacion_euro
      : $d1->cotizacion_dolar;
      $ultima_cotizacion_dolar = bccomp_precise($d1->cotizacion_dolar ?? '0','0') == 0? 
       $ultima_cotizacion_dolar
      : $d1->cotizacion_dolar;
      
      $primer_bruto_euro = bccomp_precise($primer_bruto_euro ?? '0','0') == 0?
       $T->bruto_euro
      : $primer_bruto_euro;
      $primer_bruto_dolar = bccomp_precise($primer_bruto_dolar ?? '0','0') == 0?
       $T->bruto_dolar
      : $primer_bruto_dolar;
      $ultimo_bruto_euro = bccomp_precise($d1->bruto_euro ?? '0','0') == 0? 
       $ultimo_bruto_euro
      : $T->bruto_dolar;
      $ultimo_bruto_dolar = bccomp_precise($d1->bruto_dolar ?? '0','0') == 0? 
       $ultimo_bruto_dolar
      : $T->bruto_dolar;
    ?>
    <tr>
      <th style="border-bottom: 1px solid black;" rowspan="2">Año {{$_aabs-$primer_año+1}}</th>
      <td>{{$fcot1 ?? ''}}</td>
      <td>{{$formatear_decimal($d1->cotizacion_euro ?? null)}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$formatear_porcentaje($d2->variacion_cotizacion_euro ?? null)}}</td>
      <td>{{$fcot1 ?? ''}}</td>
      <td>{{$formatear_decimal($d1->cotizacion_dolar ?? null)}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$formatear_porcentaje($d2->variacion_cotizacion_dolar ?? null)}}</td>
      @if($_aabs == $primer_año)
      <td class="celda-vacia" style="border-right: 1px solid black !important;" rowspan="{{$filas_cuerpo}}">&nbsp;</td>
      @endif
      <th>{{$_aabs}}/{{$_aabs+1}}</th>
      <td>{{$formatear_decimal($T->bruto_euro_yoy ?? null)}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$formatear_porcentaje($T->variacion_euro ?? null)}}</td>
      <td>{{$formatear_decimal($T->bruto_dolar_yoy ?? null)}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$formatear_porcentaje($T->variacion_dolar ?? null)}}</td>
      @if($pad_vars>0)
      <td class="celda-vacia" colspan="{{$pad_vars}}">&nbsp;</td>
      @endif
    </tr>
    <tr>
      <td style="border-bottom: 1px solid black;">{{$fcot2 ?? ''}}</td>
      <td style="border-bottom: 1px solid black;">{{$d2->cotizacion_euro ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;">{{$fcot2 ?? ''}}</td>
      <td style="border-bottom: 1px solid black;">{{$d2->cotizacion_dolar ?? '-'}}</td>
      <th style="border-bottom: 1px solid black;border-left: 1px solid black;">{{$_aabs+1}}/{{$_aabs+2}}</th>
      <td style="border-bottom: 1px solid black;">{{$formatear_decimal($T->bruto_euro ?? null)}}</td>
      <td style="border-bottom: 1px solid black;">{{$formatear_decimal($T->bruto_dolar ?? null)}}</td>
      @if($pad_vars>0)
      <td class="celda-vacia" colspan="{{$pad_vars}}">&nbsp;</td>
      @endif
    </tr>
    @endforeach
    <tr>
      <td colspan="3" class="celda-vacia" style="border-right: 1px solid black !important;">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{$formatear_porcentaje(100*($fdiv($ultima_cotizacion_euro,$primer_cotizacion_euro) ?? 1 - 1))}}</td>
      <td colspan="2" class="celda-vacia" style="border-right: 1px solid black !important;">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{$formatear_porcentaje(100*($fdiv($ultima_cotizacion_dolar,$primer_cotizacion_dolar) ?? 1 - 1))}}</td>
      <td colspan="3" class="celda-vacia" style="border-right: 1px solid black !important;">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{$formatear_porcentaje(100*($fdiv($ultimo_bruto_euro,$primer_bruto_euro) ?? 1 - 1))}}</td>
      <td class="celda-vacia" style="border-right: 1px solid black !important;">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{$formatear_porcentaje(100*($fdiv($ultimo_bruto_dolar,$primer_bruto_dolar) ?? 1 - 1))}}</td>
      @if($pad_vars>0)
      <td class="celda-vacia" colspan="{{$pad_vars}}">&nbsp;</td>
      @endif
    </tr>
  </tbody>
</table>
</div>
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
