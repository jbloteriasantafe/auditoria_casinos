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
        <img src="img/logos/nuevo_color.jpg" width="175">
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
      <p>El Programa de Autoexclusión de los Casinos y Bingos de la Provincia de Santa Fe, de la CAS - Lotería de Santa Fe,
      se encuentra destinado a proveer ayuda a quienes consideren de su mayor interés, no participar en Salas de juegos de azar.</p>
      <p>Para ello, la C.A.S. Lotería de Santa Fe, puede asistirlo en su decisión de autoexcluirse,
      a través de la suscripción de la presente solicitud.</p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <p style="font-size:15px" ><b>ACUERDO:</b></p>
      <p>
        Yo <b>{{$autoexcluido['nombres']}} {{$autoexcluido['apellido']}}</b>, DNI <b>{{$autoexcluido['nro_dni']}}</b>,
        constituyendo domicilio a los efectos del presente en calle <b>{{$autoexcluido['domicilio']}}</b> Nº <b>{{$autoexcluido['nro_domicilio']}}</b>
        {!! is_null($autoexcluido['piso'])? '' : ', Piso <b>'.$autoexcluido['piso'].'</b>' !!}
        {!! is_null($autoexcluido['dpto'])? '' : ', Dpto <b>'.$autoexcluido['dpto'].'</b>' !!}
         de la localidad de <b>{{$autoexcluido['nombre_localidad']}}</b>, Provincia de <b>{{$autoexcluido['nombre_provincia']}}</b>
        {!! is_null($autoexcluido['codigo_postal'])? '' : ', C.P. <b>'.$autoexcluido['codigo_postal'].'</b>' !!}
        {!! is_null($autoexcluido['telefono'])? '' : ', Teléfono <b>'.$autoexcluido['telefono'].'</b>' !!}; manifiesto voluntariamente, que no ingresaré a ninguna Sala de Juego de los Casinos y
        Bingos de la Provincia de Santa Fe, durante el plazo de duración del
        @if($es_primer_ae)
        presente, que se extiende por seis (6) meses desde su suscripción y
        cuyo primer vencimiento operará en la siguiente fecha:
        @else
        presente:
        @endif
      </p>
    </div>

    @if($es_primer_ae)
    <p style="margin-left: 40%;width: 20%;margin-right: 40%;text-align: center;font-size:18px;border: 1px solid black;">
      <b>{{$datos_estado['fecha_vencimiento']}}</b>
    </p>

    <p class="primerEncabezado" style="font-size:13px">
      Que, asimismo, si dentro de los treinta días anteriores al primer vencimiento del plazo de duración del presente acuerdo,
      no expreso en forma fehaciente y documentada mi voluntad de dar por finalizada la autoexclusión (*), la misma se renovará
      automáticamente por otros seis (6) meses, a cuyo término operará el vencimiento definitivo, el día:
    </p>
    @endif

    <table style="table-layout: fixed;width: 100%;">
      <tr>
        <td width="40%" style="text-align: center;">
        @if(!$es_primer_ae)
          <b>VENCIMIENTO (*)</b>
        @endif
        </td>
        <td width="20%" style="font-size:18px;text-align: center;border: 1px solid black;"><b>{{$datos_estado['fecha_cierre']}}</b></td>
        <td width="40%"></td>
      </tr>
    </table>

    @if(!$es_primer_ae)
      <br>
      <p class="primerEncabezado"><b>(*) R.V.E. N° 983/19 –cito en Art. 1, último párrafo 
      “Para las personas que ya fueron parte del pro-grama, cuando soliciten ingresar 
      nuevamente al mismo, el tiempo de vigencia será de un (1) año.
      </b></p>
    @endif

    <div class="primerEncabezado" style="font-size:13px">
      <p><b>Que la presente solicitud tiene carácter de IRREVOCABLE.</b></p>
      <p>  Que en tal sentido, y durante el período de vigencia de la Autoexclusión, solicito me sea rechazado la entrada a todos los Casinos
      de la Provincia de Santa Fe, y se me prohíba, en la medida de lo posible, la permanencia en los mismos. Que si intentara, o lograra
      ingresar a cualquier Sala de Juego, me sea requerido el retiro del lugar.</p>
      <p>Asimismo autorizo a que me sean tomadas las imágenes necesarias con el fin de mi identificación, aceptando que las mismas sean
      remitidas a las restantes Salas de Juego, al único efecto del cumplimiento del presente.</p>
    </div>

    <div style="page-break-after: always;"></div>
    <div class="encabezadoImg">
        <img src="img/logos/nuevo_color.jpg" width="175">
    </div>
    <hr style="border-bottom: 0px">

    <div class="primerEncabezado" style="font-size:13px">
      <p>Asimismo, expreso:</p>
      @if(count($contacto['nombre_apellido']) > 0)
      <p>Que nombro como persona de contacto en forma de referencia a:</p>
      <p>Sr./Sra <b>{{ifempty($contacto['nombre_apellido'],'NOINFORMA')}}</b> Domiciliado en calle <b>{{ifempty($contacto['domicilio'])}}</b>
      de la Localidad de <b>{{ifempty($contacto['nombre_localidad'])}}</b> Provincia de <b>{{ifempty($contacto['nombre_provincia'])}}</b>
      Tel <b>{{ifempty($contacto['telefono'])}}</b> Vínculo con el Solicitante <b>{{ifempty($contacto['vinculo'])}}</b>.</p>
      @endif
      <p>Que el ingreso al presente Programa de Autoexclusión, es voluntario, resultando exclusivamente responsable de su cumplimiento,
      para lo cual eximo expresamente de toda responsabilidad al respecto a la C.A.S.-Lotería de Santa Fe y los Concesionarios.
      Que comprendo y consiento que ni los Casinos y Bingos habilitados en la Pcia., ni la C.A.S.-Lotería de Santa Fe pueden garantizar
      totalmente el cumplimiento del presente.</p>
    </div>

    <div class="primerEncabezado" style="font-size:13px; border: 1px solid black; padding:7px;background-color: #f3f3f3;">
      <p><b>IMPORTANTE - LEER CUIDADOSAMENTE:</b> Entiendo que el ingresar a este Programa, no resulta obligación ni responsabilidad de terceros,
      por lo que expresamente renuncio a iniciar cualquier acción legal contra los concesionarios de las Salas de Juegos, la C.A.S.- Lotería
      de Santa Fe y/ o el Estado Provincial, por violación o incumplimiento del presente. Reconozco que las Salas de Juego y sus concesionarios,
      ni la CAS - Lotería de Santa Fe ni el Estado Provincial, resultan responsables de las pérdidas o daños que por mi propio accionar se
      produzcan en mi patrimonio y/o persona y/o en la de terceros.</p>
    </div>

    @if($es_primer_ae)
    <div class="primerEncabezado" style="font-size:13px">
      <p><b>(*) Si decide realizar el trámite de finalización de la autoexclusión, deberá concurrir con D.N.I., a Calle 1° junta 2724 8vo. Piso
      de la Ciudad de Santa Fe o en Av. Pellegrini 947 de la Ciudad de Rosario, los días hábiles de 7:30 a 13:30 hs. En el caso de Melincué
      dicho trámite se podrá realizar en el propio casino. Para las finalizaciones de Santa Fe y Rosario, si el primer plazo de seis meses
      finaliza un día inhábil administrativo, el día de finalización se trasladará al día hábil inmediatamente posterior.-</b></p>
    </div>
    @endif
    <table style="table-layout: fixed;width: 100%;margin-top: 25%;">
      <tr>
        <td style="width: 33.3%;text-align: center;font-size: 21px;">...........................</td>
        <td style="width: 33.3%;text-align: center;font-size: 21px;">...........................</td>
        <td style="width: 33.3%;text-align: center;font-size: 21px;">...........................</td>
      </tr>
      <tr>
        <td style="width: 33.3%;text-align: center;font-size: 12px;">Firma Interesado</td>
        <td style="width: 33.3%;text-align: center;font-size: 12px;">Firma Concesionario</td>
        <td style="width: 33.3%;text-align: center;font-size: 12px;">Firma C.A.S. Lotería</td>
      </tr>
    </table>

    <div style="page-break-after: always;"></div>
    <div class="encabezadoImg">
        <img src="img/logos/nuevo_color.jpg" width="175">
    </div>
    <hr style="border-bottom: 0px">

    <!-- DATOS ADICIONALES PARA LA AUTOEXCLUSIÓN (ENCUESTA) -->
    <table style="table-layout: fixed;width: 100%;">
      <tr>
        <td style="width: 15.5%;"></td>
        <td style="width: 69%;text-align: center;font-family: Arial, sans-serif;font-weight: bold;border-bottom: 1px solid black;">DATOS ADICIONALES PARA LA AUTOEXCLUSIÓN</td>
        <td style="width: 15.5%;"></td>
      <tr>
    </table>
    <div class="primerEncabezado" style="font-size:13px">
      <p>Datos solicitados para elaboración estadística dentro de los parámetros de absoluta confidencialidad.</p>
    </div>
    <div class="primerEncabezado" style="font-size:13px;margin-left: 2%;">
      <!-- PREGUNTA N°1 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;">1. ¿Con qué frecuencia asiste al casino?</td>
        </tr>
      </table>
      <table style="table-layout: fixed;width: 50%;">
        <tr>
          <td style="width: 25%;">Diaria</td>
          <td style="width: 25%;"><input type="checkbox" {{$encuesta['id_frecuencia_asistencia'] == 1 ? 'checked' : ''}}/></td>
        </tr>
        <tr>
          <td style="width: 25%;">Semanal</td>
          <td style="width: 25%;"><input type="checkbox" {{$encuesta['id_frecuencia_asistencia'] == 2 ? 'checked' : ''}}/></td>
          <td style="width: 15%;border: 1px solid black;">{{$encuesta['id_frecuencia_asistencia'] == 2 ? $encuesta['veces'] : ''}}</td>
          <td style="width: 35%;">Veces</td>
        </tr>
        <tr>
          <td style="width: 25%;">Mensual</td>
          <td style="width: 25%;"><input type="checkbox" {{$encuesta['id_frecuencia_asistencia'] == 3 ? 'checked' : ''}}/></td>
          <td style="width: 15%;border: 1px solid black;">{{$encuesta['id_frecuencia_asistencia'] == 3 ? $encuesta['veces'] : ''}}</td>
          <td style="width: 35%;">Veces</td>
        </tr>
      </table>
      <!-- PREGUNTA N°2 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="width: 50%;font-size: 14px;">2. ¿Cuánto tiempo permanece jugando?</td>
          <td style="width: 7.5%;border: 1px solid black;">{{$encuesta['tiempo_jugado'] != -1? $encuesta['tiempo_jugado'] : ''}}</td>
          <td style="width: 42.5%;">Horas.</td>
        </tr>
      </table>
      <!-- PREGUNTA N°3 -->
      <table style="table-layout: fixed;width: 80%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;width: 40%;">3. ¿Cómo asiste al casino?</td>
          <td style="font-size: 14px;width: 10%;">Solo</td>
          <td style="width: 10%;"><input type="checkbox" {{$encuesta['como_asiste'] === 0 ? 'checked' : ''}}/></td>
          <td style="font-size: 14px;width: 20%;">Acompañado</td>
          <td style="width: 10%;"><input type="checkbox" {{$encuesta['como_asiste'] === 1 ? 'checked' : ''}}/></td>
        </tr>
      </table>
      <!-- PREGUNTA N°4 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;width: 100%;">4. ¿Qué tipo de juego le atrae?</td>
        </tr>
      </table>
      <table style="table-layout: fixed;width: 100%;">
        <tr>
          <td style="width: 30%;text-align: center;">Máquinas Tragamonedas</td>
          <td style="width: 5%;"></td>
          <td style="width: 30%;text-align: center;">Mesas de Paño</td>
          <td style="width: 5%;"></td>
          <td style="width: 20%;text-align: center;">Bingo</td>
          <td style="width: 20%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 3 ? 'checked' : ''}}/></td>
        </tr>
        <tr>
          <td style="width: 30%;text-align: center;font-size: 11px;">|</td>
          <td style="width: 5%;"></td>
          <td style="width: 30%;text-align: center;font-size: 11px;">|</td>
        </tr>
        <tr>
          <td style="width: 30%;">Máquinas Tradicionales</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 1 ? 'checked' : ''}}/></td>
          <td style="width: 30%;">Carteados</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 2 ? 'checked' : ''}}/></td>
          <td style="width: 20%;text-align: center;">Otro</td>
          <td style="width: 20%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 7 ? 'checked' : ''}}/></td>
        </tr>
        <tr>
          <td style="width: 30%;">Ruleta Electrónica</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 5 ? 'checked' : ''}}/></td>
          <td style="width: 30%;">Ruleta Americana</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 4 ? 'checked' : ''}}/></td>
        </tr>
        <tr>
          <td style="width: 30%;"></td>
          <td style="width: 5%;"></td>
          <td style="width: 30%;">Dados</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['id_juego_preferido'] == 6 ? 'checked' : ''}}/></td>
        </tr>
      </table>
      <!-- PREGUNTA N°5 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;width: 50%;">5. ¿Es socio del club de jugadores?</td>
          <td style="font-size: 14px;width: 5%;">Sí</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['club_jugadores'] == 'SI' ? 'checked' : ''}}/></td>
          <td style="font-size: 14px;width: 5%;">No</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['club_jugadores'] == 'NO' ? 'checked' : ''}}/></td>
        </tr>
      </table>
      <!-- PREGUNTA N°6 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;width: 50%;">6. ¿Considera que su decisión de autoexcluirse responde a problemas de autocontrol sobre el juego?</td>
          <td style="font-size: 14px;width: 5%;">Sí</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['autocontrol_juego'] == 'SI' ? 'checked' : ''}}/></td>
          <td style="font-size: 14px;width: 5%;">No</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['autocontrol_juego'] == 'NO' ? 'checked' : ''}}/></td>
        </tr>
      </table>
      <!-- PREGUNTA N°7 -->
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="font-size: 14px;width: 50%;">7. ¿Desea recibir información sobre Juego Responsable?</td>
          <td style="font-size: 14px;width: 5%;">Sí</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['recibir_informacion'] == 'SI' ? 'checked' : ''}}/></td>
          <td style="font-size: 14px;width: 5%;">No</td>
          <td style="width: 5%;"><input type="checkbox" {{$encuesta['recibir_informacion'] == 'NO' ? 'checked' : ''}}/></td>
        </tr>
      </table>
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;margin-left: 5%;">
        <tr>
          <td style="font-size: 14px;">Si está de acuerdo con recibir más información, ¿por qué medio le gustaría recibirlo?</td>
        </tr>
      </table>
      <table style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          @if($encuesta['medio_recibir_informacion'] != -1 && is_numeric($encuesta['medio_recibir_informacion']))
          <td style="width: 5%;"><input type="checkbox" checked/></td>
          <td style="width: 15%;">Al Teléfono</td>
          <td style="width: 80%;">{{$encuesta['medio_recibir_informacion']}}</td>
          @else
          <td style="width: 5%;"><input type="checkbox"/></td>
          <td style="width: 15%;">Al Teléfono</td>
          <td style="width: 80%;">[............]................................</td>
          @endif
        </tr>        
        <tr>
          @if($encuesta['medio_recibir_informacion'] != -1 && strpos($encuesta['medio_recibir_informacion'], '@'))
          <td style="width: 5%;"><input type="checkbox" checked/></td>
          <td style="width: 25%;">Al Correo electrónico</td>
          <td style="width: 70%;">{{$encuesta['medio_recibir_informacion']}}</td>
          @else
          <td style="width: 5%;"><input type="checkbox"/></td>
          <td style="width: 25%;">Al Correo electrónico</td>
          <td style="width: 70%;">..............................................</td>
          @endif
        </tr>
        <tr>
          @if($encuesta['medio_recibir_informacion'] != -1 && !is_numeric($encuesta['medio_recibir_informacion']) && strpos($encuesta['medio_recibir_informacion'], '@') == false && count($encuesta['medio_recibir_informacion']) > 0)
          <td style="width: 5%;"><input type="checkbox" checked/></td>
          <td style="width: 15%;">Otro medio</td>
          <td style="width: 80%;">{{$encuesta['medio_recibir_informacion']}}</td>
          @else
          <td style="width: 5%;"><input type="checkbox"/></td>
          <td style="width: 15%;">Otro medio</td>
          <td style="width: 80%;">..............................................</td>
          @endif
        </tr>
      </table>
      @if(count($encuesta['observacion']) > 0)
      <table  style="table-layout: fixed;width: 100%;margin-top: 1%;">
        <tr>
          <td style="width: 15%;">Observación:</td>
          <td style="width: 85%;">{{$encuesta['observacion']}}</td>
        </tr>
      </table>
      @endif
    </div>
  </body>
</html>
