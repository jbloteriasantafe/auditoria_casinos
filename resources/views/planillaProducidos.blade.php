<!DOCTYPE html>
<?php
  $ancho_total_pagina = 106.6;
  $inicio_pagina = -6;
  $ancho_divisiones = $ancho_total_pagina/$cols_x_pag;
  
  $pad_fijo = 1.0;
  //La tabla mide la division menos los 2 pads (1 de cada lado)
  $ancho_tabla = $ancho_divisiones - 2*$pad_fijo;

  $posicion = [];
  {
    $posx = $inicio_pagina + $pad_fijo;
    $posicion[0] = 'position: absolute;left:'.$posx.'%;';
    for($col=1;$col<$cols_x_pag;$col++){
      $posx += $ancho_tabla;
      $posx += 2*$pad_fijo;
      $posicion[$col] = 'position: absolute;left:'.$posx.'%;';
    }
  }

  $filas_por_pag = $filas_por_col*$cols_x_pag;
  $paginas = ceil(count($detalles) /$filas_por_pag);
?>
<html>
  <style>
  table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
  }
  td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
  }
  tr:nth-child(even) {
    background-color: #dddddd;
  }
  
  .center {
    text-align: center;
    font-size: 7.5 !important;
    padding: 0 !important;
  }
  .right {
    text-align: right;
    font-size: 7.5 !important;
    padding: 0 !important;
  }
  </style>
  <head>
    <meta charset="utf-8">
    <title></title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>
    @for($p = 0;$p < $paginas;$p++)
    @if($p != 0)
    <div style="page-break-after:always;"></div>
    @endif
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <h2><span>RMTM09 | Producidos diarios por máquina tragamonedas (MTM) en {{$pro['tipo_moneda']}}</span></h2>
    </div>
    <!-- Esto es hacky, habria que usar una tabla -->
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy);?></span></div>
    <div class="camposInfo" style="top:88px; left: 0%;"><b>Fecha de producido:</b> {{$pro['fecha_prod']}}</div>
    <div class="camposInfo" style="top:88px; left: 25%;"><b>Casino:</b> {{$pro['casinoNom']}}</div>
    <div class="camposInfo" style="top:88px; left: 40%;"><b>Maquinas con producidos:</b> {{$cantidad_totales}}</div>
    <div class="camposInfo" style="top:88px; left: 65% !important;"><b>Total:</b></div>
    <div class="camposInfo" style="top:88px; left: 70% !important">{{$pro['valor']}}</div>
    <br>
    <?php
      $startidxpag = $p*$filas_por_pag;
      $endidxpag   = ($p+1)*$filas_por_pag;
    ?>
    @for($col=0;$col<$cols_x_pag;$col++)
    <?php 
        $start = $startidxpag+$filas_por_col*$col;
        $end   = min($startidxpag+$filas_por_col*($col+1),count($detalles));
    ?>
    @if($start<$end)
    <table style="table-layout:fixed;width: {{$ancho_tabla}}%;{{$posicion[$col%$cols_x_pag]}}">
      <tr>
        <th class="tablaInicio center">MTM</th>
        <th class="tablaInicio center">APUESTA</th>
        <th class="tablaInicio center">PREMIO</th>
        <th class="tablaInicio center">PRODUCIDO</th>
      </tr>
      @for($i=$start;$i<$end;$i++)
      <?php $d = $detalles[$i] ?>
      <tr>
        <td class="tablaCampos center">{{$d['maquina']}}</td>
        <td class="tablaCampos right">{{$d['apuesta']}}</td>
        <td class="tablaCampos right">{{$d['premio']}}</td>
        <td class="tablaCampos right">{{$d['valor']}}</td>
      </tr>
      @endfor
    </table>
    @endif
    @endfor
    @endfor
  </body>
</html>
