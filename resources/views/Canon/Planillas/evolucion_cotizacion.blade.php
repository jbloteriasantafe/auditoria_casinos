<style>
table td {
  padding: 0.1em;
}
table th {
  padding: 0.1em;
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
  .mes {
    width: 4%;
  }
</style>
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
  ?>
  @for($_aabs=$año_inicio;$_aabs<=$año_fin;$_aabs+=$años_por_fila)
  <thead>
    <tr>
      <th rowspan="2">Mes</th>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <th colspan="4">CAS {{$_a2}}</th>
      @else
      <th colspan="4" rowspan="{{12+2}}">&nbsp;</th>
      @endif
      @endfor
    </tr>
    <tr>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <th>euro</th>
      <th>dolar</th>
      <th>fecha_cotizacion</th>
      <th>fecha_pago</th>
      @endif
      @endfor
    </tr>
  </thead>
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <td>{{$meses_calendario[$_mnum]}}</td>
      @for($_a=0;$_a<$años_por_fila;$_a++)
      <?php $_a2 = $_aabs+$_a ?>
      @if($_a2 <= $año_fin)
      <td>euro</td>
      <td>dolar</td>
      <td>fecha_cotizacion</td>
      <td>fecha_pago</td>
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
      <th>Año</th>
      <th colspan="2">Euro</th>
      <th>%</th>
      <th colspan="2">Dólar</th>
      <th>%</th>
      <th>Período</th>
      <th colspan="2">Euro</th>
      <th colspan="2">Dólar</th>
    </tr>
  </thead>
  <tbody>
    @for($_aabs=$año_inicio;$_aabs<=$año_fin;$_aabs+=1)
    <tr>
      @if($_aabs == $año_inicio)
      <td style="border-bottom: 0;border-left: 0;" rowspan="{{$filas_cuerpo+1}}">&nbsp;</td>
      @endif
      <td style="border-bottom: 1px solid black;" rowspan="2" >Año {{$_aabs-$año_inicio+1}}</td>
      <td>año1</td>
      <td>cot euro 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      <td>año1</td>
      <td>cot dolar 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      @if($_aabs == $año_inicio)
      <td style="border-bottom: 0;" rowspan="{{$filas_cuerpo+1}}">&nbsp;</td>
      @endif
      <td style="border-bottom: 1px solid black;">año2/año1</td>
      <td style="border-bottom: 1px solid black;">rec euro 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
      <td style="border-bottom: 1px solid black;">rec dolar 1</td>
      <td style="border-bottom: 1px solid black;" rowspan="2">%</td>
    </tr>
    <tr>
      <td style="border-bottom: 1px solid black;">año 2</td>
      <td style="border-bottom: 1px solid black;">cot euro 2</td>
      <td style="border-bottom: 1px solid black;">año2</td>
      <td style="border-bottom: 1px solid black;">cot dolar 2</td>
      <td style="border-bottom: 1px solid black;">año2/año3</td>
      <td style="border-bottom: 1px solid black;">rec euro 2</td>
      <td style="border-bottom: 1px solid black;">rec dolar 2</td>
    </tr>
    @endfor
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  </tbody>
</table>
</div>
@else
<div style="width: 100%;display: flex;align-items: baseline;">
<table style="table-layout: fixed;flex: 1;">
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
      <th rowspan="2">Mes</th>
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
      <th>Euro</th>
      <th>Dólar</th>
      <th>Euro</th>
      <th>Dólar</th>
      <th>Euro</th>
      <th>Dólar</th>
      <th>Euro</th>
      <th>Dólar</th>
      <th>€</th>
      <th>U$S</th>
    </tr>
  </thead>
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <td>{{$meses_calendario[$_mnum]}}</td>
      <td>Rdo. Bruto</td>
      <td>Rdo. Bruto</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>€</td>
      <td>U$S</td>
    </tr>
    @endfor
    <tr class="total">
      <td>Total</td>
      <td>Rdo. Bruto</td>
      <td>Rdo. Bruto</td>
      <td colspan="4">&nbsp;</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>Euro</td>
      <td>Dólar</td>
      <td>€</td>
      <td>U$S</td>
    </tr>
    <tr>
      <td colspan="13">&nbsp;</td>
    </tr>
  </tbody>
  <thead>
    <th>Actualización</th>
    <th>{{$año-1-1}}/{{$año-1}}</th>
    <th>{{$año-1}}/{{$año}}</th>
    <th>%</th>
    <th>Valores CC</th>
    <th>Valores Nuevos</th>
  </thead>
  <tbody>
    <tr>
      <td>Dólar</td>
      <td>{{$año-1-1}}/{{$año-1}}</td>
      <td>{{$año-1}}/{{$año}}</td>
      <td>%</td>
      <td>Valores CC</td>
      <td>valores Nuevos</td>
    </tr>
    <tr>
      <td>Euro</td>
      <td>{{$año-1-1}}/{{$año-1}}</td>
      <td>{{$año-1}}/{{$año}}</td>
      <td>%</td>
      <td>Valores CC</td>
      <td>valores Nuevos</td>
    </tr>
  </tbody>
</table>
</div>
@endif
