//Cuando se sube el archivo se identifican los datos posibles
var id_casino;
var id_tipo_moneda;
var fecha_date;

//Tamaños de los diferentes archivos CSV
var COL_PROD_ROS = 4;
var COL_PROD_SFE = 32;
var COL_BEN_ROS = 8;

//Opacidad del modal al minimizar
$('#btn-minimizarProducidos').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//Opacidad del modal al minimizar
$('#btn-minimizarBeneficios').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('Importaciones');
  $('#opcImportaciones').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcImportaciones').addClass('opcionesSeleccionado');

  //Habilitar o no la fecha según el casino
  // habilitarFechayMoneda();
  $('#mensajeInformacion').hide();

  //Fecha para el casino de Rosario
  $('#modalImportacionContadores #fecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yyyy',
    // pickerPosition: "bottom-left",
    pickerPosition: "top-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  });

  $('#fecha_busqueda').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  });

  $('#mesInfoImportacion').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
  });

  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date());

  if($('#casino_busqueda option').length == 2 ){
    $('#casino_busqueda option:eq(1)').prop('selected', true);
  }

  setearValueFecha();
  //Paginar
    $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

  id_casino = 1;
  id_tipo_moneda = 1;

  $('#casinoInfoImportacion').val(id_casino);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  $('#casinoInfoImportacion').change();
});


$('#casinoInfoImportacion').change(function() {
  $('#monedaInfoImportacion').change();
});

$('#mesInfoImportacion').on("change.datetimepicker",function(){
  $('#monedaInfoImportacion').change();
});

$('#monedaInfoImportacion').change(function() {
  const id_moneda = $(this).val();
  const fecha_sort = $('#infoImportaciones .activa').attr('estado');

  if (id_moneda == 1) $('.tablaBody').removeClass('dolares').addClass('pesos');
  else $('.tablaBody').removeClass('pesos').addClass('dolares');

  //Esto pasa siempre en Rosario, el único casino que tiene dolar
  cargarTablasImportaciones($('#casinoInfoImportacion').val(), id_moneda, fecha_sort);
});

function limpiarBodysImportaciones() {
  $('.tablaBody tr').not('#moldeFilaImportacion').remove();
  $('.tablaBody').hide();
}

function cargarTablasImportaciones(casino, moneda, fecha_sort) {
  const fecha = $('#mes_info_hidden').val();
  const url = fecha.size == 0? '/' : ('/' + fecha);
  $.get('importaciones/' + casino + url + '/' + (fecha_sort? fecha_sort : ''), function(data) {
    let tablaBody = $();

    limpiarBodysImportaciones();

    switch (casino) {
      case '1':
        tablaBody = $('#bodyMelincue');
        break;
      case '2':
        tablaBody = $('#bodySantaFe');
        break;
      case '3':
        tablaBody = $('#bodyRosario');
        break;
    }

    for (let i = 0; i < data.arreglo.length; i++) {
      const fila = $('#moldeFilaImportacion').clone();
      fila.removeAttr('id');
      fila.find('.fecha').text(convertirDate(data.arreglo[i].fecha));

      const tipos = ['contador','producido','beneficio'];
      for(const idx in tipos){
        const t = tipos[idx];
        fila.find('.'+t).addClass(data.arreglo[i]?.[t]?.[moneda]? 'true' : 'false');
      }

      tablaBody.append(fila);
      fila.show();
    }

    tablaBody.show();
  });
  $('#moldeFilaImportacion').hide();
}


function setearValueFecha() {
  var tipo_archivo = $('#tipo_archivo').val();

  switch (tipo_archivo) {
    case '1':
      $('#tablaImportaciones #tipo_fecha').attr('value',"contador_horario.fecha");
      break;
    case '2':
      $('#tablaImportaciones #tipo_fecha').attr('value',"producido.fecha");
      break
    case '3':
      $('#tablaImportaciones #tipo_fecha').attr('value',"beneficio.fecha");
      break;
  }
}

