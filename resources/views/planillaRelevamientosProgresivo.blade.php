<!DOCTYPE html>

<?php
use App\Usuario;
use App\EstadoRelevamiento;
?>

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
              <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
              <h2><span>RMTM06 | Procedimiento de Control de valores de Pozos Progresivos de MTM</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
                    <!-- Momentaneamente esta vista va a lanzar un error si se intenta generar una planilla que no tenga detallles de relevamiento progresivo, porque solo hay una cargada en bdd-->

                    <table>
                      <tr>
                        <th class="tablaInicio" style="background-color: #dddddd">NÚMERO DE RELEVAMIENTO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FECHA PRODUCCIÓN</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FECHA AUDITORÍA</th>
                        <th class="tablaInicio" style="background-color: #dddddd">FISCALIZADOR</th>
                        <th class="tablaInicio" style="background-color: #dddddd">ESTADO</th>
                      </tr>

                      <tr>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->nro_relevamiento_progresivo}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->fecha_generacion}}</td>
                        <td class="tablaInicio" style="background-color: white">{{$relevamiento_progresivo->fecha_ejecucion}}</td>
                        <td class="tablaInicio" style="background-color: white"><?php if ($relevamiento_progresivo->id_usuario_fiscalizador != NULL) print_r(Usuario::find($relevamiento_progresivo->id_usuario_fiscalizador)->nombre); ?></td>
                        <td class="tablaInicio" style="background-color: white"><?php print_r(EstadoRelevamiento::find($relevamiento_progresivo->id_estado_relevamiento)->descripcion) ?></td>
                      </tr>
                    </table>
                    <br>

                    <table>
                      <tr>
                        <th class="tablaInicio" style="background-color: #dddddd">ISLA/S</th>
                        <th class="tablaInicio" style="background-color: #dddddd">MÁQ./S</th>
                        <th class="tablaInicio" style="background-color: #dddddd">PROGRESIVO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">POZO</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 1</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 2</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 3</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 4</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 5</th>
                        <th class="tablaInicio" style="background-color: #dddddd">NIVEL 6</th>
                        <th class="tablaInicio" style="background-color: #dddddd">CAUSA NO TOMA</th>
                      </tr>

                      @foreach ($detalles as $detalle)
                      <tr>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['nro_islas']}} </td>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['nro_maquinas']}} </td>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['progresivo']}} </td>
                        <td class="tablaInicio" style="background-color: white">{{$detalle['pozo']}} </td>
                        @if ($detalle['causa_no_toma_progresivo'] != -1)
                          @for ($i=0; $i<6; $i++)
                            <td class="tablaInicio" style="background-color: white"> - </td>
                          @endfor
                        @else
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel1'] != 0.00) {{$detalle['nivel1']}} @endif </td>
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel2'] != 0.00) {{$detalle['nivel2']}} @endif </td>
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel3'] != 0.00) {{$detalle['nivel3']}} @endif </td>
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel4'] != 0.00) {{$detalle['nivel4']}} @endif </td>
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel5'] != 0.00) {{$detalle['nivel5']}} @endif </td>
                          <td class="tablaInicio" style="background-color: white"> @if($detalle['nivel6'] != 0.00) {{$detalle['nivel6']}} @endif </td>
                        @endif
                        <td class="tablaInicio" style="background-color: white"> @if($detalle['causa_no_toma_progresivo'] != -1) {{$detalle['causa_no_toma_progresivo']}} @endif </td> </td>
                      </tr>
                      @endforeach

                    </table>
                    <br><br>

                    <div class="primerEncabezado">Observaciones generales del proceso:</div><br>
                    <div style="color: #9c9c9c; ">
                    @for($i = 0; $i<552; $i++)
                    .
                    @endfor
                  </div><br><br>
                    <br><br><br><br><br>
                    <!-- Si la planilla fue relevada -->
                    <div class="primerEncabezado" style="padding-left: 440px;"><p style="width: 250px; padding-left: 60px;">Firma y aclaración/s responsable/s.</p></div>
  </body>
</html>
