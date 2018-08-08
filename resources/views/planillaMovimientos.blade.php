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

    <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  </head>
  <body>

    @foreach ($relevamientos as $relevamiento)
    @if ($relevamiento->nro > 0)
      <div style="page-break-after:always;"></div>
      @endif
        <div class="encabezadoImg">
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>PMTM 01-02 | Control de movimientos de MTMs.</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <table><tr><th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">TIPO DE MOVIMIENTO</th>
              </tr>
              <td class="tablaInicio" style="background-color: #dddddd; border-color: gray;">{{$relevamiento->tipo_movimiento}}</td>
              </table>
              <br>
              <table>
                <tr><th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">FECHA Y HORA TOMA</th>
                </tr>
                <td class="tablaInicio" style="border-color: gray;" >@if($relevamiento->fecha_relev_sala != null) {{$relevamiento->fecha_relev_sala}} @else ____/____/____, ____:____ @endif</td>

              </table>
              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° ADMIN</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->nro_admin}}</td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° ISLA</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->nro_isla}}</td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">N° SERIE</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;"> {{$relevamiento->nro_serie}}</td>
                </tr>

              </table>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">MARCA</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->marca}}</td>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">MODELO</th>
                  <td class="tablaInicio" style="padding-left: 36px; background-color: #dddddd; border-color: gray;">{{$relevamiento->modelo}}</td>
                </tr>

              </table>
              <br>
              <table>
                <!-- Tabla COIN IN -->
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">CONTADORES</th>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">@if ($relevamiento->toma) TOMA EGRESO @else TOMA 1 @endif</th>

                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">@if ($relevamiento->toma) TOMA REINGRESO @else TOMA 2 @endif</th>
                </tr>
                <tr class="filaContadores">

                  <th class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->nom_cont1 != null) {{$relevamiento->nom_cont1}} @endif</th>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont1!=null){{$relevamiento->toma1_cont1}} @endif @else  @if($relevamiento->toma2_cont1!=null){{$relevamiento->toma2_cont1}} @endif @endif </td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont1!=null){{$relevamiento->toma2_cont1}} @endif @endif</td>
                </tr>
                <!-- FIN TABLA COIN IN -->
                <!-- TABLA COIN OUT -->
                <tr class="filaContadores " >

                  <th class="tablaInicio" style="border-color: gray;" >@if($relevamiento->nom_cont2 != null) {{$relevamiento->nom_cont2}} @endif</th>
                  <td class="tablaInicio" style="border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont2!=null){{$relevamiento->toma1_cont2}} @endif @else @if($relevamiento->toma2_cont2!=null){{$relevamiento->toma2_cont2}} @endif @endif</td>
                  <td class="tablaInicio" style="border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont2!=null){{$relevamiento->toma2_cont2}} @endif @endif</td>
                </tr>
                <!-- TABLA ANEXA COIN OUT -->
                <tr class="filaContadores">
                  <th class="tablaInicio" style="background-color: #fff;  border-color: gray;">@if($relevamiento->nom_cont3 != null) {{$relevamiento->nom_cont3}} @endif</th>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont3!=null){{$relevamiento->toma1_cont3}} @endif @else @if($relevamiento->toma2_cont3!=null){{$relevamiento->toma2_cont3}} @endif @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont3!=null){{$relevamiento->toma2_cont3}} @endif @endif</td>
                </tr>
                <!-- FIN TABLA ANEXA COIN OUT-->
                <!-- FIN TABLA COIN OUT -->
                <!-- TABLA JACKPOT -->
                <tr class="filaContadores">
                  <th class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->nom_cont4 != null) {{$relevamiento->nom_cont4}} @endif</th>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont4!=null){{$relevamiento->toma1_cont4}} @endif @else @if($relevamiento->toma2_cont4!=null){{$relevamiento->toma2_cont4}} @endif @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont4!=null){{$relevamiento->toma2_cont4}} @endif @endif</td>
                </tr>
                <!-- TABLA ANEXA JACKPOTS -->
                <tr class="filaContadores">
                  <th class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->nom_cont5 != null) {{$relevamiento->nom_cont5}} @endif</th>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont5!=null){{$relevamiento->toma1_cont5}} @endif @else @if($relevamiento->toma2_cont5!=null){{$relevamiento->toma2_cont5}} @endif @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont5!=null){{$relevamiento->toma2_cont5}} @endif @endif</td>
                </tr>
                <tr class="filaContadores">
                  <th class="tablaInicio" style="background-color: #fff; border-color: gray;">@if($relevamiento->nom_cont6 != null) {{$relevamiento->nom_cont6}} @endif</th>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma1_cont6!=null){{$relevamiento->toma1_cont6}} @endif @else @if($relevamiento->toma2_cont6!=null){{$relevamiento->toma2_cont6}} @endif @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) @if($relevamiento->toma2_cont6!=null){{$relevamiento->toma2_cont6}} @endif @endif</td>
                </tr>
              </table>
              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="border-right: 0px; background-color: #dddddd; border-color: gray;">@if ($relevamiento->toma) TOMA EGRESO @else TOMA 1 @endif</th>
                  <th class="tablaInicio" style="border-left: 0px; background-color: #dddddd; border-color: gray;"></th>
                  <th class="tablaInicio" style="border-right: 0px; background-color: #dddddd; border-color: gray;">@if ($relevamiento->toma) TOMA REINGRESO @else TOMA 2 @endif</th>
                  <th class="tablaInicio" style="border-left: 0px; background-color: #dddddd; border-color: gray;"></th>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>JUEGO</b></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_juego}}  @else {{$relevamiento->toma2_juego}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>JUEGO</b></td>
                  <td class="tablaInicio" style="background-color: #fff; padding-right: 150px; border-color: gray;">{{$relevamiento->toma2_juego}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>APUESTA MÁX</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_apuesta_max}} @else {{$relevamiento->toma2_apuesta_max}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>APUESTA MÁX</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_apuesta_max}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT LÍNEAS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_cant_lineas}} @else {{$relevamiento->toma2_cant_lineas}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT LÍNEAS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_cant_lineas}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>% DEVOLUCIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_porcentaje_devolucion}} @else {{$relevamiento->toma2_porcentaje_devolucion}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>% DEVOLUCIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_porcentaje_devolucion}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>DENOMINACIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_denominacion}} @else {{$relevamiento->toma2_denominacion}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>DENOMINACIÓN</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_denominacion}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT CRÉDITOS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_cant_creditos}} @else {{$relevamiento->toma2_cant_creditos}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>CANT CRÉDITOS</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_cant_creditos}}</td>
                </tr>
                <tr>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>MAC</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">@if ($relevamiento->toma) {{$relevamiento->toma1_mac}} @else {{$relevamiento->toma2_mac}} @endif</td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;"><b>MAC</b></td>
                  <td class="tablaInicio" style="background-color: #fff; border-color: gray;">{{$relevamiento->toma2_mac}}</td>
                </tr>
              </table>

              <br><br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd; border-color: gray;">OBSERVACIONES GENERALES</th>
                </tr>
                <tr>
                    @if($relevamiento->toma2_observ != null)
                  <td class="tablaInicio" style="height:auto; background-color: #fff; border-color: gray;">@if ($relevamiento->toma2_observ != null) {{$relevamiento->toma2_observ}} @else {{$relevamiento->toma1_observ}} @endif</td>
                  @else
                    <td class="tablaInicio" style="background-color: #fff; border-color: gray;">
                    <div style="color: #dddddd;">
                    @for($i = 0; $i<1200; $i++)
                    .
                    @endfor
                    </p>
                    </div>
                  </td>
                    @endif
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
              @endforeach
      </body>
</html>
