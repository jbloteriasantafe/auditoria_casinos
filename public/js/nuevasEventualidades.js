$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});

function convertirDateTime(input) {
  if (!input) return null;

  const meses = ['PAD','ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

  const [fechaPart, horaPart] = input.split(' ');

  const [year, month, day] = fechaPart.split('-');
  const mesAbrev = meses[parseInt(month, 10)];

  const fechaFormateada = `${day} ${mesAbrev} ${year}`;

  if (horaPart) {
    const [hh, mm] = horaPart.split(':');
    return `${fechaFormateada} ${hh}:${mm}`;
  }

  return fechaFormateada;
}



function cargarIntervenciones({ page = 1, perPage = 10, fecha,hasta, casino, turno, estado, observado }) {
  $.ajax({
    url: '/eventualidades/ultimas',
    data: {
      page,
      page_size: perPage,
      fecha,                     // yyyy-mm-dd
      hasta,
      id_casino: casino,
      nro_turno: turno,
      estado_eventualidad: estado,
      observados: observado
    },
    dataType: 'json',
    success(res) {
      // 1) Limpio tabla
      $('#cuerpoTablaEv').empty();

      // 2) Inserto filas
      res.intervenciones.forEach(ev => {
        $('#cuerpoTablaEv').append(generarFilaTabla(ev,res.controlador));
      });

      // 3) Renderizo paginación
      $('#herramientasPaginacion').generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndice
      );
      $('#herramientasPaginacion').generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndice
      );
    },
    error(err) {
      console.error('Error cargando eventualidades:', err);
    }
  });
}



var aEliminar=0;
var aVisar=0;


$(document).ready(function(){

  $('[data-toggle="tooltip"]').tooltip();

  cargarIntervenciones({ page:1, perPage:10 });

  $('.tituloSeccionPantalla').text('Eventualidades');

  $('#cargaInforme').on('fileerror', function(event, data, msg) {
    // get message
    alert(msg);
  });

  $('#alertaArchivo').hide();


  $('#dtpFechaEv').datetimepicker({
      language: 'es',
      format: 'yyyy-mm-dd',
      autoclose: true,
      todayBtn: true,
      minView: 2
    });

  $('#dtpFechaEvHasta').datetimepicker({
      language: 'es',
      format: 'yyyy-mm-dd',
      autoclose: true,
      todayBtn: true,
      minView: 2,
    });

  $('#evFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd HH:ii',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 0,
    ignoreReadonly: true,
    container:$('main section'),


  });

  clickIndice(null,
    $('#herramientasPaginacion').getCurrentPage(),
    $('#herramientasPaginacion').getPageSize());
});
$('#fechaEv').on('change', function (e) {
  $(this).trigger('focusin');
})
//botón para cargar informe dentro del modal de carga de eventualidades
$('#cargaArchivo').click(function(){
  $('#alertaArchivo').hide();
});

$('#B_fecha_ev').on('change', function (e) {
  $(this).trigger('focusin');
})


//CIERRA MODAL
$('#modalCargarEventualidad').on('hidden.bs.modal', function() {
    ocultarErrorValidacion($('.form-control'));
  $("#modalCargarEventualidad #cargaInforme").fileinput('destroy');
  $('#select_event').prop('disabled', false);

})

$('#cargaInforme').on('fileclear', function(event) {
  $('#cargaInforme').attr('data-borrado','true');
  $('#cargaInforme')[0].files[0] = null;
});

//boton borrar en fila
$(document).on('click','#btn-eliminarEvent',function(e){

    //se abre un modal de advertencia
    $('#modalEliminarEventualidad').modal('show');
    aEliminar= $(this);

});

//Si presiona el botón eliminar dentro del modal de advertencia
$('#btn-eliminarEventualidad').click(function (e){

  const id = aEliminar.val();

  $.get('eventualidades/eliminarEventualidad/' + id, function(data){

      if(data==1){
        $('#btn-buscarEventualidades').click();
        $('#modalEliminarEventualidad').modal('hide');
     }

  }) //fin del get

});

//boton visar en fila
$(document).on('click','.btn-visarEvent',function(e){

    //se abre un modal de advertencia
    $('#modalVisarEventualidad').modal('show');
    aVisar= $(this).val();

});