function obtenerFechaString(dateFecha, conDia) {
    var arrayFecha = dateFecha.split('/');
    console.log(arrayFecha);
    var meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];

    if (conDia) {
      return arrayFecha[0] + ' ' +  meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
    }
    else return meses[arrayFecha[1] - 1] + ' ' + arrayFecha[2];
}

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$(document).on('click','.planilla', function(){
  //Limpiar el modal
  $('#modalPlanilla #fecha').val('');
  $('#modalPlanilla #casino').val('');
  $('#modalPlanilla #tipo_moneda').val('');
  const head = $('#tablaVistaPrevia thead tr');
  head.children().remove();
  $('#tablaVistaPrevia tbody tr').remove();

  let url = "";
  let success = function(){};
  let formData = {};
  const tipo_importacion = $('#tablaImportaciones').attr('data-tipo');
  const id_importacion = $(this).val();
  if(tipo_importacion == 3){
    $('#modalPlanilla h3.modal-title').text('VISTA PREVIA BENEFICIO');
    url = 'importaciones/previewBeneficios';
    formData = {
      mes: $(this).attr('data-mes'),
      anio: $(this).attr('data-anio'),
      id_tipo_moneda: $(this).attr('data-moneda'),
      id_casino: $(this).attr('data-casino'),
    }
    success = function (data) {
      $('#modalPlanilla #fecha').val(convertirDate(data.beneficios[0].fecha).substring(3,11));
      $('#modalPlanilla #casino').val(data.casino.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('FECHA')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COININ')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('COINOUT')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('VALOR')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('% DEVOLUCION')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROMEDIO')));

      for (let i = 0; i < data.beneficios.length; i++) {
        agregarFilaDetalleBeneficio(data.beneficios[i]);
      };
    }
  }
  else if(tipo_importacion == 2){
    $('#modalPlanilla h3.modal-title').text('VISTA PREVIA PRODUCIDO');
    url = 'importaciones/previewProducidos';
    formData = { id: id_importacion }
    success =  function (data) {
      $('#modalPlanilla #fecha').val(convertirDate(data.producido.fecha));
      $('#modalPlanilla #casino').val(data.casino.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

      head.append($('<th>').addClass('col-xs-5').append($('<h5>').text('MTM')));
      head.append($('<th>').addClass('col-xs-7').append($('<h5>').text('VALOR')));

      for (let i = 0; i < data.detalles_producido.length; i++) {
        agregarFilaDetalleProducido(data.detalles_producido[i]);
      }
    };
  }
  else if(tipo_importacion == 1){
    $('#modalPlanilla h3.modal-title').text('VISTA PREVIA CONTADOR');
    url = 'importaciones/previewContadores';
    formData = { id: id_importacion }
    success =  function (data) {
      $('#modalPlanilla #fecha').val(convertirDate(data.contador.fecha));
      $('#modalPlanilla #casino').val(data.casino.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);

      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('MTM')));
      head.append($('<th>').addClass('col-xs-3').append($('<h5>').text('COININ')));
      head.append($('<th>').addClass('col-xs-3').append($('<h5>').text('COINOUT')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('JACKPOT')));
      head.append($('<th>').addClass('col-xs-2').append($('<h5>').text('PROGRESIVO')));

      for (let i = 0; i < data.detalles_contador.length; i++) {
        agregarFilaDetalleContador(data.detalles_contador[i]);
      }
    };
  }
  else{
    console.log('Error tipo de importacion',tipo_importacion);
    return;
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  const s = function(data){
    success(data);
    $('#modalPlanilla').modal('show');
  }
  $.ajax({
    type: 'POST',
    url: url,
    data: formData,
    dataType: 'json',
    success: s,
    error: function (data) { console.log(data); }
  });
});

$(document).on('click','.borrar',function(){

  $('.modal-title').removeAttr('style');
  $('.modal-title').text('ADVERTENCIA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');


  var id_importacion = $(this).val();
  //Mirar en la tabla los tipos de archivos listados (1:contadores;2:producidos;3:beneficios).
  var tipo_archivo = $('#tablaImportaciones').attr('data-tipo');
  const casino = $(this).attr('data-casino');
  const moneda = $(this).attr('data-moneda');
  const anio   = $(this).attr('data-anio');
  const mes    = $(this).attr('data-mes');
  var nombre_tipo_archivo;

  switch (tipo_archivo) {
    case '1':
      nombre_tipo_archivo = 'CONTADOR';
      break;
    case '2':
      nombre_tipo_archivo = 'PRODUCIDO';
      break;
    case '3':
      nombre_tipo_archivo = 'BENEFICIO';
      break;
  }

  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val(id_importacion).attr('data-tipo',tipo_archivo)
  .attr('data-casino',casino).attr('data-moneda',moneda).attr('data-anio',anio).attr('data-mes',mes);
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ nombre_tipo_archivo + '?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  var id_importacion = $(this).val();
  var tipo_archivo = $(this).attr('data-tipo');
  console.log('Borrar ' + tipo_archivo + ': ' + id_importacion);

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  var url;

  switch(tipo_archivo){
    case '1':
      url = "contadores/eliminarContador/" + id_importacion;
      break;
    case '2':
      url = "producidos/eliminarProducido/" + id_importacion;
      break;
    case '3':
      url = "beneficios/eliminarBeneficios/"
          + $(this).attr('data-casino')+'/'+$(this).attr('data-moneda')+'/'+$(this).attr('data-anio')+'/'+$(this).attr('data-mes');
      break;
  }

  $.ajax({
      type: "DELETE",
      url: url,
      success: function (data) {
        //Remueve de la tabla
        console.log();
        // $('#' + tipo_archivo + id_importacion).remove();
        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalEliminar').modal('hide');

      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });
});

/*********************** CONTADORES **********************************/
function agregarFilaDetalleContador(contador) {
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-2').text(contador.nro_admin));
  fila.append($('<td>').addClass('col-xs-3').text(addCommas(contador.coinin)));
  fila.append($('<td>').addClass('col-xs-3').text(addCommas(contador.coinout)));
  fila.append($('<td>').addClass('col-xs-2').text(addCommas(contador.jackpot)));
  fila.append($('<td>').addClass('col-xs-2').text(addCommas(contador.progresivo)));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-importarContadores').click(function(e){
    e.preventDefault();
    $('.modal-title').text('| IMPORTADOR CONTADOR');
    $('#modalImportacionContadores .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');
    //Mostrar: rowArchivo
    $('#modalImportacionContadores #rowArchivo').show();
    $('#valoresArchivoContador').hide();
    //Ocultar: rowFecha, mensajes, iconoCarga
    $('#modalImportacionContadores #mensajeError').hide();
    $('#modalImportacionContadores #mensajeInvalido').hide();
    $('#modalImportacionContadores #iconoCarga').hide();

    habilitarInputContador();
    $('#modalImportacionContadores').find('.modal-footer').children().show();

    $('#mensajeExito').hide();
    $('#modalImportacionContadores').modal('show');

    //Ocultar botón SUBIR
    $('#btn-guardarContador').hide();
});

