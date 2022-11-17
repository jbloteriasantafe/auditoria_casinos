//Cuando se sube el archivo se identifican los datos posibles
let id_casino = null;
let id_tipo_moneda = null;
var fecha_date;

//Tamaños de los diferentes archivos CSV
var COL_PROD_ROS = 4;
var COL_PROD_SFE = 32;
var COL_BEN_ROS = 8;
var COL_BEN_MEL_SFE = 14;

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
  $('.tituloSeccionPantalla').text('Importaciones');
  $('#mensajeInformacion').hide();
  
  const dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    ignoreReadonly: true,
  };
  
  const dtp_dd_mm_yyyy = {
    ...dtp,
    format: 'dd/mm/yyyy',
    pickerPosition: "top-left",
    startView: 2,
    minView: 2,
  };
  
  $('#modalImportacionContadores #fecha').datetimepicker(dtp_dd_mm_yyyy);
  
  const dtp_mm_yyyy = {
    ...dtp,
    format: 'MM yyyy',
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
  };
  
  $('#fecha_busqueda').datetimepicker(dtp_mm_yyyy);
  $('#mesInfoImportacion').datetimepicker(dtp_mm_yyyy);
  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date());

  if($('#casino_busqueda option').length == 2 ){
    $('#casino_busqueda option:eq(1)').prop('selected', true);
  }
  $('#casino_busqueda,#casinoInfoImportacion').change();
});


$('#casinoInfoImportacion').change(function() {
  $('#monedaInfoImportacion').change();
});

$('#mesInfoImportacion').on("change.datetimepicker",function(){
  $('#monedaInfoImportacion').change();
});

$('#monedaInfoImportacion').change(function() {
  const id_moneda = $('#monedaInfoImportacion').val();
  if (id_moneda == 1) $('.tablaBody').removeClass('dolares').addClass('pesos');
  else $('.tablaBody').removeClass('pesos').addClass('dolares');

  const fecha_sort = $('#infoImportaciones .activa').attr('estado');
  cargarTablasImportaciones($('#casinoInfoImportacion').val(), id_moneda, fecha_sort);
});

function limpiarBodysImportaciones() {
  $('.tablaBody').hide().find('tr:not(#moldeFilaImportacion)').remove();
}

function cargarTablasImportaciones(casino, moneda, fecha_sort) {
  const fecha = $('#mes_info_hidden').val();
  $.get(`importaciones/${casino}/${fecha}/${fecha_sort ?? ''}`, function(data) {
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
      default:
        throw 'Casino no implementado';
    }
    
    data.arreglo.forEach(function(v){
      const fila = $('#moldeFilaImportacion').clone();
      fila.removeAttr('id');
      fila.find('.fecha').text(convertirDate(v.fecha));
      ['contador','producido','beneficio'].forEach(function(t){
        fila.find('.'+t).addClass(v?.[t]?.[moneda]? 'true' : 'false');
      });
      tablaBody.append(fila);
      fila.show();
    });

    tablaBody.show();
  });
  $('#moldeFilaImportacion').hide();
}


function setearValueFecha() {
  const tipo_archivo = $('#tipo_archivo').val();

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
    default:
      throw 'Tipo de archivo no implementado = '+tipo_archivo;
  }
}

