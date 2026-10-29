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
    <!--
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">
    -->

    <p style="font-family: Arial, sans-serif;font-weight: bold;font-size:13px;">
      SOLICITUD DE AUTOEXCLUSIÓN
    </p>

    <div class="primerEncabezado"  style="font-size:13px;">
      <b>Fecha:</b> {{$ae['estado_fecha'] ?? 'ERROR'}}
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p style="font-size:15px" ><b>OBJETO:</b></p>
      <p>
        El Programa Autoexclusión constituye una herramienta destinada a aquellas personas que voluntariamente 
        decidan excluirse a sí mismos de concurrir a las salas de juegos de los casinos y bingos 
        de la Provincia de Santa Fe, así como de participar en las plataformas de juego en línea 
        (tanto de juegos de azar como de apuestas deportivas) permitidas en la Ley N° 14.235.
      </p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p style="font-size:15px" ><b>ACUERDO:</b></p>
      <p>
        Yo <b>{{$ae['nombres'] ?? 'ERROR'}} {{$ae['apellidos'] ?? 'ERROR'}}</b>, 
        DNI N° <b>{{$ae['nro_dni'] ?? 'ERROR'}}</b>, 
        de la localidad de <b>{{$ae['nombre_localidad'] ?? 'ERROR'}}</b>, 
        Provincia de <b>{{$ae['nombre_provincia'] ?? 'ERROR'}}</b>
        @if(!empty($ae['telefono']))
        , Teléfono <b>{{$ae['telefono']}}</b>
        @endif
        @if(!empty($ae['correo']))
        , Correo electrónico: <b>{{$ae['correo'] ?? 'ERROR'}}</b> 
        @endif
      </p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p><b>Solicito mi incorporación al Programa de Autoexclusión por el término de:</b></p>
      <table style="border-collapse:collapse;">
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0; width:20px;">
            <input type="checkbox" {{ empty($ae['estado_años_adicionales']) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;">1 (un) año (Plazo mínimo normativo)</td>
        </tr>
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0;">
            <input type="checkbox" {{ !empty($ae['estado_años_adicionales']) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;">Plazo extendido de: <b>{{ !empty($datos_estado['años_adicionales']) ? ($datos_estado['años_adicionales']+1) : 'N/A' }}</b> años</td>
        </tr>
      </table>
      <p>Cuyo vencimiento operará el día: <b>{{$ae['estado_fecha_vencimiento'] ?? 'ERROR'}}</b></p>
    </div>

    <div class="primerEncabezado" style="font-size:13px;padding-left: 1em;">
      <p>
        Declaro conocer que la presente solicitud tiene carácter de <b>IRREVOCABLE</b> durante 
        todo el tiempo de vigencia estipulado.
      </p>
      <p>
        Durante el período de vigencia, solicito me sea rechazado el ingreso y permanencia en todos los
        Casinos y Bingos de la Provincia de Santa Fe. Asimismo, solicito la suspensión de mi cuenta de
        juego en todas las Plataformas de Juego en Línea y Apuestas deportivas autorizadas por la C.A.S.-
        Lotería de Santa Fe, impidiéndose la realización de apuestas y cargas de crédito, permitiéndose
        únicamente el retiro de saldos a favor existentes. Acepto que la solicitud en una sala física o
        plataforma virtual (casinos on line - apuestas deportivas) importará idéntico efecto recíproco
        respecto de todas las demás.
      </p>
      <p>
        Manifiesto mi consentimiento libre, expreso e informado para la captación, procesamiento y
        almacenamiento de mi imagen y mis datos biométricos mediante tomas fotográficas e 
        integración a sistemas de reconocimiento facial, con la única y exclusiva finalidad de aplicar las
        medidas de seguridad del Programa.
      </p>
    </div>

     <div class="primerEncabezado" style="font-size:13px;padding-left: 1em;">
      <p><b>Alcance Interjurisdiccional:</b></p>
      <table style="border-collapse:collapse;">
        <tr>
          <td style="vertical-align:middle; padding:2px 6px 2px 0; width:20px;">
            <input type="checkbox" {{ ($ae['estado_alcance_interjurisdiccional'] ?? 0) ? 'checked' : '' }}>
          </td>
          <td style="vertical-align:middle; padding:2px 0;"><b>SÍ CONSIENTO</b> que la medida se extienda a otras jurisdicciones con las cuales la C.A.S.-Lotería de Santa Fe mantenga convenios de reciprocidad.</td>
        </tr>
      </table>
    </div>

    <div style="page-break-after: always;"></div>
    <!--
    <div class="encabezadoImg">
        <img src="img/logos/logo_2024_loteria.png" width="160">
    </div>
    <hr style="border-bottom: 0px">
    -->

    <div class="primerEncabezado" style="font-size:13px">
      <p style="font-size:15px"><b>Asimismo, Expreso: (IMPORTANTE - LEER CUIDADOSAMENTE)</b></p>
      <p>
        Que el ingreso al presente Programa de Autoexclusión es voluntario, resultando el suscripto
        exclusivamente responsable de su cumplimiento, para lo cual eximo expresamente de toda
        responsabilidad al respecto a la C.A.S.-Lotería de Santa Fe, al Estado Provincial y a los
        Concesionarios/Licenciatarios. Comprendo y consiento que ni los Casinos y Bingos habilitados, ni las
        plataformas de juego online, ni la C.A.S.-Lotería de Santa Fe pueden garantizar totalmente el
        cumplimiento del presente si mediaran conductas elusivas de mi parte.
      </p>
      <p style="padding-left: 1em;">
        <b>Línea telefónica de atención gratuita y confidencial:</b> 
        Si considera que tiene algún inconveniente con su manera de jugar puede informarse al <b>N.º 0800-268-5640</b>.
      </p>
    </div>
    
    <div class="primerEncabezado" style="font-size:13px">
      <p>
        Los datos contenidos en el presente formulario se encuentran resguardados bajo los términos de la Ley
        Nacional 25.326 de Protección de Datos Personales (Arts. 5, 6, 8, 9 y 10), Ley Provincial 14.235, Ley
        Provincial 14.293 y Ley Provincial 8525 (Art. 13 inc. f).
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
