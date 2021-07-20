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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>


        <div class="encabezadoImg">
              <img src="img/logos/banner_nuevo2_landscape.png" width="900">
              <h2><span>MTM | Informe de beneficios ({{$sum->tipoMoneda}})</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></span></div>

              <div class="primerEncabezado">Se han realizado los procedimientos de control correspondientes
              al mes de <b>{{$sum->mes}}</b> del <b>Casino de {{$sum->casino}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>Máquinas Tragamonedas</b>
              se obtuvo un beneficio de <b>${{$sum->totalBeneficio}}</b>, detallando a continuación el producido diario.</div>

              <br>

              <table>
                <tr>
                  <th class="tablaInicio">FECHA</th>
                  <th class="tablaInicio">APOSTADO</th>
                  <th class="tablaInicio">PREMIOS</th>
                  @if ($sum->casino != 'Rosario')
                  <th class="tablaInicio">P.MAYORES</th>
                  @endif
                  <th class="tablaInicio">BENEFICIO</th>
                </tr>
                @foreach ($beneficios as $ajuste)
                <tr>
                  <td class="tablaCampos">{{$ajuste->fecha}}</td>
                  <td class="tablaCampos">{{$ajuste->apostado}}</td>
                  <td class="tablaCampos">{{$ajuste->premios}}</td>
                  @if ($sum->casino != 'Rosario')
                  <td class="tablaCampos">{{$ajuste->pmayores}}</td>
                  @endif
                  <td class="tablaCampos">{{$ajuste->beneficio}}</td>
                </tr>
               @endforeach
              </table>

              <br><br>

              <table>
                <tr>
                  <th class="tablaInicio">APOSTADO</th>
                  <th class="tablaInicio">PREMIOS</th>
                  @if ($sum->casino != 'Rosario')
                  <th class="tablaInicio">P.MAYORES</th>
                  @endif
                  <th class="tablaInicio">BENEFICIO</th>
                </tr>
                <tr>
                  <td class="tablaCampos">{{$sum->totalApostado}}</td>
                  <td class="tablaCampos">{{$sum->totalPremios}}</td>
                  @if ($sum->casino != 'Rosario')
                  <td class="tablaCampos">{{$sum->totalPmayores}}</td>
                  @endif
                  <td class="tablaCampos">{{$sum->totalBeneficio}}</td>
                </tr>
              </table>
  </body>
</html>
