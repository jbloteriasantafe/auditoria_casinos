$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Mesas - Importaciones Diarias');
  $('#barraImportaciones').attr('aria-expanded','true');
  $('#barraImportaciones').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraImportaciones').addClass('opcionesSeleccionado');
  $('#pestImportaciones').show();
  $('#pestImportaciones').css('display','inline-block');
  
  limpiarFiltrosDiaria();

  $('#dtpFechaImp').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    container:$('#modalImportacionDiaria'),
  });

  $('#collapseFiltros').collapse('show');
  $('#buscar-importacionesDiarias').click();
});

function limpiarFiltrosDiaria(){
  $('#filtroCas').val($('#filtroCas option:first').val());
  $('#filtroMon').val($('#filtroMon option:first').val());
  $('#dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  }).data('datetimepicker').setDate(new Date());
}

$('#filtroCas').change(function() {
  $('#buscar-importacionesDiarias').click();
});

$('#filtroMon').change(function() {
  $('#buscar-importacionesDiarias').click();
});

$('#dtpFecha').on("change.datetimepicker",function(){
  $('#buscar-importacionesDiarias').click();
})

$('#archivo').on('change',function(){
  $('#btn-guardarDiario').show();
});

//boton grande de importar
$('#btn-importar,#btn-importarCierres').on('click', function(e){
  e.preventDefault();
  ocultarErrorValidacion($('#B_fecha_imp'));
  ocultarErrorValidacion($('#monedaSel'));
  ocultarErrorValidacion($('#casinoSel'));
  $('#B_fecha_imp').val("");
  $('#casinoSel').val('0');
  $('#monedaSel').val('0');
  $('#modalImportacionDiaria').find('.modal-footer').children().show();
  $('#modalImportacionDiaria').find('.modal-body').children().show();
  $('#iconoCarga').hide();

  if($(this).attr('id') == 'btn-importar'){
    $('#modalImportacionDiaria .modal-title').text('| IMPORTAR INFORME DIARIO DE MESAS');
    $('#modalImportacionDiaria .modal-header').css('background-color','#6dc7be');
    $('#btn-guardarDiario').data('modo','importacionDiaria');
  }
  else if($(this).attr('id') == 'btn-importarCierres'){
    $('#modalImportacionDiaria .modal-title').text('| IMPORTAR CIERRES DE MESAS');
    $('#modalImportacionDiaria .modal-header').css('background-color','rgb(113, 191, 154)');
    $('#btn-guardarDiario').data('modo','cierres');
  }
  else return;

  //Mostrar: rowArchivo
  $('#modalImportacionDiaria #rowArchivo').show();

  //Ocultar: mensajes, iconoCarga
  $('#modalImportacionDiaria #mensajeError').hide();
  $('#modalImportacionDiaria #mensajeInvalido').hide();

  habilitarInputDiario();

  //Ocultar botón SUBIR
  $('#iconoCarga').hide();
  $('#btn-guardarDiario').hide();

  $('#modalImportacionDiaria').modal('show');
  $('#mensajeErrorJuegos').hide();
});

$(document).on('click', '#archivo', function(){
  $('#modalImportacionDiaria #mensajeInvalido').hide();
});

//presiona subir en el modal de importación
$('#btn-guardarDiario').on('click', function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  e.preventDefault();

  let formData = new FormData;
  formData.append('name', $('#modalImportacionDiaria #archivo')[0].files[0].name);
  formData.append('fecha', $('#fecha_importacion').val());
  formData.append('id_moneda', $('#monedaSel').val());
  formData.append('id_casino', $('#casinoSel').val());

  //Si subió archivo lo guarda
  if($('#modalImportacionDiaria #archivo').attr('data-borrado') == 'false' && $('#modalImportacionDiaria #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionDiaria #archivo')[0].files[0]);
  }

  let url = '';
  if($(this).data('modo') == 'importacionDiaria') url = 'importacionDiaria/importar';
  if($(this).data('modo') == 'cierres')           url = 'importacionDiaria/importarCierres';

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      $('#modalImportacionDiaria').find('.modal-footer').children().hide();
      $('#modalImportacionDiaria').find('.modal-body').children().hide();
      $('#mensajeErrorJuegos').hide();
      $('#iconoCarga').show();
    },
    complete: function(data){},
    success: function (data) {
      $('#modalImportacionDiaria').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('El archivo fue importado');
      $('#mensajeExito').show();
      const fecha = formData.get('fecha').split('-').join('/');
      $('#dtpFecha').data('datetimepicker').setDate(new Date(fecha));
      $('#filtroCas').val(formData.get('id_casino'));
      $('#filtroMon').val(formData.get('id_moneda'));
      $('#buscar-importacionesDiarias').trigger('click',[1,10,'fecha','desc']);

    },
    error: function (data) {
      ///debería mostrar el mensaje y nada más.
      console.log('error',data);
      var response = data.responseJSON;

      $('#modalImportacionDiaria').find('.modal-footer').children().show();
      $('#frmImportacion').show();
      $('#rowArchivo').show();
      $('#iconoCarga').hide();

      if(typeof response === 'undefined'){
        $('#mensajeErrorJuegos #span').text('');
        $('#mensajeErrorJuegos').show();
        return;
      }
      if(typeof response.fecha !== 'undefined')     mostrarErrorValidacion($('#B_fecha_imp'),response.fecha[0],false);
      if(typeof response.id_casino !== 'undefined') mostrarErrorValidacion($('#casinoSel'),response.id_casino[0],false);
      if(typeof response.id_moneda !== 'undefined') mostrarErrorValidacion($('#monedaSel'),response.id_moneda[0],false);
      if(typeof response.error !== 'undefined'){
        $('#mensajeErrorJuegos #span').empty().append('<p>'+response.error.join('</p><p>')+'</p>');
        $('#mensajeErrorJuegos').show();
      }
      if(typeof response.archivo !== 'undefined'){
        $('#mensajeErrorJuegos #span').empty().append('<p>'+response.archivo.join('</p><p>')+'</p>');
        $('#mensajeErrorJuegos').show();
      }
    }
  });
});

