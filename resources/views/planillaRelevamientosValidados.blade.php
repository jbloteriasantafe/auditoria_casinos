<!DOCTYPE html>

<html>

<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 80%;
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
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span style="color: #9c9c9c; ">PMTM04 | Relevamiento diario de contadores de máquinas tragamonedas (MTM)</span></h2>
        </div>
        <div class="primerEncabezado" style="padding-left: 120px;">Datos del relevamiento para la fecha de producción - <b><i><?php echo $fecha_final;?> </i></b></div><br>
                  <div style="padding-left: 150px;">
                    <table>
                            <tr>
                              <th class="tablaInicio" style="text-align: center;">CANTIDAD DE MÁQUINAS RELEVADAS</th>
                              <th class="tablaInicio" style="text-align: center;">CANTIDAD DE MÁQUINAS CON DIFERENCIAS</th>
                              <th class="tablaInicio" style="text-align: center;">MÁQUINAS HABILITADAS</th>
                            </tr>
                            <tr>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_relevadas}}</td>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_con_diferencia}}</td>
                              <td class="tablaInicio" style="background-color: #fff; text-align: center;">{{$rel->cantidad_habilitadas}}</td>
                            </tr>
                    </table>
                  </div><br>
        <div class="primerEncabezado" style="padding-left: 120px;">A continuación se detallan diferencias con relación al sistema, en los contadores observados</div><br>
        <div >
          <table STYLE="width: 100%;">
                  <tr>
                    <th class="tablaInicio" style="text-align: center;">N° DE MÁQUINA</th>
                    <th class="tablaInicio" style="text-align: center;">SECTOR</th>
                    <th class="tablaInicio" style="text-align: center;">ISLA</th>
                    <th class="tablaInicio" style="text-align: center;">PRODUCIDO CALCULADO OBSERVADO</th>
                    <th class="tablaInicio" style="text-align: center;">PRODUCIDO CALCULADO DEL SISTEMA</th>
                    <th class="tablaInicio" style="text-align: center;">CAUSA DE NO TOMA</th>
                    <th class="tablaInicio" style="text-align: center;">OBSERVACIONES</th>
                  </tr>
                  @if(isset($rel->detalles))
                    @foreach($rel->detalles as $detalle)
                    <tr>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 10%;">{{$detalle->nro_admin}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 10%;">{{$detalle->sector}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 10%;">{{$detalle->isla}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 15%;">{{$detalle->producido_calculado_relevado}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 15%;">{{$detalle->producido}}</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 15%;">@if($detalle->no_toma != null){{$detalle->no_toma}}@endif</td>
                      <td class="tablaInicio" style="background-color: #fff; text-align: center; width: 25%;">{{$detalle->observacion}}</td>
                    </tr>
                    @endforeach
                  @endif
          </table>
        </div>

      </div><br><br><br><br>
        <div class="primerEncabezado" style="padding-left: 450px;">________________________________________</div><br>
        <div class="primerEncabezado" style="padding-left: 520px;">Firma Responsable</div>
      <!--  @for($i = 0; $i<450; $i++)
        .
        @endfor
      -->
      </div><br><br>
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

      </body>
</html>
