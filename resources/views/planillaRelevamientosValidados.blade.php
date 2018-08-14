<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 70%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 3px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
div.breakNow { page-break-inside:avoid; page-break-after:always; }

.centrado {
  padding-top: 40px 0;
}

#one td {
    border: 1px solid #ff0000;
}

<?php $anyo = substr($rel->fecha,0,-6);
      $mes = substr($rel->fecha,4,-2);
      $dia = substr($rel->fecha,-2);
      $fecha_final = $dia.$mes.$anyo;
      ?>

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
              <img src="img/logos/banner_loteria_landscape_medium.png" width="900">
              <h2><span style="color: #9c9c9c; padding-left: 200px;">PMTM04 | Relevamiento diario de contadores de máquinas tragamonedas (MTM)</span></h2>
        </div>
        <div class="primerEncabezado" style="padding-left: 120px;">Datos del relevamiento para la fecha de producción - <b><i><?php echo $fecha_final;?> </i></b></div><br>
                  <div style="padding-left: 230px;">
                    <table>
                            <tr>
                              <th class="tablaInicio">CANTIDAD DE MÁQUINAS RELEVADAS</th>
                              <th class="tablaInicio">CANTIDAD DE MÁQUINAS CON DIFERENCIAS</th>
                              <th class="tablaInicio">MÁQUINAS HABILITADAS</th>
                            </tr>
                            <tr>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_relevadas}}</td>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_con_diferencia}}</td>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_habilitadas}}</td>
                            </tr>
                    </table>
                  </div><br>
        <div class="primerEncabezado" style="padding-left: 120px;">A continuación se detallan diferencias con relación al sistema, en los contadores observados</div><br>
        <div style="padding-left: 230px;">
          <table>
                  <tr>
                    <th class="tablaInicio">N° DE MÁQUINA</th>
                    <th class="tablaInicio">PRODUCIDO CALCULADO OBSERVADO</th>
                    <th class="tablaInicio">PRODUCIDO CALCULADO DEL SISTEMA</th>
                  </tr>
                  @if(isset($rel->detalles))
                    @foreach($rel->detalles as $detalle)
                    <tr>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$detalle->nro_admin}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$detalle->producido_calculado_relevado}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$detalle->producido}}</td>
                    </tr>
                    @endforeach
                  @endif
          </table>
        </div>
        <br><div class="primerEncabezado" style="padding-left: 120px;">Observaciones generales del proceso:</div><br>
        <div style=" padding-left: 130px; width: 65%;">
        @foreach($rel->observaciones as $unaObservacion)
        <div class="primerEncabezado" style="padding-left: 120px;">
          <b>{{$unaObservacion['zona']}} :</b> <i>{{$unaObservacion['observacion']}} </div><br>
        @endforeach
      <!--  @for($i = 0; $i<450; $i++)
        .
        @endfor
      -->
      </div><br><br>
      <div class="primerEncabezado" style="padding-left: 555px;">________________________________________</div><br>
      <div class="primerEncabezado" style="padding-left: 563px;">Firma Responsable</div>
      </body>
</html>
