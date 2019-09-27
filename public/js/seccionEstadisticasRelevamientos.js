$(document).ready(function() {

    $('#barraMaquinas').attr('aria-expanded', 'true');
    $('#maquinas').removeClass();
    $('#maquinas').addClass('subMenu1 collapse in');
    $('#informesMTM').removeClass();
    $('#informesMTM').addClass('subMenu2 collapse in');

    $('.tituloSeccionPantalla').text('Estadísticas de relevamientos');
    $('#opcEstadisticas').attr('style', 'border-left: 6px solid #25306b; background-color: #131836;');
    $('#opcEstadisticas').addClass('opcionesSeleccionado');

    habilitarDTP();
    habilitarDTPPedido();
    $('#btn-buscarMaquina').popover({ html: true });
    $('#fecha_desde').popover({ html: true });
    $('#B_fecha_inicio_m').popover({ html: true });
    cargarMaquinas();
});

function cargarMaquinas() {
    let id_casino = $('#b_casinoMaquina').val();
    if (id_casino.length == 0) id_casino = 0;

    function callbackMaquinas(resultados) {
        let maquinas_lista = $('#maquinas_lista');
        maquinas_lista.empty();
        let option = $('<option></option>');
        for (var i = 0; i < resultados.length; i++) {
            let fila = option.clone();
            if (id_casino == 0) {
                fila.val(resultados[i].nro_admin + resultados[i].codigo);
            } else {
                fila.val(resultados[i].nro_admin);
            }

            fila.attr('data-id', resultados[i].id_maquina);
            fila.attr('data-casino', resultados[i].id_casino);
            fila.attr('data-nro_admin', resultados[i].nro_admin);
            maquinas_lista.append(fila);
        };
        actualizarBusqueda($('#b_adminMaquina').val());
    }

    $.ajax({
        type: 'GET',
        url: 'estadisticas_relevamientos/buscarMaquinas/' + id_casino,
        success: callbackMaquinas,
        error: function(data) {
            console.log(data)
        }
    });
}

//Filtra datalist segun lo que tipea
//Se hace asi pq sino matchea en el medio del string
//Y no le gustaba al usuario 
//i.e input='asd' -> recomendaba ('pepeasd','asd1','holaasd')
//Ahora seria input='asd' -> recomendar ('asd1')
function actualizarBusqueda(str) {
    console.log('actualizando con', str);
    $('#b_adminMaquina').attr('list', '');
    $('#maquinas_lista_sub').empty();
    const valores = $('#maquinas_lista').find('option');
    for (let i = 0; i < valores.length; i++) {
        const opt = $(valores[i]);
        if (opt.val().substr(0, str.length) === str) {
            //console.log('Agregando ', opt.val());
            $('#maquinas_lista_sub').append(opt.clone());
        }
    }
    $('#b_adminMaquina').attr('list', 'maquinas_lista_sub');
    $('#b_adminMaquina').focus();
}
$('#b_adminMaquina').on('input', function() {
    actualizarBusqueda($('#b_adminMaquina').val());
})

$('#b_casinoMaquina').change(function() {
    cargarMaquinas();
})

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function() {
    if ($(this).data("minimizar") == true) {
        $('.modal-backdrop').css('opacity', '0.1');
        $(this).data("minimizar", false);
    } else {
        $('.modal-backdrop').css('opacity', '0.5');
        $(this).data("minimizar", true);
    }
});
//Opacidad del modal al minimizar
$('#btn-minimizarDetalle').click(function() {
    if ($(this).data("minimizar") == true) {
        $('.modal-backdrop').css('opacity', '0.1');
        $(this).data("minimizar", false);
    } else {
        $('.modal-backdrop').css('opacity', '0.5');
        $(this).data("minimizar", true);
    }
});

$('#btn-ayuda').click(function(e) {
    e.preventDefault();

    $('.modal-title').text('| ESTADÍSTICAS DE RELEVAMIENTOS');
    $('.modal-header').attr('style', 'font-family: Roboto-Black; background-color: #aaa; color: #fff');

    $('#modalAyuda').modal('show');

});

$('#seccionBusquedaPorMaquina input ,#seccionBusquedaPorMaquina select').on('focusin', function() {
    $('#btn-buscarMaquina').popover('hide');
})

