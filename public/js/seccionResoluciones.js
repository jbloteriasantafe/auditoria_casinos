//Resaltar la sección en el menú del costado
$(document).ready(function() {
  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Resoluciones');
  $('#opcResoluciones').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcResoluciones').addClass('opcionesSeleccionado');

  $('#tablaResoluciones').tablesorter({});

  //Quitar eventos de la tecla Enter y guardar
  $('#collapseFiltros').on('keypress',function(e){
      if(e.which == 13) {
        e.preventDefault();
        $('#buscarResolucion').click();
      }
  });

  $('#liExpedientes .setNavIcono').addClass('iconoAzul');
  $('#buscarResolucion').click();
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('.modal-title').text('| RESOLUCIONES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

$('#buscarResolucion').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
     headers: {
         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
     }
 })

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResoluciones .activa').attr('value'),orden: $('#tablaResoluciones .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResoluciones th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData={
    nro_resolucion: $('#nro_resolucion').val(),
    nro_resolucion_anio:$('#nro_resolucion_anio').val(),
    nro_exp_org:$('#nro_exp_org').val(),
    nro_exp_interno:$('#nro_exp_interno').val(),
    nro_exp_control:$('#nro_exp_control').val(),
    casino:$('#sel1').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }


 $.ajax({
     type: "post",
     url: "resoluciones/buscar",
     dataType: 'json',
     data: formData,
     success: function (data) {
       $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.resultados.total,clickIndice);
       $('tbody').empty();
       for (var i = 0; i < data.resultados.data.length; i++) {
         $('tbody')
            .append($('<tr>')
                .append($('<td>')
                  .text(data.resultados.data[i].nro_exp_org + '-' + data.resultados.data[i].nro_exp_interno + '-' + data.resultados.data[i].nro_exp_control)
                  .addClass('col-xs-4')
                )
                .append($('<td>')
                  .text(data.resultados.data[i].nombre)
                  .addClass('col-xs-4')
                )
                .append($('<td>')
                  .text(data.resultados.data[i].nro_resolucion+'-'+data.resultados.data[i].nro_resolucion_anio)
                  .addClass('col-xs-4')
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
  var columna = $('#tablaResoluciones .activa').attr('value');
  var orden = $('#tablaResoluciones .activa').attr('estado');
  $('#buscarResolucion').trigger('click',[pageNumber,tam,columna,orden]);
}