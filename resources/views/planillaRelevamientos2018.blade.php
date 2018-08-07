<!DOCTYPE html>

<html>

<style>
/*table {
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
}*/

</style>

  <head>
    <meta charset="utf-8">
    <title></title>

    <!-- <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> -->

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- <link href="css/bootstrap.min.css" rel="stylesheet"> -->

    <!-- <link href="css/estiloPlanillaPortrait.css" rel="stylesheet"> -->

    <link rel="stylesheet" href="css/estiloPlanillaRelevamiento.css">

  </head>
  <body>

        <!-- Encabezado para todas las páginas -->
        <div class="encabezado">
            <!-- Logo de la Lotería -->
            <img src="img/logos/banner_relevamiento_contadores.png" width="1090">

            <!-- Título de la planilla -->
            <div class="tituloPlanilla">
                <h3>PMTM04</h3>
                <h4>| Relevamiento diario de contadores de máquinas tragamonedas (MTM)</h4>
            </div>

            <!-- Casino y sector -->
            <div class="infoCasinoSector">
                <span class="casino">{{$rel->casinoNom}}</span>
                <span>-</span>
                <span class="sector">{{$rel->sector}}</span>
            </div>

            <!-- Fecha y hora -->
            <div class="infoFecha">
                <span>{{$rel->fecha}}</span>
            </div>

            @if($rel->fecha_ejecucion != null)
            <div class="infoHora">
                <span> {{$rel->fecha_ejecucion}}</span>
            </div>
            @endif
        </div>


                  @php
                    $maquina = 1;
                    $hoja = 1;

                    $tabla1 = '<table style="margin-top:10px;">
                                      <tr>
                                        <th class="contador">CONTADOR 1</th>
                                        <th class="contador">CONTADOR 2</th>
                                        <th class="contador">CONTADOR 3</th>
                                        <th class="contador">CONTADOR 4</th>
                                        <th class="contador">CONTADOR 5</th>
                                        <th class="contador borde_negro">CONTADOR 6</th>
                                      </tr>';

                    $tabla2 = '
                               </table>
                               <div class="referencias">';
                                 foreach($rel->causas_no_toma as $causa_no_toma){
                                   $tabla2 = $tabla2.'<span class="codigoReferencia">' . $causa_no_toma->codigo . '</span><span class="descripcionReferencia"> : '. $causa_no_toma->descripcion . '</span> ';
                                 }



                    $tabla2 = $tabla2.'</div>
                    <table style="margin-top:10px; page-break-before: always;">
                                      <tr>
                                        <th class="contador">CONTADOR 1</th>
                                        <th class="contador">CONTADOR 2</th>
                                        <th class="contador">CONTADOR 3</th>
                                        <th class="contador">CONTADOR 4</th>
                                        <th class="contador">CONTADOR 5</th>
                                        <th class="contador borde_negro">CONTADOR 6</th>
                                      </tr>';

                  @endphp

                  @foreach ($detalles as $detalle)
                      @if ($maquina == 1 && $hoja == 1)
                          {!! $tabla1 !!}
                      @elseif ($maquina == 1 && $hoja > 1)
                          {!! $tabla2 !!}
                      @endif

                    <!-- una maquina -->
                      <tr class="infoMaquina">
                        <td class="mtm">
                            <span class="titulo">MTM</span>
                            <span class="nro_admin">{{$detalle->maquina}}</span>
                        </td>
                        <td class="isla">
                            <span class="titulo">ISLA</span>
                            <span class="nro_isla">{{$detalle->isla}}</span>
                        </td>
                        <td class="marca" colspan="2">
                            <span class="titulo">MARCA</span>
                            <span class="nombre_juego">{{$detalle->marca}}</span>
                        </td>
                        <td class="unidad">
                            <span class="titulo">UNIDAD</span>
                            <span class="unidad_medida">{{$detalle->unidad_medida}}</span>
                        </td>
                        <td class="no_toma borde_negro">
                            <span class="titulo">NO SE RELEVÓ POR </span>
                            <span class="unidad_medida">@if($detalle->no_toma != null) {{$detalle->no_toma}} @endif</span>
                        </td>
                      </tr>
                      <tr class="detalleContadores">
                        <!-- <td>Un detalle de contador que tiene seguramente dos lineas en el campo</td> -->
                        <td>@if($detalle->formula->cont1 != null) {{$detalle->formula->cont1}} @endif</td>
                        <td>@if($detalle->formula->cont2 != null) {{$detalle->formula->cont2}} @endif</td>
                        <td>@if($detalle->formula->cont3 != null) {{$detalle->formula->cont3}} @endif</td>
                        <td>@if($detalle->formula->cont4 != null) {{$detalle->formula->cont4}} @endif</td>
                        <td>@if($detalle->formula->cont5 != null) {{$detalle->formula->cont5}} @endif</td>
                        <td class="borde_negro">@if($detalle->formula->cont6 != null) {{$detalle->formula->cont6}} @endif</td>
                        <!-- <td rowspan="2"></td> -->
                      </tr>

                      @if($detalle->no_toma != null)
                      <tr class="camposContadores sin_toma">
                      @else
                      <tr class="camposContadores">
                      @endif

                        <td class="campoContador">{{$detalle->cont1}}</td>
                        <td class="campoContador">{{$detalle->cont2}}</td>
                        <td class="campoContador">{{$detalle->cont3}}</td>
                        <td class="campoContador">{{$detalle->cont4}}</td>
                        <td class="campoContador">{{$detalle->cont5}}</td>
                        <td class="borde_negro">{{$detalle->cont6}}</td>
                      </tr>

                      @php
                        $maquina = $maquina + 1;

                        if($hoja == 1 && $maquina == 9) {
                          $hoja = $hoja + 1;
                          $maquina = 1;
                        }
                        else if ($hoja > 1 && $maquina == 10){
                          $hoja = $hoja + 1;
                          $maquina = 1;
                        }
                      @endphp

                  @endforeach

                  </table>
                  <div class="referencias">
                    @foreach($rel->causas_no_toma as $causa_no_toma)
                      <span class="codigoReferencia"> {{ $causa_no_toma->codigo}} </span><span class="descripcionReferencia"> : {{ $causa_no_toma->descripcion}} </span>
                    @endforeach
                  </div>

  </body>
</html>
