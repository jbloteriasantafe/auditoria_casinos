<!DOCTYPE html>
<html>
  <?php
  function ifempty($s,$val = 'NOINFORMA'){
    return count($s) > 0? $s : $val;
  }
  ?>
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
    <!-- SOLICITUD DE AUTOEXCLUSIÓN -->
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">

    <p style="margin-left: 27.5%;width: 45.5%;margin-right: 27.5%;
    text-align: center;font-family: Arial, sans-serif;font-weight: bold;border-bottom: 1px solid black;">
      SOLICITUD DE AUTOEXCLUSIÓN
    </p>

    <div class="primerEncabezado"  style="margin-left: 74%;border: 1px solid black;text-align: center;font-size:12px;">
      Fecha: {{$datos_estado['fecha_ae']}}
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p>
        El Programa de Autoexclusión de los Casinos y Bingos y Plataformas de Juego en Línea de la Provincia de Santa Fe, 
        de la C.A.S.-Lotería de Santa Fe, se encuentra destinado a proveer ayuda a quienes consideren de su mayor interés no
         participar en salas de juegos de azar ni en plataformas de juego en línea (Ley N.º 14.235).
      </p>
      <p>
        Para ello, la C.A.S. Lotería de Santa Fe puede asistirlo en su decisión de autoexcluirse,
         a través de la suscripción de la presente solicitud.
      </p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p style="font-size:15px" ><b>ACUERDO:</b></p>
      <p>
        Yo <b>{{$autoexcluido['nombres']}} {{$autoexcluido['apellido']}}</b>, DNI N° <b>{{$autoexcluido['nro_dni']}}</b>,
        con domicilio a los efectos del presente en
         calle <b>{{$autoexcluido['domicilio']}}</b> Nº <b>{{$autoexcluido['nro_domicilio']}}</b>
        @if(!empty($autoexcluido['piso']))
        , Piso <b>{{$autoexcluido['piso']}}</b>
        @endif
        @if(!empty($autoexcluido['dpto']))
        , Dpto <b>{{$autoexcluido['dpto']}}</b>
        @endif
        , de la localidad de <b>{{$autoexcluido['nombre_localidad']}}</b>
        @if(!empty($autoexcluido['codigo_postal']))
        , C.P. <b>{{$autoexcluido['codigo_postal']}}</b>
        @endif
        , Provincia de <b>{{$autoexcluido['nombre_provincia']}}</b>
        @if(!empty($autoexcluido['telefono']))
        , Teléfono <b>{{$autoexcluido['telefono']}}</b>
        @endif
        @if(!empty($autoexcluido['correo']))
        , Correo electrónico: <b>{{$autoexcluido['correo']}}</b> 
        @endif
      </p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <table style="border-collapse:collapse;">
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0; width:20px;">
            <input type="checkbox" {{ empty($datos_estado['años_adicionales']) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;">1 (un) año (Plazo mínimo normativo)</td>
        </tr>
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0;">
            <input type="checkbox" {{ !empty($datos_estado['años_adicionales']) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;">Plazo extendido de: <b>{{ $datos_estado['años_adicionales']+1 }}</b> años</td>
        </tr>
      </table>
      <p>Cuyo vencimiento operará el día: <b>{{$datos_estado['fecha_cierre']}}</b></p>
    </div>

    <div class="primerEncabezado" style="font-size:13px;padding-left: 1em;">
      <p>
        Declaro conocer que la presente solicitud tiene carácter de <b>IRREVOCABLE</b> durante 
        todo el tiempo de vigencia estipulado.
      </p>
      <p>
        Durante el período de vigencia, solicito me sea rechazado el ingreso y permanencia en todos los Casinos y Bingos de la Provincia de Santa Fe. Asimismo, solicito la suspensión inmediata de mi cuenta de juego en todas las Plataformas de Juego en Línea y Apuestas deportivas  autorizadas por la C.A.S.-Lotería de Santa Fe, impidiéndose la realización de apuestas y cargas de crédito, permitiéndose únicamente el retiro de saldos a favor existentes. Acepto que la solicitud en una sala física o plataforma virtual importará idéntico efecto recíproco respecto de todas las demás.
      </p>
      <p>
        Manifiesto mi consentimiento libre, expreso e informado para la captación, procesamiento y almacenamiento de mi imagen y mis datos biométricos mediante tomas fotográficas e integración a sistemas de reconocimiento facial, con la única y exclusiva finalidad de aplicar las medidas de seguridad del Programa.
      </p>
    </div>

     <div class="primerEncabezado" style="font-size:13px">
      <p><b>Alcance Interjurisdiccional:</b></p>
      <table style="border-collapse:collapse;">
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0; width:20px;">
            <input type="checkbox" {{ ($datos_estado['alcance_interjurisdiccional'] ?? 0) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;"><b>SÍ CONSIENTO</b> que la medida se extienda a otras jurisdicciones con las cuales la C.A.S.-Lotería de Santa Fe mantenga convenios de reciprocidad.</td>
        </tr>
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0; width:20px;">
            <input type="checkbox" {{ ($datos_estado['alcance_interjurisdiccional'] ?? 0) ? '' : 'checked' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;"><b>NO CONSIENTO</b> el alcance interjurisdiccional (exclusivo Pcia. de Santa Fe).</td>
        </tr>
      </table>
    </div>

    <div style="page-break-after: always;"></div>
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">

    <div class="primerEncabezado" style="font-size:13px">
      <p><b>Asimismo, expreso:</b></p>
      @if(count($contacto['nombre_apellido']) > 0)
      <p>
        Que nombro como persona de contacto en forma de referencia a: <b>{{$contacto['nombre_apellido']}}</b>, 
        @if(!empty($contacto['domicilio']))
        con domicilio en calle <b>{{$contacto['domicilio']}}</b>, 
        @endif
        @if(!empty($contacto['nombre_localidad']))
        de la localidad de <b>{{$contacto['nombre_localidad']}}</b>, 
        @endif
        @if(!empty($contacto['nombre_provincia']))
        Provincia de <b>{{$contacto['nombre_provincia']}}</b>, 
        @endif
        @if(!empty($contacto['telefono']))
        Tel. <b>{{$contacto['telefono']}}</b>,
        @endif
        @if(!empty($contacto['vinculo']))
        Vínculo con el Solicitante: <b>{{$contacto['vinculo']}}</b>
        @endif
      </p>
      @endif
      <p>
        Que el ingreso al presente Programa de Autoexclusión es voluntario, resultando el suscripto 
        exclusivamente responsable de su cumplimiento, para lo cual eximo expresamente de toda 
        responsabilidad al respecto a la C.A.S.-Lotería de Santa Fe, al Estado Provincial 
        y a los Concesionarios/Licenciatarios. Comprendo y consiento que ni los Casinos y Bingos habilitados, 
        ni las plataformas de juego online, ni la C.A.S.-Lotería de Santa Fe pueden garantizar totalmente el 
        cumplimiento del presente si mediaran conductas elusivas de mi parte.
      </p>
    </div>

    <div class="primerEncabezado" style="font-size:13px; border: 1px solid black; padding:7px;background-color: #f3f3f3;">
      <p style="font-size:15px"><b>IMPORTANTE - LEER CUIDADOSAMENTE:</b></p>
      <p>Entiendo que el ingresar a este Programa no resulta obligación ni responsabilidad de terceros, por lo que expresamente renuncio a iniciar cualquier acción legal contra los concesionarios y licenciatarios de las Salas de Juegos y Plataformas Online, la C.A.S.-Lotería de Santa Fe y/o el Estado Provincial, por violación o incumplimiento del presente. Reconozco que ni las Salas de Juego, ni los operadores en línea, ni la C.A.S.-Lotería de Santa Fe, ni el Estado Provincial resultan responsables de las pérdidas o daños que por mi propio accionar se produzcan en mi patrimonio y/o persona y/o en la de terceros.</p>
      <p><b>Línea telefónica de atención gratuita y confidencial:</b> Si considera que tiene algún inconveniente con su manera de jugar puede informarse al <b>N.º 0800-345-5640</b>.</p>
    </div>
    
    <div class="primerEncabezado" style="font-size:13px">
      <p>
        Los datos contenidos en el presente formulario se encuentran resguardados bajo los términos 
        de la Ley Nacional 25.326 de Protección de Datos Personales (Arts. 5, 6, 8, 9 y 10), 
        Ley Provincial 14.235, Ley Provincial 14.293 y Ley Provincial 8525 (Art. 13 inc. f).
      </p>
    </div>

    <table style="table-layout: fixed;width: 100%;margin-top: 25%;">
      <tr>
        <td style="width: 50%;text-align: center;font-size: 21px;">...........................</td>
        <td style="width: 50%;text-align: center;font-size: 21px;">...........................</td>
      </tr>
      <tr>
        <td style="width: 50%;text-align: center;font-size: 12px;">Firma del Solicitante</td>
        <td style="width: 50%;text-align: center;font-size: 12px;">Firma Concesionario / Licenciatario</td>
      </tr>
    </table>
  </body>
</html>
