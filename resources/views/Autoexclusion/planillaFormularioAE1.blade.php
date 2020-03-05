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
    .checkboxes label {
      display: inline-block;
      padding-right: 10px;
      white-space: nowrap;
    }
    .checkboxes input {
      vertical-align: middle;
    }
    .checkboxes label span {
      vertical-align: middle;
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
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
    </div><br>
    <section><p class="centrar" style="padding-top:-20px">SOLICITUD DE AUTOEXCLUSIÓN</p></section>

    <div class="camposTab titulo" style="top:110px; right:-15px; padding:3px; padding-top:10px; border: 1px solid black">
      FECHA: ......../......../........
    </div>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>El Programa de Autoexclusión de los Casinos y Bingos de la Provincia de Santa Fe, de la CAS - Lotería de Santa Fe,
      se encuentra destinado a proveer ayuda a quienes consideren de su mayor interés, no participar en Salas de juegos de azar.</p>
      <p>Para ello, la C.A.S. Lotería de Santa Fe, puede asistirlo en su decisión de autoexcluirse,
      a través de la suscripción de la presente solicitud.</p>
    </div><br>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p style="font-family:Arial, sans-serif; font-weight: bold; font-size:15px" ><i>ACUERDO:<i></p>
      <p>Yo <b>{{$autoexcluido['nombres']}} {{$autoexcluido['apellido']}}</b>, DNI <b>{{$autoexcluido['nro_dni']}}</b>,
      constituyendo domicilio a los efectos del presente en calle <b>{{$autoexcluido['domicilio']}}</b> Nº <b>{{$autoexcluido['nro_domicilio']}}</b>,
      de la localidad de <b>{{$autoexcluido['nombre_localidad']}}</b> Provincia de <b>{{$autoexcluido['nombre_provincia']}}</b>, C.P....................,
      Teléfono <b>{{$autoexcluido['telefono']}}</b>; manifiesto voluntariamente, que no ingresaré a ninguna Sala de Juego de los Casinos y
      Bingos de la Provincia de Santa Fe, durante el plazo de duración del presente, que se extiende por seis (6) meses desde su suscripción y
      cuyo primer vencimiento operará en la siguiente fecha:</p>
      <p class="camposTab titulo" style="right:293px; top:400px; padding:3px; padding-top:7px; padding-bottom:7px; border: 1px solid black; font-size:15px">
        {{$datos_estado['fecha_vencimiento']}}
      </p>
    </div><br>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Que, asimismo, si dentro de los treinta días anteriores al primer vencimiento del plazo de duración del presente acuerdo,
      no expreso en forma fehaciente y documentada mi voluntad de dar por finalizada la autoexclusión (*), la misma se renovará
      automáticamente por otros seis (6) meses, a cuyo término operará el vencimiento definitivo, el día:</p>
      <p class="camposTab titulo" style="right:293px; top:505px; padding:3px; padding-top:10px; padding-bottom:7px; border: 1px solid black; font-size:15px">
        {{$datos_estado['fecha_cierre']}}
      </p>
    </div><br>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p><b>Que la presente solicitud tiene carácter de IRREVOCABLE.</b></p>
      <p>  Que en tal sentido, y durante el período de vigencia de la Autoexclusión, solicito me sea rechazado la entrada a todos los Casinos
      de la Provincia de Santa Fe, y se me prohíba, en la medida de lo posible, la permanencia en los mismos. Que si intentara, o lograra
      ingresar a cualquier Sala de Juego, me sea requerido el retiro del lugar.</p>
      <p>Asimismo autorizo a que me sean tomadas las imágenes necesarias con el fin de mi identificación, aceptando que las mismas sean
      remitidas a las restantes Salas de Juego, al único efecto del cumplimiento del presente.</p>
    </div><br>


    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Asimismo, expreso:</p>
      <p>Que nombro como persona de contacto en forma de referencia a:</p>
      <p>Sr./Sra <b>{{$contacto['nombre_apellido']}}</b> Domiciliado en calle <b>{{$contacto['domicilio']}}</b>
      de la Localidad de <b>{{$contacto['nombre_localidad']}}</b> Provincia de <b>{{$contacto['nombre_provincia']}}</b>
      Tel <b>{{$contacto['telefono']}}</b> Vínculo con el Solicitante <b>{{$contacto['vinculo']}}</b>.</p>
      <p>Que el ingreso al presente Programa de Autoexclusión, es voluntario, resultando exclusivamente responsable de su cumplimiento,
      para lo cual eximo expresamente de toda responsabilidad al respecto a la C.A.S.-Lotería de Santa Fe y los Concesionarios.
      Que comprendo y consiento que ni los Casinos y Bingos habilitados en la Pcia., ni la C.A.S.-Lotería de Santa Fe pueden garantizar
      totalmente el cumplimiento del presente.</p>
    </div>
    <div style="page-break-after:always;"></div>

    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
    </div><br>
    <div class="primerEncabezado" align="justify" style="font-size:13px; border: 1px solid black; padding:7px">
      <p><b>IMPORTANTE - LEER CUIDADOSAMENTE:</b> Entiendo que el ingresar a este Programa, no resulta obligación ni responsabilidad de terceros,
      por lo que expresamente renuncio a iniciar cualquier acción legal contra los concesionarios de las Salas de Juegos, la C.A.S.- Lotería
      de Santa Fe y/ o el Estado Provincial, por violación o incumplimiento del presente. Reconozco que las Salas de Juego y sus concesionarios,
      ni la CAS - Lotería de Santa Fe ni el Estado Provincial, resultan responsables de las pérdidas o daños que por mi propio accionar se
      produzcan en mi patrimonio y/o persona y/o en la de terceros.</p>
    </div>

    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p><b>(*) Si decide realizar el trámite de finalización de la autoexclusión, deberá concurrir con D.N.I., a Calle 1° junta 2724 8vo. Piso
      de la Ciudad de Santa Fe o en Av. Pellegrini 947 de la Ciudad de Rosario, los días hábiles de 7:30 a 13:30 hs. En el caso de Melincué
      dicho trámite se podrá realizar en el propio casino. Para las finalizaciones de Santa Fe y Rosario, si el primer plazo de seis meses
      finaliza un día inhábil administrativo, el día de finalización se trasladará al día hábil inmediatamente posterior.-</b></p>
    </div>

    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
    <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
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
    <div style="page-break-after:always;"></div>




    <!-- DATOS ADICIONALES PARA LA AUTOEXCLUSIÓN (ENCUESTA) -->
    <div class="encabezadoImg">
        <img src="img/logos/banner_loteria_landscape2_f.png" width="900">
    </div>
    <p align="center"><b>DATOS ADICIONALES PARA LA AUTOEXCLUSIÓN</b></p>
    <div class="primerEncabezado" align="justify" style="font-size:13px">
      <p>Datos solicitados para elaboración estadística dentro de los parámetros de absoluta confidencialidad.</p>
    </div>

    <div class="primerEncabezado" style="font-size:13px">
      <form id="formEncuesta">

        <!-- PREGUNTA N°1 -->
        <p>1. ¿Con qué frecuencia asiste al casino?</p>
        <div class="checkboxes">
          <label for="diaria"><input type="checkbox" id="diaria" {{$encuesta['id_frecuencia_asistencia'] == 1 ? 'checked' : ''}}/> <span> Diaria</span></label><br><br>
          <label for="semanal"><input type="checkbox" id="semanal" {{$encuesta['id_frecuencia_asistencia'] == 2 ? 'checked' : ''}}/> <span> Semanal</span></label> &nbsp;&nbsp;&nbsp;
            <div style="width:30px; height:20px; display:inline-block; border: 1px solid black; text-align:center"></div>
            <p style="padding-top:-38px; padding-left:135px;"> veces.</p><br>
          <label for="mensual"><input type="checkbox" id="mensual" {{$encuesta['id_frecuencia_asistencia'] == 3 ? 'checked' : ''}}/> <span> Mensual</span></label> &nbsp;&nbsp;&nbsp;
            <div style="width:30px; height:20px; display:inline-block; border: 1px solid black; text-align:center"></div>
            <p style="padding-top:-38px; padding-left:135px;"> veces.</p>
        @if ($encuesta['veces'] != -1)
          @if ($encuesta['frecuencia_asistencia'] == 2)
            <p style="padding-top:-71px; padding-left:112px;">{{$encuesta['veces']}}</p>
          @elseif ($encuesta['frecuencia_asistencia'] == 3)
            <p style="padding-top:-28px; padding-left:111px;">{{$encuesta['veces']}}</p>
          @endif
        @endif
        </div><br>

        <!-- PREGUNTA N°2 -->
        <p>2. ¿Cuánto tiempo permanece jugando?</p>
        <div style="width:30px; height:20px; display:inline-block; border: 1px solid black; text-align:center"></div>
        <p style="padding-top:-36px; padding-left:40px;"> horas.</p><br>

        @if ($encuesta['tiempo_jugado'] != -1)
            <p style="padding-top:-45px; padding-left:13px;">{{$encuesta['tiempo_jugado']}}</p>
        @endif

        <!-- PREGUNTA N°3 -->
        <p>3. ¿Cómo asiste al casino?</p>
        <div class="checkboxes">
          <label for="solo"><input type="checkbox" id="solo" {{$encuesta['como_asiste'] == 0 ? 'checked' : ''}}/> <span> Solo</span></label><br>
          <label for="acompañado"><input type="checkbox" id="acompañado" {{$encuesta['como_asiste'] == 1 ? 'checked' : ''}}/> <span> Acompañado</span></label>
        </div><br>

        <!-- PREGUNTA N°4 -->
        <p>4. ¿Qué tipo de juego le atrae?</p>
        <div class="checkboxes">
          <div>
            <label for="tragamonedas"><input type="checkbox" id="tragamonedas" {{$encuesta['id_juego_preferido'] == 1 ? 'checked' : ''}}/> <span> Máquinas tragamonedas</span></label>
            <label for="carteados"><input type="checkbox" id="carteados" {{$encuesta['id_juego_preferido'] == 2 ? 'checked' : ''}}/> <span> Juegos carteados</span></label>
          </div>
          <div>
            <label for="bingo"><input type="checkbox" id="bingo" {{$encuesta['id_juego_preferido'] == 3 ? 'checked' : ''}}/> <span> Bingo</span></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <label for="anericana"><input type="checkbox" id="anericana" {{$encuesta['id_juego_preferido'] == 4 ? 'checked' : ''}}/> <span> Ruleta americana</span></label>
          </div>
          <div>
            <label for="electronica"><input type="checkbox" id="electronica" {{$encuesta['id_juego_preferido'] == 5 ? 'checked' : ''}}/> <span> Ruleta electrónica</span></label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <label for="dados"><input type="checkbox" id="dados" {{$encuesta['id_juego_preferido'] == 6 ? 'checked' : ''}}/> <span> Dados</span></label>
          </div>
        </div><br>

        <!-- PREGUNTA N°5 -->
        <p>5. ¿Es socio del club de jugadores?</p>
        <div class="checkboxes">
          <label for="si"><input type="checkbox" id="si" {{$encuesta['club_jugadores'] == 'SI' ? 'checked' : ''}}/> <span> Sí</span></label><br>
          <label for="no"><input type="checkbox" id="no" {{$encuesta['club_jugadores'] == 'NO' ? 'checked' : ''}}/> <span> No</span></label><br>
        </div><br>

        <!-- PREGUNTA N°6 -->
        <p>6. ¿Considera que su decisión de autoexcluirse responde a problemas de autocontrol sobre el juego?</p>
        <div class="checkboxes">
          <label for="si"><input type="checkbox" id="si" {{$encuesta['autocontrol_juego'] == 'SI' ? 'checked' : ''}}/> <span> Sí</span></label><br>
          <label for="no"><input type="checkbox" id="no" {{$encuesta['autocontrol_juego'] == 'NO' ? 'checked' : ''}}/> <span> No</span></label><br>
        </div><br>

        <!-- PREGUNTA N°7 -->
        <p>7. ¿Desea recibir información sobre Juego Responsable?</p>
        <div class="checkboxes">
          <label for="si"><input type="checkbox" id="si" {{$encuesta['recibir_informacion'] == 'SI' ? 'checked' : ''}}/> <span> Sí</span></label><br>
          <label for="no"><input type="checkbox" id="no" {{$encuesta['recibir_informacion'] == 'NO' ? 'checked' : ''}}/> <span> No</span></label><br>
        </div>

        <p>- Si está de acuerdo con recibir más información, ¿por qué medio le gustaría recibirlo?</p>
        <div class="checkboxes">
          <label for="telefono"><input type="checkbox" id="telefono" {{($encuesta['medio_recibir_informacion'] != -1 && is_numeric($encuesta['medio_recibir_informacion'])) ? 'checked' : ''}}/>
            <span>
              Teléfono &nbsp;&nbsp;
              {{($encuesta['medio_recibir_informacion'] != -1 && is_numeric($encuesta['medio_recibir_informacion'])) ?
              '('.$encuesta['medio_recibir_informacion'].')' : '...................................................'}}
            </span>
          </label><br>
          <label for="correo"><input type="checkbox" id="correo" {{($encuesta['medio_recibir_informacion'] != -1 && strpos($encuesta['medio_recibir_informacion'], '@') != false) ? 'checked' : ''}}/>
            <span>
              Correo electrónico &nbsp;&nbsp;
              {{($encuesta['medio_recibir_informacion'] != -1 && strpos($encuesta['medio_recibir_informacion'], '@') != false) ?
              '('.$encuesta['medio_recibir_informacion'].')' : '...................................................'}}
            </span>
          </label><br>
          <label for="otro"><input type="checkbox" id="otro" {{($encuesta['medio_recibir_informacion'] != -1 && !is_numeric($encuesta['medio_recibir_informacion']) && strpos($encuesta['medio_recibir_informacion'], '@') == false) ? 'checked' : ''}}/>
            <span> Otro medio &nbsp;&nbsp;
              {{($encuesta['medio_recibir_informacion'] != -1 && !is_numeric($encuesta['medio_recibir_informacion']) && strpos($encuesta['medio_recibir_informacion'], '@') == false) ?
              '('.$encuesta['medio_recibir_informacion'].')' : '...................................................'}}
            </span>
          </label>

        </div>

      </form>
    </div>

  </body>
</html>
