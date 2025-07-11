function formatear_numero_ingles(s){
  return s.replaceAll(/(\.|\s)/gi,'').replaceAll(',','.')
}
  
function formatear_numero_español(s){
  s = s.replaceAll(/[^0-9\-\.,]/gi,'');
  const negativo = (s?.[0] ?? null) == '-'? '-' : '';
  const abs = negativo.length? s.substring(1) : s;
  
  const partes = abs.split('.');
  const entero = partes?.[0] ?? '';
  const decimal = partes?.[1] ?? null;
  
  const entero_separado = [];
  for(let i=0;i<entero.length;i++){//De atras para adelante voy agregando numeros en baldes
    const bucket = Math.floor(i/3);
    
    if(bucket == entero_separado.length){
      entero_separado.push('');
    }
    
    const c = entero[entero.length-1-i];
    entero_separado[bucket] = c+entero_separado[bucket];
  }
  
  //Puede quedar vacio el ultimo por eso chequeo
  if(entero_separado.length && (entero_separado[entero_separado.length-1]).length == 0){
    entero_separado.pop();
  }
  let ret = negativo+entero_separado.reverse().join('.');
  if(decimal !== null && parseInt(decimal) != 0){
    ret+=','+decimal;
  }
  return ret;
}

function filtrarNumeros(e){
  const code = e.which || e.keyCode;
  const c = String.fromCharCode(code);
  const tgt = $(e.target);
  const val = tgt.val();
  
  if((c == ',' || c == '.') && val.includes(',')){
    e.preventDefault();
    return;
  }
  if(c == '-' && val.includes('-')){
    e.preventDefault();
    return;
  }
  
  if(c == '.'){
    e.preventDefault();
    const pos = this.selectionStart;
    tgt.val(
      val.substring(0,pos)+','+val.substring(pos)
    );
    this.selectionStart = pos+1;
    this.selectionEnd = pos+1;
    return;
  }
  if(c == '-' && this.selectionStart == 0){
    e.preventDefault();
    tgt.val('-'+val.substring(this.selectionEnd));
    this.selectionStart = 1;
    this.selectionEnd = 1;
    return;
  }
  if(!c.match(/^(,|[0-9])$/)){
    e.preventDefault();
  }
};

$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Relevamiento de progresivos');
  const yyyymmdd_hhiiss = {
      language: 'es',
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-mm-dd HH:ii:ss',
      pickerPosition: "bottom-left",
      startView: 2,
      minView: 0,
      ignoreReadonly: true,
      minuteStep: 5,
      endDate: '+0d'
  };
  $('#dtpBuscadorFecha').datetimepicker({
    ...yyyymmdd_hhiiss,
    format: 'yyyy-mm-dd',
    minView: 2,
  });
  $('#dtpFecha').datetimepicker(yyyymmdd_hhiiss);
  $('#dtpFecha span.nousables').off();
  $('#fechaRelevamientoDiv').datetimepicker(yyyymmdd_hhiiss);
  //trigger buscar, carga de tabla, fecha desc
  $('#btn-buscar').trigger('click');
  $('#modalRelevamientoProgresivos').trigger('hidden.bs.modal');
});

$('.minimizar').click(function(){
  const minimizado = $(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizado? '0.1' : '0.5');
  $(this).data("minimizar",!minimizado);
});

$('#btn-ayuda').click(function(e) {
  e.preventDefault();
  $('#modalAyuda').modal('show');
});

$(document).on('change','.form-control',function(){
  const t = $(this);
  if (t.val().length > 0) {
    t.removeClass('alerta');
  }
});

$('#btn-nuevo').click(function(e) {
  e.preventDefault();
  $('#iconoCarga').hide();
  $('#modalRelevamiento').modal('show');
});