$('#btn-guardarContador').on('click', function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarContador';

  var formData = new FormData();

  const casinoCont = $('#contSelCasino').val();
  if(casinoCont == -1){
    errorContadores('Error al obtener el casino');
    return;
  }
  formData.append('id_casino', casinoCont);
  const fechaCont = $('#fecha_hidden').val();
  if(fecha == ""){
    errorContadores('Error al obtener la fecha');
    return;
  }
  formData.append('fecha', fechaCont);
  const monedaCont = $('#contSelMoneda').val();
  if(monedaCont == -1){
    errorContadores('Error al obtener la moneda');
    return;
  }
  formData.append('id_tipo_moneda', monedaCont);

  formData.append('md5',$('#modalImportacionContadores .hashCalculado').val());


  $('#casinoInfoImportacion').val(casinoCont);
  $('#monedaInfoImportacion').val(monedaCont);
  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date(fechaCont.replaceAll('-','/')));
  $('#casinoInfoImportacion').change();
  

  //Si subió archivo lo guarda
  if($('#modalImportacionContadores #archivo').attr('data-borrado') == 'false' && $('#modalImportacionContadores #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionContadores #archivo')[0].files[0]);
  }
  else{
    errorContadores('Error al obtener el archivo');
    return;
  }


  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      console.log('Empezó');
      $('#modalImportacionContadores').find('.modal-footer').children().hide();
      $('#modalImportacionContadores').find('.modal-body').children().hide();
      $('#modalImportacionContadores').find('.modal-body').children('#iconoCarga').show();
    },
    success: function (data) {
      //existe para el casino y la fecha relevamientos visados, por lo que no se puede importar
      $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN CONTADOR');
      $('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del CONTADOR fueron importados');
      $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);
      $('#modalImportacionContadores').modal('hide');
      limpiarBodysImportaciones();
      $('#casinoInfoImportacion').change();
      $('#mensajeExito').show();
    },
    error: function (data) {
      console.log(data);
      const response = data.responseJSON;
      if(response.existeRel){
        $('#modalImportacionContadores').modal('hide');
        $('#modalErrorVisado').modal(true);
      }
      else{
        $('#modalImportacionContadores #mensajeError').show();
        $('#modalImportacionContadores #rowArchivo').hide();
        $('#modalImportacionContadores #rowFecha').hide();
        $('#modalImportacionContadores #mensajeInvalido').hide();
        $('#modalImportacionContadores #mensajeInformacion').hide();
        $('#modalImportacionContadores #iconoCarga').hide();
      }
    }
  });
});

