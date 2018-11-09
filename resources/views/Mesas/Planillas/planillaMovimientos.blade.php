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
.filaContadores{
  height: 10px !important;
}
footer
{
    margin-top:50px;
    width:200%;
    height:300px;
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

    <link href="css/estiloPlanillaLandscape.css" rel="stylesheet">
  </head>
  <body>


        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM 01-02 | Control de movimientos de MTMs.</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <table><tr><th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">TIPO DE MOVIMIENTO</th>
              </tr>
              <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;"></td>
              </table>
              <br>
              <table>
                <tr><th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">FECHA Y HORA TOMA</th>
                </tr>
                <td class="tablaInicio" style="border-color: gray;" ></td>

              </table>
              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° ADMIN</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"></td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° ISLA</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"></td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° SERIE</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"> </td>
                </tr>

              </table>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">MARCA</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"></td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">MODELO</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"></td>
                </tr>

              </table>
              <br>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">CONTADORES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;"></th>

                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;"></th>
                </tr>
                <tr class="filaContadores">

                  <th class="tablaInicio" style="background-color: #fff; border-color: gray;"></th>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;"></td>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr class="filaContadores " >

                  <th class="tablaInicio" style="border-color: gray;" ></th>
                  <td class="tablaInicio" style="border-color: gray;"></td>
                  <td class="tablaInicio" style="border-color: gray;"></td>
                </tr>


              <br><br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">OBSERVACIONES GENERALES</th>
                </tr>
                <tr>

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
              @
      </body>
</html>
