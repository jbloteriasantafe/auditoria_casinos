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

$('#modalRelevamiento select').change(sacarAlerta);

$('#btn-nuevo').click(function(e) {
  e.preventDefault();
  $('#iconoCarga').hide();
  $('#modalRelevamiento').modal('show');
});

$('#modalRelevamientoProgresivos').on('hidden.bs.modal', function() {
  ocultarErrorValidacion($('#modalRelevamientoProgresivos .form-control')); //oculto todos los errores
  $('#modalRelevamientoProgresivos .form-control').val('');
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#modalRelevamiento #casino').on('change', function() {
  $('#modalRelevamiento #sector').empty().removeClass('alerta');
  $.get("sectores/obtenerSectoresPorCasino/" + $(this).val(), function(data) {
    data.sectores.forEach(function(s,idx){
      $('#modalRelevamiento #sector').append(
        $('<option>').val(s.id_sector).text(s.descripcion)
      );
    });
  });
});

$('#buscadorCasino').on('change', function() {
  $('#buscadorSector').empty();
  $.get("sectores/obtenerSectoresPorCasino/" + $(this).val(), function(data) {
    $('#buscadorSector').append($('<option>').val(0).text('-Todos los sectores-'));
    data.sectores.forEach(function(s,idx){
      $('#buscadorSector').append(
        $('<option>').val(s.id_sector).text(s.descripcion)
      );
    });
  });
});

function setearValorMinimoRelevamientoProgresivo(after = function(){}) {
  const id_casino      = $('#selectCasinoModificarRelev').val();
  const id_tipo_moneda = $('#selectTipoMonedaModificarRelev').val();
  $.ajax({
    url: "progresivos/obtenerMinimoRelevamientoProgresivo/" + id_casino + "/" + id_tipo_moneda,
    type: "GET",
    dataType: "json",
    success: function(data){
      $('#valorMinimoRelevamientoProgresivo').val(data.rta);
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
        $('#sector,#casino').addClass('alerta');
      }
      if (typeof response.fecha_generacion !== 'undefined') {
        $('#fechaRelevamientoInput').addClass('alerta');
      }
    }
  });
});

function sacarAlerta() {
    let this2 = $(this);
    if (this2.val().length > 0) {
        this2.removeClass('alerta');
    }
};

$('input').change(sacarAlerta);

//PAGINACION
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    //Fix error cuando librería saca los selectores
    if (isNaN($('#herramientasPaginacion').getPageSize())) {
        var size = 10; // por defecto
    } else {
        var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaRelevamientos .activa').attr('value'), orden: $('#tablaRelevamientos .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaRelevamientos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        fecha_generacion: $('#buscadorFecha').val(),
        casino: $('#buscadorCasino').val(),
        sector: $('#buscadorSector').val(),
        estadoRelevamiento: $('#buscadorEstado').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/relevamientosProgresivo/buscarRelevamientosProgresivos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);

            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaEjemplo').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                var fila = generarFilaTabla(resultados.data[i]);
                $('#cuerpoTabla').append(fila);
            }

            $('#herramientasPaginacion')
                .generarIndices(page_number, page_size, resultados.total, clickIndice);
        },
        error: function(data) {
            console.log('Error:', data);
        }
    });
});

//Paginacion
$(document).on('click', '#tablaRelevamientos thead tr th[value]', function(e) {
    $('#tablaRelevamientos th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i')
            .removeClass().addClass('fa fa-sort-desc')
            .parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-desc')) {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort-asc')
                .parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i')
                .removeClass().addClass('fa fa-sort')
                .parent().attr('estado', '');
        }
    }
    $('#tablaRelevamientos th:not(.activa) i')
        .removeClass().addClass('fa fa-sort')
        .parent().attr('estado', '');
    clickIndice(e,
        $('#herramientasPaginacion').getCurrentPage(),
        $('#herramientasPaginacion').getPageSize());
});

