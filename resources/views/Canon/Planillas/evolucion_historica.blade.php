<style>
.año_mes {
  width: 4%;
}
<?php $width_casino = count($casinos)? ((100-4)/count($casinos)) : 0;  ?>
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
    @foreach($casinos as $_cas)
    <col class="devengado">
    <col class="variacion_mensual_devengado">
    <col class="canon">
    <col class="variacion_mensual_canon">
    <col class="diferencia">
    <col class="variacion_sobre_devengado">
    @endforeach
  </colgroup>
  
  @foreach($años as $a)
  <thead>
    <tr>
      <th colspan="{{6*count($casinos)+1}}">{{$loop->first? 'Evolución Histórica' : '&nbsp;'}}</th>
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
      <th class="variacion_mensual_devengado {{$_cas}} texto_pequeño">Var. Devengado</th>
      <th class="canon {{$_cas}}">Canon</th>
      <th class="variacion_mensual_canon {{$_cas}} texto_pequeño">Var. Canon</th>
      <th class="diferencia {{$_cas}}">Diferencia</th>
      <th class="variacion_sobre_devengado {{$_cas}} texto_pequeño" style="border-right: 1px solid black;">Var. Dif. sobre Canon</th>
      @endforeach
    </tr>
  </thead>
  
  <tbody>
    @for($_mnum=1;$_mnum<=12;$_mnum++)
    <tr>
      <th class="año_mes" style="border-right: 1px solid black;">{{$meses_calendario[$_mnum] ?? null}}</th>
      @foreach($casinos as $cas)
      <?php 
        $d = $data[$cas] ?? [];
        $d = $d[$a] ?? [];
        $d = $d[$_mnum] ?? (new \stdClass());
        $devengado = $formatear_decimal($d->devengado ?? null);
        $variacion_mensual_devengado = $formatear_porcentaje($d->variacion_mensual_devengado ?? null);
        $canon = $formatear_decimal($d->canon ?? null);
        $variacion_mensual_canon     = $formatear_porcentaje($d->variacion_mensual_canon ?? null);
        $diferencia = $formatear_decimal($d->diferencia ?? null);
        $variacion_sobre_devengado   = $formatear_porcentaje($d->variacion_sobre_devengado ?? null);
       ?>
      <td class="devengado {{$N($devengado)}}">{{$devengado}}</td>
      <td class="variacion_mensual_devengado {{$N($variacion_mensual_devengado)}} texto_pequeño">{{$variacion_mensual_devengado}}</td>
      <td class="canon {{$N($canon)}}">{{$canon}}</td>
      <td class="variacion_mensual_canon {{$N($variacion_mensual_canon)}} texto_pequeño">{{$variacion_mensual_canon}}</td>
      <td class="diferencia {{$N($diferencia)}}">{{$diferencia}}</td>
      <td class="variacion_sobre_devengado {{$N($variacion_sobre_devengado)}} texto_pequeño" style="border-right: 1px solid black;">{{$variacion_sobre_devengado}}</td>
      @endforeach
    </tr>
    @endfor
    
    <tr class="total">
      <th class="año_mes celda_especial" style="border-right: 1px solid black;">TOTAL</th>
      @foreach($casinos as $cas)
      <?php 
        $d = $data[$cas] ?? [];
        $d = $d[$a] ?? [];
        $d = $d[0] ?? (new \stdClass());
        $devengado = $formatear_decimal($d->devengado ?? null);
        $variacion_anual_devengado = $formatear_porcentaje($d->variacion_anual_devengado ?? null);
        $canon = $formatear_decimal($d->canon ?? null);
        $variacion_anual_canon     = $formatear_porcentaje($d->variacion_anual_canon ?? null);
        $diferencia = $formatear_decimal($d->diferencia ?? null);
        $variacion_sobre_devengado   = $formatear_porcentaje($d->variacion_sobre_devengado ?? null);
      ?>
      <td class="devengado {{$N($devengado)}}">{{$devengado}}</td>
      <td class="variacion_anual_devengado {{$N($variacion_anual_devengado)}} texto_pequeño">{{$variacion_anual_devengado}}</td>
      <td class="canon {{$N($canon)}}">{{$canon}}</td>
      <td class="variacion_anual_canon {{$N($variacion_anual_canon)}} texto_pequeño">{{$variacion_anual_canon}}</td>
      <td class="diferencia {{$N($diferencia)}}">{{$diferencia}}</td>
      <td class="variacion_sobre_devengado {{$N($variacion_sobre_devengado)}} texto_pequeño" style="border-right: 1px solid black;">{{$variacion_sobre_devengado}}</td>
      @endforeach
    </tr>
  </tbody>
  @endforeach
</table></div>