function habilitarInputContador(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionContadores #archivo')[0].files[0] = null;
  $('#modalImportacionContadores #archivo').attr('data-borrado','false');
  $("#modalImportacionContadores #archivo").fileinput('destroy').fileinput({
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

function errorContadores(msg){
  $('#valoresArchivoContador').hide();
  $('#modalImportacionContadores #mensajeError').hide();
  $('#modalImportacionContadores #iconoCarga').hide();
  //Ocultar botón de subida
  $('#modalImportacionContadores #btn-guardarContador').hide();
  $('#modalImportacionContadores #mensajeInvalido').show();
  $('#modalImportacionContadores #mensajeInvalido p').text(msg);
}

function obtener_id_tipo_moneda(id_casino,nro_admin){
  let id_tipo_moneda = null;
  $.ajax({
    url:'importaciones/getMoneda/'+id_casino+'/'+nro_admin, 
    async: false,
    success: function(moneda) {
      if (moneda) { id_tipo_moneda = moneda.id_tipo_moneda; }
    },
    error: function(data){ console.log(data)  }
  });
  return id_tipo_moneda;
}

function procesarDatosContador(e) {
  $('#modalImportacionContadores #mensajeInvalido').hide();
  $('#modalImportacionContadores select').prop('disabled','disabled');
  $('#modalImportacionContadores #fecha input').prop('disabled','disabled');
  $('#modalImportacionContadores #fecha span').hide();
  $('#modalImportacionContadores #fecha input').val('');
  $('#contSelCasino').val(-1);
  $('#contSelMoneda').val(-1);
  $('#contSelCasino option').prop('disabled',false);
  $('#contSelMoneda option').prop('disabled',false);
  $('#valoresArchivoContador').show();

  let csv = e.target.result;
  csv = csv.replace('\r','');
  let lineas = csv.split('\n'); //Se obtienen todas las filas del archivo
  let cols = lineas[0].split(';');

  //Habilito todo y voy deshabilitando segun el archivo
  $('#contSelMoneda').val(-1).attr('disabled',false).show();
  $('#contSelCasino').val(-1).attr('disabled',false).show().find('option').attr('disabled',false);
  $('#modalImportacionContadores #fecha').data('datetimepicker').reset();
  $('#modalImportacionContadores #fecha input').prop('disabled',false);
  $('#modalImportacionContadores #fecha span').show();
  $('#btn-guardarContador').hide()
  if(cols.length == 16){ // Rosario
    $('#contSelCasino').val(3).attr('disabled','disabled');
    if(lineas.length >= 5){
      const primer_renglon = lineas[2].split(';');
      const nro_admin = primer_renglon[1].slice(0,4);
      const id_tipo_moneda = obtener_id_tipo_moneda(3,nro_admin);
      if(id_tipo_moneda != null){
        $('#contSelMoneda').val(id_tipo_moneda).attr('disabled','disabled');
      }
    }
    return $('#btn-guardarContador').show();
  }
  if(cols.length == 18){//Santa Fe o Melinque
    //Deshabilito la selección de Rosario
    $('#contSelCasino option[value="3"]').attr('disabled','disabled');
    if(lineas.length >= 3){//Si tiene maquinas, saco la fecha, casino y moneda de ahi.
      const primer_renglon = lineas[1].split(';');

      //Seteo y deshabilito las fechas
      const fecha = primer_renglon[primer_renglon.length-1];
      const ddmmyyyy = fecha.trim().split("/");
      const isofecha = ddmmyyyy[0] + '-' + ddmmyyyy[1] + '-' + ddmmyyyy[2]+'T00:00:00.0';
      const date = new Date(isofecha);
      $('#modalImportacionContadores #fecha').data('datetimepicker').setDate(date);
      $('#modalImportacionContadores #fecha input').prop('disabled','disabled');
      $('#modalImportacionContadores #fecha span').hide();

      //Seteo y deshabilito el casino
      const nro_admin = primer_renglon[3];
      const id_casino = nro_admin < 2000? 1 : 2;
      $('#contSelCasino').val(id_casino).attr('disabled','disabled');

      //Seteo y deshabilito la moneda si hay
      const id_tipo_moneda = obtener_id_tipo_moneda(id_casino,nro_admin);
      if(id_tipo_moneda != null){
        $('#contSelMoneda').val(id_tipo_moneda).attr('disabled','disabled');
      }
    }
    return $('#btn-guardarContador').show();
  }
  errorContadores('El archivo no contiene contadores de ningún casino');
  return;
}

$('#modalImportacionContadores #fecha > input').on('change', function(){
  //Si hay una fecha mostrar el mensaje de información
  if ($(this).val() != '') {
    $('#btn-guardarContador').show();
  } else {
    $('#btn-guardarContador').hide();
  }
});

//Eventos de la librería del input
$('#modalImportacionContadores #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionContadores #rowFecha').hide();
   $('#modalImportacionContadores #mensajeInformacion').hide();
   $('#modalImportacionContadores #mensajeInvalido').show();
   $('#modalImportacionContadores #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarContador').hide();

});

$('#modalImportacionContadores #archivo').on('fileclear', function(event) {
    $('#modalImportacionContadores #archivo').attr('data-borrado','true');
    $('#modalImportacionContadores #archivo')[0].files[0] = null;
    $('#modalImportacionContadores #mensajeInformacion').hide();
    $('#modalImportacionContadores #mensajeInvalido').hide();
    $('#modalImportacionContadores #rowFecha').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarContador').hide();
});

$('#modalImportacionContadores #archivo').on('fileselect', function(event) {
    $('#modalImportacionContadores #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionContadores #archivo')[0].files[0]);
    reader.onload = procesarDatosContador;
});

$('#btn-reintentarContador').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacionContadores #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionContadores #rowFecha').hide();
  $('#modalImportacionContadores #mensajeError').hide();
  $('#modalImportacionContadores #mensajeInvalido').hide();
  $('#modalImportacionContadores #mensajeInformacion').hide();
  $('#modalImportacionContadores #iconoCarga').hide();

  habilitarInputContador();
  $('#modalImportacionContadores').find('.modal-footer').children().show();
});

