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
              <img src="img/logos/banner_2024_landscape.png" width="900">
              <h2><span>RMTM ?? | Formato de evaluación a progresivos. </span></h2>
        </div>
              <div class="camposTab titulo" style="right:18px; top:-18px;">CASINO</div>
              <div class="camposInfo" style="right:22px; top:-3px;">{{$prueba->casino}}</div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">PROGRESIVO</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none white #dddddd;">SECTOR</th>
                  <th class="tablaInicio" style="text-align: center;">{{$prueba->sector}}</th>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd;">ISLA</th>
                  <th class="tablaInicio" style="text-align: center;">{{$prueba->isla}}</th>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none white none;">ADMIN</th>
                  <th class="tablaInicio" style="text-align: center;">{{$prueba->admin}}</th>
                </tr>
              </table>
              <table>
                  <tr>
                    <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none none none;">NOMBRE</th>
                    <th class="tablaInicio" style="text-align: center;">{{$prueba->nombre_progresivo}}</th>
                    <th class="tablaInicio" style="text-align: center; background-color: #dddddd;">TIPO</th>
                    <th class="tablaInicio" style="text-align: center;">{{$prueba->tipo}}</th>
                  </tr>
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">POZO PROGRESIVO</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: none white none #dddddd">NIVEL</th>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none white none none">BASE</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">% APORTE</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">INICIAL</th>
                </tr>
                @foreach($nivel_progresivo as $nivel)
                <tr>
                  <td class="tablaInicio" style="text-align:center; background-color: #fff;">{{$nivel->nombre_nivel}}</td>
                  <td class="tablaInicio" style="text-align:center; background-color: #fff; padding-right: 150px;">{{$nivel->base}}</td>
                  <td class="tablaInicio" style="text-align:center; background-color: #fff; padding-right: 150px;">{{$nivel->porc_visible}}</td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                @endforeach
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 1</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">NIVEL</th>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">APUESTA</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">RELEVADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">CALCULADO</th>
                </tr>
                @foreach($nivel_progresivo as $nivel)
                <tr>
                  <td class="tablaInicio" style="text-align:center;  background-color: #fff;">{{$nivel->nombre_nivel}}</td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                @endforeach
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 2</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">NIVEL</th>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">APUESTA</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">RELEVADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">CALCULADO</th>
                </tr>
                @foreach($nivel_progresivo as $nivel)
                <tr>
                  <td class="tablaInicio" style="text-align:center; background-color: #fff;">{{$nivel->nombre_nivel}}</td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                @endforeach
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 3</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">NIVEL</th>
                  <th class="tablaInicio" style="text-align:center; background-color: #dddddd; border-color: none white none none">APUESTA</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">RELEVADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">CALCULADO</th>
                </tr>
                @foreach($nivel_progresivo as $nivel)
                <tr>
                  <td class="tablaInicio" style="text-align:center; background-color: #fff;">{{$nivel->nombre_nivel}}</td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                @endforeach
              </table>
              <br><br>
              <!-- <div style="page-break-after:always;"></div> -->
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd;">OBSERVACIONES GENERALES</th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff;">
                    <div style="color: #dddddd;">
                    @for($i = 0; $i<750; $i++)
                    .
                    @endfor
                    </p>
                  </div>
                </tr>
              </table>
              <br><br>
              <table>
                <tr>
                  <th class="tablaInicio" style="padding-top: 50px; border-right: 0px;  padding-left: 100px;"></th>
                  <th class="tablaInicio"></th>
                  <th class="tablaInicio"></th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #dddddd;"><center>Técnico</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd;"><center>Fiscalizador en Sala de Juegos</center></td>
                  <td class="tablaInicio" style="background-color: #dddddd;"><center>Administración de Lotería</center></td>
                </tr>
              </table>


      </body>
</html>
