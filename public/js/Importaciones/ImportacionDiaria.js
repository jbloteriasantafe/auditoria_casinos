$(document).ready(function() {
  $('#barraImportaciones').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').hide();
  $('#barraImportaciones').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraImportaciones').addClass('opcionesSeleccionado');
  $('#pestImportaciones').show();
  $('#pestImportaciones').css('display','inline-block');
  //pestañas
  $(".tab_content").hide(); //Hide all content
  $("ul.pestImportaciones li:first").addClass("active").show(); //Activate first tab
  $(".tab_content:first").show(); //Show first tab content
  
  limpiarFiltrosDiaria();
  limpiarFiltrosMensual();

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
  $('#dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  $('#buscar-importacionesDiarias').click();
});

function limpiarFiltrosDiaria(){
  $('#filtroCas').val('0');
  $('#B_fecha_filtro').val('');
  $('#filtroMon').val('0');
}

function limpiarFiltrosMensual(){
  $('#filtroCasino').val('0');
  $('#filtroFecha').val('');
  $('#filtroMoneda').val('0');
}
//PESTAÑAS
$("ul.pestImportaciones li").click(function() {
    $("ul.pestImportaciones li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
    if(activeTab == '#pest_mensual'){
      limpiarFiltrosMensual();
      $('#buscar-impMensuales').click();
    }
    else if(activeTab == '#pest_diaria'){
      limpiarFiltrosDiaria();
      $('#buscar-importacionesDiarias').click();
    }
    else return;
    $(activeTab).fadeIn(); //Fade in the active ID content
});

$('#archivo').on('change',function(){
  $('#btn-guardarDiario').show();
});

//boton grande de importar
$('#btn-importar').on('click', function(e){
  e.preventDefault();
  $('#cotizacion_diaria').prop('readonly',true);
  $('#mensajeErrorJuegos').hide();

  ocultarErrorValidacion($('#B_fecha_imp'));
  ocultarErrorValidacion($('#monedaSel'));
  ocultarErrorValidacion($('#casinoSel'));
  $('#B_fecha_imp').val("");
  $('#casinoSel').val('0');
  $('#monedaSel').val('0');
  $('#cotizacion_diaria').val("");
  $('#modalImportacionDiaria').find('.modal-footer').children().show();
  $('#modalImportacionDiaria').find('.modal-body').children().show();
  $('#iconoCarga').hide();

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
});

$(document).on('change','#monedaSel',function(){
  $('#cotizacion_diaria').val('');
  $('#cotizacion_diaria').prop('readonly',$(this).val() == 1 || $(this).val() == 0);
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
  formData.append('fecha', $('#B_fecha_imp').val());
  formData.append('id_moneda', $('#monedaSel').val());
  formData.append('id_casino', $('#casinoSel').val());
  formData.append('cotizacion_diaria', $('#cotizacion_diaria').val());

  //Si subió archivo lo guarda
  if($('#modalImportacionDiaria #archivo').attr('data-borrado') == 'false' && $('#modalImportacionDiaria #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionDiaria #archivo')[0].files[0]);
  }

  $.ajax({
    type: "POST",
    url: 'importacionDiaria/importar',
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

$('#buscar-importacionesDiarias').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  let sort_by = (columna != null) ? {columna: columna,orden: orden} : {
    columna: $('#tablaResultadosDiarios .activa').attr('value'),
    orden:   $('#tablaResultadosDiarios .activa').attr('estado')
  };

  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    sort_by = {columna: 'fecha',orden: 'desc'} ;
  }

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  const formData = {
    fecha: $('#B_fecha_filtro').val(),
    id_moneda:$('#filtroMon').val(),
    casino: $('#filtroCas').val(),
    page: (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage(),
    sort_by: sort_by,
    page_size: (page_size == null || isNaN(page_size))? size : page_size,
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importacionDiaria/filtros',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#tablaResultadosDiarios tbody tr').remove();
      $('#herramientasPaginacion').generarTitulo(formData.page,formData.page_size,data.importaciones.total,clickIndice);
      for (let i = 0; i < data.importaciones.data.length; i++) {
          $('#cuerpoTablaImpD').append(generarFilaImportaciones(data.importaciones.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(formData.page,formData.page_size,data.importaciones.total,clickIndice);
    },
    error: function(data){ console.log(data); },
  })
});

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
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }

  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultadosDiarios .activa').attr('value');
  const orden = $('#tablaResultadosDiarios .activa').attr('estado');
  $('#buscar-importacionesDiarias').trigger('click',[pageNumber,tam,columna,orden]);
}

//fin PAGINACION

//genera filas de la pantalla principal de importaciones diarias

//boton ver imp de cada fila
$(document).on('click', '.obsImpD', function(e) {
  e.preventDefault();

  $('#modalVerImportacion').modal('show');
  $('#mensajeExito').hide();
  $('#observacionesImpD').val('');
  $('#selectMesa').val(1);

  $('#datosImpDiarios > tr').remove();
  const id_imp = $(this).val();
  $('#guardar-observacion').val(id_imp);

  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + 'RULETA', function(data){
    $('#fechaImpD').val(data.importacion.fecha);
    $('#casinoImpD').val(data.casino.nombre);
    $('#monedaImpD').val(data.moneda.descripcion);
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosImpDiarios').append(generarFilaVerImpValidar(data.detalles[i]));
    }
  });
});