/*********************** PRODUCIDOS *********************************/
function agregarFilaDetalleProducido(producido) {
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-5').text(producido.nro_admin));
  fila.append($('<td>').addClass('col-xs-7').text(producido.valor));
  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-importarProducidos').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| IMPORTAR PRODUCIDOS');
  $('#modalImportacionProducidos .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();

  //Ocultar: rowFecha, mensajes, iconoCarga
  // $('#modalImportacionProducidos #rowMoneda').hide();
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #mensajeInformacion').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();

  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionProducidos').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
});

$('#btn-guardarProducido').on('click',function(e){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarProducido';

  var formData = new FormData();

  formData.append('id_casino', id_casino);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);
  formData.append('md5',$('#modalImportacionProducidos .hashCalculado').val());


  $('#casinoInfoImportacion').val(id_casino);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date(fecha_date));
  $('#casinoInfoImportacion').change();
  

  //Si subió archivo lo guarda
  if($('#modalImportacionProducidos #archivo').attr('data-borrado') == 'false' && $('#modalImportacionProducidos #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionProducidos #archivo')[0].files[0]);
  }


    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        processData: false,
        contentType:false,
        cache:false,
        beforeSend: function(data){
          console.log('Empezó');
          $('#modalImportacionProducidos').find('.modal-footer').children().hide();
          $('#modalImportacionProducidos').find('.modal-body').children().hide();

          $('#modalImportacionProducidos').find('.modal-body').children('#iconoCarga').show();
        },
        complete: function(data){
          console.log('Terminó');
        },
        success: function (data) {

          $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

          $('#modalImportacionProducidos').modal('hide');

          limpiarBodysImportaciones();

          $('#casinoInfoImportacion').change();

          $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN PRODUCIDO');

          text=data.cantidad_registros + ' registro(s) del PRODUCIDO fueron importados'
          if(data.cant_mtm_forzadas){
            text=text+ '<br>' + data.cant_mtm_forzadas +' Máquinas no reportaron'
          }

          $('#mensajeExito p').html(text);

          $('#mensajeExito').show();
        },
        error: function (data) {
          //alerta de error si el archivo ya se encuentra cargado y validado.
          var response = JSON.parse(data.responseText);
          if(response.producido_validado !== 'undefined'){
            $('#mensajeError h6').text('El Producido para esa fecha ya está validado y no se puede reimportar.')
          }
          //Mostrar: mensajeError
          $('#modalImportacionProducidos #mensajeError').show();
          //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
          $('#modalImportacionProducidos #rowArchivo').hide();
          $('#modalImportacionProducidos #mensajeInvalido').hide();
          $('#modalImportacionProducidos #mensajeInformacion').hide();
          $('#modalImportacionProducidos #iconoCarga').hide();


          console.log('ERROR!');
          console.log(data);
        }
    });
});