$('input , button').on('focusin', function() {
    $(this).popover('hide');
})

$('select').on('focusin', function() {
    $(this).removeClass('alerta');
})

$('#seccionBusquedaPorMaquina input').on('focusin', function() {
    $('#btn-buscarMaquina').popover('hide');
})

$('#b_casino').on('change', function() {
    var id_casino = $('#b_casino').val();


    $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data) {
        var selectSector = $('#busqueda_sector');
        selectSector.empty();
        selectSector.append($('<option>')
            .val(0)
            .text('Todos los sectores')
        )

        for (var i = 0; i < data.sectores.length; i++) {
            selectSector.append($('<option>')
                .val(data.sectores[i].id_sector)
                .text(data.sectores[i].descripcion)
            )
        }
    });
});

function generarFilaTabla(resultado) {
    var fila = $('<tr>');

    var botonPedir = $('<button>').addClass('btn btn-success pedido').val(resultado.id_maquina)
        .append($('<i>').addClass('fa fa-tag'));

    fila.append($('<td>').addClass('col-xs-3').text(resultado.casino));
    fila.append($('<td>').addClass('col-xs-2').text(resultado.sector));
    fila.append($('<td>').addClass('col-xs-2').text(resultado.isla));
    fila.append($('<td>').addClass('col-xs-2').text(resultado.maquina));
    fila.append($('<td>').addClass('col-xs-3').append(botonPedir));

    return fila;
}

//Filtro de busqueda
$('#btn-buscar').click(function(e, pagina, page_size, columna, orden) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();

    var page_size = (page_size != null) ? page_size : 10;
    var page_number = (pagina != null) ? pagina : 1;
    var sort_by = (columna != null) ? { columna, orden } : null;
    if (sort_by == null) { // limpio las columnas
        $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        id_casino: $('#b_casino').val(),
        id_sector: $('#busqueda_sector').val(),
        nro_isla: $('#b_isla').val(),
        fecha_desde: $('#fecha_desde_date').val(),
        fecha_hasta: $('#fecha_hasta_date').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'POST',
        url: 'estadisticas_relevamientos/buscarMaquinasSinRelevamientos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            console.log(resultados);

            $('#herramientasPaginacion').generarTitulo(page_number, page_size, resultados.total, clickIndice);
            $('#cuerpoTabla tr').remove();
            console.log(resultados.data);
            for (var i = 0; i < resultados.data.length; i++) {
                var filaMTMPedido = generarFilaTabla(resultados.data[i]);
                $('#cuerpoTabla').append(filaMTMPedido)
            }
            $('#herramientasPaginacion').generarIndices(page_number, page_size, resultados.total, clickIndice);

        },
        error: function(data) {
            var response = JSON.parse(data.responseText);

            if (typeof response.id_casino !== 'undefined') {
                $('#b_casino').addClass('alerta');
            }

            if (typeof response.fecha_desde !== 'undefined') {
                $('#fecha_desde').popover('show');
                $('.popover').addClass('popAlerta');
            }
        }
    });
});

