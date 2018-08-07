$(document).ready(function(){
    //Agregar barra
    // $('#menu_inicio').parent().css('border-right','6px solid #3fbbff');
    $('.tituloSeccionPantalla').text('Inicio');
    $('#opcInicio').attr('style','border-left: 6px solid #185891;');
    $('#opcInicio').addClass('opcionesSeleccionado');



    var calendarioInicio = $('#calendarioInicio').fullCalendar({  // assign calendar
              locale: 'es',
              header:{
                left: 'prev,next',
                center: 'title',
                right: 'month'
              },
              //defaultView: 'agendaWeek',
              googleCalendarApiKey: 'AIzaSyAtOBqjaKwycXtSb_H1GXhbPBusz64ZCX4',
              events: {
                googleCalendarId: 'es.ar#holiday@group.v.calendar.google.com',
                color: 'red',
                textColor: 'white',
              },
              editable: true,
              selectable: true,
              allDaySlot: false,
              eventTextColor:'pink',
          });


});