function habilitarInputProducido(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionProducidos #archivo')[0].files[0] = null;
  $('#modalImportacionProducidos #archivo').attr('data-borrado','false');
  $("#modalImportacionProducidos #archivo").fileinput('destroy').fileinput({
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

function procesarDatosProducidos(e) {
  const allTextLines = e.target.result.split('\n');

  const fail = function(){
    console.log((new Error()).stack);
    $('#modalImportacionProducidos #mensajeInformacion').hide();
    $('#modalImportacionProducidos #mensajeInvalido p').text('El archivo no contiene producidos');
    $('#modalImportacionProducidos #mensajeInvalido').show();
    $('#modalImportacionProducidos #iconoCarga').hide();
    //Ocultar botón de subida
    $('#btn-guardarProducido').hide();
  };

  if(allTextLines.length <= 2){
    return fail();
  }

  const columnas = allTextLines[2].split(';');
  let nro_admin = null;
  let ddmmaaaa = null;

  if(columnas.length == COL_PROD_ROS){
    id_casino = 3;
    //Se obtiene la fecha del CSV para mostrarlo
    ddmmaaaa = columnas[0].substring(0,10).split("/");

    if(allTextLines.length > 7){
      const aux = allTextLines[6].split(";")[1];
      nro_admin = aux.substring(0,aux.length-2);
    }
    else{
      return fail();
    }
  }
  else if(columnas.length == COL_PROD_SFE){
    if(columnas[0] != 1 && columnas[0] != 2){
      return fail();
    }

    id_casino = parseInt(columnas[0]);

    //Se saca la fecha del CSV en formato string
    const fecha = columnas[2];
    ddmmaaaa = [fecha.substring(6,8),fecha.substring(4,6),fecha.substring(0,4)];

    if(allTextLines.length > 2){
      const aux = allTextLines[0].split(";")[1];
      nro_admin = aux.substring(2);
    }
    else{
      return fail();
    }
  }
  else{ return fail(); }

  if(id_casino == null || nro_admin == null || ddmmaaaa == null) return fail();

  //Se modifica el date para guardalo en la BD

  switch(id_casino){
    case 1:{
      $('#modalImportacionProducidos #informacionCasino').text('CASINO MELINCUÉ');
    }break;
    case 2:{
      $('#modalImportacionProducidos #informacionCasino').text('CASINO SANTA FE');
    }break;
    case 3:{
      $('#modalImportacionProducidos #informacionCasino').text('CASINO ROSARIO');
    }break;
    default: return fail();
  }

  fecha_date = ddmmaaaa.reverse().join("/");
  $('#modalImportacionProducidos #informacionFecha').text(obtenerFechaString(ddmmaaaa.join("/"), true));

  id_tipo_moneda = obtener_id_tipo_moneda(id_casino,nro_admin);
  if(id_tipo_moneda != 1 && id_tipo_moneda != 2){
    return fail();
  }
  $('#modalImportacionProducidos #informacionMoneda').text(id_tipo_moneda == 1? 'ARS' : 'USD');
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #mensajeInformacion').show();
  //Mostrar botón SUBIR
  $('#btn-guardarProducido').show();
}

//Eventos de la librería del input
$('#modalImportacionProducidos #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionProducidos #mensajeInformacion').hide();
   $('#modalImportacionProducidos #mensajeInvalido').show();
   $('#modalImportacionProducidos #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileclear', function(event) {
    id_tipo_moneda = 0;
    $('#modalImportacionProducidos #archivo').attr('data-borrado','true');
    $('#modalImportacionProducidos #archivo')[0].files[0] = null;
    $('#modalImportacionProducidos #mensajeInformacion').hide();
    $('#modalImportacionProducidos #mensajeInvalido').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileselect', function(event) {
    $('#modalImportacionProducidos #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionProducidos #archivo')[0].files[0]);
    reader.onload = procesarDatosProducidos;
});

$('#btn-reintentarProducido').click(function(e) {
  //Mostrar: rowArchivo
  $('#modalImportacionProducidos #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionProducidos #mensajeError').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  $('#modalImportacionProducidos #mensajeInformacion').hide();
  $('#modalImportacionProducidos #iconoCarga').hide();

  habilitarInputProducido();
  $('#modalImportacionProducidos').find('.modal-footer').children().show();
});

/*********************** BENEFICIOS *********************************/
function agregarFilaDetalleBeneficio(beneficio){
  var fila = $('<tr>');

  fila.append($('<td>').addClass('col-xs-2').text(convertirDate(beneficio.fecha)));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinin));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.coinout));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.valor));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.porcentaje_devolucion));
  fila.append($('<td>').addClass('col-xs-2').text(beneficio.promedio_por_maquina));

  $('#tablaVistaPrevia tbody').append(fila);
}

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| IMPORTACIONES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

$('#btn-importarBeneficios').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| IMPORTAR BENEFICIOS');
  $('#modalImportacionBeneficios .modalNuevo').attr('style','font-family: Roboto-Black; background-color: #6dc7be;');

  //Mostrar: rowArchivo
  $('#modalImportacionBeneficios #rowArchivo').show();
  //Ocultar: rowFecha, mensajes, iconoCarga
  $('#modalImportacionBeneficios #rowMoneda').hide();
  $('#modalImportacionBeneficios #mensajeError').hide();
  $('#modalImportacionBeneficios #mensajeInvalido').hide();
  $('#modalImportacionBeneficios #mensajeInformacion').hide();
  $('#modalImportacionBeneficios #iconoCarga').hide();

  habilitarInputBeneficio();
  $('#modalImportacionBeneficios').find('.modal-footer').children().show();

  $('#mensajeExito').hide();
  $('#modalImportacionBeneficios').modal('show');

  //Ocultar botón SUBIR
  $('#btn-guardarBeneficio').hide();
});

$('#btn-guardarBeneficio').on('click', function(e){

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var url = 'importaciones/importarBeneficio';

  var formData = new FormData();


  formData.append('id_casino', 3);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);
  formData.append('md5',$('#modalImportacionBeneficios .hashCalculado').val());

  $('#casinoInfoImportacion').val(3);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  {
    const aux = fecha_date.split('/');
    $('#mesInfoImportacion').data('datetimepicker').setDate(new Date(aux[2]+'/'+aux[1]+'/'+aux[0]));
  }
  $('#casinoInfoImportacion').change();

  //Si subió archivo lo guarda
  if($('#modalImportacionBeneficios #archivo').attr('data-borrado') == 'false' && $('#modalImportacionBeneficios #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionBeneficios #archivo')[0].files[0]);
  }


  $.ajax({
      type: "POST",
      url: url,
      data: formData,
      processData: false,
      contentType:false,
      cache:false,
      beforeSend: function(data){
        console.log('Empezó');
        $('#modalImportacionBeneficios').find('.modal-footer').children().hide();
        $('#modalImportacionBeneficios').find('.modal-body').children().hide();

        $('#modalImportacionBeneficios').find('.modal-body').children('#iconoCarga').show();
      },
      complete: function(data){
        console.log('Terminó');
      },
      success: function (data) {

        $('#btn-buscarImportaciones').trigger('click',[1,10,$('#tipo_fecha').attr('value'),'desc']);

        $('#modalImportacionBeneficios').modal('hide');

        limpiarBodysImportaciones();

        $('#casinoInfoImportacion').change();


        $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN BENEFICIO');
        $('#mensajeExito p').text(data.cantidad_registros + ' registro(s) del BENEFICIO fueron importados');

        $('#mensajeExito').show();
      },
      error: function (data) {
        //Mostrar: mensajeError
        $('#modalImportacionBeneficios #mensajeError').show();
        //Ocultar: rowArchivo, rowFecha, mensajes, iconoCarga
        $('#modalImportacionBeneficios #rowArchivo').hide();
        $('#modalImportacionBeneficios #rowFecha').hide();
        $('#modalImportacionBeneficios #mensajeInvalido').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();
        $('#modalImportacionBeneficios #iconoCarga').hide();
        console.log('ERROR!');
        console.log(data);
      }
  });
});

