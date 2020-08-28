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
        <img src="img/logos/nuevo_color.jpg" width="175">
    </div>
    <hr style="border-bottom: 0px">

    <p style="margin-left: 12.5%;width: 75%;margin-right: 12.5%;
    text-align: center;font-family: Arial, sans-serif;font-weight: bold;border-bottom: 1px solid black;">
      SOLICITUD DE FINALIZACIÓN DE LA AUTOEXCLUSIÓN
    </p>

    <div class="primerEncabezado"  style="margin-left: 74%;border: 1px solid black;text-align: center;">
      Fecha: {{$datos['fecha_revocacion_ae']}}
    </div>

    <p class="primerEncabezado" style="font-size:13px">Por medio de la presente,</p>
    <p class="primerEncabezado" style="font-size:13px"><b>Expreso:</b></p>
    <p class="primerEncabezado" style="font-size:13px">
      Que yo, <b>{{$ae['nombres']}} {{$ae['apellido']}}</b>, DNI <b>{{$ae['nro_dni']}}</b>,
      con domicilio real en calle <b>{{$ae['domicilio']}}</b> Nº <b>{{$ae['nro_domicilio']}}</b>,
      Teléfono <b>{{$ae['telefono']}}</b> de la localidad de <b>{{$ae['nombre_localidad']}}</b> Provincia de
      <b>{{$ae['nombre_provincia']}}</b>; manifiesto expresamente mi voluntad de finalizar con la autoexclusión,
      solicitando que se me PERMITA INGRESAR a las Salas de Juego de los Casinos y Bingos de la Provincia de Santa Fe a partir
      del día siguiente al de cumplimiento efectivo de los 6 meses de la suscripción de la solicitud de autoexclusión, que opera
      en la siguiente fecha:
    </p>

    <p class="primerEncabezado" style="margin-left: 25%;width: 50%;margin-right: 25%;text-align: center;font-size:16px;">
      <b>Fecha: {{$datos['fecha_vencimiento']}}</b>
    </p>

    <p class="primerEncabezado" style="font-size:13px">
    Por consiguiente, firmo de conformidad adjuntando al presente fotocopia de mi documento de identidad, a fin de cumplimentar
    con las disposiciones establecidas por la Caja de Asistencia Social - Lotería de Santa Fe - en el marco de la Resolución de
    Vicepresidencia Ejecutiva N° 270/10 y modificaciones.
    </p>

    <table class="primerEncabezado" style="table-layout: fixed;width: 100%;margin-top: 20%;">
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