function obtenerFechaString(dateFecha, conDia) {
  const arrayFecha = dateFecha.split('/');
  const meses = ['ERROR','ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
  return `${arrayFecha[0]} ${meses[arrayFecha[1]]} ${conDia? arrayFecha[2] : ''}`;
}

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

function mostrarBeneficio(data){
  $('#modalPlanilla .modal-title').text('VISTA PREVIA BENEFICIO');
  $('#modalPlanilla #fecha').val(convertirDate(data.beneficios[0].fecha).substring(3,11));
  $('#tablaVistaPrevia thead #headerBeneficio').show();
  data.beneficios.forEach(function(b){
    const fila = $('#moldeBeneficio').clone().removeAttr('id');
    fila.find('.fecha').text(convertirDate(b.fecha));
    fila.find('.coinin').text(b.coinin);
    fila.find('.coinout').text(b.coinout);
    fila.find('.valor').text(b.valor);
    fila.find('.pdev').text(b.porcentaje_devolucion);
    fila.find('.promedio').text(b.promedio_por_maquina);
    $('#tablaVistaPrevia tbody').append(fila);
  });
}

function mostrarProducido(data){
  $('#modalPlanilla .modal-title').text('VISTA PREVIA PRODUCIDO');
  $('#modalPlanilla #fecha').val(convertirDate(data.producido.fecha));
  $('#tablaVistaPrevia thead #headerProducido').show();
  data.detalles_producido.forEach(function(p){
    const fila = $('#moldeProducido').clone().removeAttr('id');
    fila.find('.mtm').text(p.nro_admin);
    fila.find('.valor').text(p.valor);
    $('#tablaVistaPrevia tbody').append(fila);
  });
}

function mostrarContador(data){
  $('#modalPlanilla .modal-title').text('VISTA PREVIA CONTADOR');
  $('#modalPlanilla #fecha').val(convertirDate(data.contador.fecha));
  $('#tablaVistaPrevia thead #headerContador').show();
  data.detalles_contador.forEach(function(c){
    const fila = $('#moldeContador').clone().removeAttr('id');
    fila.find('.mtm').text(c.nro_admin);
    fila.find('.coinin').text(c.coinin);
    fila.find('.coinout').text(c.coinout);
    fila.find('.jackpot').text(c.jackpot);
    fila.find('.progresivo').html(c.progresivo ?? '&nbsp;');
    $('#tablaVistaPrevia tbody').append(fila);
  });
}

$(document).on('click','.planilla', function(){
  //Limpiar el modal
  $('#modalPlanilla').find('#fecha,#casino,#tipo_moneda,.modal-title').val('');
  $('#tablaVistaPrevia thead tr').hide();
  $('#tablaVistaPrevia tbody').empty();

  const formData = {
    mes: $(this).attr('data-mes'),
    anio: $(this).attr('data-anio'),
    id_tipo_moneda: $(this).attr('data-moneda'),
    id_casino: $(this).attr('data-casino'),
    id: $(this).val(),
  };
  const tipo_importacion = $('#tablaImportaciones').attr('data-tipo');
  const tmap = {
    1:['Contadores',mostrarContador],
    2:['Producidos',mostrarProducido],
    3:['Beneficios',mostrarBeneficio]
  };
  if(!(tipo_importacion in tmap)) throw 'Error tipo de importacion = '+tipo_importacion;
  const url = tmap[tipo_importacion][0];
  const mostrar = tmap[tipo_importacion][1];

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importaciones/preview'+url,
    data: formData,
    dataType: 'json',
    success: function(data){
      $('#modalPlanilla #casino').val(data.casino.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      mostrar(data);
      $('#modalPlanilla').modal('show');
    },
    error: function (data) { console.log(data); }
  });
});

$(document).on('click','.borrar',function(){  
  let nombre_tipo_archivo = null;
  const tipo_archivo = $('#tablaImportaciones').attr('data-tipo');
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
    default:
      throw 'Tipo de archivo no implementado = '+tipo_archivo;
  }
  
  const id_importacion = $(this).val();
  const casino = $(this).attr('data-casino');
  const moneda = $(this).attr('data-moneda');
  const anio   = $(this).attr('data-anio');
  const mes    = $(this).attr('data-mes');
  //Se muestra el modal de confirmación de eliminación
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val(id_importacion).attr('data-tipo',tipo_archivo)
  .attr('data-casino',casino).attr('data-moneda',moneda).attr('data-anio',anio).attr('data-mes',mes);
  $('#titulo-modal-eliminar').text('¿Seguro desea eliminar el '+ nombre_tipo_archivo + '?');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
  const id_importacion = $(this).val();
  const tipo_archivo = $(this).attr('data-tipo');
  let url = null;
  switch(tipo_archivo){
    case '1':
      url = `contadores/eliminarContador/${id_importacion}`;
      break;
    case '2':
      url = `producidos/eliminarProducido/${id_importacion}`;
      break;
    case '3':
      const desc_ben = [$(this).attr('data-casino'),$(this).attr('data-moneda'),$(this).attr('data-anio'),$(this).attr('data-mes')].join('/');
      url = `beneficios/eliminarBeneficios/${desc_ben}`;
      break;
    default:
      throw 'Tipo de archivo no implementado - '+tipo_archivo;
  }
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "DELETE",
    url: url,
    success: function (data) {
      $('#btn-buscarImportaciones').click();
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});

