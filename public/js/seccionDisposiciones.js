//Resaltar la sección en el menú del costado
$(document).ready(function() {

  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Disposiciones');
  $('#opcDisposiciones').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcDisposiciones').addClass('opcionesSeleccionado');

  $('#tablaDisposiciones').tablesorter({});

  //Quitar eventos de la tecla Enter y guardar
  $('#collapseFiltros').on('keypress',function(e){
      if(e.which == 13) {
        e.preventDefault();
        $('#btn-buscarDisposiciones').click();
      }
  });
  $('#collapseFiltros #nro_exp_org').val("");
  $('#collapseFiltros #nro_exp_interno').val("");
  $('#collapseFiltros #nro_exp_control').val("");
  $('#collapseFiltros #sel1').val("0");
  $('#collapseFiltros #nro_disposicion').val("");
  $('#collapseFiltros #nro_disposicion_anio').val("");
  $('#buscarDisposiciones').click();
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('#modalAyuda .modal-title').text('| DISPOSICIONES');
  $('#modalAyuda .modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

$('#buscarDisposiciones').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });
  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaDisposiciones .activa').attr('value'),orden: $('#tablaDisposiciones .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaDisposiciones th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData={
    nro_disposicion: $('#nro_disposicion').val(),
    nro_disposicion_anio:$('#nro_disposicion_anio').val(),
    nro_exp_org:$('#nro_exp_org').val(),
    nro_exp_interno:$('#nro_exp_interno').val(),
    nro_exp_control:$('#nro_exp_control').val(),
    casino: $('#sel1').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
     type: "post",
     url: "disposiciones/buscar",
     dataType: 'json',
     data: formData,
     success: function (data) {
       $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.resultados.total,clickIndice);
       $('#tablaDisposiciones tbody').empty();
       const d =  data.resultados.data;
       for (var i = 0; i < d.length; i++) {
         $('#tablaDisposiciones tbody')
            .append($('<tr>')
                .append($('<td>').addClass('col-xs-4')
                  .text(d[i].nro_exp_org + '-' + d[i].nro_exp_interno + '-' + d[i].nro_exp_control)
                )
                .append($('<td>').addClass('col-xs-4')
                  .text(d[i].nombre)
                )
                .append($('<td>').addClass('col-xs-4')
                  .text(d[i].nro_disposicion+'-'+d[i].nro_disposicion_anio)
              )
          )
       }
       $('#herramientasPaginacion').generarIndices(page_number,page_size,data.resultados.total,clickIndice);
     },
     error: function (data) {
       console.log('Error: ', data);
     }
 });
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaDisposiciones .activa').attr('value');
  var orden = $('#tablaDisposiciones .activa').attr('estado');
  $('#buscarDisposiciones').trigger('click',[pageNumber,tam,columna,orden]);
}