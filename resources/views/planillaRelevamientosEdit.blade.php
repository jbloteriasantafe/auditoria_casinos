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
div.breakNow { page-break-inside:avoid; page-break-after:always; }

.centrado {
  padding-top: 40px 0;
}

#one td {
    border: 1px solid #ff0000;
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
              <img src="img/logos/banner_nuevo_landscape2.png" width="900">
              <h2><span style="color: #9c9c9c;">RMTM04 | Relevamiento diario de contadores de máquinas tragamonedas (MTM)</span></h2>
        </div>
              <div class="camposTab titulo" style="color: #9c9c9c;">CASINO | FECHA P.</div><div class="camposTab titulo" style="right:196px; color: #9c9c9c;">| SECTOR</div>
              <div class="camposInfo"><span>{{$rel->casinoNom}} | </span>
                                      <span>{{$rel->fecha_generacion}} |</span>
                                      </div>
                                      <div class="campoSector"><span style="position: fixed; top:28px;">{{$rel->sector}}</span></div>
              <div class="horaEjecucion">
                    <span class="titulo">HORA DE EJECUCIÓN</span>
                <div class="campo titulo">
                  <span>{{$rel->fecha_ejecucion}}</span>
                </div>
              </div>
                      @php
                      $var = 0;
                      $hoja = 1;
                      $cabecera = '<table>
                                        <tr>
                                          <th class="tablaInicio">MTM</th>
                                          <th class="tablaInicio">ISLA</th>
                                          <th class="tablaInicio">MARCA</th>
                                          <th class="tablaInicio">CONTADOR 1</th>
                                          <th class="tablaInicio">CONTADOR 2</th>
                                          <th class="tablaInicio">CONTADOR 3</th>
                                          <th class="tablaInicio">CONTADOR 4</th>
                                          <th class="tablaInicio">CONTADOR 5</th>
                                          <th class="tablaInicio">CONTADOR 6</th>
                                          <th class="tablaInicio">N.T</th>
                                        </tr>';
                      $cabeceraTable = '</table><div class="breakNow"></div><div class="centrado"><table>
                                        <tr>
                                          <th class="tablaInicio">MTM</th>
                                          <th class="tablaInicio">ISLA</th>
                                          <th class="tablaInicio">MARCA</th>
                                          <th class="tablaInicio">CONTADOR 1</th>
                                          <th class="tablaInicio">CONTADOR 2</th>
                                          <th class="tablaInicio">CONTADOR 3</th>
                                          <th class="tablaInicio">CONTADOR 4</th>
                                          <th class="tablaInicio">CONTADOR 5</th>
                                          <th class="tablaInicio">CONTADOR 6</th>
                                          <th class="tablaInicio">N.T</th>
                                        </tr>';
                      $cabeceraTable2 = '</div></table><div class="breakNow"></div><div class="centrado"><table>
                                        <tr>
                                          <th class="tablaInicio">MTM</th>
                                          <th class="tablaInicio">ISLA</th>
                                          <th class="tablaInicio">MARCA</th>
                                          <th class="tablaInicio">CONTADOR 1</th>
                                          <th class="tablaInicio">CONTADOR 2</th>
                                          <th class="tablaInicio">CONTADOR 3</th>
                                          <th class="tablaInicio">CONTADOR 4</th>
                                          <th class="tablaInicio">CONTADOR 5</th>
                                          <th class="tablaInicio">CONTADOR 6</th>
                                          <th class="tablaInicio">N.T</th>
                                        </tr>';
                      @endphp
                            @foreach ($detalles as $detalle)
                            @if($var == 0 && $hoja == 1)
                            {!! $cabecera !!}
                            @elseif ($var == 0 && $hoja == 2)
                            {!! $cabeceraTable !!}
                            @elseif ($var == 0 && $hoja > 2)
                            {!! $cabeceraTable2 !!}
                            @endif
                            <tr>
                              <td class="tablaCampos" rowspan="2" style="border-left: 2px solid #000; border-top: 2px solid #000; border-bottom: 2px solid #000; border-right: 0px;">{{$detalle->maquina}}</td>
                              <td class="tablaCampos" rowspan="2" style="border-bottom: 2px solid #000; border-top: 2px solid #000; border-right: 0px; border-left: 0px;">{{$detalle->isla}}</td>
                              <td class="tablaCampos" rowspan="2" style="border-top: 2px solid #000; border-bottom: 2px solid #000; border-left: 0px;"><b>{{$detalle->marca}}</b></td>
                              <td class="tablaCampos" style="border-top: 2px solid #000;">@if($detalle->formula->cont1 != null) {{$detalle->formula->cont1}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000;">@if($detalle->formula->cont2 != null) {{$detalle->formula->cont2}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000;">@if($detalle->formula->cont3 != null) {{$detalle->formula->cont3}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000;">@if($detalle->formula->cont4 != null) {{$detalle->formula->cont4}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000;">@if($detalle->formula->cont5 != null) {{$detalle->formula->cont5}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000; border-right: 2px solid #000;">@if($detalle->formula->cont6 != null) {{$detalle->formula->cont6}} @endif</td>
                              <td class="tablaCampos" style="border-top: 2px solid #000; border-right: 2px solid #000; border-bottom: 2px solid #fff; background-color: #fff;"><b></b></td>
                            </tr>
                            <tr>
                              <td class="tablaCampos" style="height: 3%; border-bottom: 2px solid #000; border-right: 2,5px solid #dddddd;">{{$detalle->cont1}}</td>
                              <td class="tablaCampos" style="border-bottom: 2px solid #000; border-right: 2,5px solid #dddddd;">{{$detalle->cont2}}</td>
                              <td class="tablaCampos" style="border-bottom: 2px solid #000; border-right: 2,5px solid #dddddd;">{{$detalle->cont3}}</td>
                              <td class="tablaCampos" style="border-bottom: 2px solid #000; border-right: 2,5px solid #dddddd;">{{$detalle->cont4}}</td>
                              <td class="tablaCampos" style="border-bottom: 2px solid #000; border-right: 2,5px solid #dddddd;">{{$detalle->cont5}}</td>
                              <td class="tablaCampos" style="border-bottom: 2px solid #000; border-right: 2,5px solid #000;">{{$detalle->cont6}}</td>
                              <td class="tablaCampos" style="border-right: 2,5px solid #000; border-bottom: 2px solid #000;"></td>

                            </tr>
                            @php
                            $var = $var +1;
                            if($var == 9 && $hoja == 1){
                              $var = 0;
                              $hoja = $hoja + 1;
                            }
                            else if($var == 10 && $hoja != 1){
                              $var = 0;
                              $hoja = $hoja + 1;
                            }
                            @endphp
                          @endforeach
                        </table>

      </body>
</html>
