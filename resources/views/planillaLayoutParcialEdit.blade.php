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
              <img src="img/logos/banner_2024_landscape.png" width="900">
              <h2><span>RMTM08 | Control de Layout Parcial de sala (MTM)</span></h2>
        </div>
              <div class="camposTab titulo" style="top: -15px; right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="top: 0px; right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
              <div class="camposTab titulo" style="top: 15px; right:-30px;">HORA DE EJECUCIÓN</div>
              <div class="camposTab titulo" style="top: 30px; right:-5px;"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; : &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;</div>

              <table>
                <tr>
                  <th class="tablaInicio">SECTOR</th>
                  <th class="tablaInicio">N° ADMIN</th>
                  <th class="tablaInicio">ISLA</th>
                  <th class="tablaInicio"> </th>
                  <th class="tablaInicio">FABRICANTES</th>
                  <th class="tablaInicio"> </th>
                  <th class="tablaInicio">JUEGO</th>
                  <th class="tablaInicio"> </th>
                  <th class="tablaInicio">N° SERIE</th>
                  <th class="tablaInicio">DEN. SALA</th>
                  <th class="tablaInicio">% DEV.</th>
                </tr>
                <?php $total = 0;?>
                @foreach($detalles as $det)
                <?php $total += 1;?>
                <tr>
                  <td class="tablaCampos" style="padding: 9px;">{{$rel->sector}}</td>
                  <td class="tablaCampos">{{$det->nro_admin}}</td>
                  <td class="tablaCampos">{{$det->nro_isla}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"></td>
                  <td class="tablaCampos">{{$det->marca}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"></td>
                  <td class="tablaCampos">{{$det->juego}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"></td>
                  <td class="tablaCampos">{{$det->nro_serie}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"> {{$det->denominacion}} </td>
                  <td class="tablaCampos" style="background-color: #fff;"> {{$det->porcentaje_devolucion}}</td>
                </tr>
                @endforeach
              </table>

              <br><br>

              <div class="primerEncabezado"><b>Máquinas Tragamonedas con diferencias en Datos de Identificación y/o Descripción.</b></div>

              <br>
              <table>
                <tr>
                  <th class="tablaInicio" style="background-color: #dddddd;">N° ADMIN</th>
                  <th class="tablaInicio" style="background-color: #dddddd;">ISLA</th>
                  <th class="tablaInicio" style="background-color: #dddddd;">FABRICANTE</th>
                  <th class="tablaInicio" style="background-color: #dddddd;">JUEGO</th>
                  <th class="tablaInicio" style="background-color: #dddddd;">N° SERIE</th>
                  <th class="tablaInicio" style="background-color: #dddddd;">DEN. SALA</th>
                </tr>
              @foreach($detalles as $det)
              @if($det->diferencias->count() != 0)
              <?php
                $total--;
                $diffs = [
                  'nro_admin' => $det->nro_admin,
                  'nro_isla'  => $det->nro_isla,
                  'marca'     => $det->marca,
                  'juego'     => $det->juego,
                  'nro_serie' => $det->nro_serie,
                  'denominacion' => $det->denominacion,
                ];
                foreach ($det->diferencias as $diff) {
                  if(!array_key_exists($diff->columna,$diffs)) continue;
                  $diffs[$diff->columna] = $diff->valor.' *';
                }
              ?>

              <tr>
                <td class="tablaCampos" style="padding: 11px; background-color: #fff;">{{$diffs['nro_admin']}} </td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$diffs['nro_isla']}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$diffs['marca']}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$diffs['juego']}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$diffs['nro_serie']}}</td><!-- -->
                <td class="tablaCampos" style="background-color: #fff;"> {{$diffs['denominacion']}}</td> <!-- den sala -->
              </tr>
              @endif
              @endforeach
              @for($i = 0; $i < $total; $i++)
                <tr>
                  <td class="tablaCampos" style="padding: 11px; background-color: #fff;"> </td>
                  <td class="tablaCampos" style="background-color: #fff;"> </td>
                  <td class="tablaCampos" style="background-color: #fff;"> </td>
                  <td class="tablaCampos" style="background-color: #fff;"> </td>
                  <td class="tablaCampos" style="background-color: #fff;"> </td>
                  <td class="tablaCampos" style="background-color: #fff;"> </td>
                </tr>
                @endfor
              </table>
              
              @if(!empty($rel->observacion_fiscalizacion) || !empty($rel->observacion_validacion))
              <br>
              <table>
                @if(!empty($rel->observacion_fiscalizacion))
                <tr><th class="tablaInicio">Observacion Fiscalización</th></tr>
                <tr><td class="tablaCampos">{{$rel->observacion_fiscalizacion}}</td></tr>
                @endif
                @if(!empty($rel->observacion_validacion))
                <tr><th class="tablaInicio">Observacion Administracion</th></tr>
                <tr><td class="tablaCampos">{{$rel->observacion_validacion}}</td></tr>
                @endif
              </table>
              <br>
              @endif

              <br><br><br><br><br><br><br><br>
              <!-- Si la planilla fue relevada -->
              <div class="primerEncabezado"><p style="width: 250px;">Firma y Aclaración/s Responsable/s.</p></div>
  </body>
</html>