/*********************** CONTADORES **********************************/
function formatNumber(f){
  return f !== null && f !== undefined? f.toLocaleString() : '&nbsp;';
}

$('#btn-importarContadores').click(function(e){
  e.preventDefault();
  $('#mensajeExito').hide();
  $('#modalImportacionContadores #rowArchivo').show();
  $('#modalImportacionContadores .modal-footer').children().show();
  $('#modalImportacionContadores')
  .find('#valoresArchivoContador,#mensajeError,\
         #mensajeInvalido,#iconoCarga,#btn-guardarContador').hide();
  habilitarInputContador();
  $('#modalImportacionContadores').modal('show');
});

$('#btn-guardarContador').on('click', function(e){
  e.preventDefault();
  
  const formData = new FormData();  
  formData.append('md5',$('#modalImportacionContadores .hashCalculado').val());

  const casinoCont = $('#contSelCasino').val();
  if(casinoCont == -1){
    return errorContadores('Error al obtener el casino');
  }
  formData.append('id_casino', casinoCont);
  $('#casinoInfoImportacion').val(casinoCont);
  
  const fechaCont = $('#fecha_hidden').val();
  if(fecha == ""){
    return errorContadores('Error al obtener la fecha');
  }
  formData.append('fecha', fechaCont);
  $('#mesInfoImportacion').data('datetimepicker').setDate(new Date(fechaCont.replaceAll('-','/')));
  
  const monedaCont = $('#contSelMoneda').val();
  if(monedaCont == -1){
    return errorContadores('Error al obtener la moneda');
  }
  formData.append('id_tipo_moneda', monedaCont);
  $('#monedaInfoImportacion').val(monedaCont).change();
  
  if($('#modalImportacionContadores #archivo').attr('data-borrado') == 'false' && $('#modalImportacionContadores #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionContadores #archivo')[0].files[0]);
  }
  else{
    return errorContadores('Error al obtener el archivo');
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'importaciones/importarContador',
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      $('#modalImportacionContadores').find('.modal-footer').children().hide();
      $('#modalImportacionContadores').find('.modal-body').children().hide();
      $('#modalImportacionContadores').find('.modal-body').children('#iconoCarga').show();
    },
    success: function (data) {
      //existe para el casino y la fecha relevamientos visados, por lo que no se puede importar
      $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN CONTADOR');
      $('#mensajeExito p').text(`${data.cantidad_registros} registro(s) del CONTADOR fueron importados`);
      $('#btn-buscarImportaciones').click();
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
        $('#modalErrorVisado').modal('show');
      }
      else{
        $('#modalImportacionContadores #mensajeError').show();
        $('#modalImportacionContadores')
          .find('#rowArchivo,#mensajeInvalido,#iconoCarga').hide();
      }
    }
  });
});

function habilitarInputContador(){
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
  }).attr('data-borrado','false')[0].files[0] = null;
}

function errorContadores(msg){
  $('#modalImportacionContadores')
    .find('#valoresArchivoContador,#mensajeError,\
           #iconoCarga,#btn-guardarContador').hide();
  $('#modalImportacionContadores #mensajeInvalido p').text(msg);
  $('#modalImportacionContadores #mensajeInvalido').show();
}

function obtener_id_tipo_moneda(id_casino,nro_admin){
  let id_tipo_moneda = null;
  $.ajax({
    url: `importaciones/getMoneda/{$id_casino}/${nro_admin}`,
    async: false,
    success: function(moneda) {
      if (moneda) { id_tipo_moneda = moneda.id_tipo_moneda; }
    },
    error: function(data){ console.log(data)  }
  });
  return id_tipo_moneda;
}

function obtener_casinos(nro_admin){
  const ids = [];
  $.ajax({//Lo mas probable es que retorne 1 pero podria retornar mas...
    url: `importaciones/getCasinos/${nro_admin}`,
    async: false,
    success: function(casinos) {
      for(const cidx in casinos){
        ids.push(casinos[cidx]);
      }
    },
    error: function(data){ console.log(data)  }
  });
  return ids;
}

