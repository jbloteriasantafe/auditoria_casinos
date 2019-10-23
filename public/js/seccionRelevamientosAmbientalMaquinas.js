$(document).ready(function() {
    $('.tituloSeccionPantalla').text('Relevamientos de control ambiental - Máquinas');
    $('#opcRelevamientosProgresivos').attr('style', 'border-left: 6px solid #673AB7; background-color: #131836;');
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
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
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
        url: 'http://' + window.location.host + '/relevamientosControlAmbiental/buscarRelevamientosAmbiental',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);

            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaEjemplo').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                //var fila = generarFilaTabla(resultados.data[i]);
                //$('#cuerpoTabla').append(fila);
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
    //var subrelevamiento;
    //relevamiento.sub_control != null ? subrelevamiento = relevamiento.sub_control : subrelevamiento = '';
    let fila = $('#cuerpoTabla .filaEjemplo').clone().removeClass('filaEjemplo').show();
    fila.attr('data-id', relevamiento.id_relevamiento_ambiental);
    fila.find('.fecha').text(relevamiento.fecha_generacion);
    fila.find('.casino').text(relevamiento.casino);
    //fila.find('.sector').text(relevamiento.sector);
    //fila.find('.subcontrol').text(subrelevamiento);
    fila.find('.textoEstado').text(relevamiento.estado);
    fila.find('button').each(function(idx, c) { $(c).val(relevamiento.id_relevamiento_ambiental); });
    let planilla = fila.find('.planilla').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VER PLANILLA', 'data-delay': '{"show":"300", "hide":"100"}' });
    let carga = fila.find('.carga').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'CARGAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });
    let validacion = fila.find('.validar').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VISAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });
    let imprimir = fila.find('.imprimir').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'IMPRIMIR PLANILLA', 'data-delay': '{"show":"300", "hide":"100"}' });
    let eliminar = fila.find('.eliminar').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'ELIMINAR RELEVAMIENTO', 'data-delay': '{"show":"300", "hide":"100"}' });
    //Se setea el display como table-row por algun motivo :/
    //Lo saco a pata.
    fila.css('display', 'flow-root');
    //Qué ESTADO e ICONOS mostrar
    cambiarEstadoFila(fila, relevamiento);

    return fila;
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
        window.open('relevamientosControlAmbiental/generarPlanilla/' + relevamiento.id_relevamiento_ambiental, '_blank');
    });
    /*
    imprimir.click(function() {
        window.open('relevamientosProgresivo/generarPlanilla/' + relevamiento.id_relevamiento_progresivo, '_blank');
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
                    throw 'Error al eliminar progresivo id no definido';
                }

                $.ajax({
                    type: "GET",
                    url: 'relevamientosProgresivo/eliminarRelevamientoProgresivo/' + id,
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
    */
}

//ABRIR MODAL DE NUEVO RELEVAMIENTO
$('#btn-nuevo').click(function(e) {
    e.preventDefault();
    $('.modal-title').text('| NUEVO RELEVAMIENTO DE CONTROL AMBIENTAL - MÁQUINAS');
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
        url: 'relevamientosControlAmbiental/crearRelevamiento',
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
