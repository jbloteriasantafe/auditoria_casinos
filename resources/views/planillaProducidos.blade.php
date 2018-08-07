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
  padding: 8px;
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
              <h2><span>PMTM09 | Ajuste de producidos diarios por máquina tragamonedas (MTM) en {{$pro->tipo_moneda}}</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></span></div>

              <div class="titulo">
                Fecha de producido: <div class="camposInfo" style="top:88px; right:545px;">{{$pro->fecha_prod}}</div>
              </div>

              <div class="camposInfo" style="top:88px; right:345px; font: bold 12px Helvetica, Sans-Serif;">Casino:</div>
              <div class="camposInfo" style="top:88px; right:300px;">{{$pro->casinoNom}}</div>

              <br><br>

              <table>
                <tr>
                  <th class="tablaInicio">MTM</th>
                  <th class="tablaInicio">PROD. CALC.</th>
                  <th class="tablaInicio">PROD. SIST.</th>
                  <th class="tablaInicio">DIFERENCIAS</th>
                  <th class="tablaInicio">AJUSTES</th>
                </tr>
                @foreach ($ajustes as $ajuste)
                <tr>
                  <td class="tablaCampos">{{$ajuste->maquina}}</td>
                  <td class="tablaCampos">{{$ajuste->calculado}}</td>
                  <td class="tablaCampos">{{$ajuste->sistema}}</td>
                  <td class="tablaCampos">{{$ajuste->dif}}</td>
                  <td class="tablaCampos">{{$ajuste->descripcion}}</td>
                </tr>
                @endforeach
              </table>
  </body>
</html>
