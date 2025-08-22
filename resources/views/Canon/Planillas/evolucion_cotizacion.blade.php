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
.border-bottom {
  border-bottom: 1px solid black;
}
.celda-vacia {
  border: 0 !important;
  background: white;
}
.celda-oscura {
  background: grey;
  border: 1px solid black !important;
}

.mes {
  width: 9em;
}
</style>

<?php
  $año = $parametros['año'] ?? null;
  $casino = $parametros['casino'] ?? '';
?>

@if($año === null || empty($casino))
@elseif($año == 'total')
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
    <col class="mes">
    @for($_a=0;$_a<$años_por_fila;$_a++)
    <col class="euro">
    <col class="dolar">
    <col class="fecha_cotizacion">
    @endfor
  </colgroup>
  <thead>
    <tr>
      <th colspan="{{4*$años_por_fila+1}}">Cotizaciones</th>
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
      <td>{{$d->cotizacion_euro?? '-'}}</td>
      <td>{{$d->cotizacion_dolar?? '-'}}</td>
      <td>{{$d->fecha_cotizacion ?? '-'}}</td>
      @else
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      <td class="celda-vacia celda-oscura">&nbsp;</td>
      @endif
      @endfor
    </tr>
    @endfor
    <tr>
      <td colspan="{{1+$años_por_fila*4}}">&nbsp;</td>
    </tr>
  </tbody>
  @endfor