function clickIndice(e, pageNumber, tam) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
    var columna = $('#tablaRelevamientos .activa').attr('value');
    var orden = $('#tablaRelevamientos .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function obtenerMensajesError(response) {
    json = response.responseJSON;
    mensajes = [];
    keys = Object.keys(json);
    for (let i = 0; i < keys.length; i++) {
        let k = keys[i];
        let msgs = json[k];
        for (let j = 0; j < msgs.length; j++) {
            mensajes.push(msgs[j]);
        }
    }

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

function causaNoTomaCallback(x) {
  let fila = $(x).parent().parent();
  const seteado = $(x).val() != -1;
  fila.find('input').attr('disabled',seteado).css('color',seteado? '#fff' : '');
  fila.find('input:not([data-id])').attr('disabled',true);
}

function obtenerFila(detalle,modo){
  let fila = null;
  if(modo == 'validar'){
    fila = $('#modalRelevamientoProgresivos .filaEjemplo.validacion').clone();
  }
  else if(modo == 'cargar'){
    fila = $('#modalRelevamientoProgresivos .filaEjemplo:not(.validacion)').clone();
  }
  else throw "Modo no soportado";
  fila.removeClass('filaEjemplo validacion').show().css('display', '');
  const nombre_prog = detalle.nombre_progresivo;
  if(!detalle.pozo_unico){
    nombre_prog += ` (${detalle.nombre_pozo})`;
  }
  fila.find('.nombreProgresivo').text(nombre_prog);
  fila.find('.maquinas').text(detalle.nro_admins);
  fila.find('.isla').text(detalle.nro_islas);  
  fila.attr('data-id', detalle.id_detalle_relevamiento_progresivo);
  fila.find('.causaNoToma').on('change', function() {
    causaNoTomaCallback(this);
  });
  if (detalle.id_tipo_causa_no_toma_progresivo != null) {
    fila.find('.causaNoToma').val(detalle.id_tipo_causa_no_toma_progresivo);
    fila.find('.causaNoToma').change();
  }
  detalle.niveles.forEach(function(n,idx){
    const nivel = fila.find('.nivel'+n.nro_nivel);
    if (n.nombre_nivel != null) nivel.attr('placeholder', n.nombre_nivel);
    nivel.val(n.valor).attr('data-id', n.id_nivel_progresivo);
  });
  fila.find('input').off().change(sacarAlerta);
  fila.find('input:not([data-id])').attr('disabled', true);
  return fila;
}

function setearRelevamiento(data, filaCallback) {
    //Limpio los campos
    $('#modalRelevamientoProgresivos input').val('');
    $('#modalRelevamientoProgresivos select').val(-1);
    $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr').not('.filaEjemplo').remove();

    $('#usuario_fiscalizador').attr('list', 'datalist' + data.casino.id_casino);

    $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
    $('#cargaCasino').val(data.casino.nombre);
    $('#cargaSector').val(data.sector.descripcion);
    $('#fiscaCarga').val(data.relevamiento.id_usuario_cargador);
    $('#fecha').val(data.relevamiento.fecha_ejecucion);

    if (data.usuario_cargador != null)
        $('#usuario_cargador').val(data.usuario_cargador.nombre);
    if (data.usuario_fiscalizador != null)
        $('#usuario_fiscalizador').val(data.usuario_fiscalizador.nombre);

    if (data.relevamiento.subrelevamiento != null) {
        $('#cargaSubrelevamiento').val(data.relevamiento.subrelevamiento);
    }

    $('#observacion_carga').val('');
    if (data.relevamiento.observacion_carga != null) {
        $('#observacion_carga').val(data.relevamiento.observacion_carga);
    }

    $('#observacion_validacion').val('');
    if (data.relevamiento.observacion_validacion != null) {
        $('#observacion_validacion').val(data.relevamiento.observacion_validacion);
    }

    let tabla = $('#modalRelevamientoProgresivos .cuerpoTablaPozos');
    let individuales = [];
    data.detalles.forEach(function(d){
        if(d.es_individual == 0) tabla.append(filaCallback(d).addClass('linkeado'));
        else individuales.push(d);
    });
    if(individuales.length>0){
        individuales.forEach(function(d){
            tabla.append(filaCallback(d).addClass('individual'));
        });
        setTimeout(setearBordeSeparadorFilaProgresivos,1000);
    }
}

function setearBordeSeparadorFilaProgresivos(){
    let fila = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr.linkeado').not('.filaEjemplo').last();
    //Le seteo la misma altura a todas las celdas y le pongo el borde
    //No se puede poner el borde a la fila por que no lo toma, y se necesita ponerle la misma altura
    //Porque tienen alturas distintas y el borde se ve horrible si no. 
    //Tomo la altura de la celda mas grande de la fila.
    let altura = 0;
    fila.find('td').each(function(){
        const h = parseFloat($(this).css('height'));
        if(h>altura){
            altura = h;
        } 
    });
    fila.addClass('separadorProgresivos');
    fila.find('td').css('height',altura).css('border-bottom','double gray');
}
function sacarBordeSeparadorFilaProgresivos(){
    let fila = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr.separadorProgresivos').not('.filaEjemplo');
    fila.find('td').css('height','revert').css('border-bottom','revert');
    fila.removeClass('separadorProgresivos');
}

function mensajeError(errores) {
    $('#mensajeError .textoMensaje').empty();
    for (let i = 0; i < errores.length; i++) {
        $('#mensajeError .textoMensaje').append($('<h4></h4>').text(errores[i]));
    }
    $('#mensajeError').hide();
    setTimeout(function() {
        $('#mensajeError').show();
    }, 250);
}

function obtenerIdFiscalizador(id_casino, str) {
    let f = $('#datalist' + id_casino).find('option:contains("' + str + '")');
    if (f.length != 1) return null;
    else return f.attr('data-id');
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
    const niveles = f.find(causaNoToma == -1? 'input:not([disabled])' : '').map(function(idx, input) {
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
      id_tipo_causa_no_toma: causaNoToma == -1? null : causaNoToma,
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
    let mensajes = [];
    let fisca = $('#usuario_fiscalizador').val();
    if (fisca == "" ||
        obtenerIdFiscalizador(id_casino, fisca) === null) {
        errores = true;
        mensajes.push("Ingrese un fiscalizador");
        $('#usuario_fiscalizador').addClass('alerta');
    }

    let fecha = $('#fecha').val();
    if (fecha == "") {
        errores = true;
        mensajes.push("Ingrese una fecha de ejecución");
        $('#fecha').addClass('alerta');
    }

    let filas = $('#modalRelevamientoProgresivos .cuerpoTablaPozos tr')
        .not('.filaEjemplo');
    let inputs = filas.find('input:not([disabled])');
    let hay_vacio = false;
    for (let i = 0; i < inputs.length; i++) {
        let input = $(inputs[i]);
        const fval = parseFloat(input.val());
        if (input === null ||
            input.val() == "" ||
            isNaN(fval) || fval < 0) {
            errores = true;
            hay_vacio = true;
            input.addClass('alerta');
        }
    }
    if (hay_vacio) mensajes.push("Tiene al menos un nivel sin ingresar o con valores invalidos");
    return { errores: errores, mensajes: mensajes };
}

$('.cabeceraTablaPozos th.sortable').click(function() {
    let sort_by = $(this).attr('data-id');
    let filas_linkeados = $('.cuerpoTablaPozos tr.linkeado');
    let filas_individuales = $('.cuerpoTablaPozos tr.individual');
    console.log(sort_by);
    if ($(this).attr('sorted') === undefined) {
        $(this).attr('sorted', false);
    }

    let xor = $(this).attr('sorted');

    if (xor === "true") $(this).attr('sorted', false);
    else $(this).attr('sorted', true);

    function comp(a, b) {
        let aa = $(a).find('.' + sort_by)[0];
        let bb = $(b).find('.' + sort_by)[0];

        let aa_type = aa.tagName;
        let bb_type = bb.tagName;

        if (aa_type === bb_type) {
            if (aa_type === "TD") {
                return aa.textContent.localeCompare(bb.textContent) != xor;
            } else throw "Comparison not programmed.";
        } else throw "Error not matching types in comparison";
    }

    function clonar(add){
        let clonado = $(add).clone();
        //Tengo que setear todo de vuelta, el clone no clona bien -___-
        clonado.find('.causaNoToma').val($(add).find('.causaNoToma').val());
        clonado.find('.causaNoToma').off().change(function() {
            causaNoTomaCallback(this);
        });
        clonado.find('input').off().change(sacarAlerta);
        return clonado;
    }

    let reordenadas = ordenar(filas_linkeados, comp, clonar);
    $('.cuerpoTablaPozos tr').not('.filaEjemplo').remove();
    $('.cuerpoTablaPozos').append(reordenadas);
    if(filas_individuales.length>0){
        sacarBordeSeparadorFilaProgresivos();
        reordenadas = ordenar(filas_individuales,comp,clonar);
        $('.cuerpoTablaPozos').append(reordenadas);
        setearBordeSeparadorFilaProgresivos();
    }
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

function ordenar(list, comp, onadd = function(add) { return add; }) {
    //Encuentra el optimo valor, con una lista negra
    function find_val(list, comp, blacklist) {
        let ret = null;
        let ret_idx = null;
        for (let i = 0; i < list.length; i++) {
            let item = list[i];
            if (!blacklist[i] && (ret_idx === null || comp(item, ret))) {
                ret = item;
                ret_idx = i;
            }
        }
        return { elem: ret, index: ret_idx };
    }

    let newlist = [];
    let used = [];

    for (let i = 0; i < list.length; i++) {
        used.push(false);
    }

    for (let i = 0; i < list.length; i++) {
        let to_add = find_val(list, comp, used);
        newlist.push(onadd(to_add.elem));
        used[to_add.index] = true;
    }

    return newlist;
}