//si cambia el select dentro del modal de ver importacion
$(document).on('change','#selectMesa',function(){
  const id_imp = $('#guardar-observacion').val();
  $('#datosImpDiarios tr').remove();

  const tipo_str = $(this).find('option:selected').text();
  if(tipo_str.length == 0) return;
  
  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + tipo_str, function(data){
    $('#fechaImpD').val(data.importacion.fecha);
    $('#casinoImpD').val(data.casino.nombre);
    $('#monedaImpD').val(data.moneda.descripcion);
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosImpDiarios').append(generarFilaVerImpValidar(data.detalles[i]));
    }
  });
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
    error: function(data){ console.log(data); },
  });
})

//ver datos de importaciones guardados
$(document).on('click','.infoImpD',function(e){
  const id = $(this).val();
  $('#selectMesaInfo').val("1").prop('selected',true);
  const tipo = $('#selectMesaInfo option:selected').text();

  $('#datosInfoDiarios  tr').remove();
  $.get('importacionDiaria/verImportacion/' + id + '/' + tipo, function(data){
    $('#fechaInfo').val(data.importacion.fecha).prop('readonly',true);
    $('#casinoInfo').val(data.casino.nombre).prop('readonly',true);
    $('#monedaInfo').val(data.moneda.descripcion).prop('readonly',true);
    $('#guardar-observacion-info').val(data.importacion.id_importacion_diaria_mesas);
    $('#guardar-observacion-info').hide();
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosInfoDiarios').append(generarFilaVerImp(data.detalles[i]));
    }
    $('#datosInfoDiarios').append(generarFilaTotalesDia(data.importacion));
    $('#modalInfoImportacion').modal('show');
  });
});

$(document).on('change','#selectMesaInfo',function(){
  const id_imp = $('#guardar-observacion-info').val();
  $('#datosInfoDiarios tr').remove();

  const tipo_str = $(this).find('option:selected').text();
  if(tipo_str.length == 0) return;

  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + tipo_str, function(data){
    $('#fechaInfo').val(data.importacion.fecha);
    $('#casinoInfo').val(data.casino.nombre);
    $('#monedaInfo').val(data.moneda.descripcion);
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosInfoDiarios').append(generarFilaVerImpValidar(data.detalles[i]));
    }
    $('#datosInfoDiarios').append(generarFilaTotalesDia(data.importacion));
  });
});

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

function generarFilaImportaciones(data){
  const fila = $('#moldeFilaImpD').clone();
  fila.attr('id', data.id_importacion_diaria_mesas);
  fila.find('.d_fecha').text(data.fecha);
  fila.find('.d_casino').text(data.nombre);
  fila.find('.d_moneda').text(data.descripcion);

  if(data.diferencias) fila.find('.d_dif').append($('<i>').addClass('fas fa-fw fa-times' ).css('color', '#D32F2F').css('text-align','center'));
  else                 fila.find('.d_dif').append($('<i>').addClass('fas fa-check-circle').css('color', '#4CAF50').css('text-align','center'));
  
  fila.find('button').val(data.id_importacion_diaria_mesas);
  if(data.validado)  fila.find('.d_accion').find('.obsImpD,.eliminarDia').remove();
  else               fila.find('.d_accion').find('.infoImpD').remove();

  fila.css('display', 'block');
  return fila;
}

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

//genera las filas a la tabla dentro del modal ver
function generarFilaVerImp(data){
  const fila = $('#moldeInfoDiarios').clone();
  fila.attr('id', data.id_importacion_diaria_mesas);
  fila.css('display', '');
  fila.find('.info_juego').text(data.nombre_juego);
  fila.find('.info_mesa').text(data.nro_mesa);
  fila.find('.info_drop').text(data.droop);
  fila.find('.info_reposiciones').text(data.reposiciones);
  fila.find('.info_retiros').text(data.retiros);
  fila.find('.info_utilidad').text(data.utilidad);
  fila.find('.info_hold').text(data.hold);
  return fila;
}

function generarFilaTotalesDia(data){
  const fila = $('#moldeInfoDiarios').clone().removeAttr('id');
  const cssText = 'padding:5px !important;font-weight:bold;text-align:center';
  fila.find('td').css('cssText',cssText);
  fila.css('cssText','background-color:#aaa; color:black;');
  fila.css('display', '');
  fila.find('.info_juego').text('TOTALES');
  fila.find('.info_drop').text(data.total_diario);
  fila.find('.info_utilidad').text(data.utilidad_diaria_total);
  fila.find('.info_reposiciones').text(data.total_diario_reposiciones);
  fila.find('.info_retiros').text(data.total_diario_retiros);
  fila.find('.info_hold').text('-');
  return fila;
};


function generarFilaVerImpValidar(data){
  const fila = $('#moldeImpDiarios').clone();
  fila.attr('id', data.id_importacion_diaria_mesas);
  fila.find('.v_juego').text(data.nombre_juego);
  fila.find('.v_mesa').text(data.nro_mesa);
  fila.find('.v_drop').text(data.droop);
  fila.find('.v_reposiciones').text(data.reposiciones);
  fila.find('.v_retiros').text(data.retiros);
  fila.find('.v_utilidad').text(data.utilidad);
  fila.find('.v_hold').text(data.hold);
  fila.css('display', '');
  return fila;
}
