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
              <h2><span>MTM | Informe de beneficios ({{$sum->tipoMoneda}})</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></span></div>

              <div class="primerEncabezado">Se han realizado los procedimientos de control correspondientes
              al mes de <b>{{$sum->mes}}</b> del <b>Casino de {{$sum->casino}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Máquinas Tragamonedas</b>
              se obtuvo un beneficio de <b>US$ {{$sum->totalBeneficioDolares}}</b>, detallando a continuación el producido diario.</div>

              <br>

              <table>
                <tr>
                  <th class="tablaInicio">FECHA</th>
                  <th class="tablaInicio">MTM</th>
                  <th class="tablaInicio">APOSTADO</th>
                  <th class="tablaInicio">PREMIOS</th>
                  <th class="tablaInicio">BENEFICIO (US$)</th>
                  <th class="tablaInicio">COTIZACIÓN (*)</th>
                  <th class="tablaInicio">BENEFICIO ($)</th>
                </tr>
                @foreach ($ajustes as $ajuste)
                <tr>
                  <td class="tablaCampos">{{$ajuste->fecha}}</td>
                  <td class="tablaCampos">{{$ajuste->maq}}</td>
                  <td class="tablaCampos">{{$ajuste->apostado}}</td>
                  <td class="tablaCampos">{{$ajuste->premios}}</td>
                  <td class="tablaCampos">{{$ajuste->beneficioDolares}}</td>
                  <td class="tablaCampos">{{$ajuste->cotizacion}}</td>
                  <td class="tablaCampos">{{$ajuste->beneficioPesos}}</td>
                </tr>
               @endforeach
              </table>

              <br><br>

              <table>
                <tr>
                  <th class="tablaInicio">APOSTADO</th>
                  <th class="tablaInicio">PREMIOS</th>
                  <th class="tablaInicio">BENEFICIO (US$)</th>
                  <th class="tablaInicio">BENEFICIO ($)</th>
                </tr>
                <tr>
                  <td class="tablaCampos">{{$sum->totalApostado}}</td>
                  <td class="tablaCampos">{{$sum->totalPremios}}</td>
                  <td class="tablaCampos">{{$sum->totalBeneficioDolares}}</td>
                  <td class="tablaCampos">{{$sum->totalBeneficioPesos}}</td>
                </tr>
              </table>

              <div>
                <p > 
                  <FONT SIZE=1> <strong>* </strong>Cotización establecida por la Dirección General de Casinos y Bingos (Nota N° 277/16) <br>
                  <i> "... se utilizará como tipo de cambio para efectuar la conversión a pesos, el valor del dólar 
                    oficial tipo comprador (información suministrada por el Banco de la Nación Argentina) correspondiente a la fecha de producción
                     de las MTM. Para el caso de los días Sábados, Domingos y Feriados, se utilizará como tipo de cambio, el del último día hábil disponible.."
                    </FONT>
                  </i>
                </p>
              </div>
  </body>
</html>
