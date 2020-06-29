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
    .break{
      word-wrap: break-word;
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

    <?php 
    $contador_tablas=0; 
    $turnos_size = sizeof($relevamiento_ambiental->casino->turnos);
    function clearnull($val,$default = ''){
      return is_null($val)? $default : $val;
    }
    function clearzero($val,$default = ''){
      return $val == 0? $default : $val;
    }
    $id_casino = $relevamiento_ambiental->casino->id_casino;
    $nombre_columna = $id_casino == 3? 'ISLOTES' : 'ISLAS';
    $atributo = $id_casino == 3? 'nro_islote' : 'nro_isla';
    ?>
      @foreach ($relevamiento_ambiental->casino->sectores as $sector)
      <br>
      <?php $total_turno = [];
      for($i=1;$i<=$turnos_size;$i++) $total_turno[$i] = 0;
      ?>
      <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
      <table style="table-layout:fixed;">
        <thead>
          <tr>
            <th class="tablaInicio" style="background-color: #e6e6e6" rowspan="2">{{$nombre_columna}}</th>
            <th class="tablaInicio" style="background-color: #e6e6e6; text-align: center" colspan="{{$turnos_size}}">TURNOS</th>
          </tr>
          <tr>
            @foreach ($relevamiento_ambiental->casino->turnos as $turno)
            <th class="tablaInicio" style="background-color: #e6e6e6">{{$turno->nro_turno}}</th>
            @endforeach
          </tr>
        </thead>
        @foreach ($detalles as $detalle)
          @if ($detalle['id_sector'] == $sector->id_sector)
            <tr>
              <td class="tablaAmbiental" style="background-color: white">{{$detalle[$atributo]}} </td>
              @for($i=1;$i<=$turnos_size;$i++)
              <?php $total_turno[$i] += $detalle['turno'.$i];?>
              <td class="tablaAmbiental" style="background-color: white">{{clearnull($detalle['turno'.$i])}}</td>
              @endfor
            </tr>
          @endif
        @endforeach
            <tr>
              <td class="tablaAmbiental" style="background-color: #e6e6e6"><b>TOTAL</b></td>
              @for($i=1;$i<=$turnos_size;$i++)
              <td class="tablaAmbiental" style="background-color: white">{{clearzero($total_turno[$i])}}</td>
              @endfor
            </tr>
            <?php $contador_tablas++; ?>
      </table>
      @if ($contador_tablas == 2)
        <div style="page-break-after:always;"></div>
      @endif
      @endforeach


    <div style="page-break-after:always;"></div>
    <!-- Tabla de detalles de generalidades-->
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
          @for($i=1;$i<=$turnos_size;$i++)
          <td class="tablaAmbiental" style="background-color: white">{{is_null($g['turno'.$i])? '' : $g['turno'.$i]}}</td>
          @endfor
        </tr>
        @endforeach
    </table>
    <br>
    <table style="table-layout:fixed;">
        <thead><tr>
          <th class="tablaInicio" style="background-color: #e6e6e6">OBSERVACIONES GENERALES</th>
        </tr></thead>
        <tbody><tr>
          <td class="tablaAmbiental" style="background-color: #white">
            <div class="break">
            {{clearnull($relevamiento_ambiental->observacion_carga,str_repeat('.',648))}}
            </div>
          </td>
        </tr></tbody>
    </table>

    @if ($relevamiento_ambiental->observacion_validacion != NULL)
      <br>
      <table style="table-layout:fixed;">
        <thead><tr>
          <th class="tablaInicio" style="background-color: #e6e6e6">OBSERVACIONES VALIDACIÓN</th>
        </tr></thead>
        <tbody><tr>
          <td class="tablaAmbiental" style="background-color: #white">
            <div class="break">
            {{$relevamiento_ambiental->observacion_validacion}}
            </div>
          </td>
        </tr></tbody>
      </table>
    @endif

    <br>
    <div class="primerEncabezado" style="padding-left: 460px;"><p style="width: 250px; padding-left: 50px;">Firma y aclaración/s responsable/s.</p></div>
  </body>

</html>
