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

    <script src="/js/jquery.js"></script>
    <script src="/js/bootstrap.js"></script>
    <script src="/js/ajaxError.js"></script>
  </head>

  <body>
    <div class="encabezadoImg">
      <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
      <br>
      <h2><span>PVAR03 | Procedimiento de Control Ambiental</span></h2>
    </div>
    <div class="camposTab titulo" style="top:-20px; right:-15px;">FECHA INFORME</div>
    <div class="camposInfo" style="top:-5px; right:-5px;"></span><?php $hoy = date('j-m-y / h:i'); print_r($hoy); ?></div>
    <div class="camposTab titulo" style="top:18px; right:-40px;">FECHA DE PRODUCCIÓN</div>
    <div class="camposInfo" style="top:33px; right:5px;"></span>{{$otros_datos['fecha_produccion']}}</div>
    <h4 style="font-family:Roboto-Condesed !important;top:-10px;bottom:-30px!important;padding-top:-40px !important;"><i>ESTADÍSTICAS DE OCUPACIÓN DE LAS SALAS DE JUEGO<i></h4>

      <!-- Tabla de estadísticas de máquinas tragamonedas-->
      <div class="primerEncabezado">Reporte estadístico de control ambiental - Máquinas tragamonedas:</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">SECTOR</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">PORCENTAJE DE OCUPACIÓN</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">TOTALES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">DISTRIBUCIÓN GLOBAL</th>
          </tr>
          <tr>
            @for ($i=1; $i<=3; $i++)
              @foreach ($otros_datos['casino']->turnos as $turno)
              <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">T{{$turno->nro_turno}}</th>
              @endforeach
            @endfor
          </tr>
        </thead>
        <?php $una_sola_vez=1?>
        @foreach ($detalles_informe_mtm as $detalle)
        <tr>
          <td class="tablaInicio" style="background-color: white" width="10px">{{$detalle['sector_nombre']}}</td>
          @foreach ($detalle['porcentajes_sector'] as $porc)
          <td class="tablaInicio" style="background-color: white" width="10px">{{$porc['porcentaje'] . '%'}}</td>
          @endforeach
          @foreach ($detalle['totales_sector'] as $tot)
          <td class="tablaInicio" style="background-color: white" width="10px">{{$tot['total']}}</td>
          @endforeach
          @if ($una_sola_vez)
          @foreach ($distribuciones_globales_mtm as $dist)
          <td class="tablaInicio" style="background-color: white" width="10px" rowspan="{{sizeof($otros_datos['casino']->sectores)}}">{{$dist['distribucion']}}</td>
          @endforeach
          <?php $una_sola_vez=0; ?>
          @endif
        </tr>
        @endforeach
      </table>
      <br><br>

      <!-- Tabla de estadísticas de mesas de paño-->
      <div class="primerEncabezado">Reporte estadístico de control ambiental - Mesas de paño:</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">SECTOR</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">PORCENTAJE DE OCUPACIÓN</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">TOTALES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">DISTRIBUCIÓN GLOBAL</th>
          </tr>
          <tr>
            @for ($i=1; $i<=3; $i++)
              @foreach ($otros_datos['casino']->turnos as $turno)
              <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">T{{$turno->nro_turno}}</th>
              @endforeach
            @endfor
          </tr>
        </thead>
        <?php $una_sola_vez=1?>
        @foreach ($detalles_informe_mesas as $detalle)
        <tr>
          <td class="tablaInicio" style="background-color: white" width="10px">{{$detalle['sector_nombre']}}</td>
          @foreach ($detalle['porcentajes_sector'] as $porc)
          <td class="tablaInicio" style="background-color: white" width="10px">{{$porc['porcentaje'] . '%'}}</td>
          @endforeach
          @foreach ($detalle['totales_sector'] as $tot)
          <td class="tablaInicio" style="background-color: white" width="10px">{{$tot['total']}}</td>
          @endforeach
          @if ($una_sola_vez)
          @foreach ($distribuciones_globales_mesas as $dist)
          <td class="tablaInicio" style="background-color: white" width="10px" rowspan="{{sizeof($otros_datos['casino']->sectores_mesas)}}">{{$dist['distribucion']}}</td>
          @endforeach
          <?php $una_sola_vez=0; ?>
          @endif
        </tr>
        @endforeach
      </table>
      <br><br>

      <!-- Tabla de totales absolutos-->
      <div class="primerEncabezado">Reporte estadístico de control ambiental - Totales absolutos:</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;"></th>
            @foreach ($otros_datos['casino']->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">T{{$turno->nro_turno}}</th>
            @endforeach
          </tr>
        </thead>
        <tr>
          <td class="tablaInicio" style="background-color: #e6e6e6" width="10px"><b>TOTALES ABSOLUTOS</b></td>
          @for ($i=1; $i<=sizeof($otros_datos['totales_absolutos']); $i++)
          <td class="tablaInicio" style="background-color: white" width="10px">{{$otros_datos['totales_absolutos'][$i-1]}}</td>
          @endfor
        </tr>
      </table>
      <br><br>

  </body>
</html>
