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
    <!-- SOLICITUD DE FINALIZACIÓN DE LA AUTOEXCLUSIÓN -->
    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
    </div><br>
    <section><p class="centrar" style="padding-top:-20px">SOLICITUD DE FINALIZACIÓN DE LA AUTOEXCLUSIÓN</p></section>

    <div class="camposTab titulo" style="top:110px; right:-15px; padding:3px; padding-top:10px; border: 1px solid black">
      FECHA: <?php print_r(date('d-m-y')); ?>
    </div>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Por medio de la presente,</p>
      <p><n>Expreso:</b></p>
      <p>Que yo, <b>{{$ae['nombres']}} {{$ae['apellido']}}</b>, DNI <b>{{$ae['nro_dni']}}</b>,
      con Domiclio real en calle <b>{{$ae['domicilio']}}</b> Nº <b>{{$ae['nro_domicilio']}}</b>,
      Teléfono <b>{{$ae['telefono']}}</b> de la localidad de <b>{{$ae['nombre_localidad']}}</b> Provincia de
      <b>{{$ae['nombre_provincia']}}</b>; <b>manifiesto expresamente mi voluntad de finalizar con la autoexclusión,
      solicitando que se me PERMITA INGRESAR a las Salas de Juego de los Casinos y Bingos de la Provincia de Santa Fe a partir
      del día siguiente al de cumplimiento efectivo de los 6 meses de la suscripción de la solicitud de autoexclusión, que opera
      en la siguiente fecha:</b>
      </p>
      <p class="camposTab titulo" style="right:293px; top:315px; padding:3px; padding-top:7px; padding-bottom:7px; border: 1px solid black; font-size:15px">
        {{$datos['fecha_vencimiento']}}
      </p>
    </div><br>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Por consiguiente, firmo de conformidad adjuntando al presente fotocopia de mi documento de identidad, a fin de cumplimentar
      con las disposiciones establecidas por la Caja de Asistencia Social - Lotería de Santa Fe - en el marco de la Resolución de
      Vicepresidencia Ejecutiva N° 270/10 y modificaciones</p>
    </div><br>

    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <br><br><br><br><br><br><br><br>
    <div style="top:1500px">
      <p style="text-align:center">
        .......................................
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        .......................................
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        .......................................
      </p>
      <p style="font-size:12px">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Firma Interesado
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Firma Concesionario
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Firma C.A.S. Lotería
      </p>
    </div>

  </body>
</html>