$('#modalImportacionDiaria #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacionDiaria #mensajeInvalido').show();
  $('#modalImportacionDiaria #mensajeInvalido #span').text(msg);
});

$('#modalImportacionDiaria #archivo').on('fileselect', function(event) {
  $('#modalImportacionDiaria #archivo').attr('data-borrado','false');
});

$('#buscar-importacionesDiarias').click(function(e){
  e.preventDefault();

  let sort_by = {
    columna: $('#tablaResultadosDiarios .activa').attr('value'),
    orden:   $('#tablaResultadosDiarios .activa').attr('estado')
  };

  if(typeof sort_by['columna'] == 'undefined' || typeof sort_by['orden'] == 'undefined'){
    sort_by = {columna: 'fecha',orden: 'desc'} ;
  }

  const formData = {
    fecha: $('#dtpFecha_hidden').val(),
    id_moneda:$('#filtroMon').val(),
    id_casino: $('#filtroCas').val(),
    sort_by: sort_by,
  }

  $('#tablaResultadosDiarios tbody tr').remove();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importacionDiaria/filtros',
    data: formData,
    dataType: 'json',

    success: function (data){
      for (let i = 0; i < data.length; i++) {
        $('#cuerpoTablaImpD').append(generarFilaImportaciones(data[i]));
      }
    },
    error: function(data){ console.log(data); },
  })
});

$('#btn-informeMensual').click(function(e){
  e.preventDefault();
  window.open('importacionDiaria/imprimirMensual/' + $('#dtpFecha_hidden').val() + '/' + $('#filtroCas').val(),'_blank');
});

function generarFilaImportaciones(imp){
  const fila = $('#moldeFilaImpD').clone();
  const id = imp.id_importacion_diaria_mesas;
  fila.attr('id', id);
  fila.find('.d_fecha').text(imp.fecha);

  const classbool = ['fas fa-fw fa-times','fas fa-check-circle'];
  const colorbool = ['#D32F2F','#4CAF50'];
  const importado = (id !== null) | 0; //cast to int
  const cierre = imp.tiene_cierre | 0;
  const validado = (id !== null && imp.validado) | 0;
  fila.find('.d_importado').append($('<i>').addClass(classbool[importado]).css('color',colorbool[importado]).css('text-align','center'));
  fila.find('.d_relevado' ).append($('<i>').addClass(classbool[cierre]   ).css('color',colorbool[cierre]   ).css('text-align','center'));
  fila.find('.d_validado' ).append($('<i>').addClass(classbool[validado] ).css('color',colorbool[validado] ).css('text-align','center'));
  if(id == null){
    fila.find('.d_accion').empty().append('<span>&nbsp;</span>');
  }
  else fila.find('button').val(id);

  if(validado) fila.find('.valImpD').remove();
  fila.css('display', 'block');
  return fila;
}

