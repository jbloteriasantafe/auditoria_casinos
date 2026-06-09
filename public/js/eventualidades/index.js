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
    avisoEv(msg);
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
  // Al cerrar, suelto el borrador en edición (evita que un id_borrador viejo —ya finalizado—
  // se reenvíe en el próximo guardado). Continuar/Nueva vuelven a setearlo según corresponda.
  $('#id_borrador').val('');
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
        const paginaActual = $('#herramientasPaginacion').getCurrentPage();
        $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
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
      const paginaActual = $('#herramientasPaginacion').getCurrentPage();
      $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
    } else {
      avisoEv('No se pudo visarla. ¿Tienes permiso?');
    }
    $('#modalVisarEventualidad').modal('hide');
  }).fail(() => {
    avisoEv('Error en la petición al servidor.');
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
      case 0:
        estadoIcon  = '<i class="fas fa-fw fa-dot-circle iconoEstado faSinTerminar"></i>';
        estadoTexto = 'Sin terminar';
        break;
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

  if(ev.menores == 'Si' || ev.menores == 'Sí'){
    const $badge18 = $('<span>')
      .attr('data-toggle', 'tooltip')
      .attr('title', 'Menores en sala')
      .css({
         'display': 'inline-block',
         'position': 'relative',
         'width': '30px',
         'height': '30px',
         'border': '2px solid #d9534f', // Rojo
         'border-radius': '50%',
         'text-align': 'center',
         'line-height': '26px', // 30 - 4
         'margin-right': '5px',
         'color': '#000', // Negro
         'font-weight': 'bold',
         'font-family': 'Arial, sans-serif',
         'font-size': '12px',
         'vertical-align': 'middle',
         'cursor': 'default',
         'background-color': '#fff'
      })
      .text('+18');

      // La linea tachada (roja)
      const $linea = $('<div>').css({
        'position': 'absolute',
        'top': '50%',
        'left': '50%',
        'width': '26px',
        'height': '2px',
        'background-color': '#d9534f', // Rojo
        'transform': 'translate(-50%, -50%) rotate(45deg)'
      });

      $badge18.append($linea);
      tdAcc.append($badge18);
  }

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

  }

} else if (estado === 0) {
  // Borrador "sin terminar": continuar (reabre el form precargado) + eliminar.
  const btnContinuar = $('<button>')
    .addClass('btn btn-warning btn-sm mr-1 btn-continuarEvent')
    .attr('value', ev.id_eventualidades)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'CONTINUAR')
    .append($('<i>').addClass('fa fa-pencil-alt'));
  tdAcc.append(btnContinuar).append($('<span>').text(' '));
  const btnDelete = $('<button>')
    .addClass('btn btn-danger btn-sm')
    .attr('id', 'btn-eliminarEvent')
    .attr('value', ev.id_eventualidades)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement','bottom')
    .attr('title', 'ELIMINAR')
    .append($('<i>').addClass('fa fa-trash'));
  tdAcc.append(btnDelete);
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

// Botón VER / AGREGAR observaciones: disponible desde FIRMADA (estado 2) y en VISADA (3).
// (El modal #modalVerObservaciones ya trae el form de alta.)
if (estado === 2 || estado === 3) {
  const butVerObs = $('<button>')
    .addClass('btn btn-warning btn-sm mr-1 btn-verObs')
    .attr('data-id', ev.id_eventualidades)
    .attr('data-toggle', 'tooltip')
    .attr('data-placement', 'bottom')
    .attr('title', 'OBSERVACIONES')
    .append($('<i>').addClass('fa fa-comments'));
  tdAcc.append(butVerObs);
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
      // // cod 4 ("el archivo no es original") ya no lo devuelve el backend:
      // // se quitó la validación de "PDF original" en subirEventualidad.
      // if (respuesta.cod === 4) { ... }
      console.log("Guardado exitoso", respuesta);
      setTimeout(() => $('#modalSubirEventualidad').modal('hide'), 100);
      $('#mensajeExito').hide();
      $('#mensajeExito h3').text('EVENTUALIDAD subida');
      $('#mensajeExito p').text('');
      $('#mensajeExito').show();
      const paginaActual = $('#herramientasPaginacion').getCurrentPage();
      $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
    },
    error: function (xhr) {
      const res = xhr.responseJSON || {};
      console.error("Error al guardar:", xhr);
      // Mostramos directamente el mensaje del backend cuando viene (es el más preciso).
      // cod 2 = 404 (id detectado pero no existe) · cod 3 = 422 (no se pudo detectar el PDF).
      // // cod 1 ("no existe") y cod 4 ("no original") ya no los devuelve el backend.
      let msg;
      if (res.cod === 2) {
        msg = res.error || 'No existe eventualidad con esa ID.';
      } else if (res.cod === 3) {
        msg = res.error || 'No se pudo detectar a qué eventualidad pertenece el PDF.';
      } else {
        msg = res.error || 'Error subiendo eventualidad.';
      }
      $archivo.closest('.col-md-9')
              .addClass('has-error')
              .append('<span class="help-block js-error">' + msg + '</span>');
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
      const paginaActual = $('#herramientasPaginacion').getCurrentPage();
      $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);

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


