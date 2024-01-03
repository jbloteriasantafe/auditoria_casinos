@section('encabezado')
<div>
  <div class="encabezadoImg">
    <img src="{{public_path()}}/img/logos/banner_2024_landscape.png" width="900">
    <h2><span>RMES02 | Control de apertura y cierre de MESA DE PAÑO.</span></h2>
  </div>
  <div class="camposTab titulo" style="top: -15px; right:-15px;">
    FECHA PLANILLA
  </div>
  <div class="camposInfo" style="top: 0px; right:0px;"></span>
    <?php $hoy = date('j-m-y / h:i');print_r($hoy); ?>
  </div>
</div>
@endsection

@section('fichas')
<table>
  <thead>
    <tr>
      <th style="width:11.75%;">FECHA</th>
      <th style="width:9.25%;">HORA</th>
      <th style="width:49%;">JUEGO</th>
      <!--Si es de Rosario le damos el nmesa + urs + ars. Sino le damos solo nmesa. -->
      @if($rel->id_casino == 3 || $rel->casino == "Rosario")
      <th style="width:16.5%;">N° MESA</th>
      <th style="width:6.75%;">ARS</th>
      <th style="width:6.75%;">USD</th>
      @else
      <th style="width:30%;">N° MESA</th>
      @endif
    </tr>
  </thead>
  <tr>
    <td style="width:11.75%;">
      <?php $hoy = date('j-m-y'); print_r($hoy); ?>
    </td>
    <td style="width:9.25%;">__:__</td>
    <td style="width:49%;">&nbsp;</td>
    @if($rel->id_casino == 3 || $rel->casino == "Rosario")
    <td style="width:16.5%;">&nbsp;</td>
    <td style="width:6.75%;">&nbsp;</td>
    <td style="width:6.75%">&nbsp;</td>
    @else
    <td style="width:30%">&nbsp;</td>
    @endif
  </tr>
</table>

<table>
  <?php
    $max_rows = -1;
    foreach($rel->fichas as $l) $max_rows = max($max_rows,count($l));
    $weight = ['valor_ficha' => 21,'cantidad' => 79 ];
    $cols = count($rel->fichas);
    foreach($weight as &$w) $w /= $cols;
  ?>
  <thead>
    <tr>
      @foreach($rel->fichas as $ign)
      <th style="width: {{$weight['valor_ficha']}}%;">VALOR FICHA</th>
      <th style="width: {{$weight['cantidad']}}%;">CANTIDAD</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @for($i=0;$i<$max_rows;$i++)
    <tr>
      @foreach($rel->fichas as $lista_fichas)
      @if(array_key_exists($i,$lista_fichas))
      <th style="width: {{$weight['valor_ficha']}}%;">{{$lista_fichas[$i]}}</th>
      <td style="width: {{$weight['cantidad']}}%;">&nbsp;</td>
      @else
      <td style="width: {{$weight['valor_ficha']}}%;border: 0;">&nbsp;</td>
      <td style="width: {{$weight['cantidad']}}%;border: 0;">&nbsp;</td>
      @endif
      @endforeach
    </tr>
    @endfor
  </tbody>
</table>
<br>
<br>
<div style="width: 100%;">
  <div style="text-align: center;width: 100%;">.........................................</div>
  <div style="text-align: center;width: 100%;">Firma y aclaración</div>
</div>
@endsection