//PAGINACION
$(document).on('click','#tablaResultadosDiarios thead tr th[value]',function(e){
  $('#tablaResultadosDiarios th').removeClass('activa');

  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultadosDiarios th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  $('#buscar-importacionesDiarias').click();
});


//fin PAGINACION

function mostrarImportacion(id_imp,modo,tipo_mesa = -1,observacion = null){
  if(modo != 'ver' && modo != 'validar') return;
  ocultarErrorValidacion($('#modalVerImportacion input'));
  $('#mensajeExito').hide();
  $('#observacionesImpD').val('');
  $('#selectMesa').val(tipo_mesa);
  $('#ajuste').hide();
  const tipo = $('#selectMesa option:selected').val() == ""? "" : $('#selectMesa option:selected').text();
  $('#guardar-observacion').val(id_imp).data('modo',modo).toggle(modo == 'validar');
  $('#datosImpDiarios > tr').remove();
  
  if(modo == 'ver'){
    $('#modalVerImportacion .modal-title').text('| DETALLE IMPORTACIÓN DIARIA');
    $('#modalVerImportacion .modal-header').css('background-color','#0D47A1');
    $('#ajuste input,#ajuste button').attr('disabled',true);
  }
  else if(modo == 'validar'){
    $('#modalVerImportacion .modal-title').text('| VALIDAR IMPORTACIÓN DIARIA');
    $('#modalVerImportacion .modal-header').css('background-color','#6dc7be');
    $('#ajuste input,#ajuste button').attr('disabled',false);
  }

  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + tipo, function(data){
    $('#fechaImpD').val(data.importacion.fecha).prop('readonly',true);
    $('#casinoImpD').val(data.casino.nombre).prop('readonly',true);
    $('#monedaImpD').val(data.moneda.descripcion).prop('readonly',true);
    $('#observacionesImpD').val(observacion? observacion : data.importacion.observacion).prop('readonly',modo == 'ver');
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosImpDiarios').append(generarFilaVerImp(data.detalles[i]));
    }
    $('#modalVerImportacion').modal('show');
  });
}

$(document).on('click','.infoImpD',function(e){
  e.preventDefault();
  mostrarImportacion($(this).val(),'ver');
});
$(document).on('click','.valImpD',function(e){
  e.preventDefault();
  mostrarImportacion($(this).val(),'validar');
});
$(document).on('click','.impImpD',function(e){
  e.preventDefault();
  window.open('importacionDiaria/imprimir/' + $(this).val(),'_blank');
});

//si cambia el select dentro del modal de ver importacion
$(document).on('change','#selectMesa',function(e){
  e.preventDefault();
  const id = $('#guardar-observacion').val();
  const modo = $('#guardar-observacion').data('modo');
  const obs =  $('#observacionesImpD').val();
  mostrarImportacion(id,modo,$(this).val(),obs);
});

$('#guardar-observacion').on('click', function(e){
  e.preventDefault();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importacionDiaria/guardar',
    data: {
      id_importacion: $(this).val(),
      observacion:    $('#observacionesImpD').val(),
    },
    dataType: 'json',
    success: function (data){
      $('#buscar-importacionesDiarias').click();
      $('#modalVerImportacion').modal('hide');
      $('#mensajeExito h3').text('VALIDADO');
      $('#mensajeExito p').text(' ');
      $('#mensajeExito').show();
    },
    error: function(data){ 
      const errores = data.responseJSON;
      console.log(errores); 
      if(errores.observacion) mostrarErrorValidacion($('#observacionesImpD'),errores.observacion[0],true);
    },
  });
})

$(document).on('click','.eliminarDia',function(e){
  $('#btn-eliminar').val($(this).val());
  $('#modalAlertaEliminar').modal('show');
});

$('#btn-eliminar').on('click', function(){
  const id = $(this).val();
  $.get('importacionDiaria/eliminarImportacion/' + id , function(data){
    if(data==1){
      $('#modalAlertaEliminar').modal('hide');
      $('#mensajeExito h3').text('ARCHIVO ELIMINADO');
      $('#mensajeExito p').text(' ');
      $('#mensajeExito').show();
      $('#cuerpoTablaImpD').find('#'+ id).remove();
    }
  });
});

