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
    <h4 style="font-family:Roboto-Condesed !important;top:-10px;bottom:-30px!important;padding-top:-40px !important;"><i>ESTADÍSTICAS DE OCUPACION DE LAS SALAS DE JUEGO<i></h4>


      <!-- Tabla de estadísticas de máquinas tragamonedas-->
      <div class="primerEncabezado">Reporte estadístico de control ambiental - Máquinas tragamonedas:</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">SECTOR</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{$otros_datos['cantidad_turnos']}}">PORCENTAJE DE OCUPACIÓN</th>
          </tr>
          <tr>
            @foreach ($relevamiento_ambiental->casino->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">{{$turno->nro_turno}}</th>
            @endforeach
          </tr>
        </thead>
        @foreach ($detalles as $detalle)
          @if ($detalle['id_sector'] == $sector->id_sector)
            <tr>
              <td class="tablaAmbiental" style="background-color: white" width="10px">{{$detalle['nro_isla']}} </td>
              @if ($detalle['turno1'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno1']}}</td> <?php $total_turno1+=$detalle['turno1']; ?>
              @elseif ($turnos_size >=1) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno2'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno2']}}</td> <?php $total_turno2+=$detalle['turno2']; ?>
              @elseif ($turnos_size >=2) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno3'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno3']}}</td> <?php $total_turno3+=$detalle['turno3']; ?>
              @elseif ($turnos_size >=3) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno4'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno4']}}</td> <?php $total_turno4+=$detalle['turno4']; ?>
              @elseif ($turnos_size >=4) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno5'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno5']}}</td> <?php $total_turno5+=$detalle['turno5']; ?>
              @elseif ($turnos_size >=5) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno6'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno6']}}</td> <?php $total_turno6+=$detalle['turno6']; ?>
              @elseif ($turnos_size >=6) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno7'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno7']}}</td> <?php $total_turno7+=$detalle['turno7']; ?>
              @elseif ($turnos_size >=7) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($detalle['turno8'] != NULL) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$detalle['turno8']}}</td> <?php $total_turno8+=$detalle['turno8']; ?>
              @elseif ($turnos_size >=8) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
            </tr>
          @endif
        @endforeach
            <tr>
              <td class="tablaAmbiental" style="background-color: #e6e6e6"><b>TOTAL</b></td>
              @if ($total_turno1>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno1}}</td>
              @elseif ($turnos_size >=1) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno2>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno2}}</td>
              @elseif ($turnos_size >=2) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno3>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno3}}</td>
              @elseif ($turnos_size >=3) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno4>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno4}}</td>
              @elseif ($turnos_size >=4) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno5>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno5}}</td>
              @elseif ($turnos_size >=5) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno6>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno6}}</td>
              @elseif ($turnos_size >=6) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno7>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno7}}</td>
              @elseif ($turnos_size >=7) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
              @if ($total_turno8>0) <td class="tablaAmbiental" style="background-color: #e6e6e6">{{$total_turno8}}</td>
              @elseif ($turnos_size >=8) <td class="tablaAmbiental" style="background-color: white" width="11px"></td>
              @endif
            </tr>
      </table>
      <br><br>

  </body>
</html>
