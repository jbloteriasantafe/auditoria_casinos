<!DOCTYPE html>
<html>
  <style>
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
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">

    <p style="margin-left: 13.375%;width: 73.25%;margin-right: 13.375%;
    text-align: center;font-family: Arial, sans-serif;font-weight: bold;border-bottom: 1px solid black;">
      CONSTANCIA DE AUTOEXCLUSIÓN CONCLUIDA
    </p>

    <div class="primerEncabezado"  style="margin-left: 74%;border: 1px solid black;text-align: center;">
      FECHA: <?php print_r(date('d/m/Y')); ?>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p>Por la presente dejamos constancia que <b>{{$datos['apellido_y_nombre']}}</b>, documento de identidad <b>{{$datos['dni']}}</b>,
      @if(explode(' ',$datos['domicilio_completo'])[0] == '-' || explode(' ',$datos['domicilio_completo'])[1] == '0')
      @else
      con domicilio en <b>{{$datos['domicilio_completo']}}</b>,
      @endif
       de la localidad de <b>{{$datos['localidad']}}</b>, puede ingresar a partir
      de la fecha <b>{{$datos['fecha_cierre_definitivo']}}</b> a los casinos de la provincia de Santa Fe (físicos y en linea) ya que su período de
      autoexclusión ha vencido segun Resolución V.P.E 270/10 y S.S.</p>
    </div>
    <table style="table-layout: fixed;width: 100%;margin-top: 20%;">
      <tr>
        <td style="width: 50%;text-align: center;font-size: 21px;">...........................</td>
        <td style="width: 50%;text-align: center;font-size: 21px;">...........................</td>
      </tr>
      <tr>
        <td style="width: 50%;text-align: center;font-size: 12px;">Firma Interesado</td>
        <td style="width: 50%;text-align: center;font-size: 12px;">Firma C.A.S. Lotería</td>
      </tr>
    </table>
  </body>
</html>
