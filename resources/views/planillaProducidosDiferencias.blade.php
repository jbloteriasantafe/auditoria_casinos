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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>


        <div class="encabezadoImg">
              <img src="img/logos/banner_2024_landscape.png" width="900">
              <h2><span>RMTM09 | Ajuste de producidos diarios por máquina tragamonedas (MTM) en {{$pro->tipo_moneda}}</span></h2>
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
                  <th class="tablaInicio">PROD. IMP.</th>
                  <th class="tablaInicio">DIFERENCIAS</th>
                  <th class="tablaInicio">AJUSTES</th>
                  <th class="tablaInicio">PROD AJUSTADO</th>
                </tr>
                @foreach ($ajustes as $ajuste)
                <tr>
                  <td class="tablaCampos">{{$ajuste->maquina}}</td>
                  <td class="tablaCampos">{{$ajuste->calculado}}</td>
                  <td class="tablaCampos">{{$ajuste->sistema}}</td>
                  <td class="tablaCampos">{{$ajuste->dif}}</td>
                  <td class="tablaCampos">{{$ajuste->descripcion}}</td>
                  <td class="tablaCampos">{{$ajuste->calculado_operado}}</td>
                </tr>
                @endforeach
              </table>
              @if(!empty($MTMobservaciones))
              <div style="page-break-after:always;"></div>
                <div class="encabezadoImg">
                  <img src="img/logos/banner_2024_landscape.png" width="900">
                  <h2><span>RMTM09 | Ajuste de producidos diarios por máquina tragamonedas (MTM) en {{$pro->tipo_moneda}}</span></h2>
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
                    <th class="tablaInicio">OBSERVACIONES</th>
                  </tr>
                  @foreach ($MTMobservaciones as $mtmObs)
                  <tr>
                    <td class="tablaCampos">{{$mtmObs->maquina}}</td>
                    <td class="tablaCampos">{{$mtmObs->observacion}}</td>
                  </tr>
                  @endforeach
                </table>
              @endif
  </body>
</html>
