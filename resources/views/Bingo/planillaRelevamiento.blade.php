<html>
<head>
  <title>Planilla de Relevamiento</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  <link href="/css/importacionFuentes.css" el="stylesheet">
</head>
<body>
  <?php
  use App\Http\Controllers\UsuarioController;
  use Illuminate\Http\Request;

  $usuario = UsuarioController::getInstancia()->buscarUsuario(session('id_usuario'))['usuario'];;
  $casinos = $usuario->casinos;
  $correcto = count($casinos);
  $nombre_casino = '';
  if($correcto == 1) {
    foreach ($casinos as $casino) {
      $nombre_casino = $casino->nombre;
    }
  }
  ?>

<div style="">
  <div class="encabezadoImg">
      <img src="img/logos/logo_planilla_sesion_bingo.png" width="100%">
  </div>

      <!-- <img src="img/logos/logo_planilla_sesion_bingo.png" width="100%"> -->
      <div class="camposTab titulo" style="right: 70px; top: 50px!important">FECHA INFORME</div>
      <div class="camposInfo" style="right: 70px; top: 70px!important"></span><?php $hoy = date('j-m-y / h:i');
            print_r($hoy); ?></div>

      <em> Importante: Todos los datos de esta planilla deben ser tomados en Sala de Juegos.</em>
      <table class="table" width="100%" border="1" cellspacing="0">
            <tbody>
         <tr>
           <td style="background-color:#e4e4e4;  width: 20%">Fiscalizador</td>
           <td style="width: 40%"></td>
           <td style="background-color:#e4e4e4;  width: 20%">Casino</td>
           <td style="width: 20%">{{$nombre_casino}}</td>
         </tr>
       </tbody>
      </table >
      <br>
      <strong><em>Datos de la jugada</em></strong><br>
      <em>Importante: Relevar los datos marcados con (*) después de que se finalizó la venta de cartones. </em>
      <table class="table" width="100%" border="1" cellspacing="0">
            <tbody>
         <tr>
           <td style="background-color:#e4e4e4;  width: 24%">Fecha de Sesión</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Número de Partida</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Hora de Jugada</td>
           <td style="width: 10.33%"></td>
         </tr>

         <tr>
           <td style="background-color:#e4e4e4;  width: 24%">Nro. de Serie</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Cartón Inicial</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Cartón Final (*)</td>
           <td style="width: 10.33%"></td>
         </tr>

         <tr>
           <td style="background-color:#e4e4e4;  width: 24%">Nro. de Serie (*)</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Cartón Inicial (*)</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">Cartón Final (*)</td>
           <td style="width: 10.33%"></td>
         </tr>

         <tr>
           <td style="background-color:#e4e4e4;  width: 24%">Valor Cartón</td>
           <td style="width: 10.33%">$</td>
           <td style="background-color:#e4e4e4;  width: 24%">Cartones Vendidos (*)</td>
           <td style="width: 10.33%"></td>
           <td style="background-color:#e4e4e4;  width: 24%">&nbsp;</td>
           <td style="background-color:#e4e4e4;  width: 10.33%">&nbsp;</td>
         </tr>

       </tbody>
      </table>
      <br>

      <strong><em>Montos destinados a premio</em></strong>
      <table class="table" width="100%" border="1" cellspacing="0">
            <tbody>
         <tr>
           <td style="background-color:#e4e4e4;  width: 43%">Monto destinado a premio línea (*)</td>
           <td style="width: 7%">$</td>
           <td style="background-color:#e4e4e4;  width: 43%">Monto destinado a premio bingo (*)</td>
           <td style="width: 7%">$</td>
         </tr>

         <tr>
           <td style="background-color:#e4e4e4;  width: 30%">Maxi línea (*)</td>
           <td style="width: 20%">$</td>
           <td style="background-color:#e4e4e4;  width: 30%">Maxi bingo (*)</td>
           <td style="width: 20%">$</td>
         </tr>

       </tbody>
      </table>

      <br>
      <strong><em>Bola por orden de extraccción</em></strong><br>
      <em>Importante: Remarcar la posición de la bola en la que se otorgó el premio Linea y Bingo.  </em>

      <table class="table" width="100%" border="1" cellspacing="0">

        <tbody>
          {{ $c = 1}}
          @for ($i = 1; $i < 10; $i++)
          <tr>
            @for ($j = 0; $j < 10; $j++)
            <td style="background-color:#e4e4e4; width: 5%">{{ $c++}}</td>
            <td style="width: 5%">&nbsp;</td>
             @endfor
          </tr>
          @endfor
        </tbody>
      </table>

      <br>
      <strong><em>Cartones Ganadores</em></strong>

      <table class="table" width="100%" border="1" cellspacing="0">
        <thead>
          <tr>
            <th style="background-color:#e4e4e4; width: 20%"></th>
            <th align="center" style="background-color:#e4e4e4; width: 20%">Nombre del Premio</th>
            <th style="background-color:#e4e4e4; width: 20%"></th>
            <th align="center" style="background-color:#e4e4e4; width: 20%">Nro. Cartón Ganador</th>
            <th style="background-color:#e4e4e4; width: 20%"></th>
          </tr>
        </thead>
        <tbody>
          @for ($i = 0; $i < 5; $i++)
          <tr>
            <td style="background-color:#e4e4e4; width: 20%"></td>
            <td style="width: 20%">&nbsp;</td>
            <td style="background-color:#e4e4e4; width: 20%"></td>
            <td style="width: 20%"></td>
            <td style="background-color:#e4e4e4; width: 20%"></td>
          </tr>
          @endfor
        </tbody>
      </table>

      <div align="center">
        <p>...........................................................</p>
        <p>Firma del Fiscalizador</p>
      </div>

</div>
</body>
</html>