// Valida el form. modo='final' (GENERAR, completo) | 'borrador' (GUARDAR TEMPORAL, relajado).
// En borrador sólo es obligatorio el encabezado (casino, turno, fecha) — el resto se completa después.
function validarFormEventualidad(modo) {
  var esBorrador = (modo === 'borrador');
  var $form = $('#formNuevaEventualidad');
  var valid = true;

  // Limpio errores previos
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error').remove();
  $form.find('.alert.js-error').remove();
  $('#tbodyProcedimientos > tr').removeClass('danger');

  // Casino (siempre)
  var $casino = $form.find('select[name="id_casino"]');
  if (!$casino.val()) {
    $casino.closest('.col-md-4').addClass('has-error')
           .append('<span class="help-block js-error">El casino es requerido.</span>');
    valid = false;
  }
  // Turno (siempre — columna NOT NULL)
  var $turno = $form.find('select[name="turno"]');
  if (!$turno.val()) {
    $turno.closest('.col-md-4').addClass('has-error')
           .append('<span class="help-block js-error">El turno es requerido.</span>');
    valid = false;
  }
  // Fecha de la eventualidad (siempre — columna NOT NULL)
  var $fechaToma = $form.find('input[name="fecha_toma"]');
  if (!$fechaToma.val()) {
    $fechaToma.closest('.col-md-4').addClass('has-error')
               .append('<span class="help-block js-error">La fecha es requerida.</span>');
    valid = false;
  }
  $('#salir').next('.help-block.js-error').remove();

  if (!esBorrador) {
    // Fiscalizadores (sólo en el guardado final)
    var $fiscales = $form.find('input[name="otros_fiscalizadores"]');
    if (!$fiscales.val().trim()) {
      $fiscales.closest('.col-md-8').addClass('has-error')
        .append('<span class="help-block js-error">El/Los fiscalizador/es es/son requerido/s.</span>');
      valid = false;
    }
    // Procedimientos: todos respondidos (sólo en el guardado final; en borrador se permite parcial)
    var $filasProc = $('#tbodyProcedimientos > tr').filter(function () {
      return $(this).find('input[type=radio]').length > 0;
    });
    if (!$filasProc.length) {
      $('#tbodyProcedimientos').closest('.table-responsive')
        .before('<div class="alert alert-danger js-error" style="margin-bottom:8px;">El casino seleccionado no tiene procedimientos asignados.</div>');
      valid = false;
    } else {
      var procIncompletos = 0;
      $filasProc.each(function () {
        var $tr = $(this);
        if (!$tr.find('input[type=radio]:checked').length) {
          $tr.addClass('danger');
          procIncompletos++;
        }
      });
      if (procIncompletos > 0) {
        $('#tbodyProcedimientos').closest('.table-responsive')
          .before('<div class="alert alert-danger js-error" style="margin-bottom:8px;">Debés marcar <strong>Realizado</strong> o <strong>No realizado</strong> en todos los procedimientos (' + procIncompletos + ' sin responder).</div>');
        valid = false;
      }
    }
  }

  // Boletín adjunto: opcional, sólo límite de longitud (siempre)
  var $boletin = $form.find('input[name="boletin_adjunto"]');
  var boletinText = $boletin.val() ? $boletin.val().trim() : '';
  if (boletinText.length > 300) {
    $boletin.closest('.col-md-6').addClass('has-error')
      .append('<span class="help-block js-error text-danger">El máximo de caracteres es de 300.</span>');
    valid = false;
  }

  return valid;
}

// Envía el form. esBorrador=true → estado 0 (sin terminar), no descarga PDF y deja el modal abierto.
function enviarEventualidad(esBorrador) {
  var $form = $('#formNuevaEventualidad');
  var formData = new FormData($form[0]); // incluye el hidden id_borrador
  formData.append('borrador', esBorrador ? 1 : 0);

  $.ajax({
    url: '/eventualidades/guardarEventualidad',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (respuesta) {
      $('#mensajeErrorCarga').attr('hidden', true);
      var paginaActual = $('#herramientasPaginacion').getCurrentPage();
      if (esBorrador) {
        // Borrador: guardo el id para seguir editando el MISMO, dejo el modal abierto.
        $('#id_borrador').val(respuesta.id);
        $('#guardarTemporal').next('.js-tempok').remove();
        $('#guardarTemporal').after('<span class="help-block js-tempok text-success" style="color:green;"> Guardado temporal ✔</span>');
        setTimeout(function () { $('#guardarTemporal').next('.js-tempok').remove(); }, 2500);
        $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
      } else {
        $('#mensajeExitoCarga').removeAttr('hidden');
        window.open('/eventualidades/pdf/' + respuesta.id, '_blank');
        setTimeout(function () { $('#modalCargarEventualidad').modal('hide'); }, 1000);
        $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
        $(document).trigger('reporteDiario:refresh');
      }
    },
    error: function (xhr) {
      $('#salir').next('.help-block.js-error').remove();
      $('#salir').after('<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>');
      console.error("Error al guardar:", xhr);
      $('#mensajeExitoCarga').attr('hidden', true);
      $('#mensajeErrorCarga').removeAttr('hidden');
    }
  });
}