function habilitarInputDiario(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionDiaria #archivo')[0].files[0] = null;
  $('#modalImportacionDiaria #archivo').attr('data-borrado','false');
  $("#modalImportacionDiaria #archivo").fileinput('destroy').fileinput({
    language: 'es',
    language: 'es',
    showRemove: false,
    showUpload: false,
    showCaption: false,
    showZoom: false,
    browseClass: "btn btn-primary",
    previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
    overwriteInitial: false,
    initialPreviewAsData: true,
    dropZoneEnabled: false,
    preferIconicPreview: true,
    previewFileIconSettings: {
      'csv': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>',
      'txt': '<i class="far fa-file-alt fa-6" aria-hidden="true"></i>'
    },
    allowedFileExtensions: ['csv','txt'],
  });
}

function clearNull(v){ return v == null? 0 : v };

$(document).on('click','.v_ajustar',function(e){
  e.preventDefault();
  ocultarErrorValidacion($('#ajuste input'));
  const fila = $(this).closest('tr');
  const cierre = fila.data('cierre');
  const estado_cierre = fila.data('estado_cierre');
  const cierre_anterior = fila.data('cierre_anterior');
  const estado_cierre_anterior = fila.data('estado_cierre_anterior');
  if(cierre != null){
    $('#cierre').find('.fecha_cierre').text(cierre.fecha);
    $('#cierre').find('.estado_cierre').text(estado_cierre);
    $('#cierre').find('.fichas_cierre').text(clearNull(cierre.total_pesos_fichas_c));
  }
  else{
    $('#cierre').find('.fecha_cierre').text('--');
    $('#cierre').find('.estado_cierre').text('SIN RELEVAR');
    $('#cierre').find('.fichas_cierre').text('--');
  }
  if(cierre_anterior != null){
    $('#cierre_anterior').find('.fecha_cierre').text(cierre_anterior.fecha);
    $('#cierre_anterior').find('.estado_cierre').text(estado_cierre_anterior);
    $('#cierre_anterior').find('.fichas_cierre').text(clearNull(cierre_anterior.total_pesos_fichas_c));
  }
  else{
    $('#cierre_anterior').find('.fecha_cierre').text('--');
    $('#cierre_anterior').find('.estado_cierre').text('SIN RELEVAR');
    $('#cierre_anterior').find('.fichas_cierre').text('--');
  }
  const ajuste = fila.data('ajuste_fichas');
  const observaciones = fila.data('observacion');
  const habilitar_ajuste = (fila.data('diferencia') != 0.0) && ($('#guardar-observacion').data('modo') == "validar");
  $('#ajuste .ajuste').val(ajuste).attr('disabled',!habilitar_ajuste);
  $('#ajuste .observaciones').val(observaciones);
  $('#confirmar_ajuste').val(fila.attr('id'));

  $('#ajuste').show();
});

$('#confirmar_ajuste').click(function(){
  //No deberia entrar pero si esta visualizando ignoro
  if($('#guardar-observacion').data('modo') == "ver") return;

  const fila = $('#datosImpDiarios #'+$(this).val());
  const id = fila.attr('id');
  const ajuste = $('#ajuste .ajuste').val();
  const observacion = $('#ajuste .observaciones').val();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importacionDiaria/ajustarDetalle',
    data: {
      id_detalle_importacion_diaria_mesas: id,
      ajuste_fichas: ajuste,
      observacion: observacion
    },
    dataType: 'json',
    success: function (data){
      fila.replaceWith(generarFilaVerImp(data));
      $('#ajuste').hide();
    },
    error: function(data){ 
      const errores = data.responseJSON;
      console.log(errores);
      if(errores.ajuste) mostrarErrorValidacion($('#ajuste .ajuste'),errores.ajuste[0],true);
      if(errores.observacion) mostrarErrorValidacion($('#ajuste .observaciones'),errores.observacion[0],true);
    },
  })
});

