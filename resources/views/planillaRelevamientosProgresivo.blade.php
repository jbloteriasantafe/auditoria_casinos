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

div.breakNow { page-break-inside:avoid; page-break-after:always; }
</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>


        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
        </div>
              <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:261px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>

                    <!-- Tabla de datos del relevamiento de progresivos -->
                    <table>
                      <tr>
                        <th class="tablaInicio" style="background-color: #dddddd">CASINO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">SECTOR</th>
                        <th class="tablaInicio" style="background-color: #dddddd">N° RELEV.</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FECHA PRODUCCIÓN</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FECHA AUDITORÍA</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FISCALIZADOR</th>
                        <th class="tablaInicio" style="background-color: #dddddd">ESTADO</th>
                      </tr>

                      <tr>
                        <td class="tablaInicio" style="background-color: white">{{$otros_datos_relevamiento_progresivo['casino']}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$otros_datos_relevamiento_progresivo['sector']}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->nro_relevamiento_progresivo}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->fecha_generacion}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->fecha_ejecucion}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$otros_datos_relevamiento_progresivo['fiscalizador']}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$otros_datos_relevamiento_progresivo['estado']}}</td>
                      </tr>
                    </table>
                    <br>

                    <!-- Tabla de progresivos linkeados -->
                    @if (count($detalles_linkeados) > 0)
                    <div class="primerEncabezado">Listado de progresivos linkeados:</div>
                    <table>
                      <tr>
                        <th class="tablaInicio" style="background-color: #dddddd">ISLA/S</th>
                        <th class="tablaInicio" style="background-color: #dddddd">PROGRESIVO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 1</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 2</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 3</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 4</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 5</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 6</th>
                        <th class="tablaInicio" style="background-color: #dddddd; width: 105px;">CAUSA NO TOMA</th>
                      </tr>

                      @foreach ($detalles_linkeados as $detalle)
                      <tr>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['nro_islas']}} </td>
                        @if ($detalle['pozo_unico'])
                          <td class="tablaInicio" style="background-color: white">{{$detalle['progresivo']}}</td>
                        @else
                          <td class="tablaInicio" style="background-color: white">{{$detalle['progresivo']}} ( {{$detalle['pozo']}} )</td>
                        @endif

                        @if ($detalle['causa_no_toma_progresivo'] != -1)
                          @for ($i=0; $i<6; $i++)
                            <td class="tablaInicio" style="background-color: white"> - </td>
                          @endfor
                          <td class="tablaInicio" style="background-color: white"> {{$detalle['causa_no_toma_progresivo']}} </td>
                        @else
                          @for ($i=1; $i<7; $i++)
                            @if ($detalle['nivel' . $i] != 0.00)
                              <td class="tablaInicio" style="background-color: white">{{$detalle['nivel' . $i]}} </td>
                            @else
                              <td class="tablaInicio" style="background-color: #f5f5f5"></td>
                            @endif
                          @endfor
                          <td class="tablaInicio" style="background-color: white"></td>
                        @endif
                      </tr>
                      @endforeach
                    </table>
                    <br><br>
                    @endif

                    <!-- Tabla de progresivos individuales -->
                    @if (count($detalles_individuales) > 0)
                    <div class="breakNow"> </div>
                    <div class="primerEncabezado">Listado de progresivos individuales:</div>
                    <table>
                      <tr>
                        <th class="tablaInicio" style="background-color: #dddddd">ISLA/S</th>
                        <th class="tablaInicio" style="background-color: #dddddd">MÁQ./S</th>
                        <th class="tablaInicio" style="background-color: #dddddd">PROGRESIVO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 1</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 2</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 3</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 4</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 5</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 6</th>
                        <th class="tablaInicio" style="background-color: #dddddd; width: 105px;">CAUSA NO TOMA</th>
                      </tr>

                      @foreach ($detalles_individuales as $detalle)
                      <tr>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['nro_islas']}} </td>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['nro_maquinas']}} </td>

                        @if ($detalle['pozo_unico'])
                          <td class="tablaInicio" style="background-color: white">{{$detalle['progresivo']}}</td>
                        @else
                          <td class="tablaInicio" style="background-color: white">{{$detalle['progresivo']}} ( {{$detalle['pozo']}} )</td>
                        @endif

                        @if ($detalle['causa_no_toma_progresivo'] != -1)
                          @for ($i=0; $i<6; $i++)
                            <td class="tablaInicio" style="background-color: white"> - </td>
                          @endfor
                          <td class="tablaInicio" style="background-color: white"> {{$detalle['causa_no_toma_progresivo']}} </td>
                        @else
                          @for ($i=1; $i<7; $i++)
                            @if ($detalle['nivel' . $i] != 0.00)
                              <td class="tablaInicio" style="background-color: white">{{$detalle['nivel' . $i]}} </td>
                            @else
                              <td class="tablaInicio" style="background-color: #f5f5f5"></td>
                            @endif
                          @endfor
                          <td class="tablaInicio" style="background-color: white"></td>
                        @endif
                      </tr>
                      @endforeach
                    </table>
                    <br><br>
                    @endif

                    @if ($relevamiento_progresivo->observacion_carga != NULL)
                      <div class="primerEncabezado">Observaciones de carga:</div>
                      <div style="color: #9c9c9c; ">
                        {{$relevamiento_progresivo->observacion_carga}}
                      </div><br><br>
                    @endif

                    @if ($relevamiento_progresivo->observacion_validacion != NULL)
                      <div class="primerEncabezado">Observaciones de validacion:</div>
                      <div style="color: #9c9c9c; ">
                        {{$relevamiento_progresivo->observacion_validacion}}
                      </div><br><br>
                    @endif

              <br>
              <!-- Si la planilla fue relevada -->
              <div class="primerEncabezado" style="padding-left: 720px;"><p style="width: 250px; padding-left: 60px;">Firma y aclaración/s responsable/s.</p></div>
  </body>
</html>
