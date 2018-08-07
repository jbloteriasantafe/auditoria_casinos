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
              <h2><span>RMTM08 | Control de Layout Parcial de sala (MTM)</span></h2>
        </div>
              <div class="camposTab titulo" style="right:-15px;">FECHA PLANILLA</div>
              <div class="camposInfo" style="right:0px;"></span><?php $hoy = date('j-m-y / h:i');
                    print_r($hoy); ?></div>
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
                  <td class="tablaCampos">{{$det->isla}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"></td>
                  <td class="tablaCampos">{{$det->marca}}</td>
                  <td class="tablaCampos" style="background-color: #fff;"></td>
                  <td class="tablaCampos">{{$det->juego->nombre_juego}}</td>
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
                $nro_admin = $det->nro_admin;
                $isla = $det->isla;
                $fabricante = $det->marca;
                $juego = $det->juego->nombre_juego;
                $nro_serie = $det->nro_serie;
                $den_sala = $det->denominacion;
                foreach ($det->diferencias as $diferencia) {
                  switch ($diferencia->columna) {
                    case 'nro_admin':
                      $nro_admin = $diferencia->valor . ' *';
                      break;
                    case 'nombre_juego':
                      $juego = $diferencia->valor . ' *';
                      break;
                    case 'marca':
                      $fabricante = $diferencia->valor . ' *';
                      break;
                    case 'nro_isla':
                      $isla = $diferencia->valor . ' *';
                      break;
                    case 'nro_serie':
                      $nro_serie = $diferencia->valor . ' *';
                      break;
                    default:
                      # code...
                      break;
                  }
                }
              ?>

              <tr>
                <td class="tablaCampos" style="padding: 11px; background-color: #fff;">{{$nro_admin}} </td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$isla}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$fabricante}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$juego}}</td>
                <td class="tablaCampos" style="background-color: #fff;"> {{$nro_serie}}</td><!-- -->
                <td class="tablaCampos" style="background-color: #fff;"> {{$den_sala}}</td> <!-- den sala -->
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

              <br><br><br><br><br><br><br><br>
              <!-- Si la planilla fue relevada -->
              <div class="primerEncabezado"><p style="width: 250px;">Firma y Aclaración/s Responsable/s.</p></div>

          
  </body>
</html>
