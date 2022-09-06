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
  ocultarErrorValidacion($('#modalRelevamientoProgresivos .form-control')); //oculto todos los errores
  $('#modalRelevamientoProgresivos .form-control').val('');
  $('#dtpFecha').data('datetimepicker').reset();
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
function setearSectores(selSector,id_casino){
  selSector.empty().removeClass('alerta');
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data) {
    selSector.append($(data.sectores).map(function(idx,s){
      return $('<option>').text(s.id_sector).text(s.descripcion)[0];
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
    url: "progresivos/obtenerMinimoRelevamientoProgresivo/" + id_casino + "/" + id_tipo_moneda,
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
      $('#btn-buscar').trigger('click');
      $('#modalRelevamiento').modal('hide');
    },
    error: function(data) {
      const response = JSON.parse(data.responseText);
      if (typeof response.id_sector !== 'undefined') {
        mostrarErrorValidacion($('#sector'),responde.id_sector,true);
      }
      if (typeof response.fecha_generacion !== 'undefined') {
        mostrarErrorValidacion($('#fechaRelevamientoInput'),responde.fecha_generacion,true);
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
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
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
      $('#cuerpoTabla tr:not(.filaEjemplo)').remove();
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

function verRelevamiento(relevamiento){
   cargarRelevamiento(relevamiento);
}

function cargarRelevamiento(relevamiento) {
    $('#modalRelevamientoProgresivos .mensajeSalida').hide();
    $('#id_relevamiento').val(relevamiento.id_relevamiento_progresivo);

    $('#btn-guardar').show();
    $('#btn-finalizar').show().text("FINALIZAR");

    $('#modalRelevamientoProgresivos .modal-header').attr("style","font-family:'Roboto-Black';color:white;background-color:#FF6E40;");
    $('#modalRelevamientoProgresivos .modal-title').text('| CARGAR RELEVAMIENTO DE PROGRESIVOS');

    $('#inputFisca').attr('disabled', false);
    $('#usuario_fiscalizador').attr('disabled', false);
    $('#fecha').attr('disabled', false);
    $('#fecha').addClass('fondoBlanco');

    $('#dtpFecha span.usables').show();
    $('#dtpFecha span.nousables').hide();

    $.get('relevamientosProgresivo/obtenerRelevamiento/' + relevamiento.id_relevamiento_progresivo,function(data) {
      setearRelevamiento(data, function(d){return obtenerFila(d,'cargar');});
      
      $('#btn-finalizar').off().click(function() {
        const err = validarFormulario(data.casino.id_casino);
        if (err.errores) {
          console.log(err.mensajes);
          return mensajeError(err.mensajes);
        }
        enviarFormularioCarga(data,'cargar');
      });
      $('#btn-guardar').off().click(function() {
        enviarFormularioCarga(data,'guardar');
      });
    });

    $('#observacion_carga').removeAttr('disabled');
    $('#observacion_validacion').parent().hide();
    $('#modalRelevamientoProgresivos').modal('show');
}

function validarRelevamiento(relevamiento) {
    $('#id_relevamiento').val(relevamiento.id_relevamiento_progresivo);
    $('#modalRelevamientoProgresivos .mensajeSalida').hide();

    $('#btn-guardar').hide();
    $('#btn-finalizar').show().text("VISAR");

    $('#modalRelevamientoProgresivos .modal-header').attr('style',"font-family:'Roboto-Black';color:white;background-color:#69F0AE;");
    $('#modalRelevamientoProgresivos .modal-title').text('| VALIDAR RELEVAMIENTO DE PROGRESIVOS');

    $('#inputFisca').attr('disabled', true);
    $('#usuario_fiscalizador').attr('disabled', true);
    $('#fecha').attr('disabled', true);
    $('#fecha').removeClass('fondoBlanco');

    $('#dtpFecha span.nousables').show();
    $('#dtpFecha span.usables').hide();

    $.get('relevamientosProgresivo/obtenerRelevamiento/' + relevamiento.id_relevamiento_progresivo,function(data) {
      setearRelevamiento(data, function(d){return obtenerFila(d,'validar');});

      $('#btn-finalizar').off().click(function() {
        enviarFormularioValidacion(relevamiento.id_relevamiento_progresivo);
      });
    });

    $('#observacion_carga').attr('disabled', true);
    $('#observacion_validacion').parent().show();
    $('#modalRelevamientoProgresivos').modal('show');
}

$(document).on('click','#tablaRelevamientos .ver',function(){
  verRelevamiento($(this).closest('tr').data('relevamiento'));
});
$(document).on('click','#tablaRelevamientos .planilla',function(){
  const id = $(this).closest('tr').data('relevamiento').id_relevamiento_progresivo;
  window.open('relevamientosProgresivo/generarPlanilla/' + id, '_blank');
});
$(document).on('click','#tablaRelevamientos .imprimir',function(){
  const id = $(this).closest('tr').data('relevamiento').id_relevamiento_progresivo
  window.open('relevamientosProgresivo/generarPlanilla/' + id, '_blank');
});
$(document).on('click','#tablaRelevamientos .carga',function(){
  cargarRelevamiento($(this).closest('tr').data('relevamiento'));
});
$(document).on('click','#tablaRelevamientos .validar',function(){
  validarRelevamiento($(this).closest('tr').data('relevamiento'));
});
$(document).on('click','#tablaRelevamientos .eliminar',function(){
  const id = $(this).closest('tr').data('relevamiento').id_relevamiento_progresivo;
  $('#mensajeAlerta .confirmar').val(id);
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
  const fila = $('#cuerpoTabla .filaEjemplo').clone().removeClass('filaEjemplo').show();
  //Se setea el display como table-row por algun motivo :/
  //Lo saco a pata.
  fila.css('display', '');
  fila.attr('data-id', relevamiento.id_relevamiento_progresivo);
  fila.find('.fecha').text(relevamiento.fecha_generacion).attr('title',relevamiento.fecha_generacion);
  fila.find('.casino').text(relevamiento.casino).attr('title',relevamiento.casino);
  fila.find('.sector').text(relevamiento.sector).attr('title',relevamiento.sector);
  fila.find('.textoEstado').text(relevamiento.estado).attr('title',relevamiento.estado);
  //Estado e Iconos a mostrar
  fila.data('relevamiento',relevamiento);//Usado en los callback de los botones
  fila.find('.textoEstado').text(relevamiento.estado);
  fila.find('.fa-dot-circle').addClass(relevamiento.estado=='Visado'? 'faValidado' : (`fa${relevamiento.estado}`));
  fila.find('button').hide();
  fila.find('.eliminar').show();
  switch(relevamiento.estado){
    case 'Generado':
    case 'Cargando':{
      fila.find('.carga,.planilla').show();
    }break;
    case 'Finalizado':{
      fila.find('.validar,.planilla').show();
    }break;
    case 'Visado':{
      fila.find('.imprimir').show();
    }break;
  }
  return fila;
}

$('#btn-salir').click(function() {
  $('#modalRelevamientoProgresivos').modal('hide');
});

function causaNoTomaCallback(t){
  const fila = $(t).closest('tr');
  const seteado = $(t).val().length > 0;
  fila.find('input[data-id]').val('').attr('disabled',seteado).css('color',seteado? '#fff' : '');
  fila.find('input:not([data-id])').attr('disabled',true);
}

$(document).on('change','#modalRelevamientoProgresivos tr:not(.filaEjemplo) .causaNoToma',function(){
  causaNoTomaCallback(this);
});

function obtenerFila(detalle,modo){
  const fila =  $('#modalRelevamientoProgresivos .filaEjemplo').clone();
  fila.find('.form-control').attr('disabled',modo == 'validar' || modo == 'ver');
  fila.removeClass('filaEjemplo').show().css('display', '');
  const nombre_prog = detalle.nombre_progresivo + (detalle.pozo_unico? '' : ` (${detalle.nombre_pozo})`);
  fila.find('.nombreProgresivo').text(nombre_prog);
  fila.find('.maquinas').text(detalle.nro_admins);
  fila.find('.isla').text(detalle.nro_islas);  
  fila.attr('data-id', detalle.id_detalle_relevamiento_progresivo);
  if (detalle.id_tipo_causa_no_toma_progresivo != null) {
    fila.find('.causaNoToma').val(detalle.id_tipo_causa_no_toma_progresivo).change();
    causaNoTomaCallback(fila.find('.causaNoToma')[0]);//@HACK: .change() no funciona porque no esta en el DOM todavia :/
  }
  detalle.niveles.forEach(function(n,idx){
    const nivel = fila.find('.nivel'+n.nro_nivel);
    if (n.nombre_nivel != null){
      nivel.attr('title',n.nombre_nivel);
      nivel.attr('placeholder', n.nombre_nivel);
    }
    nivel.val(n.valor).attr('data-id', n.id_nivel_progresivo);
  });
  fila.find('input:not([data-id])').attr('disabled', true);
  return fila;
}

function setearRelevamiento(data, filaCallback) {
  //Limpio los campos
  $('#modalRelevamientoProgresivos input').val('');
  $('#modalRelevamientoProgresivos select').val('');
  $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').not('.filaEjemplo').remove();
  $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
  $('#cargaCasino').val(data.casino.nombre);
  $('#cargaSector').val(data.sector.descripcion);
  if(data.relevamiento.fecha_ejecucion){
    $('#dtpFecha').data('datetimepicker').setDate(new Date(data.relevamiento.fecha_ejecucion));
  }
  $('#usuario_cargador').val(data.usuario_cargador?.nombre ?? '');
  $('#usuario_fiscalizador').attr('list', 'datalist' + data.casino.id_casino);
  $('#usuario_fiscalizador').val(data.usuario_fiscalizador?.nombre ?? '');
  $('#observacion_carga').val(data.relevamiento.observacion_carga ?? '');
  $('#observacion_validacion').val(data.relevamiento.observacion_validacion ?? '');

  const tabla = $('#modalRelevamientoProgresivos .cuerpoTablaPozos');
  const individuales = [];
  data.detalles.forEach(function(d,idx){
    if(d.es_individual == 0) tabla.append(filaCallback(d).addClass('linkeado').attr('idx',idx));
    else individuales.push(d);
  });
  if(individuales.length>0){
    individuales.forEach(function(d,idx){
      tabla.append(filaCallback(d).addClass('individual').attr('idx',idx));
    });
    setTimeout(setearBordeSeparadorFilaProgresivos,1000);
  }
}

//Le seteo la misma altura a todas las celdas y le pongo el borde
//No se puede poner el borde a la fila por que no lo toma, y se necesita ponerle la misma altura
//Porque tienen alturas distintas y el borde se ve horrible si no. 
//Tomo la altura de la celda mas grande de la fila.
function setearBordeSeparadorFilaProgresivos(){
  const fila = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr.linkeado').not('.filaEjemplo').last();
  let altura = 0;
  fila.find('td').each(function(){
    altura = Math.max(parseFloat($(this).css('height')),altura);
  });
  fila.addClass('separadorProgresivos');
  fila.find('td').css('height',altura).css('border-bottom','double gray');
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

function enviarFormularioCarga(relevamiento,modo) {
  const formData = {
    id_relevamiento_progresivo: relevamiento.relevamiento.id_relevamiento_progresivo,
    fecha_ejecucion: $('#fecha').val(),
    id_casino: relevamiento.casino.id_casino,
    id_usuario_fiscalizador: obtenerIdFiscalizador(relevamiento.casino.id_casino, $('#usuario_fiscalizador').val()),
    observaciones: $('#observacion_carga').val(),
  };

  formData.detalles = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').not('.filaEjemplo').map(function(idx,tr){
    const f = $(tr);
    const causaNoToma = f.find('.causaNoToma').val();
    const niveles = f.find(causaNoToma.length > 0? 'input:not([disabled])' : '').map(function(idx, input) {
      const n = $(input);
      return {
        valor: n.val(),
        numero: n.attr('title'),
        id_nivel: n.attr('data-id')
      };
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
      $('#btn-buscar').click();
    },
    error: function(data){
      console.log(data);
      mensajeError(obtenerMensajesError(data));
    }
  });
}

function enviarFormularioValidacion(id_relevamiento, succ = function(x) { console.log(x); }, err = function(x) { console.log(x); }) {
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
    },
    error: function(data){
      console.log(data);
      mensajeError(obtenerMensajesError(data));
    }
  });
}

function validarFormulario(id_casino) {
  let errores = false;
  const fisca = $('#usuario_fiscalizador');
  if (fisca.val() == "" || obtenerIdFiscalizador(id_casino, fisca.val()) === null){
    errores = true;
    mostrarErrorValidacion(fisca,"Ingrese un fiscalizador",true);
  }

  const fecha = $('#fecha');
  if (fecha.val() == "") {
    errores = true;
    mostrarErrorValidacion(fecha,"Ingrese una fecha de ejecución",true);
  }

  const inputs = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr:not(.filaEjemplo) input:not([disabled]');
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
  return { errores: errores, mensajes: mensajes };
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
  $('.cuerpoTablaPozos tr').not('.filaEjemplo').remove();
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
    url: 'progresivos/modificarParametrosRelevamientosProgresivo',
    data: {
      id_casino: $('#selectCasinoModificarRelev').val(),
      id_tipo_moneda: $('#selectTipoMonedaModificarRelev').val(),
      minimo_relevamiento_progresivo: $('#valorMinimoRelevamientoProgresivo').val(),
    },
    dataType: 'json',
    success: function(data) {
      $('#modalModificarRelev').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('Cambios GUARDADOS. ');
      $('#mensajeExito').show();
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
