//Resaltar la sección en el menú del costado
$(document).ready(function() {
  $('#barraExpedientes').attr('aria-expanded','true');
  $('#expedientes').removeClass();
  $('#expedientes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Notas');
  $('#opcNotas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcNotas').addClass('opcionesSeleccionado');

  //Quitar eventos de la tecla Enter y guardar
  $('#collapseFiltros').on('keypress',function(e){
      if(e.which == 13) {
        e.preventDefault();
        $('#buscarNota').click();
      }
  });

  $('#liExpedientes .setNavIcono').addClass('iconoAzul');
  $('#buscarNota').click();
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('#modalAyuda .modal-title').text('| NOTAS');
  $('#modalAyuda .modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

$('#buscarNota').click(function(e,pagina,page_size,columna,orden){
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
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaNotas .activa').attr('value'),orden: $('#tablaNotas .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaNotas th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData={
    identificacion: $('#B_nota').val(),
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
     url: "notas/buscar",
     dataType: 'json',
     data: formData,
     success: function (data) {
       $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.resultados.total,clickIndice);
       $('#tablaNotas tbody > tr').remove();
       const d = data.resultados.data;
       for (var i = 0; i < d.length; i++) {
         let tipo__mov_nota = ' ';
         if( d[i].tipo_movimiento != null){
           tipo__mov_nota = d[i].tipo_movimiento;
         }
         const tr = $('<tr>');
         tr.append($('<td>').addClass('col-xs-2').addClass('identificacion').text(d[i].identificacion));
         tr.append($('<td>').addClass('col-xs-2').addClass('nro_exp').text(d[i].nro_exp_org + '-' + d[i].nro_exp_interno + '-' + d[i].nro_exp_control));
         tr.append($('<td>').addClass('col-xs-2').addClass('tipo_movimiento').text(tipo__mov_nota));
         tr.append($('<td>').addClass('col-xs-4').addClass('casino').text(d[i].nombre));
         tr.append($('<td>').addClass('col-xs-2').addClass('accion')
         .append($('<button>')
             .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt'))
             .addClass('btn').addClass('btn-danger').addClass('eliminar')
             .attr('value',d[i].id_nota)));
         $('#tablaNotas tbody').append(tr);
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
  var columna = $('#tablaNotas .activa').attr('value');
  var orden = $('#tablaNotas .activa').attr('estado');
  $('#buscarNota').trigger('click',[pageNumber,tam,columna,orden]);
}

$(document).on('click','.eliminar',function(){
  $('#alerta_de_eliminacion').hide();
  $('#alerta_de_movimiento').hide();
  $('#btn-eliminarModal').hide();

  var id_expediente = $(this).val();
  $('#btn-eliminarModal').val(id_expediente);
  $('#alerta_de_eliminacion').show();
  $('#btn-eliminarModal').show();
  $('#identificacion_nota_eliminar').val($(this).parent().parent().find('.identificacion').text()).attr('disabled','disabled');
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })
    var id_expediente = $(this).val();

    $.ajax({
        type: "DELETE",
        url: "notas/eliminar-nota/" + id_expediente,
        success: function (data) {
          //Remueve de la tabla
          $('#buscarNota').click();
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
    $('#identificacion_nota_eliminar').val('');
});