function habilitarInputBeneficio(){
  //Inicializa el fileinput para cargar los CSV
  $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
  $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');
  $("#modalImportacionBeneficios #archivo").fileinput('destroy').fileinput({
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

function procesarDatosBeneficios(e) {
    var csv = e.target.result;

    // var allTextLines = csv.split(/\r\n|\n/);
    var allTextLines = csv.split('\n');

    console.log(allTextLines.length);

    if (allTextLines.length > 4) {
        var data = allTextLines[4].split(';');

        var tarr = [];

        for (var j=0; j<data.length; j++) {
              tarr.push(data[j]);
        }

        console.log(tarr);
        if (tarr.length == COL_BEN_ROS) {
            console.log('Está bien');
            id_casino = 3;
            //Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)
            $('#modalImportacionBeneficios #rowMoneda').show();
            $('#modalImportacionBeneficios #rowMoneda select').val(0);
            $('#modalImportacionBeneficios #mensajeInvalido').hide();

            //Info casino
            $('#modalImportacionBeneficios #informacionCasino').text('CASINO ROSARIO');
            //Info fecha
            fecha_date = tarr[0];

            $('#modalImportacionBeneficios #informacionFecha').text(obtenerFechaString(fecha_date, false));
        }
        else {
            $('#modalImportacionBeneficios #rowMoneda').hide();
            $('#modalImportacionBeneficios #mensajeInformacion').hide();

            $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
            $('#modalImportacionBeneficios #mensajeInvalido').show();

            $('#modalImportacionBeneficios #iconoCarga').hide();
            //Ocultar botón de subida
            $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
        }

    } else {

        $('#modalImportacionBeneficios #rowMoneda').hide();
        $('#modalImportacionBeneficios #mensajeInformacion').hide();

        $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
        $('#modalImportacionBeneficios #mensajeInvalido').show();

        $('#modalImportacionBeneficios #iconoCarga').hide();
        //Ocultar botón de subida
        $('#modalImportacionBeneficios #btn-guardarBeneficio').hide();
    }


}

$('#modalImportacionBeneficios #rowMoneda select').change(function(e) {
  console.log('CAMBIÓ');

  //Si se elige una moneda
  if ($(this).val() != 0) {
    id_tipo_moneda = $(this).val();

    $('#modalImportacionBeneficios #informacionMoneda').text($(this).find('option:selected').text());
    $('#modalImportacionBeneficios #iconoMoneda').show();
    $('#modalImportacionBeneficios #informacionMoneda').show();
    //Mostrar el mensaje de información
    $('#modalImportacionBeneficios #mensajeInformacion').show();
    //Mostrar botón SUBIR
    $('#btn-guardarBeneficio').show();
  } else {
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#btn-guardarBeneficio').hide();
  }

});

//Eventos de la librería del input
$('#modalImportacionBeneficios #archivo').on('fileerror', function(event, data, msg) {
   $('#modalImportacionBeneficios #rowMoneda').hide();
   $('#modalImportacionBeneficios #mensajeInformacion').hide();
   $('#modalImportacionBeneficios #mensajeInvalido').show();
   $('#modalImportacionBeneficios #mensajeInvalido p').text(msg);
   //Ocultar botón SUBIR
   $('#btn-guardarBeneficio').hide();

});

$('#modalImportacionBeneficios #archivo').on('fileclear', function(event) {
    id_tipo_moneda = 0;
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','true');
    $('#modalImportacionBeneficios #archivo')[0].files[0] = null;
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    $('#modalImportacionBeneficios #mensajeInvalido').hide();
    $('#modalImportacionBeneficios #rowMoneda').hide();
    //Ocultar botón SUBIR
    $('#btn-guardarBeneficio').hide();
});

$('#modalImportacionBeneficios #archivo').on('fileselect', function(event) {
    $('#modalImportacionBeneficios #archivo').attr('data-borrado','false');

    // Se lee el archivo guardado en el input de tipo 'file'.
    // Luego se lo maneja para saber a qué casino pertenece
    // y así, tener una forma para validar la importación.
    var reader = new FileReader();
    reader.readAsText($('#modalImportacionBeneficios #archivo')[0].files[0]);
    reader.onload = procesarDatosBeneficios;
});

/*****************PAGINACION******************/

function agregarFilasImportaciones(data, id) {
  var fila = $('<tr>');

  var meses = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];

  //Si es beneficio no se muestra el dia y se agregan los 'datas'
  if (id == null) {
    fila.append($('<td>').addClass('col-xs-3').text("-"));
    fila.append($('<td>').addClass('col-xs-3').text(meses[data.mes - 1] + ' ' + data.anio));
    fila.append($('<td>').addClass('col-xs-2').text(data.casino));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla')
                                              .attr('data-mes', data.mes)
                                              .attr('data-anio', data.anio)
                                              .attr('data-casino', data.id_casino)
                                              .attr('data-moneda', data.id_tipo_moneda)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))
                         )
                         .append($('<button>').addClass('btn btn-danger borrar')
                                              .attr('data-mes', data.mes)
                                              .attr('data-anio', data.anio)
                                              .attr('data-casino', data.id_casino)
                                              .attr('data-moneda', data.id_tipo_moneda)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }
  else {
    var archivo = typeof data.fecha_archivo == "undefined" ? "-" : convertirDate(data.fecha_archivo);
    fila.append($('<td>').addClass('col-xs-3').text(archivo));
    fila.append($('<td>').addClass('col-xs-3').text(convertirDate(data.fecha)));
    fila.append($('<td>').addClass('col-xs-2').text(data.casino));
    fila.append($('<td>').addClass('col-xs-2').text(data.tipo_moneda));
    fila.append($('<td>').addClass('col-xs-2')
                         .append($('<button>').addClass('btn btn-info planilla').val(id)
                                              .append($('<i>').addClass('far fa-fw fa-file-alt'))

                         )
                         .append($('<button>').addClass('btn btn-danger borrar').val(id)
                                              .append($('<i>').addClass('fa fa-fw fa-trash-alt'))

                         )
               )
  }


  $('#tablaImportaciones tbody').append(fila);
}

