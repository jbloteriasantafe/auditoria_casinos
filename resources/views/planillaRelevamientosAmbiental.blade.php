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
        <th class="tablaInicio" style="background-color: #dddddd">CASINO</th>
        <th class="tablaInicio" style="background-color: #dddddd">N° RELEVAMIENTO</th>
        <th class="tablaInicio" style="background-color: #dddddd">FECHA PRODUCCIÓN</th>
        <th class="tablaInicio" style="background-color: #dddddd">FECHA AUDITORÍA</th>
        <th class="tablaInicio" style="background-color: #dddddd">FISCALIZADOR</th>
        <th class="tablaInicio" style="background-color: #dddddd">ESTADO</th>
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
    <br><br>


    <!-- Tabla de control ambiental -->
    <?php $contador = 0; ?>
    @foreach ($relevamiento_ambiental->casino->sectores as $sector)
    <div class="primerEncabezado">Sector de control ambiental: {{$sector->descripcion}}</div>
    <table>
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #dddddd" width="10px">T</th>
          @foreach ($sector->islas as $isla)
          <th class="tablaInicio" style="background-color: #dddddd" width="11px">{{$isla->nro_isla}}</th>
          @endforeach
          <th class="tablaInicio" style="background-color: #dddddd" width="30px">TOT. </th>
        </tr>
      </thead>
      @foreach ($relevamiento_ambiental->casino->turnos as $turno)
      <tr>
        <td class="tablaInicio" style="background-color: white" width="10px">{{$turno->nro_turno}} </td>
        @foreach ($detalles as $detalle)
        @if ($detalle['id_sector'] == $sector->id_sector)
          @foreach ($detalle['cantidades'] as $cantidad)
            @if ($cantidad->cantidad_personas != NULL)
              <td class="tablaInicio" style="background-color: white" width="11px">{{$cantidad->cantidad_personas}}</td>
            @else
              <td class="tablaInicio" style="background-color: white" width="11px">25</td>
            @endif
          @endforeach
        @endif
        @endforeach
        <td class="tablaInicio" style="background-color: white" width="20px">999</td>
      </tr>
      @endforeach
    </table>
    <br>
      <?php $contador++; ?>
      @if ($contador%3 == 0)
      <div style="page-break-after:always;"></div>
      @endif
    @endforeach

    <br>
    <div class="primerEncabezado" style="padding-left: 720px;"><p style="width: 250px; padding-left: 60px;">Firma y aclaración/s responsable/s.</p></div>
  </body>

</html>