// Deja el form en blanco para una eventualidad NUEVA (limpia el id_borrador y los procedimientos).
function resetFormEventualidad() {
  var $form = $('#formNuevaEventualidad');
  if ($form.length) $form[0].reset();
  $('#id_borrador').val('');
  $form.find('.has-error').removeClass('has-error');
  $form.find('.help-block.js-error, .alert.js-error, .js-tempok').remove();
  $('#tbodyProcedimientos > tr').removeClass('danger');
  $form.find('select[name="turno"]').empty().append('<option value="">- Seleccione un turno -</option>');
  $('#tbodyProcedimientos').empty().append('<tr><td colspan="3" class="text-center text-muted">Seleccione un casino para ver los procedimientos.</td></tr>');
  $('#modalCargarEventualidad .modal-title').text('| NUEVA EVENTUALIDAD');
}

// GENERAR (guardado final)
$(document).on('click', '#guardarEv', function () {
  if (validarFormEventualidad('final')) enviarEventualidad(false);
});

// GUARDAR TEMPORAL (borrador / "sin terminar")
$(document).on('click', '#guardarTemporal', function () {
  if (validarFormEventualidad('borrador')) enviarEventualidad(true);
});

// Abrir el modal para una eventualidad NUEVA → limpio el form (el modal lo abre data-toggle).
$(document).on('click', '#btnNuevaEventualidad', function () {
  resetFormEventualidad();
});