//Detectar el cambio de TIPO DE ARCHIVO
$('#tipo_archivo').on('change',function(){
    setearValueFecha();
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaImportaciones .activa').attr('value');
  var orden = $('#tablaImportaciones .activa').attr('estado');
  $('#btn-buscarImportaciones').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','#tablaImportaciones thead tr th[value]',function(e){
  $('#tablaImportaciones th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaImportaciones th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$('#btn-buscarImportaciones').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaImportaciones .activa').attr('value'),orden: $('#tablaImportaciones .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaImportaciones th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    fecha: $('#fecha_busqueda_hidden').val(),
    casinos: $('#casino_busqueda').val(),
    tipo_moneda: $('#moneda_busqueda').val(),
    seleccion: $('#tipo_archivo').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }
  console.log('FormData de buscar: ', formData);

  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();

      //Mostrar CONTADORES
      if (typeof resultados.contadores.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 1);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.contadores.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.contadores.total,clickIndice);

          $('#tituloTabla').text('Todos los contadores');

          for (var i = 0; i < resultados.contadores.data.length; i++) {
              agregarFilasImportaciones(resultados.contadores.data[i],resultados.contadores.data[i].id_contador_horario);
          }

      }else {
        //Mostrar BENEFICIOS
        if (typeof resultados.beneficios.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 3);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.beneficios.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.beneficios.total,clickIndice);

          $('#tituloTabla').text('Todos los Beneficios');

          for (var i = 0; i < resultados.beneficios.data.length; i++) {
              agregarFilasImportaciones(resultados.beneficios.data[i], null);
          }
        }
        //Mostrar PRODUCIDOS
        else if (typeof resultados.producidos.total != 'undefined') {
          $('#tablaImportaciones').attr('data-tipo', 2);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.producidos.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.producidos.total,clickIndice);

          $('#tituloTabla').text('Todos los PRODUCIDOS');

          for (var i = 0; i < resultados.producidos.data.length; i++) {
              agregarFilasImportaciones(resultados.producidos.data[i],resultados.producidos.data[i].id_producido);
          }
        }

      }
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

$(document).on('click', '#infoImportaciones thead tr th[value]', function(e) {
  $('#infoImportaciones th').removeClass('activa');
  if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
      $(e.currentTarget).children('i')
          .removeClass('fa-sort').addClass('fa fa-sort-desc')
          .parent().addClass('activa').attr('estado', 'desc');
  } else {
      if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
          $(e.currentTarget).children('i')
              .removeClass('fa-sort-desc').addClass('fa fa-sort-asc')
              .parent().addClass('activa').attr('estado', 'asc');
      } else {
          $(e.currentTarget).children('i')
              .removeClass('fa-sort-asc').addClass('fa fa-sort')
              .parent().attr('estado', '');
      }
  }
  $('#infoImportaciones th:not(.activa) i')
      .removeClass().addClass('fa fa-sort')
      .parent().attr('estado', '');
  
  $('#casinoInfoImportacion').change();
});