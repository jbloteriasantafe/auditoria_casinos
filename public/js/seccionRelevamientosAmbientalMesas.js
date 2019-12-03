$(document).ready(function() {
    $('.tituloSeccionPantalla').text('Relevamientos de control ambiental - Mesas');
    $('#iconoCarga').hide();

    $('#dtpBuscadorFecha').datetimepicker({
        language: 'es',
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd',
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        endDate: '+0d'
    });

    $('#dtpFecha').datetimepicker({
        todayBtn: 1,
        language: 'es',
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd HH:ii:ss',
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 0,
        ignoreReadonly: true,
        minuteStep: 5,
        endDate: '+0d'
    });

    $('#dtpFecha span.nousables').off();

    $('#fechaRelevamientoDiv').datetimepicker({
        todayBtn: 1,
        language: 'es',
        autoclose: 1,
        todayHighlight: 1,
        format: 'yyyy-mm-dd HH:ii:ss',
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 0,
        ignoreReadonly: true,
        minuteStep: 5,
        endDate: '+0d'
    });

    //trigger buscar, carga de tabla, fecha desc
    $('#btn-buscar').trigger('click');
});


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
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaRelevamientos .activa').attr('value'), orden: $('#tablaRelevamientos .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaRelevamientos th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        fecha_generacion: $('#buscadorFecha').val(),
        casino: $('#buscadorCasino').val(),
        estadoRelevamiento: $('#buscadorEstado').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/relevamientosControlAmbientalMesas/buscarRelevamientosAmbiental',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);

            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaEjemplo').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaRelevamientos tbody').append(generarFilaTabla(resultados.data[i]));
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

function generarFilaTabla(relevamiento) {
    let fila = $('#cuerpoTabla .filaEjemplo').clone().removeClass('filaEjemplo').show();
    fila.attr('data-id', relevamiento.id_relevamiento_ambiental);
    fila.find('.fecha').text(relevamiento.fecha_generacion);
    fila.find('.casino').text(relevamiento.casino);
    fila.find('.textoEstado').text(relevamiento.estado);
    fila.find('button').each(function(idx, c) { $(c).val(relevamiento.id_relevamiento_ambiental); });
    let planilla = fila.find('.planilla').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VER PLANILLA', 'data-delay': '{"show":"300", "hide":"100"}' });
    let carga = fila.find('.carga').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'CARGAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });
    let validacion = fila.find('.validar').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VISAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });
    let imprimir = fila.find('.imprimir').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'IMPRIMIR PLANILLA', 'data-delay': '{"show":"300", "hide":"100"}' });
    let eliminar = fila.find('.eliminar').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'ELIMINAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });

    fila.css('display', 'flow-root');
    cambiarEstadoFila(fila, relevamiento);

    return fila;
}

function validarRelevamiento(relevamiento) {
    $('#id_relevamiento').val(relevamiento.id_relevamiento_ambiental);
    $('#modalRelevamientoAmbiental .mensajeSalida').hide();

    $('#btn-guardar').hide();
    $('#btn-finalizar').show().text("VISAR").off();

    $('#modalRelevamientoAmbiental')
        .find('.modal-header')
        .attr('style',
            "font-family:'Roboto-Black';color:white;background-color:#69F0AE;");
    $('#modalRelevamientoAmbiental').
    find('.modal-title').text('| VALIDAR RELEVAMIENTO DE CEONTROL AMBIENTAL');

    $('#usuario_fiscalizador').attr('disabled', true);
    $('#fecha').attr('disabled', true);
    $('#fecha').removeClass('fondoBlanco');

    $('#dtpFecha span.nousables').show();
    $('#dtpFecha span.usables').hide();

    $.get('relevamientosControlAmbientalMesas/obtenerRelevamiento/' + relevamiento.id_relevamiento_ambiental,
        function(data) {
            setearRelevamiento(data, obtenerFilaValidacion);

            $('#btn-finalizar').click(function() {
                enviarFormularioValidacion(relevamiento.id_relevamiento_ambiental,
                    function(x) {
                        console.log(x);

                        $('#modalRelevamientoAmbiental').modal('hide');
                        $('#tablaPersonas .cabeceraTablaPersonas').children().remove();
                        let fila = $('#cuerpoTabla tr[data-id="' + relevamiento.id_relevamiento_ambiental + '"]');
                        relevamiento.estado = "Visado";
                        cambiarEstadoFila(fila, relevamiento);
                    },
                    function(x) {
                        console.log(x);
                        let msgs = obtenerMensajesError(x);
                        mensajeError(msgs);
                    });
            });
        });

    $('#observacion_carga').attr('disabled', true);
    $('#observacion_validacion').parent().show();
    $('#modalRelevamientoAmbiental').modal('show');
}

