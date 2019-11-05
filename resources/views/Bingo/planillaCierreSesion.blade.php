<html>
<head>
  <title>Planilla de Sesión</title>
  <!-- Custom Fonts -->
  <!-- <link rel="stylesheet" type="text/css" href="/font-awesome/css/font-awesome.min.css"> -->
  <link rel="stylesheet" href="web-fonts-with-css/css/fontawesome-all.css">
  <link href="css/estiloPlanillaPortrait.css" rel="stylesheet">
  <link href="/css/importacionFuentes.css" rel="stylesheet">
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

      <table class="table" width="100%" border="1" cellspacing="0">
            <tbody>
         <tr>
           <td style="background-color:#e4e4e4;  width: 15%">Fecha cierre sesión</td>
           <td style="width: 15%"></td>
           <td style="background-color:#e4e4e4;  width: 12%">Fiscalizador</td>
           <td style="width: 23%"></td>
           <td style="background-color:#e4e4e4;  width: 10%">Casino</td>
           <td style="width: 15%">{{$nombre_casino}}</td>
         </tr>
       </tbody>
      </table >
<br>
      <strong><em>Monto de los Pozos</em></strong>

      <table class="table" width="100%" border="1" cellspacing="0">
            <tbody>
         <tr>
           <td style="background-color:#e4e4e4;  width: 25%">Maxi Linea</td>
           <td style="width: 25%">$</td>
           <td style="background-color:#e4e4e4;  width: 25%">Maxi Bingo</td>
           <td style="width: 25%">$</td>
         </tr>
       </tbody>
      </table>
<br>
      <strong><em>Cartones Finales</em></strong>

      <table class="table" width="100%" border="1" cellspacing="0">
        <thead>
          <tr>
            <th style="background-color:#e4e4e4; width: 10%"></th>
            <th align="center" style="background-color:#e4e4e4; width: 20%">Valor Cartón</th>
            <th style="background-color:#e4e4e4; width: 10%"></th>
            <th align="center" style="background-color:#e4e4e4; width: 20%">Serie Final</th>
            <th style="background-color:#e4e4e4; width: 10%"></th>
            <th align="center" style="background-color:#e4e4e4; width: 20%">Carton Final</th>
            <th style="background-color:#e4e4e4; width: 10%"></th>
          </tr>
        </thead>
        <tbody>
          @for ($i = 0; $i < 5; $i++)
          <tr>
            <td style="background-color:#e4e4e4; width: 10%"></td>
            <td style="width: 20%">&nbsp;</td>
            <td style="background-color:#e4e4e4; width: 10%"></td>
            <td style="width: 20%"></td>
            <td style="background-color:#e4e4e4; width: 10%"></td>
            <td style="width: 20%"></td>
            <td style="background-color:#e4e4e4; width: 10%"></td>
          </tr>
          @endfor
        </tbody>
      </table>
<br>
      <strong><em>Observaciones</em></strong>

      <table style="WIDTH: 100%;" cellspacing="0" cellpadding="0" width="100%" border="1">
        <tr>
          <td height="100px">&nbsp;</td>
        </tr>
      </table>
      <div align="center">
        <p>...........................................................</p>
        <p>Firma del Fiscalizador</p>
      </div>

</div>
</body>
</html>
