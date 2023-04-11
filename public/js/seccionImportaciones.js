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
  
  $('#fecha_imp').datetimepicker(dtp_dd_mm_yyyy);
  
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

/* TODO LO DE LA TABLA DE IMPORTACIONES */
$('#casinoInfoImportacion,#monedaInfoImportacion').change(cargarTablasImportaciones);
$('#mesInfoImportacion').on("change.datetimepicker",cargarTablasImportaciones);
function cargarTablasImportaciones() {
  const casino = $('#casinoInfoImportacion').val();
  const fecha = $('#mes_info_hidden').val();
  const fecha_sort = $('#infoImportaciones .activa').attr('estado') ?? '';
  const moneda = $('#monedaInfoImportacion').val();
  $('#infoImportaciones tbody').empty().attr('data-casino',casino);
  $.get(`importaciones/${casino}/${fecha}/${fecha_sort}`, function(data) {
    data.arreglo.forEach(function(v){
      const fila = $('#moldeFilaImportacion').clone().removeAttr('id');
      fila.find('.fecha').text(convertirDate(v.fecha));
      ['contador','producido','beneficio'].forEach(function(t){
        fila.find('.'+t).attr('data-imp',!!(v?.[t]?.[moneda])+0);
      });
      $('#infoImportaciones tbody').append(fila);
      fila.show();
    });
  });
}
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
  
  cargarTablasImportaciones();
});

