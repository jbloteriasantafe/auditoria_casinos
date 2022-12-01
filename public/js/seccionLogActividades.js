$(document).ready(function() {
  $('.tituloSeccionPantalla').text('Log de Actividades');
  $('#dtpFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
  $('#btn-buscar').trigger('click');
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const minimizar = !!$(this).data("minimizar");
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data("minimizar",!minimizar);
});

//Quitar eventos de la tecla Enter
$('#collapseFiltros input').on('keypress',function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-buscar').click();
  }
});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  let size   = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }
  page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'GET',
    url: 'logActividades/buscarLogActividades',
    data: {
      usuario: $('#B_usuario').val(),
      tabla:   $('#B_tabla').val(),
      accion:  $('#B_accion').val(),
      fecha:   $('#B_fecha').val().split(" / ").reverse().join('-'),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function(resultados){
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTabla tr').remove();
      resultados.data.forEach(function(l){
        $('#cuerpoTabla').append(generarFilaTablaResultados(l));
      });
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function(data){
      console.log('Error:', data);
    }
  });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  const i = $(this).children('i');
  $('#tablaResultados th i').not(i).removeClass()
  .addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  const sin_ordenar = i.hasClass('fa-sort');
  const ordenado_abajo = i.hasClass('fa-sort-down');
  if(sin_ordenar){
    i.removeClass('fa-sort').addClass('fa-sort-down')
    .parent().addClass('activa').attr('estado','desc');
  }
  else if(ordenado_abajo){
    i.removeClass('fa-sort-down').addClass('fa-sort-up')
    .parent().addClass('activa').attr('estado','asc');
  }
  else{
    i.removeClass('fa-sort-up').addClass('fa-sort')
    .parent().removeClass('activa').attr('estado','');
  }
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTablaResultados(log){
  const fila = $('#moldeLog').clone().removeAttr('id');
  fila.find('.usuario').text(log.nombre);
  fila.find('.fecha').text(log.fecha);
  fila.find('.accion').text(log.accion);
  fila.find('.tabla').text(log.tabla);
  fila.find('.id_entidad').text(log.id_entidad);
  fila.find('button').val(log.id_log);
  return fila;
}

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
  $('#frmLog').trigger('reset');
  $.get("logActividades/obtenerLogActividad/" + $(this).val(), function(data){
    $('#fecha').val(data.log.fecha);
    $('#usuario').val(data.usuario);
    $('#accion').val(data.log.accion);
    $('#tabla').val(data.log.tabla);
    $('#id_entidad').val(data.log.id_entidad);
    
    $('#tablaDetalleLog tbody tr').remove();
    data.detalles.forEach(function(d){
      const f = $('#moldeCampoValor').clone().removeAttr('id');
      f.find('.campo').text(d.campo);
      f.find('.valor').text(d.valor);
      $('#tablaDetalleLog tbody').append(f);
    });
    $('#tablaDetalleLog').toggle(data.detalles.length > 0);
    
    $('#modalLogActividad').modal('show');
  });
});