function obtenerFilaValidacion(detalle) {
  let fila = $('#modalRelevamientoAmbiental .filaEjemplo').not('.validacion')
                .clone().removeClass('filaEjemplo').show().css('display', '');

  fila.find('.mesa').text(detalle.nombre);
  fila.attr('data-id', detalle.id_detalle_relevamiento_ambiental);

  for (let i=1; i<=detalle.cantidad_turnos; i++) {
    fila.append($('<td>')
        .addClass('col-xs-1')
        .css('width','120px')
        .css('display','inline-block')
        .append($('<input>')
            .addClass('turno'+i)
            .addClass('form-control')
            .attr('min',0)
            .attr('data-toggle','tooltip')
            .attr('data-placement','down')
            .attr('title','turno'+i)
            .attr('disabled','true')
          )
        )
  }

  if (detalle.turno1 != null) {
    fila.find('.turno1')
        .val(detalle.turno1)
  }
  if (detalle.turno2 != null) {
    fila.find('.turno2')
        .val(detalle.turno2)
  }
  if (detalle.turno3 != null) {
    fila.find('.turno3')
        .val(detalle.turno3)
  }
  if (detalle.turno4 != null) {
    fila.find('.turno4')
        .val(detalle.turno4)
  }
  if (detalle.turno5 != null) {
    fila.find('.turno5')
        .val(detalle.turno5)
  }
  if (detalle.turno6 != null) {
    fila.find('.turno6')
        .val(detalle.turno6)
  }
  if (detalle.turno7 != null) {
    fila.find('.turno7')
        .val(detalle.turno7)
  }
  if (detalle.turno8 != null) {
    fila.find('.turno8')
        .val(detalle.turno8)
  }

  return fila;
}

function enviarFormularioValidacion(id_relevamiento, succ = function(x) { console.log(x); }, err = function(x) { console.log(x); }) {
    let url = "relevamientosControlAmbientalMesas/validarRelevamiento";

    let formData = {
        id_relevamiento_ambiental: id_relevamiento,
        observacion_validacion: $('#observacion_validacion').val()
    };

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: succ,
        error: err
    });
}