$('#modalRelevamientoProgresivos').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($(this).find('.form-control')); //oculto todos los errores
  $(this).find('.form-control').val('');
  $('#dtpFecha').data('datetimepicker').reset();
  $(this).find('.cuerpoTablaPozos tr').remove();
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
function setearSectores(selSector,id_casino){
  selSector.empty().removeClass('alerta');
  $.get("relevamientosProgresivo/obtenerSectoresPorCasino/" + id_casino, function(data) {
    selSector.append($(data.sectores).map(function(idx,s){
      return $('<option>').val(s.id_sector).text(s.descripcion)[0];
    }));
  });
}
$('#modalRelevamiento #casino').on('change', function() {
  setearSectores($('#modalRelevamiento #sector'),$(this).val());
});
$('#buscadorCasino').on('change', function() {
  setearSectores($('#buscadorSector'),$(this).val());
  $('#buscadorSector').prepend($('<option>').val(0).text('-Todos los sectores-')).val(0);
});

function setearValorMinimoRelevamientoProgresivo(after = function(){}) {
  const id_casino      = $('#selectCasinoModificarRelev').val();
  const id_tipo_moneda = $('#selectTipoMonedaModificarRelev').val();
  $.ajax({
    url: "relevamientosProgresivo/obtenerMinimoRelevamientoProgresivo/" + id_casino + "/" + id_tipo_moneda,
    type: "GET",
    dataType: "json",
    success: function(val){
      $('#valorMinimoRelevamientoProgresivo').val(val);
      after();
    },
    error: function(e) { console.log(e.responseJSON); }
  });
}

$('#selectCasinoModificarRelev,#selectTipoMonedaModificarRelev').change(function(){setearValorMinimoRelevamientoProgresivo();});
$('#btn-modificar-parametros-relevamientos').click(function(e) {
  e.preventDefault();
  setearValorMinimoRelevamientoProgresivo(function() {
    $('#modalModificarRelev').modal('show');
  });
});

//GENERAR RELEVAMIENTO
$('#btn-generar').click(function(e) {
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: 'relevamientosProgresivo/crearRelevamiento',
    data: {
      id_sector: $('#sector').val(),
      fecha_generacion: $('#fechaRelevamientoInput').val()
    },
    dataType: 'json',
    success: function(data) {
      mensajeExito('Generado','');
      $('#btn-buscar').trigger('click');
      $('#modalRelevamiento').modal('hide');
    },
    error: function(data) {
      console.log(data);
      mensajeError(['Error al generar']);
      const response = data.responseJSON ?? {};
      if (typeof response.id_sector !== 'undefined') {
        mostrarErrorValidacion($('#sector'),response.id_sector,true);
      }
      if (typeof response.fecha_generacion !== 'undefined') {
        mostrarErrorValidacion($('#fechaRelevamientoInput'),response.fecha_generacion,true);
      }
    }
  });
});

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
  e.preventDefault();

  let size = 10;
  //Fix error cuando librería saca los selectores
  if (!isNaN($('#herramientasPaginacion').getPageSize())) {
    size = $('#herramientasPaginacion').getPageSize();
  }
  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaRelevamientos .activa').attr('value'), orden: $('#tablaRelevamientos .activa').attr('estado') };
  if (sort_by == null) { // limpio las columnas
    $('#tablaRelevamientos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: '/relevamientosProgresivo/buscarRelevamientosProgresivos',
    data: {
      fecha_generacion: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      sector: $('#buscadorSector').val(),
      estadoRelevamiento: $('#buscadorEstado').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function(resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
      $('#cuerpoTabla tr').remove();
      for(const i in resultados.data){
        $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);
    },
    error: function(data) {
      console.log('Error:', data);
    }
  });
});

