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
              <img src="img/logos/banner_2024_portrait.png" width="900">
              <h2><span>RMTM ?? | Formato de evaluación a dispositivo de juego electrónico. </span></h2>
        </div>
              <div class="camposTab titulo" style="right:18px; top:-18px;">CASINO</div>
              <div class="camposInfo" style="right:22px; top:-3px;">{{$rel->casino}}</div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">MÁQUINA</th></tr></table>
              <table>
                <tr>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none white none;">SECTOR</th>
                  <th class="tablaInicio" style="text-align: center;">{{$rel->sector}} </th>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd;">ISLA</th>
                  <th class="tablaInicio" style="text-align: center;">{{$rel->isla}}</th>
                  <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none white none;">ADMIN</th>
                  <th class="tablaInicio" style="text-align: center;">{{$rel->nro_admin}}</th>
                </tr>
              </table>
              <table>
                  <tr>
                    <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none none none;">SERIE</th>
                    <th class="tablaInicio" style="text-align: center;">{{$rel->nro_serie}}</th>
                    <th class="tablaInicio" style="text-align: center; background-color: #dddddd;">MARCA</th>
                    <th class="tablaInicio" style="text-align: center;">{{$rel->marca}}</th>
                    <th class="tablaInicio" style="text-align: center; background-color: #dddddd; border-color: none none none none;">MODELO</th>
                    <th class="tablaInicio" style="text-align: center;">{{$rel->modelo}}</th>
                  </tr>
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">ACUMULADOR DE CONTADORES</th>
                <th class="tablaInicio" style="background-color:#dddddd; text-align:center; border-color: none white none none;">ANTERIOR JUGADA</th>
                <th class="tablaInicio" style="background-color:#dddddd; text-align:center;">POSTERIOR JUGADA</th></tr>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none none white none;">N° TICKETS</th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                </tr>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none none white #dddddd;">N° BILLETES</th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                </tr>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align:center;">N° JUGADOS</th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                  <th class="tablaInicio" style="background-color:#fff;"></th>
                </tr>
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 1</th></tr></table>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none white none none;">DINERO INSERTADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center;">DENOMINACIÓN JUGADA</th>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">$5  $10  $20  $50  $100  Otro: ___________</td>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">0.01 0.02 0.05 0.10 1 Otro: ___________</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="text-align: center; border-color: none none white none;"><b>TICKET INSERTADO</b></td>
                  <td class="tablaInicio" style="text-align: center; background-color:#fff; word-spacing: 15px;">Monto(s): ________ Monto(s): _________</td>
                </tr>
                <!-- TABLA ANEXA JACKPOTS -->
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 2</th></tr></table>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none white none none;">DINERO INSERTADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center;">DENOMINACIÓN JUGADA</th>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">$5  $10  $20  $50  $100  Otro: ___________</td>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">0.01 0.02 0.05 0.10 1 Otro: ___________</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="text-align: center; border-color: none none white none;"><b>TICKET INSERTADO</b></td>
                  <td class="tablaInicio" style="text-align: center; background-color:#fff; word-spacing: 15px;">Monto(s): ________ Monto(s): _________</td>
                </tr>
                <!-- TABLA ANEXA JACKPOTS -->
              </table>
              <br>
              <table><tr><th class="tablaInicio" style="background-color: #000000; color:#fff;">JUGADA 3</th></tr></table>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none white none none;">DINERO INSERTADO</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center;">DENOMINACIÓN JUGADA</th>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">$5  $10  $20  $50  $100  Otro: ___________</td>
                  <td class="tablaInicio" style="text-align: center; word-spacing: 15px;">0.01 0.02 0.05 0.10 1 Otro: ___________</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="text-align: center; border-color: none none white none;"><b>TICKET INSERTADO</b></td>
                  <td class="tablaInicio" style="text-align: center; background-color:#fff; word-spacing: 15px;">Monto(s): ________ Monto(s): _________</td>
                </tr>
                <!-- TABLA ANEXA JACKPOTS -->
              </table>
              <br>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #000; color:#fff; border-color: none none none none">CONTADORES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: none white none none">INICIAL</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">JUGADA 1</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: #dddddd white none none;">JUGADA 2</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center;">JUGADA 3</th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 35px;">{{$rel->formula[0]}}</td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                @foreach($rel->formula as $index => $campo)
                  @if($index < 1)
                  @else
                  <tr>
                    <td class="tablaInicio" style="background-color: #fff;">{{$campo}}</td>
                    <td class="tablaInicio" style="background-color: #fff;"></td>
                    <td class="tablaInicio" style="background-color: #fff;"></td>
                    <td class="tablaInicio" style="background-color: #fff;"></td>
                    <td class="tablaInicio" style="background-color: #fff;"></td>
                  </tr>
                  @endif
                @endforeach

              </table>
              <br>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #000; color:#fff;">TOTALES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none white none none;">CONTADORES INICIALES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; text-align: center;">CONTADORES FINALES</th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #dddddd; text-align: center; border-color: none none white none;"><b>DINERO APOSTADO</b></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px;"></td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #dddddd; text-align: center;"><b>DINERO COBRADO</b></td>
                  <td class="tablaInicio" style="background-color: #fff;"></td>
                  <td class="tablaInicio" style="background-color: #fff;"></td>
                </tr>
              </table>
              <br>

              <div style="page-break-after:always;"></div>
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