//Busqueda de máquina
$('#btn-buscarMaquina').click(function(e) {
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();
    let codigo = $('#b_adminMaquina').val();
    let id_maquina = parseInt($('#maquinas_lista').find('option[value="' + codigo + '"]').attr('data-id'));
    let cantidad_relevamientos = parseInt($('#b_cantidad_relevamientos').val());
    if (isNaN(cantidad_relevamientos) ||
        cantidad_relevamientos <= 0 ||
        isNaN(id_maquina)) {
        //Ignorar
        return;
    }
    const tomado = $('#b_tomado').val() == "" ? null : $('#b_tomado').val();
    const diferencia = $('#b_diferencia').val() == "" ? null : $('#b_diferencia').val();

    var formData = {
        id_maquina: id_maquina,
        cantidad_relevamientos: cantidad_relevamientos,
    };
    if (tomado != null) formData.tomado = tomado;
    if (diferencia != null) formData.diferencia = diferencia;

    $.ajax({
        type: 'POST',
        url: 'estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina',
        data: formData,
        dataType: 'json',
        success: function(data) {
            console.log(data);

            $('#casinoDetalle').val(data.maquina.casino);
            $('#sectorDetalle').val(data.maquina.sector);
            $('#islaDetalle').val(data.maquina.isla);
            $('#adminDetalle').val(data.maquina.nro_admin);

            $('#tablaRelevamientos tbody tr').remove();
            // se cambia pora no recalcular, el relevamiento ya se hizo, por lo que se traen los valores calculados en su momento, sin alteraciones 
            for (var i = 0; i < data.detalles.length; i++) {
                var fila = $('<tr>');

                var producidoCalculado = null;
                if (data.detalles[i].tipos_causa_no_toma == null) {
                    producidoCalculado = data.detalles[i].producido_calculado_relevado;
                } else {
                    producidoCalculado = data.detalles[i].tipos_causa_no_toma;
                    fila.addClass('no_tomado');
                }

                fila.append($('<td>').text(data.detalles[i].fecha))

                data.detalles[i].cont1 != null ? fila.append($('<td>').text(data.detalles[i].cont1)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont2 != null ? fila.append($('<td>').text(data.detalles[i].cont2)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont3 != null ? fila.append($('<td>').text(data.detalles[i].cont3)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont4 != null ? fila.append($('<td>').text(data.detalles[i].cont4)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont5 != null ? fila.append($('<td>').text(data.detalles[i].cont5)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont6 != null ? fila.append($('<td>').text(data.detalles[i].cont6)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont7 != null ? fila.append($('<td>').text(data.detalles[i].cont7)) : fila.append($('<td>').text('-'));
                data.detalles[i].cont8 != null ? fila.append($('<td>').text(data.detalles[i].cont8)) : fila.append($('<td>').text('-'));

                data.detalles[i].coinin != null ? fila.append($('<td>').text(data.detalles[i].coinin)) : fila.append($('<td>').text('-'));
                data.detalles[i].coinout != null ? fila.append($('<td>').text(data.detalles[i].coinout)) : fila.append($('<td>').text('-'));
                data.detalles[i].jackpot != null ? fila.append($('<td>').text(data.detalles[i].jackpot)) : fila.append($('<td>').text('-'));
                data.detalles[i].progresivo != null ? fila.append($('<td>').text(data.detalles[i].progresivo)) : fila.append($('<td>').text('-'));

                fila.append($('<td>').text(producidoCalculado))

                data.detalles[i].producido_importado != null ? fila.append($('<td>').text(data.detalles[i].producido_importado)) : fila.append($('<td>').text('-'));

                data.detalles[i].diferencia != null ? fila.append($('<td>').text(data.detalles[i].diferencia)) : fila.append($('<td>').text('-'));


                $('#tablaRelevamientos tbody').append(fila);

            }

            $('#modalDetalle').modal('show');
        },
        error: function(data) {
            var response = JSON.parse(data.responseText);

            if (typeof response.id_casino !== 'undefined' || typeof response.nro_admin !== 'undefined' || typeof response.cantidad_relevamientos !== 'undefined') {
                $('#btn-buscarMaquina').popover('show');
                $('.popover').addClass('popAlerta');
            }

        }
    });
});

function calcularProducido(formula, contadores) {
    var producido = 0;
    var sinProducido = false;

    if (formula.cont1 != null) contadores.cont1 == null ? sinProducido = true : producido += parseFloat(contadores.cont1);

    for (var i = 2; i < 9; i++) {
        if (formula['cont' + i] != null)
            contadores['cont' + i] == null ?
            sinProducido = true : formula['operador' + i - 1] == '+' ?
            producido += parseFloat(contadores['cont' + i]) : producido -= parseFloat(contadores['cont' + i]);

    }

    if (sinProducido) {
        return '';
    } else {
        return producido;
    }
}

function habilitarDTPPedido() {
    $('#dtpFechaFin_m')
        .datetimepicker({
            language: 'es',
            todayBtn: 1,
            autoclose: 1,
            todayHighlight: 1,
            format: 'yyyy-mm-dd',
            pickerPosition: "bottom-left",
            startView: 4,
            minView: 2,
            minDate: 1,
            startDate: new Date()
        });

    const hoy = new Date();
    const mañana = new Date(hoy.getTime() + (24 * 60 * 60 * 1000));

    $('#B_fecha_inicio_m').val(dateToString(mañana));
    $('#dtpFechaFin_m').datetimepicker("setDate", mañana);
}

function habilitarDTP() {
    $('#b_fecha_desde').datetimepicker({
        language: 'es',
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'dd / mm / yyyy',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2
    });

    $('#b_fecha_hasta').datetimepicker({
        language: 'es',
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: 'dd / mm / yyyy',
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 2
    });
}



function clickIndice(e, pageNumber, tam) {
    if (e != null) {
        e.preventDefault();
    }
    var tam = (tam != null) ? tam : $('#tituloTabla').getPageSize();
    var columna = $('#tablaResultados .activa').attr('value');
    var orden = $('#tablaResultados .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

$(document).on('click', '#tablaResultados thead tr th[value]', function(e) {

    $('#tablaResultados th').removeClass('activa');
    if ($(e.currentTarget).children('i').hasClass('fa-sort')) {
        $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado', 'desc');
    } else {
        if ($(e.currentTarget).children('i').hasClass('fa-sort-down')) {
            $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado', 'asc');
        } else {
            $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado', '');
        }
    }
    $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado', '');
    clickIndice(e, $('#herramientasPaginacion').getCurrentPage(), $('#herramientasPaginacion').getPageSize());
});

function dateToString(date) {
    var mm = date.getMonth() + 1;
    var dd = date.getDate();

    return [date.getFullYear(),
        (mm > 9 ? '' : '0') + mm,
        (dd > 9 ? '' : '0') + dd
    ].join('-');
}

//Modal para pedir máquina
$(document).on('click', '.pedido', function(e) {
    $('#modalPedido #frmPedido').trigger('reset');
    $('#modalPedido #id_maquina').val($(this).val());

    var id_maquina = $('#modalPedido #id_maquina').val();

    habilitarDTPPedido();

    $.get('estadisticas_relevamientos/obtenerFechasMtmAPedido/' + id_maquina, function(data) {
        $('#nro_admin_pedido').val(data.maquina.nro_admin).attr('data-maquina', data.maquina.id_maquina).prop('readonly', true);
        $('#casino_pedido').val(data.casino.nombre).attr('data-casino', data.casino.id_casino).prop('readonly', true);

        $('#modalPedido #fechasPedido tbody tr').remove();

        if (data.fechas.length > 0) {
            for (var i = 0; i < data.fechas.length; i++) {
                var fila = $('<tr>').append($('<td>').text(data.fechas[i].fecha));
                $('#modalPedido #fechasPedido tbody').append(fila);
                $('#modalPedido #fechasPedido').show();
            }
        } else {
            $('#modalPedido #fechasPedido').hide();
        }

        $('#modalPedido').modal('show');
    });

});

//Ver más
$(document).on('click', '.detalle', function(e) {
    $('#modalDetalle').modal('show');
});

//Pedir máquina
$('#btn-pedido').click(function(e) {

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

    e.preventDefault();

    var formData = {
        nro_admin: $('#nro_admin_pedido').val(),
        casino: $('#casino_pedido').attr('data-casino'),
        fecha_inicio: $('#B_fecha_inicio_m').val(),
        fecha_fin: $('#B_fecha_fin_m').val(),
    }

    $.ajax({
        type: 'POST',
        url: 'estadisticas_relevamientos/guardarMtmAPedido',
        data: formData,
        dataType: 'json',
        success: function(data) {
            console.log('Pedido: ', data);
            let mañana = new Date();
            const hoy = new Date();
            mañana.setDate(mañana.getDate() + 1);
            mañana.setHours(0, 0, 0, 0);
            const fecha_retorno = new Date(data.fecha.split('-').join('/'));

            console.log('Hoy: ', hoy, 'Mañana: ', mañana, 'Devuelta: ', fecha_retorno);
            for (var f = mañana; f <= fecha_retorno; f.setDate(f.getDate() + 1)) {
                var fila = $('<tr>').append($('<td>').text(dateToString(f)));
                console.log('Agregando ' + f.toString());
                $('#modalPedido #fechasPedido tbody').append(fila);
                $('#modalPedido #fechasPedido').show();
            }
        },
        error: function(data) {
            var response = JSON.parse(data.responseText);

            if (typeof response.fecha_inicio !== 'undefined') {
                $('#B_fecha_inicio_m').popover('show');
                $('.popover').addClass('popAlerta');
            }

        },
    });
});