//Paginacion
$(document).on('click', '#tablaRelevamientos thead tr th[value]', function(e) {
  const icon = $(this).find('i');
  const not_sorted = icon.hasClass('fa-sort');
  const down_sorted = icon.hasClass('fa-sort-down');
  $('#tablaRelevamientos .activa').removeClass('activa');
  $('#tablaRelevamientos th i').removeClass().addClass('fa fa-sort').parent().attr('estado', '');
  if(not_sorted){
    icon.removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else if(down_sorted){
    icon.removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado','asc');
  }
  clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaRelevamientos .activa').attr('value');
  const orden = $('#tablaRelevamientos .activa').attr('estado');
  $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function obtenerMensajesError(response) {
  const mensajes = [];
  Object.keys(response.responseJSON).forEach(function(k,_){
    response.responseJSON[k].forEach(function(m,_){mensajes.push(m);});
  });
  return mensajes;
}

function mostrarRelevamiento(id_relevamiento_progresivo,modo){
  const modos_attr = {
    'ver': {
      color: 'rgb(79, 195, 247)',
      title: '| VER RELEVAMIENTO DE PROGRESIVOS',
      inputs_enabled: false,
      boton_guardar: false,
      boton_finalizar: false,
      texto_finalizar: "",
      observacion_validacion: true,
      observacion_validacion_enabled: false,
    },
    'cargar':{
      color: '#FF6E40',
      title: '| CARGAR RELEVAMIENTO DE PROGRESIVOS',
      inputs_enabled: true,
      boton_guardar: true,
      boton_finalizar: true,
      texto_finalizar: "FINALIZAR",
      observacion_validacion: false,
      observacion_validacion_enabled: false,
    },
    'validar':{
      color: '#69F0AE',
      title: '| VALIDAR RELEVAMIENTO DE PROGRESIVOS',
      inputs_enabled: false,
      boton_guardar: false,
      boton_finalizar: true,
      texto_finalizar: "VISAR",
      observacion_validacion: true,
      observacion_validacion_enabled: true,
    }
  };
  if(!(modo in modos_attr)) throw `Modo ${modo} no soportado`;
  const attr = modos_attr[modo];
  
  const obtenerFila = function(detalle,didx,cls){
    const fila =  $('#modalRelevamientoProgresivos .filaEjemplo').clone();
    fila.removeClass('filaEjemplo');
    const nombre_prog = detalle.nombre_progresivo + (detalle.pozo_unico? '' : ` (${detalle.nombre_pozo})`);
    fila.find('.nombreProgresivo').text(nombre_prog);
    fila.find('.maquinas').text(detalle.nro_admins);
    fila.find('.isla').text(detalle.nro_islas);  
    fila.attr('data-id', detalle.id_detalle_relevamiento_progresivo);
    if (detalle.id_tipo_causa_no_toma_progresivo != null) {
      fila.find('.causaNoToma select').val(detalle.id_tipo_causa_no_toma_progresivo);
    }
    detalle.niveles.forEach(function(n,nidx){
      const nivel = fila.find('.nivel'+n.nro_nivel);
      const nombre_nivel = n?.nombre_nivel ?? ('Nivel '+n.nro_nivel);
      nivel.attr('title',nombre_nivel)
      .attr('placeholder',nombre_nivel)
      .attr('data-id', n.id_nivel_progresivo)
      .val(formatear_numero_español(n.valor))
      .on('keypress',filtrarNumeros)
      .on('change',function(e){
        nivel.val(formatear_numero_español(formatear_numero_ingles(nivel.val())));
      });
    });
    fila.attr('idx',didx).addClass(cls);
    return fila;
  };
  
  $.get('relevamientosProgresivo/obtenerRelevamiento/' + id_relevamiento_progresivo,function(data) {
    $('#modalRelevamientoProgresivos').attr('data-modo',modo);
    $('#modalRelevamientoProgresivos .modal-header').css('background-color',attr.color);
    $('#modalRelevamientoProgresivos .modal-title').text(attr.title);
    $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
    $('#cargaCasino').val(data.casino.nombre).data('id_casino',data.casino.id_casino);
    $('#cargaSector').val(data.sector.descripcion);
    $('#usuario_cargador').val(data.usuario_cargador?.nombre ?? '');
    $('#usuario_fiscalizador').attr('disabled',!attr.inputs_enabled)
    .val(data.usuario_fiscalizador?.nombre ?? '')
    .attr('list', 'datalist' + data.casino.id_casino);
    $('#fecha').attr('disabled',!attr.inputs_enabled).toggleClass('fondoBlanco',attr.inputs_enabled);
    $('#dtpFecha span.nousables').toggle(!attr.inputs_enabled);
    $('#dtpFecha span.usables').toggle(attr.inputs_enabled);
    if(data.relevamiento.fecha_ejecucion){
      $('#dtpFecha').data('datetimepicker').setDate(new Date(data.relevamiento.fecha_ejecucion));
    }
    
    const tabla = $('#modalRelevamientoProgresivos .cuerpoTablaPozos');  
    //Agrego todos los linkeados primero, luego los individuales y si hay seteo el borde separador
    data.detalles.filter(function(d,idx){
      if(!d.es_individual){
        tabla.append(obtenerFila(d,idx,'linkeado'));
      }
      return d.es_individual;
    }).filter(function(d,idx){
      tabla.append(obtenerFila(d,idx,'individual'));
      return idx == 0;
    }).forEach(function(){
      $('#modalRelevamientoProgresivos').one('shown.bs.modal', setearBordeSeparadorFilaProgresivos);
    });
    tabla.find('.form-control').attr('disabled',!attr.inputs_enabled);
    tabla.find('tr .causaNoToma select').change();
    
    $('#observacion_carga').attr('disabled',!attr.inputs_enabled)
    .val(data.relevamiento.observacion_carga ?? '');
    $('#observacion_validacion').attr('disabled',!attr.observacion_validacion_enabled)
    .val(data.relevamiento.observacion_validacion ?? '')
    .parent().toggle(attr.observacion_validacion);
    
    $('#btn-guardar').toggle(attr.boton_guardar).val(data.relevamiento.id_relevamiento_progresivo);
    $('#btn-finalizar').toggle(attr.boton_finalizar).val(data.relevamiento.id_relevamiento_progresivo);
    $('#modalRelevamientoProgresivos').modal('show');
  });
}

$(document).on('click','#modalRelevamientoProgresivos[data-modo="cargar"] #btn-guardar,#modalRelevamientoProgresivos[data-modo="cargar"] #btn-finalizar',function(){
  enviarFormularioCarga($(this).val(),$(this).attr('data-modo-form'));
});
$(document).on('click','#modalRelevamientoProgresivos[data-modo="validar"] #btn-finalizar',function(){
  enviarFormularioValidacion($(this).val());
});
$(document).on('click','#tablaRelevamientos .ver,#tablaRelevamientos .cargar,#tablaRelevamientos .validar',function(){
  mostrarRelevamiento($(this).val(),$(this).data('modo'));
});
$(document).on('click','#tablaRelevamientos .planilla',function(){
  window.open('relevamientosProgresivo/generarPlanilla/' + $(this).val() + '/1', '_blank');
});
$(document).on('click','#tablaRelevamientos .imprimir',function(){
  window.open('relevamientosProgresivo/generarPlanilla/' + $(this).val(), '_blank');
});
$(document).on('click','#tablaRelevamientos .eliminar',function(){
  $('#mensajeAlerta .confirmar').val($(this).val());
  $('#mensajeAlerta').modal('show');
});

$('#mensajeAlerta .confirmar').click(function(){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "GET",
    url: 'relevamientosProgresivo/eliminarRelevamientoProgresivo/' + $(this).val(),
    success: function(data) {
      console.log(data);
      $('#btn-buscar').click();
      $('#mensajeAlerta').modal('hide');
    },
    error: function(data) {
      console.log(data);
      $('#mensajeAlerta').modal('hide');
    }
  });
});

$('#mensajeAlerta .cancelar').click(function(){
  $('#mensajeAlerta').modal('hide');
});

function generarFilaTabla(relevamiento) {
  const fila = $('#panelBusqueda .filaEjemplo').clone().removeClass('filaEjemplo');
  fila.attr('data-id', relevamiento.id_relevamiento_progresivo);
  fila.find('.fecha').text(relevamiento.fecha_generacion).attr('title',relevamiento.fecha_generacion);
  fila.find('.casino').text(relevamiento.casino).attr('title',relevamiento.casino);
  fila.find('.sector').text(relevamiento.sector).attr('title',relevamiento.sector);
  fila.find('.textoEstado').text(relevamiento.estado).attr('title',relevamiento.estado);
  //Estado e Iconos a mostrar
  fila.find('.textoEstado').text(relevamiento.estado);
  fila.find('.fa-dot-circle').addClass(relevamiento.estado=='Visado'? 'faValidado' : (`fa${relevamiento.estado}`));
  fila.find('button').val(relevamiento.id_relevamiento_progresivo).hide();
  fila.find('.eliminar').show();
  switch(relevamiento.estado){
    case 'Generado':
    case 'Cargando':{
      fila.find('.ver,.cargar,.planilla').show();
    }break;
    case 'Finalizado':{
      fila.find('.ver,.validar,.imprimir').show();
    }break;
    case 'Visado':{
      fila.find('.ver,.imprimir').show();
    }break;
  }
  return fila;
}

$('#btn-salir').click(function() {
  $('#modalRelevamientoProgresivos').modal('hide');
});

$(document).on('change','#modalRelevamientoProgresivos[data-modo="cargar"] tr .causaNoToma select:not(:disabled)',function(){
  const fila = $(this).closest('tr');
  const seteado = $(this).val().length > 0;
  if(seteado) fila.find('input[data-id]').val('').removeClass('alerta');
  fila.find('input[data-id]').attr('disabled',seteado);
  fila.find('input:not([data-id])').attr('disabled',true);
});

//Le seteo la misma altura a todas las celdas y le pongo el borde
//No se puede poner el borde a la fila por que no lo toma, y se necesita ponerle la misma altura
//Porque tienen alturas distintas y el borde se ve horrible si no. 
//Tomo la altura de la celda mas grande de la fila.
function setearBordeSeparadorFilaProgresivos(){
  const fila = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr.linkeado').last();
  let altura = 0;
  fila.find('td').each(function(){
    altura = Math.max(parseFloat($(this).css('height')),altura);
  });
  fila.addClass('separadorProgresivos');
  fila.find('td').css('height',altura).css('border-bottom','double gray');
}

function mensajeExito(titulo,texto) {
  $('#mensajeExito h3').text(titulo ?? 'ÉXITO');
  $('#mensajeExito p').text(texto ?? 'Cambios GUARDADOS. ');
  $('#mensajeExito').hide();
  setTimeout(function() {
    $('#mensajeExito').show();
  }, 250);
}

function mensajeError(errores) {
  $('#mensajeError .textoMensaje').empty();
  for(const i in errores){
    $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
  }
  $('#mensajeError').hide();
  setTimeout(function() {
    $('#mensajeError').show();
  }, 250);
}

function obtenerIdFiscalizador(id_casino, str) {
  const f = $(`#datalist${id_casino}`).find(`option:contains('${str}')`);
  if (f.length != 1) return null;
  return f.attr('data-id');
}

function enviarFormularioCarga(id_relevamiento_progresivo,modo) {
  const err = modo == "cargar"? validarFormulario() : null;
  if (err && err.length > 0) {
    console.log(err);
    return mensajeError(err);
  }
  
  const formData = {
    id_relevamiento_progresivo: id_relevamiento_progresivo,
    fecha_ejecucion: $('#fecha').val(),
    id_usuario_fiscalizador: obtenerIdFiscalizador($('#cargaCasino').data('id_casino'), $('#usuario_fiscalizador').val()),
    observaciones: $('#observacion_carga').val(),
  };

  formData.detalles = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').map(function(idx,tr){
    const f = $(tr);
    const causaNoToma = f.find('.causaNoToma select').val();
    const niveles = f.find(causaNoToma.length > 0? '' :'input:not(:disabled)').map(function(idx, input) {
      const n = $(input);
      return { valor: formatear_numero_ingles(n.val()), id_nivel: n.attr('data-id') };
    }).toArray();
    return {
      id_detalle_relevamiento_progresivo: f.attr('data-id'),
      niveles: niveles,
      id_tipo_causa_no_toma: causaNoToma.length > 0? causaNoToma : null,
    };
  }).toArray();

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: `relevamientosProgresivo/${modo}Relevamiento`,
    data: formData,
    dataType: 'json',
    success: function(data){
      mensajeExito();
      $('#btn-buscar').click();
      if(modo == "cargar"){
        $('#modalRelevamientoProgresivos').modal('hide');
      }
    },
    error: function(data){
      console.log(data);
      mensajeError(obtenerMensajesError(data));
    }
  });
}