function cambiarEstadoFila(fila, relevamiento) {
    let planilla = fila.find('.planilla').off();
    let carga = fila.find('.carga').off();
    let validacion = fila.find('.validar').off();
    let imprimir = fila.find('.imprimir').off();
    let eliminar = fila.find('.eliminar').off().show();

    fila.find('.textoEstado').text(relevamiento.estado);

    switch (relevamiento.estado) {
        case 'Generado':
            fila.find('.fa-dot-circle').addClass('faGenerado');
            carga.click(function(e) {
                e.preventDefault();
                cargarRelevamiento(relevamiento);
            });
            validacion.hide();
            imprimir.hide();
            carga.show();
            planilla.show();
            break;
        case 'Cargando':
            fila.find('.fa-dot-circle').addClass('faCargando');
            carga.click(function(e) {
                e.preventDefault();
                cargarRelevamiento(relevamiento);
            });
            validacion.hide();
            imprimir.hide();
            carga.show();
            planilla.show();
            break;
        case 'Finalizado':
            fila.find('.fa-dot-circle').addClass('faFinalizado');
            validacion.click(function(e) {
                e.preventDefault();
                validarRelevamiento(relevamiento);
            });
            carga.hide();
            imprimir.hide();
            planilla.show();
            validacion.show();
            break;
        case 'Visado':
            fila.find('.fa-dot-circle').addClass('faValidado');
            planilla.hide();
            carga.hide();
            validacion.hide();
            imprimir.show();
            break;
    }

    planilla.click(function() {
        window.open('relevamientosControlAmbientalMesas/generarPlanilla/' + relevamiento.id_relevamiento_ambiental, '_blank');
    });

    imprimir.click(function() {
        window.open('relevamientosControlAmbientalMesas/generarPlanilla/' + relevamiento.id_relevamiento_ambiental, '_blank');
    });

    eliminar.click(function() {
        mensajeAlerta(
            //MENSAJES
            ["<h4><b>ESTA POR ELIMINAR UN RELEVAMIENTO</b></h4>"],

            //CONFIRMAR
            function() {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });

                const id = $(eliminar).val();
                if (id === null || typeof id === 'undefined') {
                    throw 'Error al eliminar: ID no definido';
                }

                $.ajax({
                    type: "GET",
                    url: 'relevamientosControlAmbientalMesas/eliminarRelevamientoAmbiental/' + id,
                    success: function(data) {
                        console.log(data);
                        $('#mensajeAlerta').hide();
                        $('#cuerpoTabla tr[data-id="' + id + '"').remove();
                    },
                    error: function(data) {
                        console.log(data);
                        $('#mensajeAlerta').hide();
                    }
                });
            },

            //CANCELAR
            function() {
                $('#mensajeAlerta').hide();
            }
        );
    })

}

function mensajeAlerta(alertas, callbackConfirmar, callbackCancelar) {
    $('#mensajeAlerta .textoMensaje').empty();
    for (let i = 0; i < alertas.length; i++) {
        $('#mensajeAlerta .textoMensaje').append($(alertas[i]));
    }
    $('#mensajeAlerta .confirmar').off().click(callbackConfirmar);
    $('#mensajeAlerta .cancelar').off().click(callbackCancelar);
    $('#mensajeAlerta').show();
}

function cargarRelevamiento(relevamiento) {
    $('#modalRelevamientoAmbiental .mensajeSalida').hide();
    $('#id_relevamiento').val(relevamiento.id_relevamiento_ambiental);
    $('#btn-guardar').show().off();
    $('#btn-finalizar').show().text("FINALIZAR").off();

    $('#modalRelevamientoAmbiental')
        .find('.modal-header')
        .attr("style","font-family:'Roboto-Black';color:white;background-color:#FF6E40;");

    $('#modalRelevamientoAmbiental').
      find('.modal-title')
      .text('| CARGAR RELEVAMIENTO DE CONTROL AMBIENTAL');

    $('#inputFisca').attr('disabled', false);
    $('#usuario_fiscalizador').attr('disabled', false);
    $('#fecha').attr('disabled', false);
    $('#fecha').addClass('fondoBlanco');

    $('#dtpFecha span.usables').show();
    $('#dtpFecha span.nousables').hide();

    $.get('relevamientosControlAmbientalMesas/obtenerRelevamiento/' + relevamiento.id_relevamiento_ambiental,
        function(data) {
            setearRelevamiento(data, obtenerFila);

            $('#btn-finalizar').click(function() {
                let err = validarFormulario(data.casino.id_casino);
                if (err.errores) {
                    console.log(err.mensajes);
                    mensajeError(err.mensajes);
                    return;
                }

                enviarFormularioCarga(data,
                    function(data) {
                        console.log(data);
                        $('#modalRelevamientoAmbiental').modal('hide');
                        $('#tablaPersonas .cabeceraTablaPersonas').children().remove();
                        let fila = $('#cuerpoTabla tr[data-id="' + relevamiento.id_relevamiento_ambiental + '"]');
                        relevamiento.estado = "Finalizado";
                        cambiarEstadoFila(fila, relevamiento);
                    },
                    function(x) {
                        console.log(x);
                        let msgs = obtenerMensajesError(x);
                        mensajeError(msgs);
                    },
                    "relevamientosControlAmbientalMesas/cargarRelevamiento"
                );
            });

            $('#btn-guardar').click(function() {
                enviarFormularioCarga(data,
                    function(x) {
                        console.log(x);
                        $('#modalRelevamientoAmbiental').modal('hide');
                        let fila = $('#cuerpoTabla tr[data-id="' + relevamiento.id_relevamiento_ambiental + '"]');
                        relevamiento.estado = "Cargando";
                        cambiarEstadoFila(fila, relevamiento);
                    },
                    function(x) {
                        console.log(x);
                    },
                    "relevamientosControlAmbientalMesas/guardarTemporalmenteRelevamiento"
                );
            });

        });

    $('#observacion_carga').removeAttr('disabled');
    $('#observacion_validacion').parent().hide();
    $('#modalRelevamientoAmbiental').modal('show');
}

