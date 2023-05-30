<!DOCTYPE html>
<html>

<style>
  table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 98%;
  }

  td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 1px;
  }

  tr:nth-child(even) {
    background-color: #dddddd;
  }

  p {
        border-top: 1px solid #000;
  }

  footer
  {
      margin-top:50px;
      width:200%;
      height:300px;
  }
  #tablaDias thead th {
    font-size:14px;
    background-color: #dddddd;
    border-color: gray;
    text-align:center !important
  }
  #tablaDias tbody tr td {
    font-size: 11px;
    text-align: right;
  }
  #tablaDias tbody tr:last-child td {
    font-size: 11px;
    font-weight: bold;
    text-align: right;
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
    <?php 
    $fecha_planilla = date('j-m-y / h:i');
    ?>
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <br>
      <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;">{{$fecha_planilla}}</div>
    <h4 style="top:-10px;bottom:-30px!important;padding-top:-40px !important;text-align: center;">
      Resultados del mes {{$mes}} para {{$casino->nombre}}
    </h4>

    @if(empty($datos['detalles']))
    <h4 style="text-align: center"><i>SIN IMPORTACIONES<i></h4>
    @else
    <h4 style="top:-10px;bottom:-30px!important;padding-top:-30px !important;">
      <i>RESULTADOS EN {{$datos['moneda']}}<i>
    </h4>
    <table style="border-collapse: collapse;" id="tablaDias">
      <thead>
        <tr align="center" >
          <th class="col-xl-2 tablaInicio">FECHA</th>
          <th class="col-xl-3 tablaInicio">MESAS</th>
          <th class="col-xl-3 tablaInicio">SALDO EN FICHAS</th>
          <th class="col-xl-2 tablaInicio">DROP</th>
          <th class="col-xl-3 tablaInicio">UTILIDAD</th>
          <th class="col-xl-2 tablaInicio">HOLD</th>
          @if($datos['moneda'] != 'ARS')
            <th class="col-xl-2 tablaInicio">COTIZACIÓN</th>
            <th class="col-xl-2 tablaInicio">CONVERSIÓN</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @foreach($datos['detalles'] as $d)
        <tr>
          <td class="tablaCampos" style="text-align: center">{{$d['fecha']}}</td>
          <td class="tablaCampos" style="text-align: center">{{$d['mesas'] ?? 1}}</td>
          <td class="tablaCampos">{{number_format($d['saldo_fichas'],2,",",".")}}</td>
          <td class="tablaCampos">{{number_format($d['droop'],2,",",".")}}</td>
          <td class="tablaCampos">{{number_format($d['utilidad'],2,",",".")}}</td>
          <td class="tablaCampos">{{$d['hold']}} %</td>
          @if($datos['moneda'] != 'ARS')
          <td class="tablaCampos">{{number_format($d['cotizacion_diaria'],2,",",".")}}</td>
          <td class="tablaCampos">{{$d['conversion_total']}}</td>
          @endif
        </tr>
        @endforeach
        <!-- fila totalizadora -->
        <tr>
          <td class="tablaCampos" style="text-align: center">{{$mes.'-##'}}</td>
          <td class="tablaCampos" style="text-align: center">{{$datos['total']->mesas}}</td>
          <td class="tablaCampos">{{number_format($datos['total']->saldo_fichas,2,",",".")}}</td>
          <td class="tablaCampos">{{number_format($datos['total']->droop,2,",",".")}}</td>
          <td class="tablaCampos">{{number_format($datos['total']->utilidad,2,",",".")}}</td>
          <td class="tablaCampos">{{$datos['total']->hold}} %</td>
          @if($datos['moneda'] != 'ARS')
          <td class="tablaCampos">--</td>
          <td class="tablaCampos">{{$datos['total']->conversion_total}}</td>
          @endif
        </tr>
      </tbody>
    </table>

    <div style="page-break-after:always;"></div>
    <div class="encabezadoImg">
          <img src="img/logos/banner_nuevo2_landscape.png" width="900">
          <br>
          <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
    </div>
    <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:0px;">{{$fecha_planilla}}</div>
    <h4 style="top:-10px;bottom:-40px!important;padding-top:-30px !important;">
      Resultado Mensual en {{$datos['moneda']}}, por Juego
    </h4>
    @endif

    <table style="border-collapse: collapse;" >
      <thead>
        <tr align="center" >
          <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">JUEGO</th>
          <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">UTILIDAD</th>
          <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">UTILIDAD (ABS)</th>
          <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">PORCENTAJE</th>
        </tr>
      </thead>
      <tbody>
        <?php $utilidad = 0 ?>
        @foreach($datos['juegos'] as $j)
        <?php $utilidad += $j->utilidad ?>
        <tr>
          <td class="tablaCampos" style="font-size: 13px;text-align: center">{{$j->siglas_juego . $j->nro_mesa}}</td>
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{number_format($j->utilidad,2,',','.')}}</td>
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{number_format($j->abs_utilidad,2,',','.')}}</td>
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{$j->porcentaje}} %</td>
        </tr>
        @endforeach
        <tr>
          <td class="tablaCampos" style="font-size: 13px;text-align: center">---</td>
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{number_format($utilidad,2,',','.')}}</td>
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{number_format($datos['total']->abs_utilidad,2,',','.')}}</td>
          <!-- Deberia ser siempre 100% -->
          <td class="tablaCampos" style="font-size: 13px;text-align: right">{{$datos['total']->porcentaje}} %</td>
        </tr>
      </tbody>
    </table>
  </body>
</html>