//boton agegar observacion en fila
$(document).on('click','#btn-obs',function(e){

  const evId = $(this).data('id');
   $('#obs_event_id').val(evId);
   $('#obs_event_id_file').val(evId);
    //se abre un modal para la generacion de la obs
    $('#modalObservacion').modal('show');

});

$('#btn-visarEventualidad').click(function(e) {
  e.preventDefault();

  if (!aVisar) return;
  $.get(`/eventualidades/visarEventualidad/${aVisar}`, function(ok) {
    if (ok == 1) {
      $('#btn-buscarEventualidades').click();    // recarga la tabla
    } else {
      alert('No se pudo visarla. ¿Tienes permiso?');
    }
    $('#modalVisarEventualidad').modal('hide');
  }).fail(() => {
    alert('Error en la petición al servidor.');
    $('#modalVisarEventualidad').modal('hide');
  });
});

function clickIndice(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  const filtros = leerFiltros();
  cargarIntervenciones({
    ...filtros,
    page:     pageNumber,
    perPage:  pageSize
  });
}

function limpiarNull(s){
  return s === null? '-' : s;
}

function leerFiltros() {
  return {
    fecha : $('#B_fecha_ev').val() || undefined,
    hasta : $('#B_fecha_evhasta').val() || undefined,
    casino: $('#B_CasinoEv').val() || undefined,
    turno : $('#B_TurnoEventualidad').val() || undefined,
    estado: $('#B_Estado').val() || undefined,
    observado: $('#B_Observado').is(':checked') ? 1 : 0
  };
}

