<style>
.año_mes {
  width: 4%;
}
<?php $width_casino = count($abbr_casinos)? ((100-4)/count($abbr_casinos)) : 0;  ?>
.devengado {
  width: {{$width_casino/4}}%;
}
.canon {
  width: {{$width_casino/4}}%;
}
.diferencia {
  width: {{$width_casino/4}}%;
}
.variacion_sobre_devengado,
.variacion_mensual_devengado,.variacion_mensual_canon,
.variacion_anual_devengado,.variacion_anual_canon {
  width: {{$width_casino/12}}%;
}

table td {
  padding: 0.1em;
}
table th {
  padding: 0.1em;
}
</style>

<div style="width: 100%;"><table style="table-layout: fixed">
  <colgroup>
    <col class="año_mes">
    @foreach($abbr_casinos as $_)
    <col class="devengado">
    <col class="variacion_devengado_mom">
    <col class="canon">
    <col class="variacion_canon_mom">
    <col class="diferencia">
    <col class="proporcion_diferencia_canon">
    @endforeach
  </colgroup>
  @foreach(($data['Total'] ?? []) as $a => $_)
  @continue($a == 0)
  <thead>
    <tr>
      <th colspan="{{6*count($abbr_casinos)+1}}">{{$loop->first? 'Evolución Histórica' : '&nbsp;'}}</th>
    </tr>
    <tr>
      <th class="año_mes" style="border-right: 1px solid black;">{{$a}}</th>
      @foreach($abbr_casinos as $_casino => $_cas)
      <th class="{{$_cas}}" colspan="6" style="border-right: 1px solid black;">{{$_casino}}</th>
      @endforeach
    </tr>
    
    <tr>
      <th class="año_mes" style="border-right: 1px solid black;">Meses</th>
      @foreach($abbr_casinos as $_cas)
      <th class="devengado {{$_cas}}">Devengado</th>
      <th class="variacion_devengado_mom {{$_cas}} texto_pequeño">Var. Devengado</th>
      <th class="canon {{$_cas}}">Canon</th>
      <th class="variacion_canon_mom {{$_cas}} texto_pequeño">Var. Canon</th>
      <th class="diferencia {{$_cas}}">Diferencia</th>
      <th class="proporcion_diferencia_canon {{$_cas}} texto_pequeño" style="border-right: 1px solid black;">Var. Dif. sobre Canon</th>
      @endforeach
    </tr>
  </thead>
  
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <th class="año_mes" style="border-right: 1px solid black;">{{$meses_calendario[$_mnum] ?? null}}</th>
      @foreach($abbr_casinos as $cas => $acas)
      <?php 
        $d = $dataf($cas,$a,$_mnum);
        $devengado = $formatear_decimal($d->devengado ?? null);
        $variacion_devengado_mom = $formatear_porcentaje($d->variacion_devengado_mom ?? null);
        $canon = $formatear_decimal($d->canon ?? null);
        $variacion_canon_mom     = $formatear_porcentaje($d->variacion_canon_mom ?? null);
        $diferencia = $formatear_decimal($d->diferencia ?? null);
        $proporcion_diferencia_canon   = $formatear_porcentaje($d->proporcion_diferencia_canon ?? null);
       ?>
      <td class="devengado {{$N($devengado)}}">{{$devengado}}</td>
      <td class="variacion_devengado_mom {{$N($variacion_devengado_mom)}} texto_pequeño">{{$variacion_devengado_mom}}</td>
      <td class="canon {{$N($canon)}}">{{$canon}}</td>
      <td class="variacion_canon_mom {{$N($variacion_canon_mom)}} texto_pequeño">{{$variacion_canon_mom}}</td>
      <td class="diferencia {{$N($diferencia)}}">{{$diferencia}}</td>
      <td class="proporcion_diferencia_canon {{$N($proporcion_diferencia_canon)}} texto_pequeño" style="border-right: 1px solid black;">{{$proporcion_diferencia_canon}}</td>
      @endforeach
    </tr>
    @endfor
    
    <tr class="total">
      <th class="año_mes celda_especial" style="border-right: 1px solid black;">TOTAL</th>
      @foreach($abbr_casinos as $cas => $acas)
      <?php 
        $d = $dataf($cas,$a,0);
        $devengado = $formatear_decimal($d->devengado ?? null);
        $variacion_devengado_yoy = $formatear_porcentaje($d->variacion_devengado_yoy ?? null);
        $canon = $formatear_decimal($d->canon ?? null);
        $variacion_canon_yoy = $formatear_porcentaje($d->variacion_canon_yoy ?? null);
        $diferencia = $formatear_decimal($d->diferencia ?? null);
        $proporcion_diferencia_canon   = $formatear_porcentaje($d->proporcion_diferencia_canon ?? null);
      ?>
      <td class="devengado {{$N($devengado)}}">{{$devengado}}</td>
      <td class="variacion_devengado_yoy {{$N($variacion_devengado_yoy)}} texto_pequeño">{{$variacion_devengado_yoy}}</td>
      <td class="canon {{$N($canon)}}">{{$canon}}</td>
      <td class="variacion_canon_yoy {{$N($variacion_canon_yoy)}} texto_pequeño">{{$variacion_canon_yoy}}</td>
      <td class="diferencia {{$N($diferencia)}}">{{$diferencia}}</td>
      <td class="proporcion_diferencia_canon {{$N($proporcion_diferencia_canon)}} texto_pequeño" style="border-right: 1px solid black;">{{$proporcion_diferencia_canon}}</td>
      @endforeach
    </tr>
  </tbody>
  @endforeach
</table></div>
