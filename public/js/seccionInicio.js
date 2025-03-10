$(function(){
  $('.tituloSeccionPantalla').text('Inicio');
  $('#calendarioInicio').fullCalendar({
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
