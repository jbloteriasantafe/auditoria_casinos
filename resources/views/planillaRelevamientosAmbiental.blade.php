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
      white-space: nowrap;
    }

    tr:nth-child(even) {
      background-color: #dddddd;
    }

    p {
      border-top: 1px solid #000;
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
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
        <h2><span>PVAR03 | Procedimiento de Control Ambiental</span></h2>
    </div>

    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>

    <!-- Tabla de datos del relevamiento de control ambiental -->
    <table>
      <tr>
        <th class="tablaInicio" style="background-color: #e6e6e6">CASINO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">N° RELEVAMIENTO</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA PRODUCCIÓN</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FECHA AUDITORÍA</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">FISCALIZADOR</th>
        <th class="tablaInicio" style="background-color: #e6e6e6">ESTADO</th>
      </tr>

      <tr>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['casino']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->nro_relevamiento_ambiental}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_generacion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$relevamiento_ambiental->fecha_ejecucion}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['fiscalizador']}}</td>
        <td class="tablaInicio" style="background-color: white">{{$otros_datos['estado']}}</td>
      </tr>
    </table>
    <br>


    <!-- Tabla de control ambiental Melincué-->
    @if ($relevamiento_ambiental->casino->id_casino == 1)
      <?php $total_turno1=0; $total_turno2=0; $total_turno3=0; $total_turno4=0; $total_turno5=0; $total_turno6=0; $total_turno7=0; $total_turno8=0;
      $turnos_size = sizeof($relevamiento_ambiental->casino->turnos);
      ?>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">ISLAS</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
          </tr>
          <tr>
            @foreach ($relevamiento_ambiental->casino->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6" width="11px">{{$turno->nro_turno}}</th>
            @endforeach
          </tr>
        </thead>
        @foreach ($detalles as $detalle)
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
          @elseif ($turnos_size >= 8) <td class="tablaAmbiental" style="background-color: white" width="11px">{{$turnos_size}}</td>
          @endif
        </tr>
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


    <!-- Tabla de control ambiental Santa Fe-->
    @elseif ($relevamiento_ambiental->casino->id_casino == 2)
      @foreach ($relevamiento_ambiental->casino->sectores as $sector)
      <?php $total_turno1=0; $total_turno2=0; $total_turno3=0; $total_turno4=0; $total_turno5=0; $total_turno6=0; $total_turno7=0; $total_turno8=0;
      $turnos_size = sizeof($relevamiento_ambiental->casino->turnos);
      ?>
      <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">ISLAS</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
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
      @endforeach


    <!-- Tabla de control ambiental Rosario-->
    @else
      <?php $contador_tablas=0; ?>
      @foreach ($relevamiento_ambiental->casino->sectores as $sector)
      <?php $total_turno1=0; $total_turno2=0; $total_turno3=0; $total_turno4=0; $total_turno5=0; $total_turno6=0; $total_turno7=0; $total_turno8=0;
      $turnos_size = sizeof($relevamiento_ambiental->casino->turnos);
      $contador_colspan=0;
      ?>
      <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
      <table>
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" width="10px;" rowspan="2">ISLOTES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" width="10px" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
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
              <td class="tablaAmbiental" style="background-color: white" width="10px">{{$detalle['nro_islote']}} </td>
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
        <?php $contador_tablas++; ?>
      </table>
      <br>
      @if ($contador_tablas == 2)
        <div style="page-break-after:always;"></div>
      @endif
      @endforeach
    @endif
    <br><br>


    <!-- Tabla de detalles de generalidades-->
    <div class="primerEncabezado">Detalles de generalidades:</div>
    <table>
      <thead>
        <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" rowspan="2" width="120px">GENERALIDADES</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" colspan="{{sizeof($relevamiento_ambiental->casino->turnos)}}">TURNOS</th>
        </tr>
        <tr>
          @foreach ($relevamiento_ambiental->casino->turnos as $turno)
          <th class="tablaInicio" style="background-color: #e6e6e6">{{$turno->nro_turno}}</th>
          @endforeach
        </tr>
      </thead>
        @foreach ($generalidades as $g)
        <tr>
          <td class="tablaInicio" style="background-color: #e6e6e6" width="120px"><b>{{$g['tipo_generalidad']}}</b></td>
          @if ($g['turno1'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno1']}}</td>
          @elseif ($turnos_size >=1) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno2'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno2']}}</td>
          @elseif ($turnos_size >=2) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno3'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno3']}}</td>
          @elseif ($turnos_size >=3) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno4'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno4']}}</td>
          @elseif ($turnos_size >=4) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno5'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno5']}}</td>
          @elseif ($turnos_size >=5) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno6'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno6']}}</td>
          @elseif ($turnos_size >=6) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno7'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno7']}}</td>
          @elseif ($turnos_size >=7) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
          @if ($g['turno8'] != null) <td class="tablaAmbiental" style="background-color: white">{{$g['turno8']}}</td>
          @elseif ($turnos_size >=8) <td class="tablaAmbiental" style="background-color: white"></td>
          @endif
        </tr>
        @endforeach
    </table>
    <br><br>


    @if ($relevamiento_ambiental->observacion_carga != NULL)
      <div class="primerEncabezado">Observaciones de carga:</div>
      <div style="color: #9c9c9c; ">
        {{$relevamiento_ambiental->observacion_carga}}
      </div><br><br>
    @endif

    <div class="primerEncabezado" style="padding-left: 460px;"><p style="width: 250px; padding-left: 50px;">Firma y aclaración/s responsable/s.</p></div>
  </body>

</html>
