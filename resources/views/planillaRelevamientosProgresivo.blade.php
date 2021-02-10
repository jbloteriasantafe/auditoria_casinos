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
    padding: 3px;
  }

  p {
    border-top: 1px solid #000;
  }

  .cell_fg{
    position:absolute; 
    width:100%; 
    height:100%; 
    z-index:1;
  }

  .cell_bg_1{
    position:relative; 
    z-index:0; 
    color: rgb(120,120,120);
    font-size: 50%;
    top: 10px;
    text-align: right;
  }
  .cell_bg_2{
    position:absolute; 
    width:100%; 
    height:100%; 
    z-index:0; 
    color: rgb(180,180,180);
    text-align:right;
    font-size: 70%;
  }
  .short_break{
    word-wrap: break-word;
    width: 70px;
    max-width: 70px;
  }
  .medium_break{
    word-wrap: break-word;
    width: 105px;
    max-width: 105px;
  }
  .long_break{
    word-wrap: break-word;
    width: 140px;
    max-width: 140px;
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
        <img src="img/logos/banner_nuevo_landscape2.png" width="900">
        <h2><span>RMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
    </div>

    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>

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
    <table style="table-layout:fixed">
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #dddddd" width="7%">ISLA/S</th>
          <th class="tablaInicio" style="background-color: #dddddd" width="10.5%">PROGRESIVO</th>
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
          <th class="tablaInicio" style="background-color: #dddddd">NIVEL {{$i}}</th>
          @endfor
          <th class="tablaInicio" style="background-color: #dddddd;" width="10.5%">CAUSA NO TOMA</th>
        </tr>
      </thead>

      @foreach ($detalles_linkeados as $detalle)
      <tr>
        <td class="tablaProgresivos" style="background-color: white;"><div class="break">{{$detalle['nro_islas']}}</div></td>
        @if ($detalle['pozo_unico'])
        <td class="tablaProgresivos" style="background-color: white;"><div class="break">{{$detalle['progresivo']}}</div></td>
        @else
        <td class="tablaProgresivos" style="background-color: white;"><div class="break">{{$detalle['progresivo']}} ( {{$detalle['pozo']}} )</div></td>
        @endif

        @if ($detalle['causa_no_toma_progresivo'] != -1)
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
        <td class="tablaProgresivos" style="background-color: white"> - </td>
          @endfor
        <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['causa_no_toma_progresivo']}}</div></td>
        @else
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
            @if (strlen($detalle['nombre_nivel' . $i])>0)
        <td class="tablaProgresivos" style="background-color: white">
          <div class="cell_fg break">
          {{$detalle['nivel' . $i]}}
          </div>
          <div class="cell_bg_1 break">
          {{$detalle['nombre_nivel' . $i]}}
          </div>
        </td>
            @else
        <td class="tablaProgresivos" style="background-color: #f5f5f5">
          <div class="cell_bg_2">
           </div>
        </td>
          @endif
        @endfor
        <td class="tablaProgresivos" style="background-color: white"></td>
        @endif
      </tr>
      @endforeach
    </table>
    <br><br>
    @endif

    <!-- Tabla de progresivos individuales -->
    @if (count($detalles_individuales) > 0)
    <div style="page-break-after:always;"></div>
    <div class="encabezadoImg">
      <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
      <h2><span>RMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
    </div>
    <div class="camposTab titulo" style="right:250px;">FECHA PLANILLA</div>
    <div class="camposInfo" style="right:261px;"></span><?php print_r(date('j-m-y / h:i')); ?></div>
    <div class="primerEncabezado">Listado de progresivos individuales:</div>
    <table style="table-layout:fixed">
      <thead>
        <tr>
          <th class="tablaInicio" style="background-color: #dddddd" width="7%">ISLA</th>
          <th class="tablaInicio" style="background-color: #dddddd" width="7%">MÁQ.</th>
          <th class="tablaInicio" style="background-color: #dddddd" width="10.5%">PROGRESIVO</th>
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
          <th class="tablaInicio" style="background-color: #dddddd">NIVEL {{$i}}</th>
          @endfor
          <th class="tablaInicio" style="background-color: #dddddd" width="10.5%">CAUSA NO TOMA</th>
        </tr>
      </thead>

      @foreach ($detalles_individuales as $detalle)
      <tr>
        <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['nro_islas']}}</div></td>
        <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['nro_maquinas']}}</div></td>

        @if ($detalle['pozo_unico'])
          <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['progresivo']}}</div></td>
        @else
          <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['progresivo']}} ( {{$detalle['pozo']}} )</div></td>
        @endif

        @if ($detalle['causa_no_toma_progresivo'] != -1)
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
            <td class="tablaProgresivos" style="background-color: white"> - </td>
          @endfor
          <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['causa_no_toma_progresivo']}}</div></td>
        @else
          @for($i=1;$i<=$otros_datos_relevamiento_progresivo['maxlvl'];$i++)
            @if (strlen($detalle['nombre_nivel' . $i])>0)
              <td class="tablaProgresivos" style="background-color: white"><div class="break">{{$detalle['nivel' . $i]}}</div></td>
            @else
              <td class="tablaProgresivos" style="background-color: #f5f5f5"></td>
            @endif
          @endfor
          <td class="tablaProgresivos" style="background-color: white"></td>
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
    <div class="primerEncabezado" style="padding-left: 720px;"><p style="width: 250px; padding-left: 60px;">Firma y aclaración/s responsable/s.</p></div>
  </body>

</html>