function setearRelevamiento(data, filaCallback, esValidacion) {
    let row_th = $('#tablaPersonas .cabeceraTablaPersonas');
    //Limpio los campos
    console.log(data.casino.id_casino);
    $('#modalRelevamientoAmbiental input').val('');
    $('#modalRelevamientoAmbiental select').val(-1);
    $('#modalRelevamientoAmbiental .cuerpoTablaPersonas tr').not('.filaEjemplo').remove();
    $('#usuario_fiscalizador').attr('list', 'datalist' + data.casino.id_casino);
    $('#tipo_control_ambiental').val("Mesas de paño");

    $('#cargaFechaGeneracion').val(data.relevamiento.fecha_generacion);
    $('#cargaCasino').val(data.casino.nombre);
    $('#fiscaCarga').val(data.relevamiento.id_usuario_cargador);
    $('#fecha').val(data.relevamiento.fecha_ejecucion);

    if (data.usuario_cargador != null)
        $('#usuario_cargador').val(data.usuario_cargador.nombre);

    if (data.usuario_fiscalizador != null)
        $('#usuario_fiscalizador').val(data.usuario_fiscalizador.nombre);

    $('#observacion_carga').val('');
    if (data.relevamiento.observacion_carga != null) {
        $('#observacion_carga').val(data.relevamiento.observacion_carga);
    }

    row_th.append($('<th>')
      .addClass('sortable')
      .attr('data-id','mesa')
      .css('width','120px')
      .css('display','inline-block')
      .text('MESA')
    );

    for (let i=1; i<=data.cantidad_turnos; i++) {
      row_th.append($('<th>')
          .attr('id','t'+i)
          .css('width','120px')
          .css('display','inline-block')
          .text('TURNO '+i)
      );
    }

    let tabla = $('#modalRelevamientoAmbiental .cuerpoTablaPersonas');
    for (let i = 0; i < data.detalles.length; i++) {
        tabla.append(filaCallback(data.detalles[i]));
    }
}

function obtenerFila(detalle) {
    let fila = $('#modalRelevamientoAmbiental .filaEjemplo').not('.validacion')
                  .clone().removeClass('filaEjemplo').show().css('display', '');

    fila.find('.mesa').text(detalle.nombre);
    fila.attr('data-id', detalle.id_detalle_relevamiento_ambiental);
    for (let i=1; i<=detalle.cantidad_turnos; i++) {
      fila.append($('<td>')
          .addClass('col-xs-1')
          .css('width','120px')
          .css('display','inline-block')
          .append($('<input>')
              .addClass('turno'+i)
              .addClass('form-control')
              .attr('min',0)
              .attr('data-toggle','tooltip')
              .attr('data-placement','down')
              .attr('title','turno'+i)
            )
          )
    }

    if (detalle.turno1 != null) {
      fila.find('.turno1')
          .val(detalle.turno1)
    }
    if (detalle.turno2 != null) {
      fila.find('.turno2')
          .val(detalle.turno2)
    }
    if (detalle.turno3 != null) {
      fila.find('.turno3')
          .val(detalle.turno3)
    }
    if (detalle.turno4 != null) {
      fila.find('.turno4')
          .val(detalle.turno4)
    }
    if (detalle.turno5 != null) {
      fila.find('.turno5')
          .val(detalle.turno5)
    }
    if (detalle.turno6 != null) {
      fila.find('.turno6')
          .val(detalle.turno6)
    }
    if (detalle.turno7 != null) {
      fila.find('.turno7')
          .val(detalle.turno7)
    }
    if (detalle.turno8 != null) {
      fila.find('.turno8')
          .val(detalle.turno8)
    }

    return fila;
}