function procesarDatosContador(e) {
  $('#modalImportacionContadores #mensajeInvalido').hide();
  $('#modalImportacionContadores #fecha span').show();
  $('#modalImportacionContadores #fecha input')
    .attr('disabled',false).val('');
  $('#modalImportacionContadores #fecha')
    .data('datetimepicker').reset();
  $('#contSelCasino,#contSelMoneda').attr('disabled',false)
    .val(-1).show().find('option').attr('disabled',false);
  $('#valoresArchivoContador').show();
  $('#btn-guardarContador').hide()
  
  const csv = e.target.result.replace('\r','');
  const lineas = csv.split('\n'); //Se obtienen todas las filas del archivo
  const cols = lineas.length? lineas[0].split(';') : [];
  if(cols.length == 16){ // Rosario
    $('#contSelCasino').val(3).attr('disabled',true);
    if(lineas.length >= 5){
      const primer_renglon = lineas[2].split(';');
      const nro_admin = primer_renglon[1].slice(0,4);
      const id_tipo_moneda = obtener_id_tipo_moneda(3,nro_admin);
      if(id_tipo_moneda != null){
        $('#contSelMoneda').val(id_tipo_moneda).attr('disabled',true);
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
      const ddmmyyyy = primer_renglon[primer_renglon.length-1].trim().split('/');
      const date = new Date(ddmmyyyy.reverse().join('-')+'T00:00:00.0');
      $('#modalImportacionContadores #fecha').data('datetimepicker').setDate(date);
      $('#modalImportacionContadores #fecha input').prop('disabled','disabled');
      $('#modalImportacionContadores #fecha span').hide();

      //Seteo y deshabilito el casino
      const nro_admin = primer_renglon[3];
      const casinos = obtener_casinos(nro_admin).filter(function(id_casino) { return id_casino != 3; });
      
      $('#contSelCasino').find('option[value!="-1"]').attr('disabled',true);
      casinos.forEach(function(c){
        $('#contSelCasino').find(`option[value="${c}"]`).attr('disabled',false);
      });
      if(casinos.length == 1){
        $('#contSelCasino').val(casinos[0]).attr('disabled','disabled');
        //Seteo y deshabilito la moneda si hay
        const id_tipo_moneda = obtener_id_tipo_moneda(casinos[0],nro_admin);
        if(id_tipo_moneda != null){
          $('#contSelMoneda').val(id_tipo_moneda).attr('disabled','disabled');
        }
      }
    }
    return $('#btn-guardarContador').show();
  }
  errorContadores('El archivo no contiene contadores de ningún casino');
}

//Si hay una fecha mostrar el mensaje de información
$('#modalImportacionContadores #fecha > input').on('change', function(){
  $('#btn-guardarContador').toggle($(this).val() != '');
});

//Eventos de la librería del input
$('#modalImportacionContadores #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacionContadores')
    .find('#mensajeInformacion,#btn-guardarContador').hide();
  $('#modalImportacionContadores #mensajeInvalido p').text(msg);
  $('#modalImportacionContadores #mensajeInvalido').show();
});

$('#modalImportacionContadores #archivo').on('fileclear', function(event) {
  $('#modalImportacionContadores')
    .find('#mensajeInformacion,#btn-guardarContador').hide();
  $('#modalImportacionContadores #archivo')
    .attr('data-borrado','true')[0].files[0] = null;
});

$('#modalImportacionContadores #archivo').on('fileselect', function(event) {
  const reader = new FileReader();
  reader.onload = procesarDatosContador;
  reader.readAsText(
    $('#modalImportacionContadores #archivo')
      .attr('data-borrado','false')[0].files[0]
  );
});

$('#btn-reintentarContador').click(function(e) {
  $('#modalImportacionContadores #rowArchivo').show();
  $('#modalImportacionContadores')
    .find('#mensajeError,#mensajeInvalido,#mensajeInformacion,#iconoCarga').hide();
  habilitarInputContador();
  $('#modalImportacionContadores .modal-footer').children().show();
});

/*********************** PRODUCIDOS *********************************/
$('#btn-importarProducidos').click(function(e){
  e.preventDefault();  
  $('#mensajeExito').hide();
  $('#modalImportacionProducidos #rowArchivo').show();
  $('#modalImportacionProducidos')
    .find('#mensajeError,#mensajeInvalido,#mensajeInformacion,\
           #iconoCarga,#btn-guardarProducido').hide();
  habilitarInputProducido();
  $('#modalImportacionProducidos .modal-footer').children().show();
  $('#modalImportacionProducidos').modal('show');
});

