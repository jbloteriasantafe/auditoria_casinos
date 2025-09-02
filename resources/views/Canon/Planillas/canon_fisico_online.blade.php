<div style="width: 100%;"><table style="width: 100%;table-layout: fixed">
  <colgroup>
    <col class="mes">
    @foreach($abbr_casinos as $_cas)
    @if($_cas != 'CME')
    <col class="canon_fisico">
    <col class="canon_online">
    @endif
    <col class="canon">
    @endforeach
    <col class="variacion_canon_yoy">
    <col class="variacion_canon_mom">
  </colgroup>
  <thead>
    <tr>
      <?php 
        $casinos_simples = ['CME'];
        $casinos_complejos = array_diff($abbr_casinos,$casinos_simples);
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
      <th class="variacion_canon_yoy" rowspan="2">{{$año}}/{{$año_anterior}}</th>
      <th class="variacion_canon_mom" rowspan="2">Dif. Mes Ant.</th>
    </tr>
    <tr>
      @foreach($abbr_casinos as $_cas)
      @if($_cas !== 'CME')
      <th class="canon_fisico {{$_cas}}">Físico</th>
      <th class="canon_online {{$_cas}}">On Line</th>
      <th class="canon  {{$_cas}}">Total</th>
      @endif
      @endforeach
    </tr>
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año_anterior}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $total = $dataf($_casino,$año_anterior,0);
        $canon_fisico = $formatear_decimal($total->canon_fisico ?? null);
        $canon_online = $formatear_decimal($total->canon_online ?? null);
        $canon  = $formatear_decimal($total->canon ?? null);
      ?>
      @if($_cas != 'CME')
      <th class="canon_fisico {{$_cas}} {{$N($canon_fisico)}}" style="text-align: right;">{{$canon_fisico}}</th>
      <th class="canon_online {{$_cas}} {{$N($canon_online)}}" style="text-align: right;">{{$canon_online}}</th>
      @endif
      <th class="canon  {{$_cas}} {{$N($canon)}}" style="text-align: right;">{{$canon}}</th>
      @endforeach
      <th class="variacion_canon_yoy" style="text-align: right;">{{$valor_vacio}}</th>
      <th class="variacion_canon_mom" style="text-align: right;">{{$valor_vacio}}</th>
    </tr>
  </thead>
  <tbody>
    @foreach($abbr_meses as $_nmes => $_mes)
    <tr>
      <th class="mes" style="border-right: 1px solid black">{{$_mes}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $canon = $dataf($_casino,$año,$_nmes);
        $canon_fisico = $formatear_decimal($canon->canon_fisico_redondeado ?? null);
        $canon_online =  $formatear_decimal($canon->canon_online_redondeado ?? null);
        $variacion_canon_yoy   = $formatear_porcentaje($canon->variacion_canon_yoy ?? null);
        $variacion_canon_mom = $formatear_porcentaje($canon->variacion_canon_mom ?? null);
        $canon  =  $formatear_decimal($canon->canon ?? null);
      ?>
      @if($_cas !== 'CME')
      <td class="canon_fisico {{$N($canon_fisico)}}">{{$canon_fisico}}</td>
      <td class="canon_online {{$N($canon_online)}}">{{$canon_online}}</td>
      @endif
      <td class="canon {{$N($canon)}}">{{$canon}}</td>
      @endforeach
      <td class="variacion_canon_yoy {{$N($variacion_canon_yoy)}}">{{$variacion_canon_yoy}}</td>
      <td class="variacion_canon_mom {{$N($variacion_canon_mom)}}">{{$variacion_canon_mom}}</td>
    </tr>
    @endforeach
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $total = $dataf($_casino,$año,0);
        $canon_fisico = $formatear_decimal($total->canon_fisico_redondeado ?? null);
        $canon_online = $formatear_decimal($total->canon_online_redondeado ?? null);
        $variacion_canon_yoy = $formatear_porcentaje($total->variacion_canon_yoy ?? null);
        $canon  = $formatear_decimal($total->canon ?? null);
      ?>
      @if($_cas != 'CME')
      <th class="canon_fisico {{$_cas}} {{$N($canon_fisico)}}" style="text-align: right;">{{$canon_fisico}}</th>
      <th class="canon_online {{$_cas}} {{$N($canon_online)}}" style="text-align: right;">{{$canon_online}}</th>
      @endif
      <th class="canon  {{$_cas}} {{$N($canon)}}" style="text-align: right;">{{$canon}}</th>
      @endforeach
      <th class="variacion_canon_yoy {{$N($variacion_canon_yoy)}}" style="text-align: right;">{{$variacion_canon_yoy}}</th>
      <th class="variacion_canon_mom" style="text-align: right;">{{$valor_vacio}}</th>
    </tr>
  </tbody>
</table></div>
