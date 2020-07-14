$(document).ready(function(){
  $('#dtpFechaAutoexclusionD').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaAutoexclusionH').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaVencimientoD').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaVencimientoH').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaFinalizacionD').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#dtpFechaFinalizacionH').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  });

  $('#barraMenu').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Autoexclusiones finalizadas');

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
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaAutoexcluidosFinalizados .activa').attr('value'), orden: $('#tablaAutoexcluidosFinalizados .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaAutoexcluidosFinalizados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        casino: $('#buscadorCasino').val(),
        fecha_autoexclusion_desde: $('#buscadorFechaAutoexclusionD').val(),
        fecha_autoexclusion_hasta: $('#buscadorFechaAutoexclusionH').val(),
        fecha_vencimiento_desde: $('#buscadorFechaVencimientoD').val(),
        fecha_vencimiento_hasta: $('#buscadorFechaVencimientoH').val(),
        fecha_finalizacion_desde: $('#buscadorFechaFinalizacionD').val(),
        fecha_finalizacion_hasta: $('#buscadorFechaFinalizacionH').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/autoexclusionesFinalizadas/buscarAutoexcluidos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaAutoexcluidosFinalizados tbody').append(generarFilaTabla(resultados.data[i]));
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
$(document).on('click', '#tablaAutoexcluidosFinalizados thead tr th[value]', function(e) {
    $('#tablaAutoexcluidosFinalizados th').removeClass('activa');
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
    $('#tablaAutoexcluidosFinalizados th:not(.activa) i')
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
    var columna = $('#tablaAutoexcluidosFinalizados .activa').attr('value');
    var orden = $('#tablaAutoexcluidosFinalizados .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(unAutoexcluido) {
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.dni').text(unAutoexcluido.nro_dni);
    fila.find('.apellido').text(unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres);
    fila.find('.casino').text(unAutoexcluido.cas);
    fila.find('.fecha_ae').text(unAutoexcluido.fecha_ae);
    fila.find('.fecha_vencimiento_primer_periodo').text(unAutoexcluido.fecha_vencimiento);
    fila.find('.fecha_finalizacion').text(unAutoexcluido.fecha_revocacion_ae);
    fila.find('button').each(function(idx, c) { $(c).val(unAutoexcluido.id_autoexcluido); });
    let ver_solicitud_finalizacion = fila.find('#btnVerSolicitudFinalizacion').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'VER SOLICITUD DE FINALIZACIÓN', 'data-delay': '{"show":"300", "hide":"100"}' });

    fila.css('display', 'flow-root');

    return fila;
}

//Boton mostrar solicitud de finalizacion
$(document).on('click', '#btnVerSolicitudFinalizacion', function(e) {
  let id_ae = $(this).val();
  window.open('autoexclusionesFinalizadas/verSolicitudFinalizacion/' + id_ae, '_blank');
});