$('#btn-guardarProducido').on('click',function(e){
  e.preventDefault();

  const formData = new FormData();
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

  $.ajaxSetup({headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'importaciones/importarProducido',
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      $('#modalImportacionProducidos').find('.modal-footer,.modal-body').children().hide();
      $('#modalImportacionProducidos .modal-body #iconoCarga').show();
    },
    success: function (data) {
      $('#btn-buscarImportaciones').click();
      $('#modalImportacionProducidos').modal('hide');
      limpiarBodysImportaciones();
      $('#casinoInfoImportacion').change();
      $('#mensajeExito h3').text('ÉXITO DE IMPORTACIÓN PRODUCIDO');
      let text = `${data.cantidad_registros} registro(s) del PRODUCIDO fueron importados`;
      if(data.cant_mtm_forzadas){
        text+=`<br>${data.cant_mtm_forzadas}  Máquinas no reportaron`;
      }
      $('#mensajeExito p').html(text);
      $('#mensajeExito').show();
    },
    error: function (data) {
      console.log(data);
      //alerta de error si el archivo ya se encuentra cargado y validado.
      const response = data.responseJSON;
      if(response.producido_validado !== 'undefined'){
        $('#mensajeError h6').text('El Producido para esa fecha ya está validado y no se puede reimportar.')
      }
      //Mostrar: mensajeError
      $('#modalImportacionProducidos #mensajeError').show();
      $('#modalImportacionProducidos')
        .find('#rowArchivo,#mensajeInvalido,#mensajeInformacion,#iconoCarga').hide();
    }
  });
});

function habilitarInputProducido(){
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
  }).attr('data-borrado','false')[0].files[0] = null
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
  $('#modalImportacionProducidos #archivo')
    .attr('data-borrado','true')[0].files[0] = null;
  $('#modalImportacionProducidos #mensajeInformacion').hide();
  $('#modalImportacionProducidos #mensajeInvalido').hide();
  //Ocultar botón SUBIR
  $('#btn-guardarProducido').hide();
});

$('#modalImportacionProducidos #archivo').on('fileselect', function(event) {
  const reader = new FileReader();
  reader.onload = procesarDatosProducidos;
  reader.readAsText(
    $('#modalImportacionProducidos #archivo')
      .attr('data-borrado','false')[0].files[0]
  );
});

$('#btn-reintentarProducido').click(function(e) {
  $('#modalImportacionProducidos #rowArchivo').show();
  $('#modalImportacionProducidos')
    .find('#mensajeError,#mensajeInvalido,#mensajeInformacion,#iconoCarga').hide();
  habilitarInputProducido();
  $('#modalImportacionProducidos .modal-footer').children().show();
});

/*********************** BENEFICIOS *********************************/
$('#btn-importarBeneficios').click(function(e){
  e.preventDefault();
  $('#mensajeExito').hide();
  $('#modalImportacionBeneficios #rowArchivo').show();
  $('#modalImportacionBeneficios')
    .find('#rowMoneda,#mensajeError,#mensajeInvalido,\
           #mensajeInformacion,#iconoCarga,#btn-guardarBeneficio').hide();
  habilitarInputBeneficio();
  $('#modalImportacionBeneficios .modal-footer').children().show();
  $('#modalImportacionBeneficios').modal('show');
});

