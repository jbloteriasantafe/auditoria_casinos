<!DOCTYPE html>
<html>
  <style>
    .centrar {
      text-align: center;
      font-family: Arial, sans-serif;
      font-weight: bold;
    }
    @page{
      margin-top: 6.25%;
      margin-left: 16.3%;
      margin-right: 16.3%;
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
        <img src="img/logos/banner_color.jpg" width="175">
    </div>
    <hr style="border-bottom: 0px">
    <p class="centrar" style="text-decoration: underline">CONSTANCIA DE AUTOEXCLUSIÓN VENCIDA</p>
    <div class="primerEncabezado"  style="margin-left: 75%;border: 1px solid black;text-align: center;">
      FECHA: <?php print_r(date('d/m/Y')); ?>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p>Por la presente dejamos constancia que <b>{{$datos['apellido_y_nombre']}}</b>, documento de identidad <b>{{$datos['dni']}}</b>,
      con domicilio en <b>{{$datos['domicilio_completo']}}</b>, de la localidad de <b>{{$datos['localidad']}}</b>, ingresar a partir
      de la fecha <b>{{$datos['fecha_cierre_definitivo']}}</b>, a los casinos de la provincia de Santa Fe, ya que su período de
      autoexclusión ha vencido segun Resolución V.P.E 270/10.</p>
    </div>
    <div style="padding-top: 15%;font-size:11px;padding-left: 15%;">
      <div style="display: inline-block;">
        <p style="">.................................................................</p>
        <p style="padding-left: 8%;">Firma Interesado</p>
      </div>
      <div style="display: inline-block;">
        <p style="">.................................................................</p>
        <p style="padding-left: 8%;">Firma C.A.S. Lotería</p>
      </div>
    </div>
  </body>
</html>
