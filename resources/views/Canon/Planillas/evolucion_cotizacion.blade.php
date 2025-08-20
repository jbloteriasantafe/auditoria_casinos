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

.mes {
  width: 9em;
}
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
<div style="width: 100%;display: flex;align-items: baseline;">
<table style="table-layout: fixed;flex: 1;">
  <colgroup>
    <col class="mes">
    @for($_a=0;$_a<$años_por_fila;$_a++)
    <col class="euro">
    <col class="dolar">
    <col class="fecha_cotizacion">
    <col class="fecha_pago">
    @endfor
  </colgroup>
  <thead>
    <tr>
      <th colspan="{{4*$años_por_fila+1}}">Cotizaciones</th>
    </tr>
  </thead>
  <?php
    $año_inicio = 2009;
    $año_fin = 2025;
    $cas = $abbr_casinos[$casino];
  ?>
  @for($_aabs=$año_inicio;$_aabs<=$año_fin;$_aabs+=$años_por_fila)
  <thead>
    <tr>
      <th class="celda_especial" rowspan="2">Mes</th>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <th class="{{$cas}}" colspan="4">{{$cas}} {{$_a2}}</th>
      @else
      <th class="celda-vacia" colspan="4">&nbsp;</th>
      @endif
      @endfor
    </tr>
    <tr>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <th class="euro">Euro</th>
      <th class="dolar">Dólar</th>
      <th>Fecha Cotización</th>
      <th>Fecha de Pago</th>
      @else
      <th class="celda-vacia" colspan="4">&nbsp;</th>
      @endif
      @endfor
    </tr>
  </thead>
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <th>{{$meses_calendario[$_mnum]}}</th>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <td>euro</td>
      <td>dolar</td>
      <td>fecha_cotizacion</td>
      <td>fecha_pago</td>
      @else
      <td class="celda-vacia" colspan="4">&nbsp;</td>
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
    $filas_cuerpo = ($año_fin-$año_inicio+1)*2;
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
    @for($_aabs=$año_inicio;$_aabs<=$año_fin;$_aabs+=1)
    <tr>
      @if($_aabs == $año_inicio)
      <td style="border-bottom: 0;border-left: 0;" rowspan="{{$filas_cuerpo}}">&nbsp;</td>
      @endif
      <th style="border-bottom: 1px solid black;" rowspan="2">Año {{$_aabs-$año_inicio+1}}</th>
      <td>{{$_aabs}}</td>
      <td>cot euro 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      <td>{{$_aabs}}</td>
      <td>cot dolar 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      @if($_aabs == $año_inicio)
      <td style="border-bottom: 0;" rowspan="{{$filas_cuerpo}}">&nbsp;</td>
      @endif
      <th>{{$_aabs}}/{{$_aabs+1}}</th>
      <td>rec euro 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      <td>rec dolar 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
    </tr>
    <tr>
      <td style="border-bottom: 1px solid black;">{{$_aabs+1}}</td>
      <td style="border-bottom: 1px solid black;">cot euro 2</td>
      <td style="border-bottom: 1px solid black;">{{$_aabs+1}}</td>
      <td style="border-bottom: 1px solid black;">cot dolar 2</td>
      <th style="border-bottom: 1px solid black;">{{$_aabs+1}}/{{$_aabs+2}}</th>
      <td style="border-bottom: 1px solid black;">rec euro 2</td>
      <td style="border-bottom: 1px solid black;">rec dolar 2</td>
    </tr>
    @endfor
    <tr>
      <td class="celda-vacia" colspan="14">&nbsp;</td>
    </tr>
  </tbody>
</table>
</div>
@else
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
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <?php
      $d = $dataf($casino,$año,$_mnum);
    ?>
    <tr>
      <th>{{$meses_calendario[$_mnum]}}</th>
      <td>{{$d->bruto_yoy}}</td>
      <td>{{$d->bruto}}</td>
      <td>{{$d->cotizacion_euro_yoy}}</td>
      <td>{{$d->cotizacion_dolar_yoy}}</td>
      <td>{{$d->cotizacion_euro}}</td>
      <td>{{$d->cotizacion_dolar}}</td>
      <td>{{$d->bruto_euro_yoy}}</td>
      <td>{{$d->bruto_dolar_yoy}}</td>
      <td>{{$d->bruto_euro}}</td>
      <td>{{$d->bruto_dolar}}</td>
      <td>{{$d->variacion_euro}}</td>
      <td>{{$d->variacion_dolar}}</td>
    </tr>
    @endfor
    <tr>
     <?php
      $d = $dataf($casino,$año,0);
    ?>
    <tr>
      <th class="celda_especial">Total</th>
      <td>{{$d->bruto_yoy}}</td>
      <td>{{$d->bruto}}</td>
      <td style="border-top: 1px solid black;border-bottom: 0;" colspan="4">&nbsp;</td>
      <td>{{$d->bruto_euro_yoy}}</td>
      <td>{{$d->bruto_dolar_yoy}}</td>
      <td>{{$d->bruto_euro}}</td>
      <td>{{$d->bruto_dolar}}</td>
      <td>{{$d->variacion_euro}}</td>
      <td>{{$d->variacion_dolar}}</td>
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