function enviarFormularioCarga(relevamiento,
    succ = function(data) { console.log(data); },
    err = function(data) { console.log(data); },
    url) {

    let id_usuario_fisca = $('#usuario_fiscalizador').val().trim() == '' ? null : obtenerIdFiscalizador(relevamiento.casino.id_casino, $('#usuario_fiscalizador').val());

    let formData = {
        id_relevamiento_ambiental: relevamiento.relevamiento.id_relevamiento_ambiental,
        fecha_ejecucion: $('#fecha').val(),
        id_casino: relevamiento.casino.id_casino,
        id_usuario_fiscalizador: id_usuario_fisca,
        observaciones: $('#observacion_carga').val(),
        detalles: []
    };

    let filas = $('#modalRelevamientoAmbiental .cuerpoTablaPersonas tr').not('.filaEjemplo');

    for (let i = 0; i < filas.length; i++) {
        let fila = $(filas[i]);
        let id_detalle_relevamiento_ambiental = fila.attr('data-id');
        let personasTurnos = [];

        fila.find('input:not([disabled])')
            .each(function(idx, c) {
                let valor = $(c).val();
                personasTurnos.push({
                    valor: valor,
                });
            });

        formData.detalles.push({
            id_detalle_relevamiento_ambiental: id_detalle_relevamiento_ambiental,
            personasTurnos: personasTurnos
        });

    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: succ,
        error: err
    });
}

function obtenerIdFiscalizador(id_casino, str) {
    let f = $('#datalist' + id_casino).find('option:contains("' + str + '")');
    if (f.length == 0) return null;
    else return f.attr('data-id');
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

    let filas = $('#modalRelevamientoAmbiental .cuerpoTablaPersonas tr')
        .not('.filaEjemplo');
    let inputs = filas.find('input:not([disabled])');
    let hay_vacio = false;

    for (let i = 0; i < inputs.length; i++) {
        let input = $(inputs[i]);
        if (input === null || input.val() == "" || input < 0) {
          errores = true;
          hay_vacio = true;
          input.addClass('alerta');
        }
    }
    if (hay_vacio) mensajes.push("Tiene al menos un nivel sin ingresar o con valores invalidos");
    return { errores: errores, mensajes: mensajes };
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

$('#btn-salir').click(function() {
    $('#tablaPersonas .cabeceraTablaPersonas').children().remove();
    $('#modalRelevamientoAmbiental').modal('hide');
});

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevo').click(function(e) {
    e.preventDefault();
    $('.modal-title').text('| NUEVO RELEVAMIENTO DE CONTROL AMBIENTAL - MESAS');
    $('#modalRelevamiento .modalNuevo').attr('style', 'font-family: Roboto-Black; background-color: #6dc7be;');
    $('#modalRelevamiento').modal('show');
});

//GENERAR RELEVAMIENTO CONTROL AMBIENTAL
$('#btn-generar').click(function(e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var formData = {
        id_casino: $('#casino').val(),
        fecha_generacion: $('#fechaRelevamientoInput').val()
    }

    $.ajax({
        type: "POST",
        url: 'relevamientosControlAmbientalMesas/crearRelevamiento',
        data: formData,
        dataType: 'json',
        success: function(data) {
            $('#btn-buscar').trigger('click');
            $('#modalRelevamiento').modal('hide');
        },
        error: function(data) {
            var response = JSON.parse(data.responseText);

            if (typeof response.id_casino !== 'undefined') {
                $('#sector').addClass('alerta');
                $('#casino').addClass('alerta');
            }
            if (typeof response.fecha_generacion !== 'undefined') {
                $('#fechaRelevamientoInput').addClass('alerta');
            }
        }
    });

});
