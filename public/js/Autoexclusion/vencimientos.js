$(document).ready(function(){

  $('#dtpFechaFinalizacionAE').datetimepicker({
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
  $('.tituloSeccionPantalla').text('Vencimientos');

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
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaAutoexcluidosRevocables .activa').attr('value'), orden: $('#tablaAutoexcluidos .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaAutoexcluidosRevocables th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    var formData = {
        apellido: $('#buscadorApellido').val(),
        dni: $('#buscadorDni').val(),
        casino: $('#buscadorCasino').val(),
        fecha_autoexclusion: $('#buscadorFechaAutoexclusion').val(),
        fecha_vencimiento: $('#buscadorFechaVencimiento').val(),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/vencimientos/buscarAutoexcluidos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaAutoexcluidosRevocables tbody').append(generarFilaTabla(resultados.data[i]));
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
$(document).on('click', '#tablaAutoexcluidosRevocables thead tr th[value]', function(e) {
    $('#tablaAutoexcluidosRevocables th').removeClass('activa');
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
    $('#tablaAutoexcluidosRevocables th:not(.activa) i')
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
    var columna = $('#tablaAutoexcluidosRevocables .activa').attr('value');
    var orden = $('#tablaAutoexcluidosRevocables .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(unAutoexcluido) {
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.dni').text(unAutoexcluido.nro_dni);
    fila.find('.apellido').text(unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres);
    fila.find('.fecha_ae').text(unAutoexcluido.fecha_ae);
    fila.find('.fecha_vencimiento_primer_periodo').text(unAutoexcluido.fecha_vencimiento);
    fila.find('button').each(function(idx, c) { $(c).val(unAutoexcluido.id_autoexcluido); });
    let imprimir_form = fila.find('#btnImprimirFormularioFinalizacionAE').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'IMPRIMIR FORMULARIO DE FINALIZACIÓN', 'data-delay': '{"show":"300", "hide":"100"}' });
    let finalizar_ae = fila.find('#btnFinalizarAE').attr({ 'data-toggle': 'tooltip', 'data-placement': 'top', 'title': 'FINALIZAR AE', 'data-delay': '{"show":"300", "hide":"100"}' });

    fila.css('display', 'flow-root');

    return fila;
}

//Boton acción imprimir formulario finalizacion
$(document).on('click', '#btnImprimirFormularioFinalizacionAE', function(e){
  e.preventDefault();
  let id_autoexcluido = $(this).val();

  window.open('vencimientos/imprimirFormularioFinalizacion/' + id_autoexcluido, '_blank');
});

//Botón acción finalizar AE
$(document).on('click', '#btnFinalizarAE', function(e){
  e.preventDefault();
  $('#btn-finalizar-ae').val($(this).val());

  //título y color header
  $('.modal-title').text('| FINALIZAR AUTOEXCLUSIÓN');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  //muestra modal
  $('#modalFinalizarAE').modal('show');

});

//botón finalizar ae
$('#btn-finalizar-ae').click(function (e) {
  var formData = new FormData();
  formData.append('formulario_finalizacion', $('#formularioFinalizacionAE')[0].files[0]);
  formData.append('fecha_finalizacion', $('#buscadorFechaFinalizacionAE').val());
  formData.append('id_ae', $(this).val());

  //hago un ajax call aparte para la subida de archivos porque sino no puedo setear
  //el contentType y processData en false (si lo hago, llegan nulos al backend los datos personales, de contacto, etc)
  $.ajax({
      type: "POST",
      url: 'vencimientos/finalizarAutoexclusion',
      data: formData,
      dataType: 'json',
      contentType: false,
      processData: false,
      cache: false,
      success: function (data) {
          console.log(data);
          $('#mensajeExito P').text('La autoexclusión fue REVOCADA correctamente.');
          $('#mensajeExito div').css('background-color','#4DB6AC');

          $('#modalFinalizarAE').modal('hide');
          $('#btn-buscar').trigger('click'); //hago un trigger al boton buscar asi actualiza la tabla sin recargar la pagina
          $('#mensajeExito').show(); //mostrar éxito
      },
      error: function (data) {
        var response = JSON.parse(data.responseText);
      }
  });
});