$('#btn-guardarBeneficio').on('click', function(e){
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  e.preventDefault();

  const formData = new FormData();
  formData.append('id_casino', id_casino);
  formData.append('fecha', fecha_date);
  formData.append('id_tipo_moneda',id_tipo_moneda);
  formData.append('md5',$('#modalImportacionBeneficios .hashCalculado').val());

  $('#casinoInfoImportacion').val(id_casino);
  $('#monedaInfoImportacion').val(id_tipo_moneda);
  $('#mesInfoImportacion').data('datetimepicker')
    .setDate(new Date(fecha_date.split('/').reverse().join('-')));
  $('#casinoInfoImportacion').change();

  //Si subió archivo lo guarda
  if($('#modalImportacionBeneficios #archivo').attr('data-borrado') == 'false' && $('#modalImportacionBeneficios #archivo')[0].files[0] != null){
    formData.append('archivo' , $('#modalImportacionBeneficios #archivo')[0].files[0]);
  }

  $.ajax({
    type: "POST",
    url: 'importaciones/importarBeneficio',
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      $('#modalImportacionBeneficios').find('.modal-footer').children().hide();
      $('#modalImportacionBeneficios').find('.modal-body').children().hide();
      $('#modalImportacionBeneficios').find('.modal-body').children('#iconoCarga').show();
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
      console.log(data);
      $('#modalImportacionBeneficios #mensajeError').show();
      $('#modalImportacionBeneficios')
        .find('#rowArchivo,#mensajeInvalido,\
               #mensajeInformacion,#iconoCarga').hide();
    }
  });
});

function habilitarInputBeneficio(){
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
  }).attr('data-borrado','false')[0].files[0] = null
}

function procesarDatosBeneficios(e) {
  const fail = function(){
    console.log((new Error()).stack);
    $('#modalImportacionBeneficios')
      .find('#rowMoneda,#mensajeInformacion,#iconoCarga,#btn-guardarBeneficio').hide();
    $('#modalImportacionBeneficios #mensajeInvalido p').text('El archivo no contiene beneficios');
    $('#modalImportacionBeneficios #mensajeInvalido').show();
  }
  const csv = e.target.result;
  const allTextLines = csv.split('\n');
  if(allTextLines.length < 1) return fail();

  id_casino = null;
  fecha_date = null;
  id_tipo_moneda = 0;
  $('#modalImportacionBeneficios #rowMoneda select').val(0);
  $('#modalImportacionBeneficios #informacionCasino').text('');
  
  const columnas = allTextLines[0].split(';');
  if(columnas.length == COL_BEN_MEL_SFE){
    const cas_fecha_timestamp = columnas[1].split("_");
    id_casino = parseInt(cas_fecha_timestamp[0]);
    fecha_date = cas_fecha_timestamp[1].substr(6,2)
    +'/'+cas_fecha_timestamp[1].substr(4,2)
    +'/'+cas_fecha_timestamp[1].substr(0,4);
  }
  if(id_casino === null) {//Pruebo procesar Rosario
    if(allTextLines.length <= 4) return fail();
    const columnas = allTextLines[4].split(';');
    if(columnas.length != COL_BEN_ROS) return fail();
    id_casino = 3;
    fecha_date = columnas[0];
  }
  //Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)
  $('#modalImportacionBeneficios #rowMoneda').show();
  $('#modalImportacionBeneficios #mensajeInvalido').hide();
  switch(id_casino){
    case 1:
      $('#modalImportacionBeneficios #informacionCasino').text('CASINO MELINCUÉ');
      break;
    case 2:
      $('#modalImportacionBeneficios #informacionCasino').text('CASINO SANTA FE');
      break;
    case 3:
      $('#modalImportacionBeneficios #informacionCasino').text('CASINO ROSARIO');
      break;
    default:
      return fail();
  }
  $('#modalImportacionBeneficios #informacionFecha').text(obtenerFechaString(fecha_date, false));
}

$('#modalImportacionBeneficios #rowMoneda select').change(function(e) {
  if($(this).val() == 0){
    $('#modalImportacionBeneficios #mensajeInformacion').hide();
    return $('#btn-guardarBeneficio').hide();
  }
  id_tipo_moneda = $(this).val();
  $('#modalImportacionBeneficios #informacionMoneda').text($(this).find('option:selected').text());
  $('#modalImportacionBeneficios').find(
    '#iconoMoneda,#informacionMoneda,#mensajeInformacion,#btn-guardarBeneficio'
  ).show();
});

//Eventos de la librería del input
$('#modalImportacionBeneficios #archivo').on('fileerror', function(event, data, msg) {
  $('#modalImportacionBeneficios').find(
    '#rowMoneda,#mensajeInformacion,#btn-guardarBeneficio'
  ).hide();
  $('#modalImportacionBeneficios #mensajeInvalido p').text(msg);
  $('#modalImportacionBeneficios #mensajeInvalido').show();
});

