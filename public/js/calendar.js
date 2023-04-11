$(document).ready(function(){

  //Prueba///////////////////////////////////////////
  // $('#diseñoCrearEvento').modal('show');
////////////////////////////////////////////////////


  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $('.tituloSeccionPantalla').text('Calendario');

  // $('#horaDesde').datetimepicker({
  //   language:  'es',
  //   autoclose: 1,
  //   todayHighlight: 1,
  //   format: 'dd MM yyyy HH:ii',
  //   pickerPosition: "bottom-left",
  //   startView: 3,
  //   minView: 0,
  //   container: $('#modalEvento'),
  // });
  // $('#horaHasta').datetimepicker({
  //   language:  'es',
  //   autoclose: 1,
  //   todayHighlight: 1,
  //   format: 'HH:ii',
  //   pickerPosition: "bottom-left",
  //   startView: 3,
  //   minView: 0,
  //   container: $('#modalEvento'),
  // });
  $('#hastaFecha').datetimepicker({
    language:  'es',
    autoclose: 1,
    todayBtn: 1,
    todayHighlight: 1,
    format: 'dd MM yyyy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    container: $('#diseñoCrearEvento'),
  });

  $('#desdeHora').datetimepicker({
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'HH:ii',
    pickerPosition: "bottom-left",
    startView: 1,
    minView: 0,
    maxView: 1,
    container: $('#diseñoCrearEvento'),
  });

  $('#hastaHora').datetimepicker({
    language:  'es',
    autoclose: 1,
    todayHighlight: 1,
    format: 'HH:ii',
    pickerPosition: "bottom-left",
    startView: 1,
    minView: 0,
    maxView: 1,
    container: $('#diseñoCrearEvento'),
  });


//CONFIGURACIÓN DE MOMENTJS:
  (function (factory) {
      factory(moment);
  }(function (moment) {

      var monthsShortDot = 'Ene._Feb._Mar._Abr._May._Jun._Jul._Ago._Sep._Oct._Nov._Dic.'.split('_'),
          monthsShort = 'Ene_Feb_Mar_Abr_May_Jun_Jul_Ago_Sep_Oct_Nov_Dic'.split('_');

      return moment.defineLocale('es', {
          months : 'ENERO_FEBRERO_MARZO_ABRIL_MAYO_JUNIO_JULIO_AGOSTO_SEPTIEMBRE_OCTUBRE_NOVIEMBRE_DICIEMBRE'.split('_'),
          monthsShort : function (m, format) {
              if (/-MMM-/.test(format)) {
                  return monthsShort[m.month()];
              } else {
                  return monthsShortDot[m.month()];
              }
          },
          weekdays : 'DOMINGO_LUNES_MARTES_MIÉRCOLES_JUEVES_VIERNES_SÁBADO'.split('_'),
          weekdaysShort : 'DOM_LUN_MAR_MIÉ_JUE_VIE_SÁB'.split('_'),
          weekdaysMin : 'Do_Lu_Ma_Mi_Ju_Vi_Sá'.split('_'),
          longDateFormat : {
              LT : 'H:mm',
              LTS : 'LT:ss',
              L : 'DD/MM/YYYY',
              LL : 'D [de] MMMM [de] YYYY',
              LLL : 'D [de] MMMM [de] YYYY LT',
              LLLL : 'dddd, D [de] MMMM [de] YYYY LT'
          },
          calendar : {
              sameDay : function () {
                  return '[hoy a la' + ((this.hours() !== 1) ? 's' : '') + '] LT';
              },
              nextDay : function () {
                  return '[mañana a la' + ((this.hours() !== 1) ? 's' : '') + '] LT';
              },
              nextWeek : function () {
                  return 'dddd [a la' + ((this.hours() !== 1) ? 's' : '') + '] LT';
              },
              lastDay : function () {
                  return '[ayer a la' + ((this.hours() !== 1) ? 's' : '') + '] LT';
              },
              lastWeek : function () {
                  return '[el] dddd [pasado a la' + ((this.hours() !== 1) ? 's' : '') + '] LT';
              },
              sameElse : 'L'
          },
          relativeTime : {
              future : 'en %s',
              past : 'hace %s',
              s : 'unos segundos',
              m : 'un minuto',
              mm : '%d minutos',
              h : 'una hora',
              hh : '%d horas',
              d : 'un día',
              dd : '%d días',
              M : 'un mes',
              MM : '%d meses',
              y : 'un año',
              yy : '%d años'
          },
          ordinalParse : /\d{1,2}º/,
          ordinal : '%dº',
          week : {
              dow : 7, // Monday is the first day of the week.
              doy : 4  // The week that contains Jan 4th is the first week of the year.
          }
      });
  }));

  //VARIABLE PARA IDENTIFICAR EL ROL DEL USUARIO
  var esAdmin=$('#calendar').hasClass('admin'); console.log('dd',esAdmin);

  if(esAdmin){//SI ES ADMINISTRADOR O SUPERUSUARIO

    var calendar = $('#calendar').fullCalendar({
        // assign calendar
        locale: 'es',
        header:{
          left: '',
          center: 'title',
          right:'prev,next today',
        },
        googleCalendarApiKey: 'AIzaSyAtOBqjaKwycXtSb_H1GXhbPBusz64ZCX4',
        events: {
          googleCalendarId: 'es.ar#holiday@group.v.calendar.google.com',
          color: 'red',
          textColor: 'white',
        },
        editable: true,
        selectable: true,
        allDaySlot: false,
        //events: "index.php?view=1",  request to load current events

        // eventTextColor:'#000',

        //SELECCIONA UN EVENTO
        eventClick:  function(event, jsEvent, view) {

            $('#mensajeExito').hide();
            $('#mensajeError').hide();

            $('#btn-eliminarEvento').val(event.id);//guardo el id en el modal para desp eliminarlo

            $.get('calendario_eventos/getEvento/' + event.id, function(data){
                console.log('getEv:',data);

                $('#diseñoCalendario .contenedorEvento .tituloEvento').text(data.evento.titulo);
                $('#diseñoCalendario .contenedorEvento .descripcionEvento').text(data.evento.descripcion);

                $('#diseñoCalendario .casinoEvento').text(data.casino.nombre);
                $('#diseñoCalendario .tipodeEvento').text(data.tipo_evento.descripcion);


                var fecha_inicio = moment(data.evento.fecha_inicio).format('dddd, DD MMMM YYYY');
                var fecha_fin = moment(data.evento.fecha_fin).format('dddd, DD MMMM YYYY');
                var desde = data.evento.hora_inicio;
                var hasta = data.evento.hora_fin;

                if (desde == null) desde = "Todo el día"
                if (hasta == null) hasta = "Todo el día"

                $('#diseñoCalendario .contenedorFecha .fechaInicio .fecha').text(fecha_inicio);
                $('#diseñoCalendario .contenedorFecha .fechaFin .fecha').text(fecha_fin);

                $('#diseñoCalendario .contenedorFecha .horaInicio p').text(desde);
                $('#diseñoCalendario .contenedorFecha .horaFin p').text(hasta);


                $('#diseñoCalendario').modal('show');
              })
        },
        //SELECCIONA UN DÍA SIN EVENTO
        select: function(start, end, jsEvent) {
              //Nuevo
              $('#diseñoCrearEvento').modal('show');

              //Ocultar mensajes
              $('#alertaDestinatario').hide();
              $('#alertaTipoEvento').hide();
              ocultarErrorValidacion($('#tituloEvento'));
              ocultarErrorValidacion($('#descripcionEvento'));
              ocultarErrorValidacion($('#hastaFecha input'));

              //Limpiar modal
              $('#tituloEvento').val('');
              $('#descripcionEvento').val('');
              $('#diseñoCrearEvento #casinoEvento option').remove();
              $('#destinatariosEvento li').remove();
              $('#tiposEvento li').remove();
              $('#desde_fecha').val('');
              $('#hasta_fecha').val('');
              $('#desde_hora').val('');
              $('#hasta_hora').val('');
              $('#desdeHora input').val('');
              $('#hastaHora input').val('');

              //Setear fecha (las fecha inicial y final es la misma. Solo se puede editar la final).
              $('#desdeFecha').datetimepicker({
                language:  'es',
                autoclose: 1,
                todayBtn: 1,
                todayHighlight: 1,
                format: 'dd MM yyyy',
                pickerPosition: "bottom-left",
                startView: 2,
                minView: 2,
                container: $('#diseñoCrearEvento'),
              });

              $('#desdeFecha').datetimepicker('update', end._d);
              $('#desdeFecha').children('input').prop('disabled',true);
              $('#desdeFecha').datetimepicker('remove');

              $('#hastaFecha').datetimepicker('update', end._d);

              //Setear destinatarios, tipos de eventos y casinos
              $.get('calendario_eventos/getOpciones', function(data){
                console.log('get:',data);
                for (var i = 0; i < data.roles.length; i++) {
                  var fila = $('<li>');
                  var input = $('<input>').attr('type','checkbox').val(data.roles[i].id_rol);
                  var span = $('<span>').text(data.roles[i].descripcion);
                  fila.append(input).append(span);
                  $('#destinatariosEvento').append(fila);
                }

                for (var i = 0; i < data.casinos.length; i++) {
                  $('#diseñoCrearEvento #casinoEvento')
                  .append($('<option>').val(data.casinos[i].id_casino).text(data.casinos[i].nombre))
                };

                for (var i = 0; i < data.tipos_eventos.length; i++) {
                  var fila = $('<li>');
                  var input = $('<input>').attr('type','radio').val(data.tipos_eventos[i].id_tipo_evento).attr('name','tiposEventosRadio');
                  var icono = $('<i>').addClass('fa fa-circle').css('color', data.tipos_eventos[i].color_back);
                  var span = $('<span>').text(data.tipos_eventos[i].descripcion);
                  fila.append(input).append(icono).append(span);
                  $('#tiposEvento').append(fila);
                }

              });
        },
        //MUEVE EL EVENTO
        eventDrop: function(event, delta){ // event drag and drop
              console.log("Drop de evento: ", event);

              $('#mensajeExito').hide();
              $('#mensajeError').hide();

              var id_Event=event.id;

              $.ajaxSetup({

                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                  }
              });

              var inicio = new Date(moment(event.start._d).format('YYYY-MM-DD'));
              inicio.setDate(inicio.getDate() + 2);

              var formData = {
                  id: id_Event,
                  inicio: moment(inicio).format('YYYY-MM-DD'),
                  fin: moment(event.end._d).format('YYYY-MM-DD'),
                }

              $.ajax({

                  type: "POST",
                  url: 'calendario_eventos/modificarEvento',
                  data: formData,//'action=update&title='+event.title+'&start='+moment(event.start).format()+'&end='+moment(event.end).format()+'&id='+event.id,
                  dataType:'json',
                  success: function(data) {
                          console.log('exito!');
                          $('#mensajeExito h3').text('EVENTO MODIFICADO');
                          $('#mensajeExito p').text('El evento se cambió de fecha correctamente.');
                          $('#mensajeExito').show();
                          },
                  error: function(data){
                          console.log('error',data);
                          },
                });

        },
        //EXPANDE EL EVENTO
        eventResize: function(event) {  // resize to increase or decrease time of event
              console.log("Resize de evento: ", event);

              var id_event=event.id;

              $.ajaxSetup({
                  headers: {
                      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                  }
              });

              var inicio = new Date(moment(event.start._d).format('YYYY-MM-DD'));
              inicio.setDate(inicio.getDate() + 2);

              var formData = {
                  id: id_event,
                  inicio: moment(inicio).format('YYYY-MM-DD'),
                  fin: moment(event.end._d).format('YYYY-MM-DD'),
                }

              $.ajax({
                  type: "POST",
                  url: 'calendario_eventos/modificarEvento',
                  data: formData,//'action=update&title='+event.title+'&start='+moment(event.start).format()+'&end='+moment(event.end).format()+'&id='+event.id,
                  dataType:'json',
                  success: function(data) {
                            console.log('exito!',data);
                            $('#mensajeExito h3').text('EVENTO MODIFICADO');
                            $('#mensajeExito p').text('El evento se cambió de fecha correctamente.');
                            $('#mensajeExito').show();
                          },
                  error: function(data)
                          {
                            console.log('error',data);
                          },
                });
        }
    })//fin de inicializacion del calendario
  }//fin del if

    else{ //NO ES NI ADMINISTRADOR NI SUPERUSUARIO, SOLO VE LOS EVENTOS

        var  calendar = $('#calendar').fullCalendar({


          locale: 'es',
          header:{
            left: '',
            center: 'title',
            right:'prev,next today',
          },
          googleCalendarApiKey: 'AIzaSyAtOBqjaKwycXtSb_H1GXhbPBusz64ZCX4',
          events: {
            googleCalendarId: 'es.ar#holiday@group.v.calendar.google.com',
            color: 'red',
            textColor: 'white',
          },
          editable: true,
          selectable: true,
          allDaySlot: false,


            // // assign calendar
            // locale: 'es',
            // header:{
            //   eft: '',
            //   center: 'title',
            //   right:'prev,next today',
            // },
            // //defaultView: 'agendaWeek',
            // editable: true,
            // selectable: true,
            // allDaySlot: false,
            // events: "index.php?view=1",  // request to load current events
            // // eventTextColor:'#000',

            eventClick:  function(event, jsEvent, view) {  // when some one click on any event

                $.get('calendario_eventos/getEvento/' + event.id, function(data){
                    console.log('getEv:',data);


                    $('#diseñoCalendario .contenedorEvento .tituloEvento').text(data.evento.titulo);
                    $('#diseñoCalendario .contenedorEvento .descripcionEvento').text(data.evento.descripcion);

                    $('#diseñoCalendario .casinoEvento').text(data.casino.nombre);
                    $('#diseñoCalendario .tipodeEvento').text(data.tipo_evento.descripcion);

                    var fecha_inicio = moment(data.evento.fecha_inicio).format('dddd, DD MMMM YYYY');
                    var fecha_fin = moment(data.evento.fecha_fin).format('dddd, DD MMMM YYYY');
                    var desde = data.evento.hora_inicio;
                    var hasta = data.evento.hora_fin;

                    if (desde == null) desde = "Todo el día"
                    if (hasta == null) hasta = "Todo el día"

                    $('#diseñoCalendario .contenedorFecha .fechaInicio .fecha').text(fecha_inicio);
                    $('#diseñoCalendario .contenedorFecha .fechaFin .fecha').text(fecha_fin);

                    $('#diseñoCalendario .contenedorFecha .horaInicio p').text(desde);
                    $('#diseñoCalendario .contenedorFecha .horaFin p').text(hasta);

                    $('#diseñoCalendario').modal('show');
                  });

              }
        })//fin de inicializacion del calendario
    };//fin del else

  //botón guardar para crear el evento
  $('#btn-eventoNuevo').on('click', function(e){ // add event submit
     // We don't want this to act as a link so cancel the link action
      guardarEvento(); //fc que hace el post
  });

  //get para pintar todos los eventos del mes actual
  $.get('calendario_eventos/buscarEventos', function(data){
    console.log('buscar', data);
    for (var i = 0; i < data.eventos.length; i++) {

      var fecha_fin = new Date(data.eventos[i].fecha_fin);
      fecha_fin.setDate(fecha_fin.getDate() + 2); //Para que pinte bien el final

      $("#calendar").fullCalendar('renderEvent',
      {
        id:data.eventos[i].id_evento,
        // title:data.eventos[i].titulo.toUpperCase(),
        title:data.eventos[i].titulo,
        start:data.eventos[i].fecha_inicio,
        end:fecha_fin,
        color: data.eventos[i].fondo,
        textColor:data.eventos[i].texto,
        allDay:true,
      }, true);
    }
  });

  //botón para ir al siguiente mes
  $('.fc-next-button').on('click', mostrarMes);

//Botón para ir al mes anterior
  $('.fc-prev-button').on('click', mostrarMes);


//BORRAR UN EVENTO
  $('#btn-eliminarEvento').on('click', function(){

      $('#mensajeExito').hide();
      $('#mensajeError').hide();

      var id = $(this).val();

       $.get('calendario_eventos/eliminarEvento/' + id, function(data){
           $('#diseñoCalendario').modal('hide');
           $('#calendar').fullCalendar('removeEvents', [id]);
       });

       $('#mensajeExito h3').text('ÉXITO');
       $('#mensajeExito p').text('El evento se ha eliminado exitosamente');
       $('#mensajeExito').show();

  });

//AGREGO EL BOTÓN DE NUEVO TIPO DE EVENTO
if(esAdmin) {
    $('.fc-left').append($('<button>').text('+ TIPO DE EVENTO')
    .attr('id', 'nuevoTipo')
    .attr('type','button')
    .addClass('btn btn-infoBuscar'))

    $('#nuevoTipo').on('click', function(){
      $('#colorFondo').val('');
      $('#colorText').val('');
      $('#tipoNuevo').val('');
      $('#mensajeErrorCreacionTipo').hide();
      $('#modalTipoEv').modal('show');

    })
}

  $('#guardarTipo').on('click', function(){
    guardarTipoEvento();
  })
}); //fin document ready
// $('#modalTipoEv').on('hidden.bs.modal', function() {
//   $('#colorFondo').hide();
// });