/* TODO LO DE LOS BOTONES DE IMPORTAR */
$('#btn-minimizar').click(function(){//Opacidad del modal al minimizar
  const minimizar = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

$('.btn-importar').click(function(e){
  e.preventDefault();
  const tipo = $(this).attr('data-importacion');
  $('#modalImportacion').data('tipo',tipo);
  $('#mensajeExito').hide();
  $('#modalImportacion .modal-title').text('| IMPORTADOR '+tipo.toUpperCase());
  
  $('#modalImportacion #rowArchivo').show();
  $('#modalImportacion .modal-footer').children().show();
  $('#modalImportacion')
  .find('#valoresArchivo,#mensajeError,\
         #mensajeInvalido,#iconoCarga,#btn-guardarImp').hide();
         
  $("#modalImportacion #archivo").fileinput('destroy').fileinput({
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
  
  $('#modalImportacion').modal('show');
});
//Si hay una fecha mostrar el mensaje de información
$('#modalImportacion #fecha_imp > input').on('change', function(){
  $('#btn-guardarImp').toggle($(this).val() != '');
});
//Eventos de la librería del input
$('#modalImportacion #archivo').on('fileerror', function(event, data, msg) {
  failImportacion(msg);
});

function failImportacion(mensaje = ''){
  console.log((new Error()).stack);
  $('#btn-guardarImp,#valoresArchivo,#iconoCarga').hide();
  $('#fecha_imp').data('datetimepicker').reset();
  $('#casinoImp').val(-1);
  $('#monedaImp').val(-1);
  $('#rowArchivo .hashRecibido').val('');
  $('#mensajeInvalido p').text(mensaje);
  $('#mensajeInvalido').show();
}

$('#modalImportacion #archivo').on('fileclear', function(event) {
  $('#btn-guardarImp,#mensajeInvalido,#valoresArchivo').hide();
  $('#fecha_imp').data('datetimepicker').reset();
  $('#casinoImp').val(-1);
  $('#monedaImp').val(-1);
  $('#rowArchivo').find('.hashCalculado,.hashRecibido').val('');
  $('#modalImportacion #archivo')
    .attr('data-borrado','true')[0].files[0] = null;
});

$('#modalImportacion #archivo').on('fileselect', function(event) {
  $('#mensajeInvalido').hide();
  const reader = new FileReader();
  const tipo = $('#modalImportacion').data('tipo');
  const tmap = {
    contadores: procesarDatosContador,
    producidos: procesarDatosProducidos,
    beneficios: procesarDatosBeneficios,
  };
  if(!(tipo in tmap)) throw 'Tipo de importación no implementada = '+tipo;
  reader.onload = tmap[tipo];
  reader.readAsText(
    $('#modalImportacion #archivo')
      .attr('data-borrado','false')[0].files[0]
  );
});
$('#btn-reintentarImp').click(function(e) {
  $('#modalImportacion #rowArchivo').show();
  $('#modalImportacion')
    .find('#mensajeError,#mensajeInvalido,#iconoCarga').hide();
  $('#modalImportacion .modal-footer').children().show();
});

function obtener_id_tipo_moneda(id_casino,nro_admin){
  let id_tipo_moneda = null;
  $.ajax({
    url: `importaciones/getMoneda/${id_casino}/${nro_admin}`,
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
  $('#mensajeInvalido').hide();
  $('#fecha_imp span').show();
  $('#fecha_imp input')
    .attr('disabled',false).val('');
  $('#fecha_imp')
    .data('datetimepicker').reset();
  $('#casinoImp,#monedaImp').attr('disabled',false)
    .val(-1).show().find('option').attr('disabled',false);
  $('#valoresArchivo').show();
  $('#btn-guardarImp').hide()
  
  const csv = e.target.result.replace('\r','');
  const lineas = csv.split('\n'); //Se obtienen todas las filas del archivo
  const cols = lineas.length? lineas[0].split(';') : [];
  if(cols.length == 16){ // Rosario
    $('#casinoImp').val(3).attr('disabled',true);
    if(lineas.length >= 5){
      const primer_renglon = lineas[2].split(';');
      const nro_admin = primer_renglon[1].slice(0,4);
      const id_tipo_moneda = obtener_id_tipo_moneda(3,nro_admin);
      if(id_tipo_moneda != null){
        $('#monedaImp').val(id_tipo_moneda).attr('disabled',true);
      }
    }
    return $('#btn-guardarImp').show();
  }
  if(cols.length == 18){//Santa Fe o Melinque
    //Deshabilito la selección de Rosario
    $('#casinoImp option[value="3"]').attr('disabled','disabled');
    if(lineas.length >= 3){//Si tiene maquinas, saco la fecha, casino y moneda de ahi.
      const primer_renglon = lineas[1].split(';');

      //Seteo y deshabilito las fechas
      const yyyymmdd = primer_renglon[primer_renglon.length-1].trim().split('/');
      const date = new Date(yyyymmdd.join('-')+'T00:00:00.0');
      $('#fecha_imp').data('datetimepicker').setDate(date);
      $('#fecha_imp input').prop('disabled','disabled');
      $('#fecha_imp span').hide();

      //Seteo y deshabilito el casino
      const nro_admin = primer_renglon[3];
      const casinos = obtener_casinos(nro_admin).filter(function(id_casino) { return id_casino != 3; });
      
      $('#casinoImp').find('option[value!="-1"]').attr('disabled',true);
      casinos.forEach(function(c){
        $('#casinoImp').find(`option[value="${c}"]`).attr('disabled',false);
      });
      if(casinos.length == 1){
        $('#casinoImp').val(casinos[0]).attr('disabled','disabled');
        //Seteo y deshabilito la moneda si hay
        const id_tipo_moneda = obtener_id_tipo_moneda(casinos[0],nro_admin);
        if(id_tipo_moneda != null){
          $('#monedaImp').val(id_tipo_moneda).attr('disabled','disabled');
        }
      }
    }
    return $('#btn-guardarImp').show();
  }
  failImportacion('El archivo no contiene contadores de ningún casino');
}

function procesarDatosProducidos(e) {
  const allTextLines = e.target.result.split('\n');

  if(allTextLines.length <= 2){
    return failImportacion('El archivo no contiene producidos');
  }

  const columnas = allTextLines[2].split(';');
  let nro_admin = null;
  let ddmmaaaa = null;
  let id_casino = null;
  if(columnas.length == 4){
    id_casino = 3;
    //Se obtiene la fecha del CSV para mostrarlo
    ddmmaaaa = columnas[0].substring(0,10).split("/");

    if(allTextLines.length > 7){
      const aux = allTextLines[6].split(";")[1];
      nro_admin = aux.substring(0,aux.length-2);
    }
    else{
      return failImportacion('El archivo no contiene producidos');
    }
  }
  else if(columnas.length == 32){
    if(columnas[0] != 1 && columnas[0] != 2){
      return failImportacion('El archivo no contiene producidos');
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
      return failImportacion('El archivo no contiene producidos');
    }
  }
  else{ return failImportacion('El archivo no contiene producidos'); }

  if(id_casino == null || nro_admin == null || ddmmaaaa == null) return failImportacion('El archivo no contiene producidos');
  const id_tipo_moneda = obtener_id_tipo_moneda(id_casino,nro_admin);
  if(id_tipo_moneda == null) return failImportacion('El archivo no contiene producidos');

  //Se modifica el date para guardalo en la BD
  const fecha_date = ddmmaaaa.reverse().join("/");
  $('#fecha_imp input').attr('disabled',true);
  $('#fecha_imp span').hide();
  $('#fecha_imp').data('datetimepicker')
  .setDate(new Date(fecha_date.split('/').join('-')+'T00:00'));

  $('#casinoImp').val(id_casino).attr('disabled',true);
  $('#monedaImp').val(id_tipo_moneda).attr('disabled',true);
  $('#valoresArchivo').show();
  $('#btn-guardarImp').show();
}

function procesarDatosBeneficios(e) {
  $('#fecha_imp input').attr('disabled',false);
  $('#fecha_imp span').show();
  $('#casinoImp').val(-1).attr('disabled',false);
  $('#monedaImp').val(-1).attr('disabled',false);
  
  const csv = e.target.result;
  const allTextLines = csv.replaceAll('\r\n','\n').split('\n').filter(function(l){
    return l.length > 0;
  });
  
  let id_casino = null;
  let fecha_date = null;
  
  if(allTextLines.length < 1) return failImportacion('El archivo no contiene beneficios');
  
  const columnas = allTextLines[allTextLines.length-1].split(';');
  if(columnas.length == 14){
    const cas_fecha_timestamp = columnas[1].split("_");
    id_casino = parseInt(cas_fecha_timestamp[0]);
    fecha_date = cas_fecha_timestamp[1].substr(6,2)
    +'/'+cas_fecha_timestamp[1].substr(4,2)
    +'/'+cas_fecha_timestamp[1].substr(0,4);
  }
  if(id_casino === null) {//Pruebo procesar Rosario
    if(allTextLines.length <= 8) return failImportacion('El archivo no contiene beneficios');
    const columnas = allTextLines[allTextLines.length-6].split(';');//Saco la ultima fila
    if(columnas.length != 8) return failImportacion('El archivo no contiene beneficios');
    id_casino = 3;
    fecha_date = columnas[0];
  }
  if(id_casino == null || fecha_date == null) return failImportacion('El archivo no contiene beneficios');
  
  $('#fecha_imp input').attr('disabled',true);
  $('#fecha_imp span').hide();
  $('#fecha_imp').data('datetimepicker')
  .setDate(new Date(fecha_date.split('/').reverse().join('-')+'T00:00'));
  $('#casinoImp').val(id_casino).attr('disabled',true);
  $('#monedaImp').show();//Mostrar el select de moneda (único dato que no se puede obtener desde el archivo)
  $('#valoresArchivo').show();
  $('#btn-guardarImp').show();
}

$('#btn-guardarImp').click(function(e){
  e.preventDefault();

  const formData = new FormData();
  formData.append('id_casino', $('#casinoImp').val());
  formData.append('fecha', $('#fecha_imp input').val());
  formData.append('fecha_iso',$('#fecha_imp_hidden').val());
  formData.append('id_tipo_moneda',$('#monedaImp').val());
  formData.append('md5',$('#modalImportacion .hashCalculado').val());
  
  $('#casinoInfoImportacion,#casino_busqueda').val(formData.get('id_casino'));
  $('#monedaInfoImportacion,#moneda_busqueda').val(formData.get('id_tipo_moneda'));
  const fecha_iso = new Date(formData.get('fecha').split('/').reverse().join('-')+'T00:00');
  $('#mesInfoImportacion').data('datetimepicker').setDate(fecha_iso);
  
  //Si subió archivo lo guarda
  if($('#archivo').attr('data-borrado') == 'false' && $('#archivo')[0].files[0] != null){
    formData.append('archivo' , $('#archivo')[0].files[0]);
  }
  
  const tipo = $('#modalImportacion').data('tipo');
  $('#tipo_archivo').val(tipo.toUpperCase());
  
  let url = '';
  if(tipo == 'contadores') url = 'Contador';
  else if(tipo == 'producidos') url = 'Producido';
  else if(tipo == 'beneficios') url = 'Beneficio';
  else throw 'Tipo no soportado = '+tipo;

  $.ajaxSetup({headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: `importaciones/importar${url}`,
    data: formData,
    processData: false,
    contentType:false,
    cache:false,
    beforeSend: function(data){
      $('#modalImportacion').find('.modal-footer,.modal-body').children().hide();
      $('#modalImportacion .modal-body #iconoCarga').show();
    },
    success: function (data) {
      cargarTablasImportaciones();
      buscarImportaciones();
      $('#modalImportacion').modal('hide');
      let titulo = null;
      let texto = null;
      if(tipo == 'beneficios'){
        titulo = 'ÉXITO DE IMPORTACIÓN BENEFICIO';
        texto  = `${data.cantidad_registros} registro(s) del BENEFICIO fueron importados`;
      }
      else if(tipo == 'producidos'){
        titulo = 'ÉXITO DE IMPORTACIÓN PRODUCIDO';
        texto = `${data.cantidad_registros} registro(s) del PRODUCIDO fueron importados`;
        if(data.cant_mtm_forzadas){
          texto += `<br>${data.cant_mtm_forzadas} Máquinas no reportaron`;
        }
      }
      else if(tipo == 'contadores'){
        titulo = 'ÉXITO DE IMPORTACIÓN CONTADOR';
        texto  = `${data.cantidad_registros} registro(s) del CONTADOR fueron importados`;
      }
      $('#mensajeExito h3').text(titulo);
      $('#mensajeExito p').html(texto);
      $('#mensajeExito').toggle(titulo && texto);
    },
    error: function (data) {
      console.log(data);
      $('#mensajeError').show();
      const response = data.responseJSON.errors;
      if(tipo == 'producidos'){
        if(response.producido_validado !== 'undefined'){
          $('#mensajeError h6').text('El Producido para esa fecha ya está validado y no se puede reimportar.')
        }
      }
      else if(tipo == 'contadores'){
        if(response.existeRel){
          $('#modalImportacion').modal('hide');
          $('#modalErrorVisado').modal('show');
        }
      }
    }
  });
});

/* TODO LO DE VISUALIZACION/ELIMINACIÓN */
function agregarFilasImportaciones(data, id) {
  const meses = [null,'ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
  const f_prod = id == null? '-' : (typeof data.fecha_archivo == "undefined" ? "-" : convertirDate(data.fecha_archivo));
  const fecha = id == null? `${meses[data.mes]} ${data.anio}` : convertirDate(data.fecha);
  
  const fila = $('#moldeFilaImp').clone().removeAttr('id');
  fila.find('.fecha_produccion').text(f_prod);
  fila.find('.fecha').text(fecha);
  fila.find('.casino').text(data.casino);
  fila.find('.moneda').text(data.tipo_moneda);
  fila.find('button').val(id).attr({
    'data-mes' : data.mes,'data-anio': data.anio,
    'data-casino': data.id_casino,'data-moneda': data.id_tipo_moneda
  });
  $('#tablaImportaciones tbody').append(fila);
}

$(document).on('click','#tablaImportaciones thead tr th[value]',function(e){
  $('#tablaImportaciones th').removeClass('activa');
  const i = $(this).children('i');
  const sin_ordenar = i.hasClass('fa-sort');
  const desc = i.hasClass('fa-sort-down');
  if(sin_ordenar){
    i.removeClass('fa-sort').addClass('fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(desc){
    i.removeClass('fa-sort-down').addClass('fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  else{
    i.removeClass('fa-sort-up').addClass('fa-sort').parent().attr('estado','');
  }
  $('#tablaImportaciones th i').not(i).removeClass().addClass('fa fa-sort').parent().attr('estado','');
  buscarImportaciones();
});

$('#filtrosBusquedaImportaciones .form-control').change(function(e){
  buscarImportaciones();
});

function buscarImportaciones(e,pagina,page_size,columna,orden){
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
  const tipo_archivo = $('#tipo_archivo').val();
  $('#tituloTabla').text(`Todos los ${tipo_archivo}`);
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'importaciones/buscar',
    data: {
      fecha: $('#fecha_busqueda_hidden').val(),
      casinos: $('#casino_busqueda').val(),
      tipo_moneda: $('#moneda_busqueda').val(),
      seleccion: tipo_archivo,
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function (resultados) {
      $('#tablaImportaciones tbody tr').remove();
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,buscarImportaciones);
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,buscarImportaciones);
      const ID = {
        CONTADORES: function(c){return c.id_contador_horario;},
        PRODUCIDOS: function(p){return p.id_producido;},
        BENEFICIOS: function(b){return null;},
      };
      if(!(tipo_archivo in ID)) throw 'Tipo de archivo no implementado';
      resultados.data.forEach(function(x,idx){
        agregarFilasImportaciones(x,ID[tipo_archivo](x));
      });
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
}

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
  const tipo_importacion = $('#tipo_archivo').val();
  const tmap = {
    CONTADORES:['Contadores',mostrarContador],
    PRODUCIDOS:['Producidos',mostrarProducido],
    BENEFICIOS:['Beneficios',mostrarBeneficio]
  };
  if(!(tipo_importacion in tmap)) throw 'Error tipo de importacion = '+tipo_importacion;

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'importaciones/preview'+tmap[tipo_importacion][0],
    data: formData,
    dataType: 'json',
    success: function(data){
      $('#modalPlanilla #casino').val(data.casino.nombre);
      $('#modalPlanilla #tipo_moneda').val(data.tipo_moneda.descripcion);
      tmap[tipo_importacion][1](data);
      $('#modalPlanilla').modal('show');
    },
    error: function (data) { console.log(data); }
  });
});

$(document).on('click','.borrar',function(){  
  const tipo_archivo = $('#tipo_archivo').val();
  //Se le pasa el tipo de archivo y el id del archivo
  $('#btn-eliminarModal').val($(this).val()).attr({
    'data-tipo':tipo_archivo,
    'data-mes':$(this).attr('data-mes'),
    'data-anio':$(this).attr('data-anio'),
    'data-casino':$(this).attr('data-casino'),
    'data-moneda':$(this).attr('data-moneda'),
  });
  $('#titulo-modal-eliminar').text(`¿Seguro desea eliminar ${tipo_archivo}?`);
  $('#modalEliminar').modal('show');//Se muestra el modal de confirmación de eliminación
});

$('#btn-eliminarModal').click(function (e) {
  const id_importacion = $(this).val();
  const tipo_archivo = $(this).attr('data-tipo');
  let url = null;
  switch(tipo_archivo){
    case 'CONTADORES':
      url = `eliminarContador/${id_importacion}`;
      break;
    case 'PRODUCIDOS':
      url = `eliminarProducido/${id_importacion}`;
      break;
    case 'BENEFICIOS':
      const desc_ben = [$(this).attr('data-casino'),$(this).attr('data-moneda'),$(this).attr('data-anio'),$(this).attr('data-mes')].join('/');
      url = `eliminarBeneficios/${desc_ben}`;
      break;
    default:
      throw 'Tipo de archivo no implementado - '+tipo_archivo;
  }
  
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "DELETE",
    url: 'importaciones/' + url,
    success: function (data) {
      cargarTablasImportaciones();
      buscarImportaciones();
      $('#modalEliminar').modal('hide');
    },
    error: function (data) {
      console.log('Error: ', data);
    }
  });
});
