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
    padding: 3px;
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
</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">

  </head>
  <body>
    <div class="encabezadoImg">
          <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
          <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
    </div>
          <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
          <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                print_r($hoy); ?></div>
<!-- habria que ubicarlo mejor porque igual le hace padding :) -->
    <h4 style="top:0px; text-align:center;padding-top:-40px !important;padding-bottom:-25px!important;bottom:-25px!important;">Resultados del mes {{$por_moneda[0]['mes']}} para {{$por_moneda[0]['casino']}}</h4>

    @foreach($por_moneda as $moneda)
      @if($loop->first)
      <h4 style="font-family:Roboto-Regular;top:-10px;bottom:-20px!important;"><i>RESULTADOS EN {{$moneda['moneda']}}<i></h4>
      @else
      <div style="page-break-after:always;"></div>
      <div class="encabezadoImg">
            <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
            <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
      </div>
      <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
      <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
            print_r($hoy); ?></div>
      <h4 style="font-family:Roboto-Regular;top:-10px;bottom:-20px!important;padding-top:-40px !important;"><i>RESULTADOS EN {{$moneda['moneda']}}<i></h4>

      @endif

      <table style="border-collapse: collapse;" >
        <thead>
          <tr align="center" >
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">FECHA</th>
            <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">SALDO EN FICHAS</th>
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">DROP</th>
            <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">UTILIDAD</th>
            <th class="col-xl-2 tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">HOLD</th>
            @if($moneda['moneda'] != 'ARS')
              <th class="col-xl-2 tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">COTIZACIÓN</th>
              <th class="col-xl-2 tablaInicio"  style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important;">CONVERSIÓN</th>
            @endif
          </tr>
        </thead>
        <tbody>
          @foreach($moneda['detalles'] as $d)
          <tr>
            <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$d->fecha_dia}}</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$d->saldo_fichas_dia}}</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$d->total_diario}}</td> <!--  drop,efectivo,platita -->
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$d->utilidad}}</td>
            <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$d->hold}}</td>
            @if($moneda['moneda'] != 'ARS')
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$d->cotizacion}}</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$d->conversion}}</td>
            @endif
          </tr>
          @endforeach
          <!-- fila totalizadora -->
          <tr>
            <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$moneda['totales_moneda']->fecha_dia}}</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$moneda['totales_moneda']->saldo_fichas_dia}}</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$moneda['totales_moneda']->total_diario}}</td> <!--  drop,efectivo,platita -->
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$moneda['totales_moneda']->utilidad}}</td>
            <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$moneda['totales_moneda']->hold}}</td>
            @if($moneda['moneda'] != 'ARS')
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">--</td>
            <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">$moneda['totales_moneda']->conversion_total</td>
            @endif
          </tr>
        </tbody>
      </table>

      <div style="page-break-after:always;"></div>
      <div class="encabezadoImg">
            <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
            <h2><span>RMES02 | Informe mensual por casinos de MESAS DE PAÑO.</span></h2>
      </div>
      <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
      <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
            print_r($hoy); ?></div>
      <h4 style="padding-top:-40px !important;">Resultado Mensual en {{$moneda['moneda']}}, por Juego</h4>
      <table style="border-collapse: collapse;" >
        <thead>
          <tr align="center" >
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray;text-align:center !important">JUEGO</th>
            <th class="col-xl-2 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">UTILIDAD TOTAL</th>
            <th class="col-xl-3 tablaInicio" style="font-size:14px;background-color: #dddddd; border-color: gray; text-align:center !important;">PORCENTAJE</th>
          </tr>
        </thead>
        <tbody>
          @foreach($moneda['juegos'] as $j)
            <tr>
              <td class="tablaCampos" style="text-align:center !important; font-size:13px !important">{{$j['nombre_juego']}}</td>
              <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$j['total']}}</td>
              <td class="tablaCampos" style="text-align:right !important; font-size:13px !important">{{$j['porcentaje']}} %</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <br>
      <br>
      <br>
    @endforeach

</html>
