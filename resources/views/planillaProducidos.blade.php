<!DOCTYPE html>

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
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <h2><span>RMTM09 | Producidos diarios por máquina tragamonedas (MTM) en {{$pro->tipo_moneda}}</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');print_r($hoy);?></span></div>
    <div class="camposInfo" style="top:88px; left: 0%;"><b>Fecha de producido:</b> {{$pro->fecha_prod}}</div>
    <div class="camposInfo" style="top:88px; left: 25%;"><b>Casino:</b> {{$pro->casinoNom}}</div>
    <div class="camposInfo" style="top:88px; left: 40%;"><b>Maquinas con producidos:</b> {{count($detalles)}}</div>
    <br>
    <?php
    $cols_x_pag = 4;
    $ancho_tabla = (100.0/$cols_x_pag);
    $filas_por_col = 24.0;
    $posicion = [
     0 =>  'position: absolute;top: 120px;left: -5%;',
     1 =>  'position: absolute;top: 120px;left: 22%;',
     2 =>  'position: absolute;top: 120px;left: 48%;',
     3 =>  'position: absolute;top: 120px;left: 75%;'
    ];
    $filas_por_pag = $filas_por_col*$cols_x_pag;
    $paginas = ceil(count($detalles)/$filas_por_pag);
    ?>
    @for($p = 0;$p < $paginas;$p++)
    @if($p != 0)
    <div style="page-break-after:always;"></div>
    @endif
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
        <th class="tablaInicio">MTM</th>
        <th class="tablaInicio">PRODUCIDO</th>
      </tr>
      @for($i=$start;$i<$end;$i++)
      <?php $d = $detalles[$i] ?>
      <tr>
        <td class="tablaCampos">{{$d->maquina}}</td>
        <td class="tablaCampos">{{$d->valor}}</td>
      </tr>
      @endfor
    </table>
    @endif
    @endfor
    @endfor
  </body>
</html>