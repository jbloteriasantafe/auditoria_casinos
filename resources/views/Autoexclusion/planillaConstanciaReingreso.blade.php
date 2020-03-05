<!DOCTYPE html>
<html>

  <style>
    section {
      display: flex;
      width: 60%;
      height: 70px;
      margin: auto;
    }
    .centrar {
      margin: auto;
      text-align: center;
      font-family:Arial, sans-serif;
      font-weight: bold;
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
    <!-- CONSTANCIA DE REINGRESO -->
    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
    </div><br>
    <section><p class="centrar" style="padding-top:-20px">CONSTANCIA DE AUTOEXCLUSIÓN VENCIDA</p></section>

    <div class="camposTab titulo" style="top:110px; right:-15px; padding:3px; padding-top:10px; border: 1px solid black">
      FECHA Y HORA DE PLANILLA: <?php print_r(date('d-m-y / h:i')); ?>
    </div>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Por la presente dejamos constancia que <b>{{$datos['apellido_y_nombre']}}</b>, documento de identidad <b>{{$datos['dni']}}</b>,
      con domicilio en <b>{{$datos['domicilio_completo']}}</b>, de la localidad de <b>{{$datos['localidad']}}</b>, ingresar a partir
      de la fecha <b>{{$datos['fecha_cierre_definitivo']}}</b>, a los casinos de la provincia de Santa Fe, ya que su período de
      autoexclusión ha vencido segun Resolución V.P.E 270/10.</p>
    </div>

    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p style="text-align:center; font-size:12px">.................................................................</p>
      <p style="text-align:center; font-size:12px">Firma y Sello</p>
    </div><br>

  </body>
</html>