// CONTINUAR un borrador → traigo sus datos y precargo el form.
$(document).on('click', '.btn-continuarEvent', function () {
  var id = $(this).val();
  $.getJSON('/eventualidades/borrador/' + id)
    .done(function (d) {
      resetFormEventualidad();
      $('#modalCargarEventualidad .modal-title').text('| CONTINUAR EVENTUALIDAD');
      var $form = $('#formNuevaEventualidad');
      $('#id_borrador').val(d.id);
      $form.find('select[name="id_casino"]').val(d.id_casino);
      // Espero a que carguen turnos + procedimientos antes de setear los valores dependientes.
      $.when(cargarTurnosCasino(d.id_casino), cargarProcedimientosCasino(d.id_casino)).done(function () {
        $form.find('select[name="turno"]').val(d.id_turno);
        $form.find('input[name="horario"]').val(d.horario || '');
        $form.find('input[name="fecha_toma"]').val(d.fecha_toma ? (d.fecha_toma + '').substring(0, 16) : '');
        $form.find('select[name="menores"]').val(d.menores || 'No');
        $form.find('select[name="fumadores"]').val(d.fumadores || 'No');
        $form.find('input[name="boletin_adjunto"]').val(d.boletin_adjunto || '');
        $form.find('textarea[name="observaciones"]').val(d.observaciones || '');
        $form.find('input[name="otros_fiscalizadores"]').val(d.otros_fiscalizadores || '');
        var procs = d.procedimientos || {};
        Object.keys(procs).forEach(function (idProc) {
          var p = procs[idProc];
          if (p.estado === 'realizado' || p.estado === 'no_realizado') {
            var val = (p.estado === 'realizado') ? '✔' : '*';
            $('input[name="procedimientos[' + idProc + '][estado]"][value="' + val + '"]')
              .prop('checked', true).closest('label').addClass('active');
          }
          if (p.observacion) {
            $('input[name="procedimientos[' + idProc + '][observacion]"]').val(p.observacion);
          }
        });
      });
      $('#modalCargarEventualidad').modal('show');
    })
    .fail(function () { avisoEv('No se pudo cargar el borrador.'); });
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

      setTimeout(() => $('#modalObservacion').modal('hide'), 100);
      $('#mensajeExito').hide();
      $('#mensajeExito h3').text('OBSERVACIÓN añadida');
      $('#mensajeExito p').text('');
      $('#mensajeExito').show();
      const paginaActual = $('#herramientasPaginacion').getCurrentPage();
      $('#btn-buscarEventualidades').trigger('click', [{ page: paginaActual }]);
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


// Carga los turnos del casino en el select del form. Devuelve la promise del getJSON
// (para poder esperar a que termine al "Continuar" un borrador).
function cargarTurnosCasino(idCasino) {
  const $turno = $('#formNuevaEventualidad').find('select[name="turno"]');
  $turno.empty().append('<option value="">- Seleccione un turno -</option>');
  if (!idCasino) return $.Deferred().resolve().promise();
  return $.getJSON('/eventualidades/obtenerTurnos/' + idCasino, function (json) {
    json.turnos.forEach(function (t) {
      $turno.append(
        $('<option>').val(t.id_turno).text(t.nro_turno)
          .attr('data-horario', t.entrada + ' a ' + t.salida)
          // data-nro = nro_turno (1/2/3). El value es id_turno (PK), que NO coincide con
          // el nro de turno; el caso "noche" se detecta por nro_turno, no por el value.
          .attr('data-nro', t.nro_turno)
      );
    });
  });
}

// Carga los procedimientos del casino en la tabla del form. Devuelve la promise del getJSON.
function cargarProcedimientosCasino(idCasino) {
  const $tbody = $('#tbodyProcedimientos');
  $tbody.empty().append('<tr><td colspan="3" class="text-center text-muted">Cargando…</td></tr>');
  if (!idCasino) {
    $tbody.empty().append('<tr><td colspan="3" class="text-center text-muted">Seleccione un casino para ver los procedimientos.</td></tr>');
    return $.Deferred().resolve().promise();
  }
  return $.getJSON('/eventualidades/procedimientosPorCasino/' + idCasino, function (res) {
    $tbody.empty();
    if (!res.procedimientos.length) {
      $tbody.append('<tr><td colspan="3" class="text-center text-warning">'
        + 'Este casino no tiene procedimientos asignados. Solicitar al administrador.</td></tr>');
      return;
    }
    res.procedimientos.forEach(function (p) {
      const id = p.id_procedimiento;
      const nombre = $('<div>').text(p.nombre).html();
      $tbody.append(
        '<tr>' +
          '<td>' + nombre + '</td>' +
          '<td class="text-center" style="white-space:nowrap;">' +
            '<div class="btn-group btn-group-sm">' +
              '<label class="btn btn-default btn-respuesta-si">' +
                '<input type="radio" name="procedimientos[' + id + '][estado]" value="✔" autocomplete="off">' +
                '<i class="fa fa-check"></i>Realizado' +
              '</label>' +
              '<label class="btn btn-default btn-respuesta-no">' +
                '<input type="radio" name="procedimientos[' + id + '][estado]" value="*" autocomplete="off">' +
                '<i class="fa fa-times"></i>No realizado' +
              '</label>' +
            '</div>' +
          '</td>' +
          '<td><input type="text" class="form-control form-control-sm" name="procedimientos[' + id + '][observacion]" placeholder="Observación"></td>' +
        '</tr>'
      );
    });
  });
}

$(function(){
  // Cuando cambias el casino: cargo turnos + procedimientos del casino
  $('#formNuevaEventualidad').on('change', 'select[name="id_casino"]', function(){
    cargarTurnosCasino($(this).val());
    cargarProcedimientosCasino($(this).val());
  });

  // Toggle manual de la clase .active sobre los <label> del btn-group de procedimientos.
  // Bootstrap 3 data-toggle=buttons no bindea de forma confiable sobre nodos insertados
  // dinámicamente vía $tbody.append, así que lo manejamos a mano.
  $(document).on('click', '#tbodyProcedimientos label.btn', function (e) {
    var $lbl   = $(this);
    var $input = $lbl.find('input[type=radio]');
    if (!$input.length) return;
    var name = $input.attr('name');
    $('input[name="' + name + '"]').each(function () {
      $(this).prop('checked', false).closest('label').removeClass('active');
    });
    $input.prop('checked', true);
    $lbl.addClass('active');
    $lbl.closest('tr').removeClass('danger');
  });

  $('#formNuevaEventualidad').on('change', 'select[name="turno"]', function() {
    const idCasino = $('#formNuevaEventualidad')
                      .find('select[name="id_casino"]').val();
    const $sel     = $(this).find('option:selected');
    const nroTurno = parseInt($sel.data('nro'), 10); // nro_turno real (no el id_turno del value)
    const $horario = $('#formNuevaEventualidad')
                      .find('input[name="horario"]');

    if (nroTurno === 3) {
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

$('#btn-buscarEventualidades').on('click',function(e,opts){

  e.preventDefault();
  const filtros = leerFiltros();

  const page      = (opts && typeof opts.page !== 'undefined') ? opts.page : 1;
  cargarIntervenciones({
    page: page,
    perPage: $('#herramientasPaginacion').getPageSize(),
    ...filtros
  });
  $(document).trigger('reporteDiario:refresh');
});