//Se generan filas en la tabla principal con las eventualidades encontradas
function generarFilaTabla(ev,controlador) {
  const fila = $('<tr>').attr('id', ev.id_eventualidades);

  // 1) Columnas fijas: FECHA, CASINO, TURNO, HORA, ESTADO
  const fecha = ev.fecha_toma ? convertirDateTime(ev.fecha_toma) : '-';
  const casino= ev.casino ? ev.casino.nombre : '-';
  const turno = ev.turno ? ev.turno.nro_turno : '-';
  const hora  = ev.horario || '-';
  const estado = ev.estado_eventualidad;

  fila
    .append($('<td>').addClass('col-xs-2').html(fecha))
    .append($('<td>').addClass('col-xs-2').text(casino))
    .append($('<td>').addClass('col-xs-2').text(turno))
    .append($('<td>').addClass('col-xs-2').text(hora));

    let estadoIcon, estadoTexto;
    switch (ev.estado_eventualidad) {
      case 1:
        estadoIcon  = '<i class="fas fa-fw fa-dot-circle iconoEstado faGenerado"></i>';
        estadoTexto = 'Generado';
        break;
      case 2:
        estadoIcon  = '<i class="fas fa-fw fa-dot-circle iconoEstado faValidado"></i>';
        estadoTexto = 'Firmado';
        break;
      case 3:
        estadoIcon  = '<i class="fas fa-fw fa-dot-circle iconoEstado faVisado"></i>';
        estadoTexto = 'Visado';
        break;
      default:
        estadoIcon  = '<i class="fa fa-fw fa-question"></i>';
        estadoTexto = 'Desconocido';
    }

    const estadoHtml = `<span>${estadoIcon} ${estadoTexto}</span>`;


    fila.append($('<td>').addClass('col-xs-2').html(estadoHtml));


  // Columna de ACCIONES
  const tdAcc = $('<td>').addClass('col-xs-2 d-flex flex-wrap');

  // IMPRIMIR

  //**GENERADO**
  if (estado === 1) {
    const btnPrint = $('<button>')
      .addClass('btn btn-info btn-sm mr-1')
      .attr('id', 'btn_imprimirEvent')
      .attr('value', ev.id_eventualidades)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'DESCARGAR PDF')
      .append($('<i>').addClass('fa fa-print'))
      .click(() => window.open(`/eventualidades/pdf/${ev.id_eventualidades}`, '_blank'));
    tdAcc.append(btnPrint).append($('<span>').text(' '));
    //BORRAR
    if(controlador){
      const btnDelete = $('<button>')
        .addClass('btn btn-danger btn-sm')
        .attr('id', 'btn-eliminarEvent')
        .attr('value', ev.id_eventualidades)
        .attr('data-toggle', 'tooltip')
        .attr('data-placement','bottom')
        .attr('title', 'ELIMINAR EVENTUALIDAD')
        .append($('<i>').addClass('fa fa-trash'));
      tdAcc.append(btnDelete);
    }
  } else if (estado === 2) {
    // **Firmado**: VER, VALIDAR (si rol), BORRAR
    const pdfUrl = `/eventualidades/visualizarArchivo/firmado/${ev.id_archivo}`;
    const btnView = $('<a>')
      .addClass('btn btn-success btn-sm mr-1')
      .attr('href', pdfUrl)
      .attr('target', '_blank')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER PDF')
      .append($('<i>').addClass('fa fa-fw fa-search-plus'));
    tdAcc.append(btnView);

    const btnPrint = $('<button>')
      .addClass('btn btn-info btn-sm mr-1')
      .attr('id', 'btn_imprimirEvent')
      .attr('value', ev.id_eventualidades)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'DESCARGAR PDF')
      .append($('<i>').addClass('fa fa-print'))
      .click(() => window.open(`/eventualidades/pdf/${ev.id_eventualidades}`, '_blank'));
    tdAcc.append(btnPrint).append($('<span>').text(' '));

    // — VISAR (sólo para superusuarios o admins)
    if (controlador) {
      const btnVisar = $('<button>')
        .addClass('btn btn-success btn-sm mr-1 btn-visarEvent')
        .attr('value', ev.id_eventualidades)
        .attr('data-toggle', 'tooltip')
        .attr('data-placement','bottom')
        .attr('title', 'VISAR EVENTUALIDAD')
        .append($('<i>').addClass('fa fa-check'))
        tdAcc.append(btnVisar).append($('<span>').text(' '));


    // — BORRAR
    const btnDelete = $('<button>')
      .addClass('btn btn-danger btn-sm')
      .attr('id', 'btn-eliminarEvent')
      .attr('value', ev.id_eventualidades)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'ELIMINAR EVENTUALIDAD')
      .append($('<i>').addClass('fa fa-trash'))
    tdAcc.append(btnDelete);
  }
  } else if(estado===3){
    //visado
    const pdfUrl = `/eventualidades/visualizarArchivo/visado/${ev.id_archivo}`;
    const btnView = $('<a>')
      .addClass('btn btn-success btn-sm mr-1')
      .attr('href', pdfUrl)
      .attr('target', '_blank')
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'VER PDF')
      .append($('<i>').addClass('fa fa-fw fa-search-plus'));
    tdAcc.append(btnView);

    if(controlador){
    const btnDelete = $('<button>')
      .addClass('btn btn-danger btn-sm')
      .attr('id', 'btn-eliminarEvent')
      .attr('value', ev.id_eventualidades)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'ELIMINAR EVENTUALIDAD')
      .append($('<i>').addClass('fa fa-trash'))

    tdAcc.append(btnDelete);

    const butObs = $('<button>')
      .addClass('btn btn-info btn-sm')
      .attr('id', 'btn-obs')
      .attr('data-id', ev.id_eventualidades)
      .attr('data-toggle', 'tooltip')
      .attr('data-placement','bottom')
      .attr('title', 'AGREGAR OBSERVACIÓN')
      .append($('<i>').addClass('fa fa-edit'))

    tdAcc.append(butObs);
  }
  const butVerObs = $('<button>')
  .addClass('btn btn-warning btn-sm mr-1 btn-verObs')
  .attr('data-id', ev.id_eventualidades)
  .attr('data-toggle', 'tooltip')
  .attr('data-placement','bottom')
  .attr('title', 'VER OBSERVACIONES')
  .append($('<i>').addClass('fa fa-eye'));
tdAcc.append(butVerObs);

} else {
  const btnDelete = $('<button>')
    .addClass('btn btn-danger btn-sm')
    .attr('id', 'btn-eliminarEvent')
    .attr('value', ev.id_eventualidades)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'ELIMINAR EVENTUALIDAD')
    .append($('<i>').addClass('fa fa-trash'))
  tdAcc.append(btnDelete);
}


  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]').tooltip();
  return fila;
}