function enviarFormularioValidacion(id_relevamiento) {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: "POST",
    url: "relevamientosProgresivo/validarRelevamiento",
    data: {
      id_relevamiento_progresivo: id_relevamiento,
      observacion_validacion: $('#observacion_validacion').val()
    },
    dataType: 'json',
    success: function(data){
      $('#btn-buscar').click();
      $('#modalRelevamientoProgresivos').modal('hide');
    },
    error: function(data){
      console.log(data);
      mensajeError(obtenerMensajesError(data));
    }
  });
}

function validarFormulario() {
  const id_casino = $('#cargaCasino').data('id_casino');
  const fisca = $('#usuario_fiscalizador');
  if (fisca.val() == "" || obtenerIdFiscalizador(id_casino, fisca.val()) === null){
    mostrarErrorValidacion(fisca,"Ingrese un fiscalizador",true);
    mensajes.push("Ingrese un fiscalizador");
  }

  const fecha = $('#fecha');
  if (fecha.val() == "") {
    mostrarErrorValidacion(fecha,"Ingrese una fecha de ejecución",true);
    mensajes.push("Ingrese una fecha de ejecución")
  }

  const inputs = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr input:not([disabled])');
  const mensajes = [];
  const vacios = inputs.filter(function(idx,input){
    input = $(input);
    const fval = parseFloat(input.val());
    return input.val() == "" || isNaN(fval) || fval < 0;
  });
  if (vacios.length > 0){
    mensajes.push("Tiene al menos un nivel sin ingresar o con valores invalidos");
    vacios.addClass('alerta');
  }
  return mensajes;
}

