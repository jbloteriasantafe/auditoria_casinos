$(document).ready(function(){

  const iso_dtp = {
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd/mm/yy',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true
  };

  $('#dtpFechaAutoexclusionD').datetimepicker(iso_dtp);
  $('#dtpFechaAutoexclusionH').datetimepicker(iso_dtp);
  $('#dtpFechaVencimientoD').datetimepicker(iso_dtp);
  $('#dtpFechaVencimientoH').datetimepicker(iso_dtp);
  $('#dtpFechaRevocacionD').datetimepicker(iso_dtp);
  $('#dtpFechaRevocacionH').datetimepicker(iso_dtp);
  $('#dtpFechaCierreD').datetimepicker(iso_dtp);
  $('#dtpFechaCierreH').datetimepicker(iso_dtp);

  $('#barraMenu').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Informes de Autoexcluidos');

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
    var sort_by = (columna != null) ? { columna, orden } : { columna: $('#tablaInformesAE .activa').attr('value'), orden: $('#tablaInformesAE .activa').attr('estado') };
    if (sort_by == null) { // limpio las columnas
        $('#tablaInformesAE th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado', '');
    }

    const iso = function(dtp){
      //getDate me retorna hoy si esta vacio, lo tengo que verificar
      if(dtp.find('input').val().length == 0) return "";
      const date = dtp.data("datetimepicker").getDate();
      const y = date.getFullYear();
      const m = date.getMonth()+1;
      const d = date.getDate();
      return y + (m<10?'-0':'-') + m + (d<10?'-0':'-') + d;
    }

    var formData = {
        casino:    $('#buscadorCasino').val(),
        estado:    $('#buscadorEstado').val(),
        apellido:  $('#buscadorApellido').val(),
        dni:       $('#buscadorDni').val(),
        sexo:      $('#buscadorSexo').val(),
        localidad: $('#buscadorLocalidad').val(),
        provincia: $('#buscadorProvincia').val(),
        fecha_autoexclusion_desde: iso($('#dtpFechaAutoexclusionD')),
        fecha_autoexclusion_hasta: iso($('#dtpFechaAutoexclusionH')),
        fecha_vencimiento_desde:   iso($('#dtpFechaVencimientoD')),
        fecha_vencimiento_hasta:   iso($('#dtpFechaVencimientoH')),
        fecha_revocacion_desde:    iso($('#dtpFechaRevocacionD')),
        fecha_revocacion_hasta:    iso($('#dtpFechaRevocacionH')),
        fecha_cierre_desde:        iso($('#dtpFechaCierreD')),
        fecha_cierre_hasta:        iso($('#dtpFechaCierreH')),
        page: page_number,
        sort_by: sort_by,
        page_size: page_size,
    }

    $.ajax({
        type: 'GET',
        url: 'http://' + window.location.host + '/informesAutoexcluidos/buscarAutoexcluidos',
        data: formData,
        dataType: 'json',
        success: function(resultados) {
            $('#herramientasPaginacion')
                .generarTitulo(page_number, page_size, resultados.total, clickIndice);

            $('#cuerpoTabla tr').not('.filaTabla').remove();

            for (var i = 0; i < resultados.data.length; i++) {
                $('#tablaInformesAE tbody').append(generarFilaTabla(resultados.data[i]));
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
$(document).on('click', '#tablaInformesAE thead tr th[value]', function(e) {
    $('#tablaInformesAE th').removeClass('activa');
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
    $('#tablaInformesAE th:not(.activa) i')
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
    var columna = $('#tablaInformesAE .activa').attr('value');
    var orden = $('#tablaInformesAE .activa').attr('estado');
    $('#btn-buscar').trigger('click', [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(unAutoexcluido) {
    const convertir_fecha = function(fecha){
      if(fecha === null || fecha.length == 0) return '-';
      yyyymmdd = fecha.split('-');
      return yyyymmdd[2] + '/' + yyyymmdd[1] + '/' + yyyymmdd[0].substring(2);
    }
    let fila = $('#cuerpoTabla .filaTabla').clone().removeClass('filaTabla').show();
    fila.attr('data-id', unAutoexcluido.id_autoexcluido);
    fila.find('.casino').text(unAutoexcluido.casino);
    const estado = unAutoexcluido.estado + (unAutoexcluido.estado == unAutoexcluido.puede? ''  : (' ⤻ ' + unAutoexcluido.puede));
    fila.find('.estado').text(estado).attr('title',estado);
    fila.find('.apellido').text(unAutoexcluido.apellido).attr('title',unAutoexcluido.apellido);
    fila.find('.nombres').text(unAutoexcluido.nombres).attr('title',unAutoexcluido.nombres);
    fila.find('.dni .link').text(unAutoexcluido.nro_dni).attr('href','/autoexclusion/'+unAutoexcluido.nro_dni);
    fila.find('.dni .btnVerFoto').attr('href','/galeriaImagenesAutoexcluidos/'+unAutoexcluido.nro_dni);
  
    fila.find('.localidad').text(unAutoexcluido.nombre_localidad).attr('title',unAutoexcluido.nombre_localidad);
    fila.find('.provincia').text(unAutoexcluido.nombre_provincia).attr('title',unAutoexcluido.nombre_provincia);

    fila.find('.fecha_ae').text(convertir_fecha(unAutoexcluido.fecha_ae));
    fila.find('.fecha_vencimiento_primer_periodo').text(convertir_fecha(unAutoexcluido.fecha_vencimiento));
    fila.find('.fecha_finalizacion').text(convertir_fecha(unAutoexcluido.fecha_revocacion_ae));
    fila.find('.fecha_cierre_ae').text(convertir_fecha(unAutoexcluido.fecha_cierre_ae));

    fila.css('display', 'flow-root');
    return fila;
}

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});