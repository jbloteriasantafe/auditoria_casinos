<?php
  $pje_plats = [];
  foreach([$año-1,$año] as $_a){
    $pje_plats[$_a] = $pje_plats[$_a] ?? [];
    for($_nmes=0;$_nmes<=12;$_nmes++){
      $pje_plats[$_a][$_nmes] = $pje_plats[$_a][$_nmes] ?? [];
      $total = $dataf('Total',$_a,$_nmes);
      foreach($relacion_plat_cas as $_plat => $_){
        $pje_plats[$_a][$_nmes][$_plat] = $total->{'participacion_'.$_plat} ?? null;
      }
    }
  }
  
  $abbr_num_meses = $meses_calendario->map(function($_,$_nmes){return str_pad($_nmes,2,'0',STR_PAD_LEFT);});
  $abbr_año = isset($año)? str_pad(substr($año,2),2,'0',STR_PAD_LEFT) : null;
?>
<div style="width: 100%"><table style="width: 100%;table-layout: fixed">
  <colgroup>
    <col class="mes">
    @foreach($abbr_casinos as $_)
    <col class="fisico">
    <col class="online">
    @endforeach
    <col class="padding">
    @foreach($relacion_plat_cas as $_plat => $_)
    <col class="JOL">
    @endforeach
  </colgroup>
  <thead>
    <tr>
      <th colspan="{{2+count($abbr_casinos)*2+count($relacion_plat_cas)}}" style="text-align: center;">Participación % de Resultados Casino Físico-JOL - {{$año}} -</th>
    </tr>
    <tr>
      <th class="mes" style="border-right: 1px solid black" rowspan="2">MES/AÑO</th>
      @foreach($abbr_casinos as $_cas)
      <th class="{{$_cas}}" colspan="2">{{$_cas}}</th>
      @endforeach
      <th class="padding" rowspan="3" style="border-top: 0;border-bottom: 0;">&nbsp;</th>
      <th class="JOL" colspan="2">JOL</th>
    </tr>
    <tr>
      @foreach($abbr_casinos as $_cas)
      <th class="fisico {{$_cas}}">% Físico</th>
      <th class="online {{$_cas}}">% On Line</th>
      @endforeach
      @foreach($relacion_plat_cas as $_plat => $_casino)
      <th class="{{$abbr_casinos[$_casino]}}">{{$_plat}}</th>
      @endforeach
    </tr>
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año-1}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $total = $dataf($_casino,$año-1,0);
        $fisico = $formatear_porcentaje($total->participacion_fisico ?? null);
        $online = $formatear_porcentaje($total->participacion_online ?? null);
      ?>
      <th class="fisico {{$_cas}} {{$N($fisico)}}" style="text-align: right;">{{$fisico}}</th>
      <th class="online {{$_cas}} {{$N($online)}}" style="text-align: right;">{{$online}}</th>
      @endforeach
      @foreach($relacion_plat_cas as $_plat => $_casino)
      <?php
        $porcentaje = $formatear_porcentaje($pje_plats[$año-1][0][$_plat]);
      ?>
      <th class="{{$N($porcentaje)}} {{$abbr_casinos[$_casino]}}" style="text-align: right;">{{$porcentaje}}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @foreach($abbr_num_meses as $_nmes => $_mes)
    <tr>
      <th class="mes" style="border-right: 1px solid black">{{$_mes}}/{{$abbr_año}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $canon = $dataf($_casino,$año,$_nmes);
        $fisico = $formatear_porcentaje($canon->participacion_fisico ?? null);
        $online = $formatear_porcentaje($canon->participacion_online ?? null);
      ?>
      <td class="fisico {{$N($fisico)}}">{{$fisico}}</td>
      <td class="online {{$N($online)}}">{{$online}}</td>
      @endforeach
      @if($loop->first)
      <td class="padding" rowspan="{{count($meses_calendario)+1}}" style="border-top: 0;border-bottom: 0;">&nbsp;</td>
      @endif
      @foreach($relacion_plat_cas as $_plat => $_casino)
      <?php
        $porcentaje = $formatear_porcentaje($pje_plats[$año][$_nmes][$_plat]);
      ?>
      <td class="JOL {{$N($porcentaje)}}">{{$porcentaje}}</td>
      @endforeach
    </tr>
    @endforeach
    <tr>
      <th class="mes celda_especial" style="border-right: 1px solid black">{{$año}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <?php
        $total = $dataf($_casino,$año,0);
        $fisico = $formatear_porcentaje($total->participacion_fisico ?? null);
        $online = $formatear_porcentaje($total->participacion_online ?? null);
      ?>
      <th class="fisico {{$_cas}} {{$N($fisico)}}" style="text-align: right;">{{$fisico}}</th>
      <th class="online {{$_cas}} {{$N($online)}}" style="text-align: right;">{{$online}}</th>
      @endforeach
      @foreach($relacion_plat_cas as $_plat => $_casino)
      <?php
        $porcentaje = $formatear_porcentaje($pje_plats[$año][0][$_plat]);
      ?>
      <th class="{$N($porcentaje)}} {{$abbr_casinos[$_casino]}}" style="text-align: right;">{{$porcentaje}}</th>
      @endforeach
    </tr>
  </tbody>
</table></div>