$('#modalImportacionBeneficios #archivo').on('fileclear', function(event) {
  id_tipo_moneda = 0;
  $('#modalImportacionBeneficios #archivo')
    .attr('data-borrado','true')[0].files[0] = null;
  $('#modalImportacionBeneficios').find(
    '#rowMoneda,#mensajeInformacion,#btn-guardarBeneficio,#mensajeInvalido'
  ).hide();
});

$('#modalImportacionBeneficios #archivo').on('fileselect', function(event) {
  const reader = new FileReader();
  reader.onload = procesarDatosBeneficios;
  reader.readAsText(
    $('#modalImportacionBeneficios #archivo')
      .attr('data-borrado','false')[0].files[0]
  );
});

/*****************PAGINACION******************/

function agregarFilasImportaciones(data, id) {
  const meses = [null,'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  const f_prod = id == null? '-' : (typeof data.fecha_archivo == "undefined" ? "-" : convertirDate(data.fecha_archivo));
  const fecha = id == null? `${meses[data.mes]} ${data.anio}` : convertirDate(data.fecha);
  
  const fila = $('#moldeFilaImp').clone().removeAttr('id');
  fila.find('.fecha_produccion').text(f_prod);
  fila.find('.fecha').text(fecha);
  fila.find('.casino').text(data.casino);
  fila.find('.moneda').text(data.tipo_moneda);
  fila.find('button').val(id).attr('data-mes', data.mes)
  .attr('data-anio', data.anio).attr('data-casino', data.id_casino)
  .attr('data-moneda', data.id_tipo_moneda);
  $('#tablaImportaciones tbody').append(fila);
}

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

$('#filtrosBusquedaImportaciones .form-control').change(function(){
  setearValueFecha();
  $('#btn-buscarImportaciones').click();
});

$('#btn-buscarImportaciones').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();
  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaImportaciones .activa').attr('value'),orden: $('#tablaImportaciones .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaImportaciones th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: {
      fecha: $('#fecha_busqueda_hidden').val(),
      casinos: $('#casino_busqueda').val(),
      tipo_moneda: $('#moneda_busqueda').val(),
      seleccion: $('#tipo_archivo').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();

      if (typeof resultados.contadores.total != 'undefined') {
        $('#tablaImportaciones').attr('data-tipo', 1);
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.contadores.total,clickIndice);
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.contadores.total,clickIndice);
        $('#tituloTabla').text('Todos los contadores');
        resultados.contadores.data.forEach(function(c,idx){
          agregarFilasImportaciones(c,c.id_contador_horario)
        });
      }
      else if (typeof resultados.producidos.total != 'undefined') {
        $('#tablaImportaciones').attr('data-tipo', 2);
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.producidos.total,clickIndice);
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.producidos.total,clickIndice);
        $('#tituloTabla').text('Todos los PRODUCIDOS');
        resultados.producidos.data.forEach(function(p,idx){
          agregarFilasImportaciones(p,p.id_producido);
        });
      }
      else if (typeof resultados.beneficios.total != 'undefined') {
        $('#tablaImportaciones').attr('data-tipo', 3);
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.beneficios.total,clickIndice);
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.beneficios.total,clickIndice);
        $('#tituloTabla').text('Todos los Beneficios');
        resultados.beneficios.data.forEach(function(b,idx){
          agregarFilasImportaciones(b,null);
        });
      }
      else throw 'Tipo de archivo no implementado';
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

$(document).on('click', '#infoImportaciones thead tr th[value]', function(e) {
  $('#infoImportaciones th').removeClass('activa');
  const i = $(this).children('i');
  const sin_ord = i.hasClass('fa-sort');
  const ord_desc = i.hasClass('fa-sort-desc');
  i.removeClass('fa-sort fa-sort-desc fa-sort-asc');
  if (sin_ord) {
    i.addClass('fa-sort-desc').parent().addClass('activa').attr('estado', 'desc');
  } 
  else if(ord_desc) {
    i.addClass('fa-sort-asc').parent().addClass('activa').attr('estado', 'asc');
  } 
  else {
    i.addClass('fa-sort').parent().attr('estado', '');
  }
  
  $('#infoImportaciones th i').not(i)
  .removeClass('fa-sort-desc fa-sort-asc').addClass('fa-sort')
  .parent().attr('estado', '');
  
  $('#casinoInfoImportacion').change();
});