$(document).on('click', '#subirEv', function (){

  var $form = $('#formSubirEventualidad');
  let valid = true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  var $archivo = $form.find('input[name="upload"]');
  if(!$archivo.val()){
    $archivo.closest('.col-md-9')
            .addClass('has-error')
            .append('<span class="help-block js-error">Es requerido subir un archivo.</span>');
            valid=false;
  }

  if(!valid){
    return;
  }

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/eventualidades/subirEventualidad',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (respuesta) {
      if (respuesta.cod === 3) {
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">Esa eventualidad ya fue visada.</span>');
        return;
      }
      if (respuesta.cod === 4) {
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">El archivo no es original.</span>');
        return;
      }
      console.log("Guardado exitoso", respuesta);
      setTimeout(() => $('#modalSubirEventualidad').modal('hide'), 100);
      $('#mensajeExito').hide();
      $('#mensajeExito h3').text('EVENTUALIDAD subida');
      $('#mensajeExito p').text('');
      $('#mensajeExito').show();
      $('#btn-buscarEventualidades').click();
    },
    error: function (xhr) {
      const res= xhr.responseJSON || {};
      console.error("Error al guardar:", xhr);
      if(res.cod===1){
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">Esa eventualidad no existe.</span>');
      }
      if(res.cod===3){
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">Title inválido.</span>');
      }
      if(res.cod===2){
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">No existe eventualidad con esa ID.</span>');
      }
      else if(res.cod===4){
        $archivo.closest('.col-md-9')
                .addClass('has-error')
                .append('<span class="help-block js-error">Ese archivo no es original.</span>');
      }
      else{
        $archivo.closest('.col-md-9')
              .addClass('has-error')
              .append('<span class="help-block js-error">Error subiendo eventualidad.</span>');
            }
      $('#mensajeExitoCarga').attr('hidden', true);
      $('#mensajeErrorCarga').removeAttr('hidden');
    }
  });

})

$(document).on('click', '#subirObs', function (){

  var $form = $('#formSubirObservacion');
  let valid = true;

  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  var $archivo = $form.find('input[name="uploadObs"]');
  if(!$archivo.val()){
    $archivo.closest('.col-sm-offset-1')
            .addClass('has-error')
            .append('<span class="help-block js-error">Es requerido subir un archivo.</span>');
            valid=false;
  }

  if(!valid){
    return;
  }

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/eventualidades/subirObservacion',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (respuesta) {


      console.log("Guardado exitoso", respuesta);


      setTimeout(() => $('#modalObservacion').modal('hide'), 100);
      $('#mensajeExito').hide();
      $('#mensajeExito h3').text('OBSERVACIÓN añadida');
      $('#mensajeExito p').text('');
      $('#mensajeExito').show();
      $('#btn-buscarEventualidades').click();

    },
    error(xhr) {
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        // Recorremos cada error de validación
        const errors = xhr.responseJSON.errors;
        Object.keys(errors).forEach(field => {
          const msgs = errors[field];
          // buscamos el input por name
          const $field = $form.find(`[name="${field}"]`);
          const $group = $field.closest('.col-sm-offset-1');
          $group.addClass('has-error');
          msgs.forEach(msg => {
            $group.append(`<span class="help-block js-error">${msg}</span>`);
          });
        });
      } else {
        // error genérico
        $archivo.closest('.col-sm-offset-1')
                .addClass('has-error')
                .append('<span class="help-block js-error">Error inesperado. Intente de nuevo.</span>');
      }
    }
  });
});


