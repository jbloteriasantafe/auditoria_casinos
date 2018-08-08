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

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| DISPOSICIONES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Casino
$('#buscarDisposiciones').click(function(){
  var formData={
    nro_disposicion: $('#nro_disposicion').val(),
    nro_disposicion_anio:$('#nro_disposicion_anio').val(),
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
     url: "disposiciones/buscar",
     dataType: 'json',
     data: formData,
     success: function (data) {
       console.log(data);
       //Remueve de la tabla
       $('tbody').empty();
       //Remueve de la tabla
       $('tbody').empty();
       var cantidad = data.resultados.length;
       switch(cantidad){
         case 0:
           var titulo = "No se encontraron expedientes";
           break;
         case 1:
           var titulo = "Se encontró 1 Expediente";
           break;
         default:
           var titulo = "Se encontraron " + cantidad + " Expedientes";
       }
       $('#tituloDisposiciones').text(titulo);
       for (var i = 0; i < data.resultados.length; i++) {
         $('tbody')
            .append($('<tr>')
                .append($('<td>')
                  .text(data.resultados[i].nro_exp_org + '-' + data.resultados[i].nro_exp_interno + '-' + data.resultados[i].nro_exp_control)
                )
                .append($('<td>')
                  .text(data.resultados[i].nombre)
                )
                .append($('<td>')
                  .text(data.resultados[i].nro_disposicion+'-'+data.resultados[i].nro_disposicion_anio)

              )
          )
       }

       $("#tablaDisposiciones").trigger("update");
       $("#tablaDisposiciones th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

     },
     error: function (data) {
       console.log('Error: ', data);
     }
 });
});
