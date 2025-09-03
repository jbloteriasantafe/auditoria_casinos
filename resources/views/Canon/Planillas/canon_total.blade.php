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
        @foreach($abbr_casinos as $_cas)
        <col class="canon_total">
        @endforeach
        <col class="variacion_canon_yoy">
        <col class="variacion_canon_mom">
      </colgroup>
      <thead>
        <tr>
          <th colspan="{{3+count($abbr_casinos)}}" style="text-align: center;">Canon Total Casinos - {{$año}} -</th>
        </tr>
        <tr>
          <th class="mes" style="border-right: 1px solid black;">MESES</th>
          @foreach($abbr_casinos as $_cas)
          <th class="canon_total {{$_cas}}">{{$_cas}}</th>
          @endforeach
          <th class="variacion_canon_yoy">{{$año}}/{{$año-1}}</th>
          <th class="variacion_canon_mom">Dif. Mes Ant.</th>
        </tr>
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año-1}}</th>
          @foreach($abbr_casinos as $_casino => $_cas)
          <?php
            $total = $dataf($_casino,$año-1,0);
            $canon = $formatear_decimal($total->canon ?? null);
          ?>
          <th class="canon {{$_cas}} {{$N($canon)}}" style="text-align: right;">{{$canon}}</th>
          @endforeach
          <th class="variacion_canon_yoy" style="text-align: right;">{{$valor_vacio}}</th>
          <th class="variacion_canon_mom" style="text-align: right;">{{$valor_vacio}}</th>
        </tr>
      </thead>
      <tbody>
        @for($_nmes=1;$_nmes<=12;$_nmes++)
        <tr>
          <th class="mes" style="border-right: 1px solid black">{{$meses_calendario[$_nmes]}}</th>
          @foreach($abbr_casinos as $_casino => $_cas)
          <?php 
            $c = $dataf($_casino,$año,$_nmes);
            $canon = $formatear_decimal($c->canon ?? null);
            $variacion_canon_yoy = $formatear_porcentaje($c->variacion_canon_yoy ?? null);
            $variacion_canon_mom = $formatear_porcentaje($c->variacion_canon_mom ?? null);
          ?>
          <td class="canon {{$N($canon)}}">{{$canon}}</td>
          @endforeach
          <td class="variacion_canon_yoy {{$N($variacion_canon_yoy)}}">{{$variacion_canon_yoy}}</td>
          <td class="variacion_canon_mom {{$N($variacion_canon_mom)}}">{{$variacion_canon_mom}}</td>
        </tr>
        @endfor
        <tr>
          <th class="mes celda_especial" style="border-right: 1px solid black;">{{$año}}</th>
          @foreach($abbr_casinos as $_casino => $_cas)
          <?php
            $total = $dataf($_casino,$año,0);
            $canon = $formatear_decimal($total->canon ?? null);
            $variacion_canon_yoy = $formatear_porcentaje($total->variacion_canon_yoy ?? null);
          ?>
          <th class="canon {{$_cas}} {{$N($canon)}}" style="text-align: right;">{{$canon}}</th>
          @endforeach
          <th class="variacion_canon_yoy {{$N($variacion_canon_yoy)}}" style="text-align: right;">{{$variacion_canon_yoy}}</th>
          <th class="variacion_canon_mom" style="text-align: right;">{{$valor_vacio}}</th>
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