</table>
<table style="table-layout: fixed;flex: 1;">
  <colgroup>
    <col class="vacia1">
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
  </colgroup>
  <?php
    $filas_cuerpo = ($ultimo_año-$primer_año+1)*2;
  ?>
  <thead>
    <tr>
      <th rowspan="2" style="border-left: 0;border-bottom: 0;border-top: 0;">&nbsp;</th>
      <th colspan="7">Variación de cotización de Moneda Extranjera</th>
      <th rowspan="2"  style="border-bottom: 0;border-top: 0;">&nbsp;</th>
      <th colspan="5">Variación de recaudación</th>
    </tr>
    <tr>
      <th class="celda_especial">Año</th>
      <th class="euro" colspan="2">Euro</th>
      <th>%</th>
      <th class="dolar" colspan="2">Dólar</th>
      <th>%</th>
      <th class="celda_especial" >Período</th>
      <th class="euro" colspan="2">Euro</th>
      <th class="dolar" colspan="2">Dólar</th>
    </tr>
  </thead>
  <tbody>
    <?php
      $mes_cotizacion = intval(substr($fecha_inicio[$casino],strlen('XXXX-'),strlen('XX')));
      $mes_cotizacion = ($mes_cotizacion+1)%12;
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
        return NAN;
      };
    ?>
    @for($_aabs=$primer_año;$_aabs<=$ultimo_año;$_aabs+=1)
    <?php
      $d1 = $dataf($casino,$_aabs,$mes_cotizacion);
      $d2 = $dataf($casino,$_aabs+1,$mes_cotizacion);
      $fcot1 = $mes_cotizacion_str.'/'.substr($_aabs,2,2);
      $fcot2 = $mes_cotizacion_str.'/'.substr($_aabs+1,2,2);
      $primer_cotizacion_euro = $primer_cotizacion_euro ?? $d1->cotizacion_euro;
      $primer_cotizacion_dolar = $primer_cotizacion_dolar ?? $d1->cotizacion_dolar;
      $primer_bruto_euro = $primer_bruto_euro ?? $d1->bruto_euro;
      $primer_bruto_dolar = $primer_bruto_dolar ?? $d1->bruto_dolar;
      
      $ultima_cotizacion_euro = $d1->cotizacion_euro ?? $ultima_cotizacion_euro;
      $ultima_cotizacion_dolar = $d1->cotizacion_dolar ?? $ultima_cotizacion_dolar;
      $ultimo_bruto_euro = $d1->bruto_euro ?? $ultimo_bruto_euro;
      $ultimo_bruto_dolar = $d1->bruto_dolar ?? $ultimo_bruto_dolar;
    ?>
    <tr>
      @if($_aabs == $primer_año)
      <td style="border-bottom: 0;border-left: 0;" rowspan="{{$filas_cuerpo}}">&nbsp;</td>
      @endif
      <th style="border-bottom: 1px solid black;" rowspan="2">Año {{$_aabs-$primer_año+1}}</th>
      <td>{{$fcot1 ?? ''}}</td>
      <td>{{$d1->cotizacion_euro ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$d2->variacion_cotizacion_euro ?? '-'}}</td>
      <td>{{$fcot1 ?? ''}}</td>
      <td>{{$d1->cotizacion_dolar ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$d2->variacion_cotizacion_dolar ?? '-'}}</td>
      @if($_aabs == $primer_año)
      <td style="border-bottom: 0;" rowspan="{{$filas_cuerpo}}">&nbsp;</td>
      @endif
      <th>{{$_aabs}}/{{$_aabs+1}}</th>
      <td>{{$d1->bruto_euro ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$d2->variacion_euro ?? '-'}}</td>
      <td>{{$d1->bruto_dolar ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">{{$d2->variacion_dolar ?? '-'}}</td>
    </tr>
    <tr>
      <td style="border-bottom: 1px solid black;">{{$fcot2 ?? ''}}</td>
      <td style="border-bottom: 1px solid black;">{{$d2->cotizacion_euro ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;">{{$fcot2 ?? ''}}</td>
      <td style="border-bottom: 1px solid black;">{{$d2->cotizacion_dolar ?? '-'}}</td>
      <th style="border-bottom: 1px solid black;">{{$_aabs+1}}/{{$_aabs+2}}</th>
      <td style="border-bottom: 1px solid black;">{{$d2->bruto_euro ?? '-'}}</td>
      <td style="border-bottom: 1px solid black;">{{$d2->bruto_dolar ?? '-'}}</td>
    </tr>
    @endfor
    <tr>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{100*($fdiv($ultima_cotizacion_euro,$primer_cotizacion_euro) - 1)}}</td>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{100*($fdiv($ultima_cotizacion_dolar,$primer_cotizacion_dolar) - 1)}}</td>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td class="celda-vacia">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{100*($fdiv($ultimo_bruto_euro,$primer_bruto_euro) - 1)}}</td>
      <td class="celda-vacia">&nbsp;</td>
      <td style="border-left: 1px solid black;">{{100*($fdiv($ultimo_bruto_dolar,$primer_bruto_dolar) - 1)}}</td>
    </tr>
  </tbody>
</table>
</div>
@else
<?php
  $data_rel = [];
  $_a = $año-1;
  $_m = intval(substr($fecha_inicio[$casino],strlen('XXXX-'),strlen('XX')));
  
  $first_cot = [
    'variacion_euro' => null,
    'variacion_euro' => null,
  ];
  
  $total = [
    'bruto_yoy' => null,
    'bruto' => null,
    'bruto_euro_yoy' => null,
    'bruto_dolar_yoy' => null,
    'bruto_euro' => null,
    'bruto_dolar' => null,
  ];
  
  for($_rm=0;$_rm<13;$_rm++){
    $d = $dataf($casino,$_a,$_m);
    $data_rel[] = $d;
    $_m++;
    if($_m > 12){
      $_a++;
      $_m=1;
    }
    
    if($_rm != 0 && $_rm != 13)
    foreach($total as $k => $v){
      $total[$k] = bcadd_precise($v ?? '0',$d->{$k});
    }
  }
  $total = (object) $total;
?>
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
      <th class="{{$abbr_casinos[$casino]}}" colspan="13">{{$casino}}</th>
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
    @foreach($data_rel as $d)
    @if($loop->first || $loop->last)
    <tr><!-- Todo implementar bruto parcial -->
      <th>{{$meses_calendario[$d->mes]}}</th>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
      <td>-</td>
    </tr>
    @else
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
    @endif
    @endforeach
    <tr>
      <th class="celda_especial">Total</th>
      <td>{{$formatear_decimal($total->bruto_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($total->bruto ?? null)}}</td>
      <td style="border-top: 1px solid black;border-bottom: 0;" colspan="4">&nbsp;</td>
      <td>{{$formatear_decimal($total->bruto_euro_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($total->bruto_dolar_yoy ?? null)}}</td>
      <td>{{$formatear_decimal($total->bruto_euro ?? null)}}</td>
      <td>{{$formatear_decimal($total->bruto_dolar ?? null)}}</td>
      <td>{{$formatear_porcentaje($total->variacion_euro ?? null)}}</td>
      <td>{{$formatear_porcentaje($total->variacion_dolar ?? null)}}</td>
    </tr>
  </tbody>
  <tbody>
    <tr>
      <td class="celda-vacia" colspan="13">&nbsp;</td>
    </tr>
  </tbody>
  <thead>
    <th>Actualización</th>
    <th>{{$año-1-1}}/{{$año-1}}</th>
    <th>{{$año-1}}/{{$año}}</th>
    <th>%</th>
    <th>Valores CC</th>
    <th>Valores Nuevos</th>
    <th class="celda-vacia" colspan="7">&nbsp;</th>
  </thead>
  <tbody>
    <tr>
      <th class="euro">Euro</th>
      <td>{{$año-1-1}}/{{$año-1}}</td>
      <td>{{$año-1}}/{{$año}}</td>
      <td>%</td>
      <td>Valores CC</td>
      <td>valores Nuevos</td>
      <td class="celda-vacia" colspan="7">&nbsp;</td>
    </tr>
    <tr>
      <th class="dolar">Dólar</th>
      <td>{{$año-1-1}}/{{$año-1}}</td>
      <td>{{$año-1}}/{{$año}}</td>
      <td>%</td>
      <td>Valores CC</td>
      <td>valores Nuevos</td>
      <td class="celda-vacia" colspan="7">&nbsp;</td>
    </tr>
  </tbody>
</table>
</div>
@endif
