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
              <img src="img/logos/banner_nuevo2_landscape.png" width="900">
              <h2><span>Intervenciones Técnicas</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>

              <table>
                <!-- Tabla COIN IN -->
                <tr>
                    <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">ALCANCE</th>
                    <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;"></th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>SECTOR/ES</b></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 265px; border-color: gray;">@if($rel->sectores != null) {{$rel->sectores}} @endif</td>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr>
                  <td class="tablaInicio" style=" border-color: gray;"><b>ISLA/S</b></td>
                  <td class="tablaInicio" style=" border-color: gray;">@if($rel->islas != null) {{$rel->islas}}@endif</td>
                </tr>
                <!-- TABLA ANEXA COIN OUT -->
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>MÁQUINA/S</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($rel->maquinas != null) {{$rel->maquinas}}@endif</td>
                </tr>
                <!-- FIN TABLA ANEXA COIN OUT-->
                <!-- FIN TABLA COIN OUT -->
                <!-- TABLA JACKPOT -->
                <tr>
                  <td class="tablaInicio" style="background-color: #fff;  padding-top: 16px; border-color: gray;"></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"></td>
                </tr>
                <!-- TABLA ANEXA JACKPOTS -->
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; padding-top: 16px; border-color: gray;"></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"></td>
                </tr>
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">TIPO</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style=" border-color: gray;">FALLA TÉCNICA</th>
                  <th class="tablaInicio" style="padding-left: 8px; border-color: gray;">@if($rel->tipo_ev_falla_tec  != null) {{$rel->tipo_ev_falla_tec }}@endif </th>
                  <th class="tablaInicio" style=" border-color: gray;">AMBIENTAL</th>
                  <th class="tablaInicio" style="padding-left: 8px; border-color: gray;">@if($rel->tipo_ev_ambiental  != null) {{$rel->tipo_ev_ambiental }}@endif</th>
                </tr>
              </table>
              <br>

              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">OBSERVACIONES GENERALES</th>
                </tr>
                <tr>
                  @if($rel->observaciones  != null)
                    <td class="tablaInicio" style="height:auto; background-color: #fff; border-color: gray;">{{$rel->observaciones}}</td>

                  @else
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;">
                    <div style="color: #dddddd;">
                    @for($i = 0; $i<1200; $i++)
                    .
                    @endfor
                    </p>
                    </div>
                    @endif
                </tr>
              </table>
              <br><br>
              <table>
                <tr>
                  <th class="tablaInicio" style="padding-top: 50px; border-right: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-right: 0px; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-left: 0px; border-color: gray;"></th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Personal del Concesionario en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"><center>Fiscalizador en Sala de Juegos</center></td>
                </tr>
              </table>


      </body>
</html>
