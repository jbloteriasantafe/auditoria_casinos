<style>
  .loader {
    width: 50px;
    aspect-ratio: 1;
    border-radius: 50%;
    padding: 6px;
    background:
      conic-gradient(from 135deg at top,currentColor 90deg, #0000 0) 0 calc(50% - 4px)/17px 8.5px,
      radial-gradient(farthest-side at bottom left,#0000 calc(100% - 6px),currentColor calc(100% - 5px) 99%,#0000) top right/50%  50% content-box content-box,
      radial-gradient(farthest-side at top        ,#0000 calc(100% - 6px),currentColor calc(100% - 5px) 99%,#0000) bottom   /100% 50% content-box content-box;
    background-repeat: no-repeat;
    animation: l11 1s infinite linear;
  }
  @keyframes l11{ 
    100%{transform: rotate(1turn)}
  }
</style>
<div style="display: flex;">
  <div style="width: 70%;">
    <table style="width: 100%;table-layout: fixed">
      <colgroup>
        <col class="mes">
        @foreach($casinos as $_casino)
        <col class="canon_total">
        @endforeach
        <col class="variacion_anual">
        <col class="variacion_mensual">
      </colgroup>
      <thead>
        <tr>
          <th colspan="{{3+count($casinos)}}" style="text-align: center;">Canon Total Casinos - {{$año}} -</th>
        </tr>
        <tr>
          <th class="mes" style="border-right: 1px solid black;">MESES</th>
          @foreach($abbr_casinos as $_cas)
          <th class="canon_total {{$_cas}}">{{$_cas}}</th>
          @endforeach
          <th class="variacion_anual">{{$año}}/{{$año_anterior}}</th>
          <th class="variacion_mensual">Dif. Mes Ant.</th>
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año_anterior}}</th>
          @foreach($abbr_casinos as $_casino => $_cas)
          <?php
            $total = $dataf($_casino,$año_anterior,0);
            $canon_total = $formatear_decimal($total->canon_total ?? null);
            $variacion_anual = $formatear_porcentaje($total->variacion_anual ?? null);
          ?>
          <th class="canon {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_total}}</th>
          @endforeach
          <th class="variacion_anual" style="text-align: right;">{{$valor_vacio}}</th>
          <th class="variacion_mensual" style="text-align: right;">{{$valor_vacio}}</th>
        </tr>
      </thead>
      <tbody>
        @for($_nmes=1;$_nmes<=12;$_nmes++)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$meses_calendario[$_nmes]}}</th>
          @foreach($casinos as $_casino)
          <?php 
            $canon = $dataf($_casino,$año,$_nmes);
            $canon_total = $formatear_decimal($canon->canon_total ?? null);
            $variacion_anual = $formatear_porcentaje($canon->variacion_anual ?? null);
            $variacion_mensual = $formatear_porcentaje($canon->variacion_mensual ?? null);
          ?>
          <td class="canon {{$N($canon_total)}}">{{$canon_total}}</td>
          @endforeach
          <td class="variacion_anual {{$N($variacion_anual)}}">{{$variacion_anual}}</td>
          <td class="variacion_mensual {{$N($variacion_mensual)}}">{{$variacion_mensual}}</td>
        </tr>
        @endfor
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($abbr_casinos as $_casino => $_cas)
          <?php
            $total = $dataf($_casino,$año,0);
            $canon_total = $formatear_decimal($total->canon_total ?? null);
            $variacion_anual = $formatear_porcentaje($total->variacion_anual ?? null);
          ?>
          <th class="canon {{$_cas}} {{$N($canon_total)}}" style="text-align: right;">{{$canon_total}}</th>
          @endforeach
          <th class="variacion_anual {{$N($variacion_anual)}}" style="text-align: right;">{{$variacion_anual}}</th>
          <th class="variacion_mensual" style="text-align: right;">{{$valor_vacio}}</th>
        </tr>
      </tbody>
    </table>
  </div>
  <div id="graficoTorta" style="width: 30%;">
    <br>
    <div class="loader"></div>
  </div>
</div>
<div id="graficoLineas">
  <br>
  <div class="loader"></div>
</div>
