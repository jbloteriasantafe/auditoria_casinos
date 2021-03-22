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
              <img src="img/logos/banner_nuevo2_landscape.png" width="900">
              <h2><span>RBIN01 | Informe de beneficios BINGO</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"><span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></span></div>

              <div class="primerEncabezado">Se han realizado los procedimientos de control correspondientes
              al mes de <b>{{ $mes }}</b> del <b>Casino de {{$casino}}</b>.<br>Teniendo en cuenta lo anterior, se informa que para <b>BINGOS</b>
              se obtuvo un beneficio de <b>${{ $beneficio }}</b>, detallando a continuación el producido diario.</div>

              <br>

              <table>
                <tr>
                  <th class="tablaInicio">FECHA</th>
                  <th class="tablaInicio">RECAUDADO</th>
                  <th class="tablaInicio">PREMIO LÍNEA</th>
                  <th class="tablaInicio">PREMIO BINGO</th>
                  <th class="tablaInicio">BENEFICIO</th>
                </tr>
                @foreach ($resultado_importaciones as $importacion)
                <tr>
                  <td class="tablaCampos">{{$importacion->fecha}}</td>
                  <td class="tablaCampos">{{$importacion->recaudado}}</td>
                  <td class="tablaCampos">{{$importacion->premio_linea}}</td>
                  <td class="tablaCampos">{{$importacion->premio_bingo}}</td>
                  <td class="tablaCampos">{{ ($importacion->recaudado - ($importacion->premio_linea + $importacion->premio_bingo))}}</td>
                </tr>
               @endforeach
              </table>

              <br><br>

              <table>
                <tr>
                  <th class="tablaInicio">RECAUDADO</th>
                  <th class="tablaInicio">PREMIO LÍNEA</th>
                  <th class="tablaInicio">PREMIO BINGO</th>
                  <th class="tablaInicio">BENEFICIO</th>
                </tr>
                <tr>
                  <td class="tablaCampos">{{$sumarecaudado}}</td>
                  <td class="tablaCampos">{{$sumapremiolinea}}</td>
                  <td class="tablaCampos">{{$sumapremiobingo}}</td>
                  <td class="tablaCampos">{{$beneficio}}</td>
                </tr>
              </table>
              <div class="primerEncabezado" style="padding-top: 20px  ">
                  {{ $valor }}
              </div>
  </body>
</html>
