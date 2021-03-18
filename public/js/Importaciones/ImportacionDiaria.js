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
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  }).data('datetimepicker').setDate(new Date());;
  $('#filtroCas').val($('#filtroCas option:first').val());
  $('#filtroMon').val($('#filtroMon option:first').val());
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
  formData.append('fecha', $('#fecha_importacion').val());
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

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importacionDiaria/filtros',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#tablaResultadosDiarios tbody tr').remove();
      for (let i = 0; i < data.importaciones.length; i++) {
        $('#cuerpoTablaImpD').append(generarFilaImportaciones(data.casino,data.moneda,data.importaciones[i]));
      }
    },
    error: function(data){ console.log(data); },
  })
});

function generarFilaImportaciones(casino,moneda,imp){
  const fila = $('#moldeFilaImpD').clone();
  const id = imp.importacion == null? "" : imp.importacion.id_importacion_diaria_mesas;
  fila.attr('id', id);
  fila.find('.d_fecha').text(imp.fecha);
  fila.find('.d_casino').text(casino);
  fila.find('.d_moneda').text(moneda);

  const classbool = ['fas fa-fw fa-times','fas fa-check-circle'];
  const colorbool = ['#D32F2F','#4CAF50'];
  const importado = (imp.importacion !== null) | 0; //cast to int
  const cierre = imp.tiene_cierre | 0;
  fila.find('.d_importado').append($('<i>').addClass(classbool[importado]).css('color',colorbool[importado]).css('text-align','center'));
  fila.find('.d_relevado').append($('<i>').addClass(classbool[cierre]).css('color',colorbool[cierre]).css('text-align','center'));
  if(id === ""){
    fila.find('.d_accion').empty().append('<span>&nbsp;</span>');
  }
  else fila.find('button').val(id);
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

function mostrarImportacion(id_imp,modo,tipo_mesa = 1,observacion = null){
  if(modo != 'ver' && modo != 'validar') return;

  $('#mensajeExito').hide();
  $('#observacionesImpD').val('');
  $('#selectMesa').val(tipo_mesa);
  const tipo = $('#selectMesa option:selected').val() == ""? "" : $('#selectMesa option:selected').text();
  $('#guardar-observacion').val(id_imp).data('modo',modo).toggle(modo == 'validar');
  $('#datosImpDiarios > tr').remove();
  
  if(modo == 'ver'){
    $('#modalVerImportacion .modal-title').text('| DETALLE IMPORTACIÓN DIARIA');
    $('#modalVerImportacion .modal-header').css('background-color','#0D47A1');
  }
  else if(modo == 'validar'){
    $('#modalVerImportacion .modal-title').text('| VALIDAR IMPORTACIÓN DIARIA');
    $('#modalVerImportacion .modal-header').css('background-color','#6dc7be');
  }

  $.get('importacionDiaria/verImportacion/' + id_imp + '/' + tipo, function(data){
    $('#fechaImpD').val(data.importacion.fecha).prop('readonly',true);
    $('#casinoImpD').val(data.casino.nombre).prop('readonly',true);
    $('#monedaImpD').val(data.moneda.descripcion).prop('readonly',true);
    $('#observacionesImpD').val(observacion? observacion : data.importacion.observacion).prop('readonly',modo == 'ver');
    for (let i = 0; i < data.detalles.length; i++) {
      $('#datosImpDiarios').append(generarFilaVerImp(data.detalles[i]));
    }
    if(modo == 'ver') $('#datosImpDiarios').append(generarFilaTotalesDia(data.importacion));
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
    error: function(data){ console.log(data); },
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

//genera las filas a la tabla dentro del modal ver
function generarFilaTotalesDia(data){
  const fila = generarFilaVerImp({
    id_importacion_diaria_mesas: '',
    siglas_juego: 'TOTALES',
    nro_mesa: '',
    droop: data.total_diario,
    reposiciones: data.total_diario_reposiciones,
    retiros: data.total_diario_retiros,
    utilidad: data.utilidad_diaria_total,
    hold: '-',
  });
  fila.css('cssText','background-color:#aaa; color:black;');
  return fila;
};

function generarFilaVerImp(data){
  const fila = $('#moldeImpDiarios').clone();
  fila.attr('id', data.id_importacion_diaria_mesas);
  fila.find('.v_juego').text(data.siglas_juego);
  fila.find('.v_mesa').text(data.nro_mesa);
  fila.find('.v_drop').text(data.droop);
  fila.find('.v_reposiciones').text(data.reposiciones);
  fila.find('.v_retiros').text(data.retiros);
  fila.find('.v_utilidad').text(data.utilidad);
  fila.find('.v_hold').text(data.hold);
  fila.css('display', '');
  return fila;
}