$(document).on('click', '#guardarEv', function () {
  var $form = $('#formNuevaEventualidad');
  var valid = true;

  // 1) Limpio errores previos
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  // 2) Valido Casino
  var $casino = $form.find('select[name="id_casino"]');
  if (!$casino.val()) {
    $casino.closest('.col-md-4')
           .addClass('has-error')
           .append('<span class="help-block js-error">El casino es requerido.</span>');
    valid = false;
  }

  // 3) Valido Turno
  var $turno = $form.find('select[name="turno"]');
  if (!$turno.val()) {
    $turno.closest('.col-md-4')
           .addClass('has-error')
           .append('<span class="help-block js-error">El turno es requerido.</span>');
    valid = false;
  }


  // 4) Valido Fecha de intervención (fecha_toma)
  const $fechaToma = $form.find('input[name="fecha_toma"]');
  if (!$fechaToma.val()) {
    $fechaToma.closest('.col-md-4')
               .addClass('has-error')
               .append('<span class="help-block js-error">La fecha es requerida.</span>');
    valid = false;
  }

  // 4.5 valido Fiscalizadores
  var $fiscales = $form.find('input[name="otros_fiscalizadores"]');
  if (!$fiscales.val().trim()) {
    $fiscales.closest('.col-md-8')
      .addClass('has-error')
      .append('<span class="help-block js-error">El/Los fiscalizador/es es/son requerido/s.</span>');
    valid = false;
  }
  $('#salir').next('.help-block.js-error').remove();

  // 5) Valido que cada fila de procedimientos tenga un radio marcado
  $form.find('table tbody tr').each(function(){
    var name = $(this).find('input[type=radio]').first().attr('name');
    if (name && !$form.find('input[name="'+name+'"]:checked').length) {
      // marco la celda de "✔"
      $(this).find('td').eq(0)
        .addClass('has-error')
        .append('<span class="help-block js-error">Requerido</span>');
      valid = false;
    }
  });

  // 6 valido el boletin adjunto
  var $boletin = $form.find('input[name="boletin_adjunto"]');
  if (!$boletin.val().trim()) {
    $boletin.closest('.col-md-6')
      .addClass('has-error')
      .append('<span class="help-block js-error">El boletín es requerido.</span>');
    valid = false;
  }
  var boletinText = $boletin.val() ? $boletin.val().trim() : '';
if (boletinText.length > 300) {
  // Quito errores anteriores
  $boletin.closest('.col-md-6')
    .find('.help-block.js-error').remove();

  // Marco el error
  $boletin.closest('.col-md-6')
    .addClass('has-error')
    .append('<span class="help-block js-error text-danger">El máximo de caracteres es de 300.</span>');
  valid = false;
}

  // 7) Si hay errores, no seguimos al AJAX
  if (!valid) {
    return;
  }

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: '/eventualidades/guardarEventualidad',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (respuesta) {
      console.log("Guardado exitoso", respuesta);
      $('#mensajeExitoCarga').removeAttr('hidden');
      $('#mensajeErrorCarga').attr('hidden', true);
      window.open('/eventualidades/pdf/' + respuesta.id, '_blank');
      setTimeout(() => $('#modalCargarEventualidad').modal('hide'), 1000);
      $('#btn-buscarEventualidades').click();
    },
    error: function (xhr) {
      $('#salir').next('.help-block.js-error').remove();
      $('#salir')
        .after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
      $('#mensajeExitoCarga').attr('hidden', true);
      $('#mensajeErrorCarga').removeAttr('hidden');
    }
  });
});

$(document).on('click', '#guardarObs', function () {
  var $form = $('#formNuevaObservacion');
  var valid = true;

  // 1) Limpio errores previos
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();

  // Valido observación
  var $obs = $form.find('textarea[name="observacion"]');
  if (!$obs.val()) {
    $obs.closest('.col-md-12')
        .addClass('has-error')
        .append('<span class="help-block js-error">La observación no puede ser vacía.</span>');
    valid = false;
  }

  // Si hay errores, no seguimos al AJAX
  if (!valid) {
    return;
  }

  var formData = new FormData($form[0]);

  $.ajax({
    url: '/eventualidades/guardarObservacion',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',               // <-- esperamos JSON
    headers: {
      'Accept': 'application/json'  // <-- forzamos respuesta JSON
    },
    success: function (respuesta) {
      console.log("Guardado exitoso", respuesta);
      $('#mensajeExitoCarga').removeAttr('hidden');
      $('#mensajeErrorCarga').attr('hidden', true);
      setTimeout(() => $('#modalObservacion').modal('hide'), 2000);
      $('#btn-buscarEventualidades').click();
    },
    error: function (xhr) {
      // Si viene error de validación 422 de Laravel
      if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
        // mostramos el primer mensaje de validación para "observacion"
        var msg = xhr.responseJSON.errors.observacion
                 ? xhr.responseJSON.errors.observacion[0]
                 : 'Ocurrió un error en la validación.';
        $obs.closest('.col-md-12')
            .addClass('has-error')
            .append('<span class="help-block js-error">' + msg + '</span>');
      } else {
        // otro tipo de error genérico
        $obs.closest('.col-md-12')
            .addClass('has-error')
            .append('<span class="help-block js-error">Ocurrió un error inesperado.</span>');
        console.error("Error al guardar:", xhr);
      }
      $('#mensajeExitoCarga').attr('hidden', true);
      $('#mensajeErrorCarga').removeAttr('hidden');
    }
  });
});