function generarFilaVerImp(data){
  const fila = $('#moldeImpDiarios').clone();
  fila.attr('id', data.id_detalle_importacion_diaria_mesas);
  function text(f,s){f.text(s).attr('title',s);};

  text(fila.find('.v_juego'),data.siglas_juego);
  text(fila.find('.v_mesa'),data.nro_mesa);
  text(fila.find('.v_drop'),data.droop);
  text(fila.find('.v_drop_tarjeta'),data.droop_tarjeta);
  text(fila.find('.v_saldofichas'),data.saldo_fichas);
  text(fila.find('.v_saldofichas_rel'),data.saldo_fichas_relevado);
  text(fila.find('.v_diff'),data.diferencia_saldo_fichas);
  fila.find('.v_diff').css('background-color',data.diferencia_saldo_fichas == 0.00? 'rgb(220,255,220)' : 'rgb(255,220,220)');
  text(fila.find('.v_ajuste'),data.ajuste_fichas? data.ajuste_fichas : 0.0);
  text(fila.find('.v_reposiciones'),data.reposiciones);
  text(fila.find('.v_retiros'),data.retiros);
  text(fila.find('.v_utilidad'),data.utilidad);
  text(fila.find('.v_hold'),data.hold);

  fila.data('cierre',data.cierre);
  fila.data('estado_cierre',data.estado_cierre);
  fila.data('cierre_anterior',data.cierre_anterior);
  fila.data('estado_cierre_anterior',data.estado_cierre_anterior);
  fila.data('ajuste_fichas',data.ajuste_fichas);
  fila.data('observacion',data.observacion);

  if(!data.cierre || !data.cierre_anterior){
    fila.find('.v_saldofichas_rel').css('background-color','rgb(255,255,180)').attr('title','SIN CIERRES');
  }
  if(data.diferencia_saldo_fichas == 0.0){
    fila.find('.v_ajustar i').removeClass('fa-wrench').addClass('fa-search-plus');
    fila.data('diferencia',data.diferencia_saldo_fichas);
  }
  fila.css('display', '');
  return fila;
}

function generarFilaCierre(data){
  const fila = $('#moldeCierre').clone();
  fila.attr('id', data.id_cierre_mesa);
  fila.find('.c_fecha').text(data.fecha);
  fila.find('.c_saldofichas').text(data.saldo_fichas);
  fila.find('.c_estado').text(data.estado);
  fila.css('display', '');
  return fila;
}


$('#btn-cotizacion').on('click', function(e){
  e.preventDefault();
  //limpio modal
  $('#labelCotizacion').html("");
  $('#labelCotizacion').attr("data-fecha","");
  $('#valorCotizacion').val("");
  //inicio calendario
  $('#calendarioInicioBeneficio').fullCalendar({  // assign calendar
    locale: 'es',
    backgroundColor: "#f00",
    eventTextColor:'yellow',
    editable: false,
    selectable: true, 
    allDaySlot: false,
    selectAllow:false, 
    customButtons: {
      nextCustom: {
        text: 'Siguiente',
        click: function() {
          cambioMes('next');
        }
      },
      prevCustom: {
        text: 'Anterior',
        click: function() {
          cambioMes('prev');
        }
      },
    },
    events: function(start, end, timezone, callback) {
      $.ajax({
        url: 'cotizacion/obtenerCotizaciones/'+ start.format('YYYY-MM'),
        type:"GET",
        success: function(doc) {
          var events = [];
          $(doc).each(function() {
            var numero=""+$(this).attr('valor');
            events.push({
              title:"" + numero.replace(".", ","),
              start: $(this).attr('fecha')
            });
          });
          callback(events);
        }
      });
    },
    dayClick: function(date) {
      $('#labelCotizacion').html('Guardar cotización para el día '+ '<u>'  +date.format('DD/M/YYYY') + '</u>' );
      $('#labelCotizacion').attr("data-fecha",date.format('YYYY-MM-DD'));
      $('#valorCotizacion').val("");
      $('#valorCotizacion').focus();
    },
  });

  $('#modal-cotizacion').modal('show')
});

$('#guardarCotizacion').on('click',function(){
  fecha=$('#labelCotizacion').attr('data-fecha');
  valor= $('#valorCotizacion').val();
  formData={
    fecha: fecha,
    valor: valor,
  }
  $.ajax({
    type: 'POST',
    url: 'cotizacion/guardarCotizacion',
    data: formData,
    success: function (data) {
     $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
      //limpio modal
      $('#labelCotizacion').html("");
      $('#labelCotizacion').attr("data-fecha","");
      $('#valorCotizacion').val("");
    }
  });
});

function cambioMes(s){
  $('#calendarioInicioBeneficio').fullCalendar(s);
  $('#calendarioInicioBeneficio').fullCalendar('refetchEvents');
};
