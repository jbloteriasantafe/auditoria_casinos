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
              <h2><span>PMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>

                    <table>
                      <tr>
                        <th class="tablaInicio">ISLA</th>
                        <th class="tablaInicio">PROGRESIVO</th>
                        <th class="tablaInicio">NIVEL</th>
                        <th class="tablaInicio">BASE</th>
                        <th class="tablaInicio">ACTUAL</th>
                      </tr>
                      @foreach($detalles as $detalle)
                      <tr>
                        <td class="tablaCampos" style="padding: 11px;">{{$detalle->nro_isla}} </td>
                        <td class="tablaCampos" style="padding: 11px;">{{$detalle->nombre_progresivo}} </td>
                        <td class="tablaCampos" style="padding: 11px;">{{$detalle->nombre_nivel}} </td>
                        <td class="tablaCampos" style="padding: 11px;">{{$detalle->base}}</td>
                        <td class="tablaCampos" style="padding: 11px; background-color: #fff;"> </td>
                      </tr>
                      @endforeach
                      <br><br>
                    </table>
                    <br><div class="primerEncabezado">Observaciones generales del proceso:</div><br>
                    <div style="color: #9c9c9c; ">
                    @for($i = 0; $i<750; $i++)
                    .
                    @endfor
                  </div><br><br>
                    <br><br><br><br><br>
                    <!-- Si la planilla fue relevada -->
                    <div class="primerEncabezado" style="padding-left: 440px;"><p style="width: 250px; padding-left: 60px;">Firma y Aclaración/s Responsable/s.</p></div>



  </body>
</html>