$(function(){
  // Cuando cambias el casino, cargas turnos “normales”
  $('#formNuevaEventualidad').on('change', 'select[name="id_casino"]', function(){
    const idCasino = $(this).val();
    const $turno   = $('#formNuevaEventualidad').find('select[name="turno"]');

    $turno
      .empty()
      .append('<option value="">- Seleccione un turno -</option>');

    if (!idCasino) return;

    $.getJSON(`/eventualidades/obtenerTurnos/${idCasino}`, json => {
      json.turnos.forEach(t => {
        $turno.append(
          $('<option>')
            .val(t.id_turno)
            .text(t.nro_turno)
            .attr('data-horario', t.entrada + ' a ' + t.salida)
        );
      });
    });
  });

  $('#formNuevaEventualidad').on('change', 'select[name="turno"]', function() {
    const idCasino = $('#formNuevaEventualidad')
                      .find('select[name="id_casino"]').val();
    const turnoSel = $(this).val();
    const $horario = $('#formNuevaEventualidad')
                      .find('input[name="horario"]');

    if (turnoSel === '3') {
      $.getJSON(`/eventualidades/obtenerTurnos/${idCasino}?useYesterday=1`, json => {
        const t = json.turnos.find(x => x.nro_turno == 3);
        if (t) {
          $horario.val(t.entrada + ' a ' + t.salida);
        } else {
          $horario.val('');
        }
      });
    } else {
      const horario = $(this).find('option:selected').data('horario') || '';
      $horario.val(horario);
    }
  });
});

$('#btn-buscarEventualidades').click(function(e){
  e.preventDefault();
  const filtros = leerFiltros();
  cargarIntervenciones({
    page: 1,
    perPage: $('#herramientasPaginacion').getPageSize(),
    ...filtros
  });
});

$(document).on('click', '.btn-verObs', function(){
  const evId = $(this).data('id');
  const $ul  = $('#listaPdfs')
                 .empty()
                 .append('<li class="list-group-item">Cargando…</li>')
                 .data('ev-id', evId)
                 .addClass('list-group');

  $.getJSON(`/eventualidades/${evId}/observaciones`, data => {
    const obs = data.obs;
    const esControlador = data.controlador === 1;
    $ul.empty();

    if (!obs.length) {
      return $ul.append('<li class="list-group-item">No hay observaciones.</li>');
    }

    obs.forEach(o => {
      if (!o.url) return;

      const $link = $('<a>')
        .attr('href', o.url)
        .attr('target','_blank')
        .text(o.id_archivo)
        .css({display:'inline-block', maxWidth:'calc(100% - 140px)', whiteSpace:'nowrap', overflow:'hidden', textOverflow:'ellipsis'});

      const $li = $('<li>')
        .addClass('list-group-item clearfix')
        .css({marginBottom:'8px'});

      $li.append($link);

      if (esControlador) {
        const $btnDeleteObs = $('<button>')
          .addClass('btn btn-danger btn-sm btn-deleteObs pull-right')
          .attr('data-id', o.id_observacion_eventualidades)
          .attr('data-toggle','tooltip')
          .attr('data-placement','bottom')
          .attr('title','ELIMINAR OBSERVACIÓN')
          .append($('<i>').addClass('fa fa-trash'));

        $li.append($btnDeleteObs);
      }

      $ul.append($li);
    });


  }).fail(() => {
    $ul.empty().append('<li class="text-danger">Error al cargar.</li>');
  });

  $('#modalVerObservaciones').modal('show');
});


//boton borrar en fila
$(document).on('click','.btn-deleteObs',function(e){

    aEliminar= $(this).data('id');

    $('#modalEliminarObservacion').modal('show');

});


$('#btn-eliminarObservacion').off('click').on('click', function(){
  $.ajax({
    url: `/eventualidades/observacion/${aEliminar}`,
    method: 'GET'
  }).done(res => {
    if (res == 1) {
      $('#modalEliminarObservacion').modal('hide');
      // vuelvo a disparar el click de verObs para recargar la lista
      const evId = $('#listaPdfs').data('ev-id');
      $(`.btn-verObs[data-id="${evId}"]`).trigger('click');
    } else {
    }
  }).fail(() => {

  });
});

$('#uploadObs').on('change', function() {
  var full = $(this).val().split('\\').pop();
  $('#fileNameObs').val(full || 'No se ha seleccionado ningún archivo');
});