<!DOCTYPE html>
<html>
  <head>
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{public_path()}}/css/estiloPlanillaPortrait.css" rel="stylesheet">
    <style>    
    table {
      font-family: arial, sans-serif;
      border-collapse: collapse;
      width: 98%;
    }
    
    th {
      background-color: #dddddd;
    }
    
    td, th {
      border: 1px solid #dddddd;
      text-align: center;
      padding: 3px;
      border-color: gray;
      font-size: 12px;/*Pasar a em*/
    }
    
    tbody th {
      font-weight: normal !important;
    }
    
    .tablaGeneral th,.tablaGeneral td {
      padding: 1px;
      margin: 0px;
    }
    </style>
  </head>

  <body>
    @yield('encabezado')
    <!-- Tabla gral -->
    <table class="tablaGeneral" style="width: 100%;margin: 0px;padding: 0px;">
      <thead>
        <tr>
          <td colspan="{{3*count($rel->mesas)}}">MESAS</td>
        </tr>
        <tr>
          @foreach($rel->mesas as $ign)
          <th>JUEGO-NRO</th>
          <th>SECTOR</th>
          <th>HORA APERTURA</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        <?php
          $max_rows = -1;
          foreach($rel->mesas as $l) $max_rows = max($max_rows,count($l));
        ?>
        @for($i=0;$i<$max_rows;$i++)
        <tr>
          @foreach($rel->mesas as $lista_mesas)
          @if(array_key_exists($i,$lista_mesas))
          <th>{{$lista_mesas[$i]['codigo_mesa']}}</th>
          <th>{{$lista_mesas[$i]['sector']}}</th>
          <td>&nbsp;</td>
          @else
          <td style="border: 0;">&nbsp;</td>
          <td style="border: 0;">&nbsp;</td>
          <td style="border: 0;">&nbsp;</td>
          @endif
          @endforeach
        </tr>
        @endfor
      </tbody>
    </table>
    
    <br>
    <table>
      <tr>
        <td style="font-size: 3em;border-right: 0px;">&nbsp;</td>
        <td style="border-left: 0px;"></td>
        <td style="border-right: 0px;"></td>
        <td style="border-left: 0px;"></td>
      </tr>
      <tr>
        <th ><center>Personal del Concesionario en Sala de Juegos</center></th>
        <th ><center>Fiscalizador en Sala de Juegos</center></th>
        <th ><center>Personal del Concesionario en Sala de Juegos</center></th>
        <th ><center>Fiscalizador en Sala de Juegos</center></th>
      </tr>
    </table>
    <!-- segunda hoja -->
    <div style="page-break-after:always;"></div>
    @yield('encabezado')
    <!-- tabla derecha RULETA -->
    <table >
      <tr>
        <td colspan="4">MESAS DE RULETA</td>
      </tr>
      <thead>
        <tr>
          <th>NRO MESA</th>
          <th>JUEGO</th>
          <th>TIPO</th>
          <th>FISCALIZÓ</th>
        </tr>
      </thead>
      @foreach($rel->sorteadas->ruletas as $ruleta)
      <tr>
        <th>{{$ruleta['nro_mesa']}}</th>
        <th>{{$ruleta['nombre_juego']}}</th>
        <th>{{$ruleta['descripcion']}}</th>
        <td>&nbsp;</td>
      </tr>
      @endforeach
    </table>
    <br>
    <br>
    <!-- tabla derecha CARTAS Y DADOS -->
    <table>
      <tr>
        <td colspan="4">MESAS DE CARTAS/DADOS</td>
      </tr>
      <thead>
        <tr>
          <th>N° MESA</th>
          <th>JUEGO</th>
          <th>TIPO</th>
          <th>FISCALIZÓ</th>
        </tr>
      </thead>
      @foreach($rel->sorteadas->cartasDados as $carta)
        <tr>
          <th>{{$carta['nro_mesa']}}</th>
          <th>{{$carta['nombre_juego']}}</th>
          <th>{{$carta['descripcion']}}</th>
          <td>&nbsp;</td>
        </tr>
      @endforeach
    </table>
    <br>
    <br>

    <!-- tabla POZO ACUMULADO -->
    <table>
      <thead>
        <tr>
          <td colspan="2">POZO ACUMULADO</td>
        </tr>
        <tr>
          <th>N° MESA/S</th>
          <th>POZO ACUMULADO</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      </tbody>
    </table>

    @for($i=0;$i<$rel->paginas;$i++)
    <div style="page-break-after:always;"></div>
    @yield('encabezado')
    @yield('fichas')
    <br>
    @yield('fichas')
    @endfor
  </body>
</html>