function mostrarMes() {
  //obtengo la fecha que me esta mostrando el calendario
  var moment = $('#calendar').fullCalendar('getDate');
  var mes = moment.format('M');
  var anio = moment.format('YYYY');

    //me trae todos los eventos correspondientes a un mes y año determinado
    $.get('calendario_eventos/verMes/' + mes + '/' + anio, function(data){

      //Se eliminan los eventos para que no estén duplicados
      for (var i = 0; i < data.eventos.length; i++) {
        $('#calendar').fullCalendar('removeEvents', data.eventos[i].id_evento);
      }

      //Se pintan los evenos que me trae el get
      for (var i = 0; i < data.eventos.length; i++) {
        var fecha_fin = new Date(data.eventos[i].evento.fecha_fin);
        fecha_fin.setDate(fecha_fin.getDate() + 2); //Para que pinte bien el final

        $("#calendar").fullCalendar('renderEvent',
        {
          id:data.eventos[i].evento.id_evento,
          title:data.eventos[i].evento.titulo,
          start:data.eventos[i].evento.fecha_inicio,
          end:fecha_fin,
          color: data.eventos[i].tipo_evento.color_back,
          textColor: data.eventos[i].tipo_evento.color_text,
          allDay:true,
        }, true);
      }

    });
}

function guardarEvento(){ // add event
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $('#mensajeExito').hide();
  $('#mensajeErrorCreacion').hide();

    var title = $('#tituloEvento').val();
    var descripcion = $('#descripcionEvento').val();

    var desde_fecha = $('#desde_fecha').val();
    var hasta_fecha = $('#hasta_fecha').val();

    var desde_hora = $('#desde_hora').val();
    var hasta_hora = $('#hasta_hora').val();

    var casino = $('#casinoEvento').val();
    var tipo = $('input:radio[name="tiposEventosRadio"]:checked').val();

    //recupero los roles seleccionados en el modal
    var id_rol=[];
    $('#destinatariosEvento li input:checked').each(function(){
        id_rol.push($(this).val());
    });

    var formData = {
      titulo: title,
      descripcion: descripcion,
      id_tipo_evento:tipo,
      inicio: desde_fecha,
      fin: hasta_fecha,
      desde: desde_hora,
      hasta: hasta_hora,
      id_casino: casino,
      id_tipo_evento: tipo,
      id_rol: id_rol,
    }

    console.log(formData);

    $.ajax({
        type: 'POST',
        url: 'calendario_eventos/crearEvento',
        data: formData,
        dataType: 'json',

        success: function(data) {
           console.log(data);
           // $('#modalEvento').modal('hide');

           var fecha_fin = new Date(data.evento.fecha_fin);
           fecha_fin.setDate(fecha_fin.getDate() + 1); //Para que pinte bien el final

           $("#calendar").fullCalendar('renderEvent',
           {
               id: data.evento.id_evento,
               title: data.evento.titulo,
               start: data.evento.fecha_inicio,
               end: fecha_fin,
               color: data.tipo.color_back,
               textColor: data.tipo.color_text,
               // allDay:true,
           },	true);
          //  var ev=$('#calendar').fullCalendar('getEventSourceById',data.id_evento);
          //  ev.eventColor= '#000';//color.toUpperCase(),

          $('#diseñoCrearEvento').modal('hide');
          $('#mensajeExito h3').text('EVENTO GUARDADO');
          $('#mensajeExito p').text('Se creó con éxito el evento.');

          $('#mensajeExito').show();
        },
        error: function (data) {
          //Ocultar mensajes
          $('#alertaDestinatario').hide();
          $('#alertaTipoEvento').hide();
          ocultarErrorValidacion($('#tituloEvento'));
          ocultarErrorValidacion($('#descripcionEvento'));
          ocultarErrorValidacion($('#hastaFecha input'));

          var response = data.responseJSON.errors;

          if (typeof response.titulo != 'undefined') {
            mostrarErrorValidacion($('#diseñoCrearEvento #tituloEvento'), response.titulo[0], true);
          }
          if (typeof response.descripcion != 'undefined') {
            mostrarErrorValidacion($('#diseñoCrearEvento #descripcionEvento'), response.descripcion[0], true);
          }

          if (typeof response.fin != 'undefined') {
            mostrarErrorValidacion($('#diseñoCrearEvento #hastaFecha input'), response.fin[0], true);
          }

          if (typeof response.id_rol != 'undefined') {
            $('#alertaDestinatario').show();
          }
          if (typeof response.id_tipo_evento != 'undefined') {
            $('#alertaTipoEvento').show();
          }


          // $('#mensajeErrorCreacion').show();
          console.log('error:',data);
        },
    });
 };

//funcion para crear tipos de eventos nuevos
function guardarTipoEvento(){

  $('#mensajeExito').hide();


    var colorT= $('input:radio[name=colorText]:checked').val();
    var colorF= $('#colorFondo').val().toUpperCase();
    var descripcion = $('#tipoNuevo').val();


    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var formData = {
      descripcion: descripcion,
      fondo:colorF,
      texto:colorT
    }

    $.ajax({
        type: 'POST',
        url: 'calendario_eventos/crearTipoEvento',
        data: formData,
        dataType: 'json',

        success: function(data) {
           console.log(data);
           $('#modalTipoEv').modal('hide');

          $('#mensajeExito h3').text('TIPO DE EVENTO GUARDADO!');
        },
        error: function (data) {
          $('#mensajeErrorCreacionTipo').show();
          console.log('error:',data);
        },
    });
}
