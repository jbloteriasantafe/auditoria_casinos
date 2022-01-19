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

    footer {
        margin-top:50px;
        width:200%;
        height:300px;
    }

    .derecha {
      text-align: right;
    }
    .centro {
      text-align: center;
    }
  </style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
    <link href="/css/importacionFuentes.css" rel="stylesheet">
    <link rel="stylesheet" href="/web-fonts-with-css/css/fontawesome-all.css">
  </head>

  <body>
    <div class="encabezadoImg">
      <img src="img/logos/banner_nuevo2_landscape.png" width="900">
      <br>
      <h2><span>PVAR03 | Procedimiento de Control Ambiental</span></h2>
    </div>
    <div class="camposTab titulo" style="top:-20px; right:-15px;">FECHA INFORME</div>
    <div class="camposInfo" style="top:-5px; right:-5px;"></span><?php $hoy = date('j-m-y / h:i'); print_r($hoy); ?></div>
    <div class="camposTab titulo" style="top:18px; right:-40px;">FECHA DE PRODUCCIÓN</div>
    <div class="camposInfo" style="top:33px; right:5px;"></span>{{$otros_datos['fecha_produccion']}}</div>
    <h4 style="font-family:Roboto-Condesed !important;top:-10px;bottom:-30px!important;padding-top:-40px !important;"><i>ESTADÍSTICAS DE OCUPACIÓN DE LAS SALAS DE JUEGO<i></h4>

    <!-- Tabla MTM -->
    <div class="primerEncabezado">Reporte de Máquinas tragamonedas:</div>
    <table style="width: 100%;">
      <thead>
        <tr>
          <th class="tablaInicio centro" style="background-color: #e6e6e6;" width="15%;" rowspan="2">SECTOR</th>
          <!-- Le sumo 1 para el total por sector -->
          <th class="tablaInicio centro" style="background-color: #e6e6e6;" colspan="{{$otros_datos['cantidad_turnos']+1}}">OCUPACIÓN</th>
        </tr>
        <tr>
          @foreach ($sectores_mtm['TOTAL']['turnos'] as $nro_turno => $cantidad)
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">T{{$nro_turno}}</th>
          @endforeach
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">TOTAL</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sectores_mtm as $s)
        <tr>
          <td class="tablaInicio centro" style="background-color: white;" width="15%">{{$s['sector']}}</td>
          @foreach($s['turnos'] as $nro_turno => $cantidad)
          <td class="tablaInicio derecha" style="background-color: white;">{{$cantidad}}</td>
          @endforeach
          <td class="tablaInicio derecha" style="background-color: white;">{{$s['total_sector']}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Tabla Mesas -->
    <div class="primerEncabezado">Reporte de Mesas de paño:</div>
    <table style="width: 100%;">
      <thead>
        <tr>
          <th class="tablaInicio centro" style="background-color: #e6e6e6;" width="15%;" rowspan="2">SECTOR</th>
          <!-- Le sumo 1 para el total por sector -->
          <th class="tablaInicio centro" style="background-color: #e6e6e6; text-align: center"
            colspan="{{$otros_datos['cantidad_turnos']+1}}">OCUPACIÓN</th>
        </tr>
        <tr>
          @foreach ($sectores_mesas['TOTAL']['turnos'] as $nro_turno => $cantidad)
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">T{{$nro_turno}}</th>
          @endforeach
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">TOTAL</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sectores_mesas as $s)
        <tr>
          <td class="tablaInicio centro" style="background-color: white;" width="15%;">{{$s['sector']}}</td>
          @foreach($s['turnos'] as $nro_turno => $cantidad)
          <td class="tablaInicio derecha" style="background-color: white;">{{$cantidad}}</td>
          @endforeach
          <td class="tablaInicio derecha" style="background-color: white;">{{$s['total_sector']}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <!-- Tabla totales (MTM + Mesas) -->    
    <div class="primerEncabezado">Reporte estadístico de control ambiental - Totales:</div>
    <table style="width: 100%;">
      <thead>
        <tr>
          <th class="tablaInicio centro" style="background-color: #e6e6e6;" width="15%;"></th>
          @foreach ($total_por_turno as $nro_turno => $cantidad)
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">T{{$nro_turno}}</th>
          @endforeach
          <th class="tablaInicio centro" style="background-color: #e6e6e6;">TOTAL</th>
        </tr>
      </thead>
      <tr>
        <td class="tablaInicio centro" style="background-color: #e6e6e6;" width="15%;"><b>OCUPACIÓN</b></td>
        @foreach ($total_por_turno as $nro_turno => $cantidad)
        <td class="tablaInicio derecha" style="background-color: white;">{{$cantidad}}</td>
        @endforeach
        <td class="tablaInicio derecha" style="background-color: white;">{{$total}}</td>
      </tr>
    </table>
  </body>
</html>
