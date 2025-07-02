<div style="width: 100%;"><table style="width: 100%;table-layout: fixed">
  <colgroup>
    <col class="mes">
    @foreach($abbr_casinos as $_cas)
    @if($_cas != 'CME')
    <col class="canon_fisico">
    <col class="canon_online">
    @endif
    <col class="canon_total">
    @endforeach
    <col class="variacion_anual">
    <col class="variacion_mensual">
  </colgroup>
  <thead>
    <tr>
      <?php 
        $casinos_simples = ['CME'];
        $casinos_complejos = array_diff($abbr_casinos->toArray(),$casinos_simples);
      ?>
      <th colspan="{{3+count($casinos_simples)+3*count($casinos_complejos)}}" style="text-align: center;">Canon Físico / On Line - {{$año}} -</th>
    </tr>
    <tr>
      <th class="mes" style="border-right: 1px solid black" rowspan="2">MESES</th>
      @foreach($abbr_casinos as $_cas)
      @if($_cas == 'CME')
      <th class="canon {{$_cas}}" rowspan="2">{{$_cas}}</th>
      @else
      <th class="canon {{$_cas}}" colspan="3">{{$_cas}}</th>
      @endif
      @endforeach
      <th class="variacion_anual" rowspan="2">{{$año}}/{{$año_anterior}}</th>
      <th class="variacion_mensual" rowspan="2">Dif. Mes Ant.</th>
    </tr>
    <tr>
      @foreach($abbr_casinos as $_cas)
      @if($_cas !== 'CME')
      <th class="canon_fisico {{$_cas}}">Físico</th>
      <th class="canon_online {{$_cas}}">On Line</th>
      <th class="canon_total  {{$_cas}}">Total</th>
      @endif
      @endforeach
    </tr>
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año_anterior}}</th>
      @foreach($abbr_casinos as $_cidx => $_cas)
      <?php
        $_casino = $casinos[$_cidx] ?? null;
        $total = $dataf($_casino,$año_anterior,0);
        $canon_fisico = $formatear_decimal($total->canon_fisico ?? null);
        $canon_online = $formatear_decimal($total->canon_online ?? null);
        $canon_total  = $formatear_decimal($total->canon_total ?? null);
      ?>
      @if($_cas != 'CME')
      <th class="canon_fisico {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_fisico}}</th>
      <th class="canon_online {{$_cas}} {{$N($canon_online)}}" style="text-align: right;">{{$canon_online}}</th>
      @endif
      <th class="canon_total  {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_total}}</th>
      @endforeach
      <th class="variacion_anual" style="text-align: right;">{{$valor_vacio}}</th>
      <th class="variacion_mensual" style="text-align: right;">{{$valor_vacio}}</th>
    </tr>
  </thead>
  <tbody>
    @foreach($abbr_meses as $_nmes => $_mes)
    <tr>
      <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
      @foreach($abbr_casinos as $_cidx => $_cas)
      <?php
        $_casino = $casinos[$_cidx] ?? null;
        $canon = $dataf($_casino,$año,$_nmes);
        $canon_fisico = $formatear_decimal($canon->canon_fisico ?? null);
        $canon_online = $formatear_decimal($canon->canon_online ?? null);
        $canon_total  = $formatear_decimal($canon->canon_total ?? null);
        $variacion_anual   = $formatear_porcentaje($canon->variacion_anual ?? null);
        $variacion_mensual = $formatear_porcentaje($canon->variacion_mensual ?? null);
      ?>
      @if($_cas !== 'CME')
      <td class="canon_fisico {{$N($canon_fisico)}}">{{$canon_fisico}}</td>
      <td class="canon_online {{$N($canon_online)}}">{{$canon_online}}</td>
      @endif
      <td class="canon_total  {{$N($canon_total)}}">{{$canon_total}}</td>
      @endforeach
      <td class="variacion_anual {{$N($variacion_anual)}}">{{$variacion_anual}}</td>
      <td class="variacion_mensual {{$N($variacion_mensual)}}">{{$variacion_mensual}}</td>
    </tr>
    @endforeach
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año}}</th>
      @foreach($abbr_casinos as $_cidx => $_cas)
      <?php
        $_casino = $casinos[$_cidx] ?? null;
        $total = $dataf($_casino,$año,0);
        $canon_fisico = $formatear_decimal($total->canon_fisico ?? null);
        $canon_online = $formatear_decimal($total->canon_online ?? null);
        $canon_total  = $formatear_decimal($total->canon_total ?? null);
        $variacion_anual = $formatear_porcentaje($total->variacion_anual ?? null);
      ?>
      @if($_cas != 'CME')
      <th class="canon_fisico {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_fisico}}</th>
      <th class="canon_online {{$_cas}} {{$N($canon_online)}}" style="text-align: right;">{{$canon_online}}</th>
      @endif
      <th class="canon_total  {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_total}}</th>
      @endforeach
      <th class="variacion_anual {{$N($variacion_anual)}}" style="text-align: right;">{{$variacion_anual}}</th>
      <th class="variacion_mensual" style="text-align: right;">{{$valor_vacio}}</th>
    </tr>
  </tbody>
</table></div>