$('.cabeceraTablaPozos th.sortable').click(function() {
  const sort_by = '.'+$(this).attr('data-id');  
  const current = $(this).attr('sorted');
  $(this).closest('tr').find('[sorted]').not(this).removeAttr('sorted');
  
  let sign = null;
  $(this).closest('tr').find('i').removeClass('fa-sort-up fa-sort-down');
  if (current === undefined) {
    sign = 1;
    $(this).attr('sorted', false);
    $(this).find('i').addClass('fa-sort-up');
  }
  else if(current === "false") {
    sign = -1;
    $(this).attr('sorted', true);
    $(this).find('i').addClass('fa-sort-down');
  }
  else{
    sign = undefined;
    $(this).removeAttr('sorted');
  }

  function comp(a, b) {
    if(sign === undefined){
      const idx1 = parseInt($(a).attr('idx'));
      const idx2 = parseInt($(b).attr('idx'));
      if(idx2 > idx1) return -1;
      if(idx2 < idx1) return  1;
      return 0;
    }
    const ta = $(a).find(sort_by).text();
    const tb = $(b).find(sort_by).text();
    return sign*ta.localeCompare(tb);
  }

  const link = $('.cuerpoTablaPozos tr.linkeado').sort(comp);
  const indiv = $('.cuerpoTablaPozos tr.individual').sort(comp);
  $('.cuerpoTablaPozos tr').remove();
  $('.cuerpoTablaPozos').append(link);
  $('.cuerpoTablaPozos').append(indiv);
  
  //Le saco el borde separador
  const filaSep = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr.separadorProgresivos');
  filaSep.find('td').css('height','revert').css('border-bottom','revert');
  filaSep.removeClass('separadorProgresivos');
  if(indiv.length > 0) setearBordeSeparadorFilaProgresivos();//Lo reagrego
})

$('#btn-guardar-param-relev-progresivos').on('click', function(e) {
  e.preventDefault();
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'POST',
    url: 'relevamientosProgresivo/modificarParametrosRelevamientosProgresivo',
    data: {
      id_casino: $('#selectCasinoModificarRelev').val(),
      id_tipo_moneda: $('#selectTipoMonedaModificarRelev').val(),
      minimo_relevamiento_progresivo: $('#valorMinimoRelevamientoProgresivo').val(),
    },
    dataType: 'json',
    success: function(data) {
      $('#modalModificarRelev').modal('hide');
      mensajeExito();
      $('#btn-buscar-apuestas').trigger('click', [1, 10, 'fecha', 'desc']);
    },
    error: function(data) {
      const errs = data.responseJSON;
      console.log(errs);
      mensajeError(Object.keys(errs).map(function(k,_){
        return `${k} => ${errs[k]}`;
      }));
    }
  });
});
