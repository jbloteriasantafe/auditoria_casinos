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
        $('#btn-buscarNota').click();
      }
  });

  $('#liExpedientes .setNavIcono').addClass('iconoAzul');


});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| RESOLUCIONES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Casino
$('#buscarNota').click(function(){
  var formData={
    identificacion: $('#B_nota').val(),
    nro_resolucion_anio:$('#nro_resolucion_anio').val(),
    nro_exp_org:$('#nro_exp_org').val(),
    nro_exp_interno:$('#nro_exp_interno').val(),
    nro_exp_control:$('#nro_exp_control').val(),
    casino:$('#sel1').val(),
  }

  $.ajaxSetup({
     headers: {
         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
     }
 })

 $.ajax({
     type: "post",
     url: "notas/buscar",
     dataType: 'json',
     data: formData,
     success: function (data) {
       console.log(data);
       //Remueve de la tabla
       $('tbody').empty();
       var cantidad = data.resultados.length;
       switch(cantidad){
         case 0:
           var titulo = "No se encontraron notas";
           break;
         case 1:
           var titulo = "Se encontró 1 Nota";
           break;
         default:
           var titulo = "Se encontraron " + cantidad + " Notas";
       }
       var tipo__mov_nota = ' ' ;
       $('#tituloNotas').text(titulo);
       $('#tablaNotas tbody > tr').remove();
       for (var i = 0; i < data.resultados.length; i++) {
         if( data.resultados[i].descripcion != null){
           tipo__mov_nota = data.resultados[i].descripcion ;
         }else{
           tipo__mov_nota = ' ' ;
         }
         $('tbody')
            .append($('<tr>')
                      .append($('<td>')
                              .addClass('col-xs-2')
                              .text(data.resultados[i].identificacion))
                              .append($('<td>')
                              .addClass('col-xs-2')
                                .text(data.resultados[i].nro_exp_org + '-' + data.resultados[i].nro_exp_interno + '-' + data.resultados[i].nro_exp_control))

                                  .append($('<td>')
                                  .addClass('col-xs-2')
                                  .text(tipo__mov_nota))

                              .append($('<td>')
                              .addClass('col-xs-2')
                                .text(data.resultados[i].nombre)) //casino
                                .append($('<td>')
                                .addClass('col-xs-2')
                                // .append($('<button>')
                                //     .append($('<i>')
                                //         .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                                //     )
                                //     .addClass('btn').addClass('btn-warning').addClass('modificar')
                                //     .attr('value',data.resultados[i].id_nota)
                                // )
                                .append($('<button>')
                                    .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                                    )
                                    .addClass('btn').addClass('btn-danger').addClass('eliminar')
                                    .attr('value',data.resultados[i].id_nota)
                                )
                              )
                    )
       }

       $("#tablaNotas").trigger("update");
       $("#tablaNotas th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

     },
     error: function (data) {
       console.log('Error: ', data);
     }
 });
});

$(document).on('click','.eliminar',function(){
  $('#alerta_de_eliminacion').hide();
  $('#alerta_de_movimiento').hide();
  $('#btn-eliminarModal').hide();
    //Cambiar colores modal
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').removeAttr('style');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

    var id_expediente = $(this).val();
    $('#btn-eliminarModal').val(id_expediente);

    $.get('notas/consulta-nota/' + id_expediente, function(data) {
      if(data.eliminable == 1){
        $('#alerta_de_eliminacion').show();
        $('#btn-eliminarModal').show();
      }else{
        $('#alerta_de_movimiento').show();
      }
      $('#identificacion_nota_eliminar').val(data.nota.identificacion).attr('disabled','disabled');
    });
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
          $('#' + id_expediente).remove();
          $("#tablaNotas").trigger("update");
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
    $('#identificacion_nota_eliminar').val('');
});
