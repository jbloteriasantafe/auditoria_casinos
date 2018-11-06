$(document).ready(function() {


  $('#barraMesas').attr('aria-expanded','true');
  $('#mesasPanio').removeClass();
  $('#mesasPanio').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar Juegos');
  $('#opcJuegos').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcJuegos').addClass('opcionesSeleccionado');

  $('#collapseFiltros #FiltroJuego').val("0");
  $('#collapseFiltros #FiltroCasino').val("0");
  $('#collapseFiltros #FIltroMesa').val(" ");

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $('#FiltroMesa').val("");
  $('#FiltroNombre').val("");
  $('#FiltroCasino').val(0);
  $('#FiltroTipo').val(0);

}); //fin document ready

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//presiona el bton de búsqueda
$('#btn-buscarJuegos').click(function(e){

  e.preventDefault();

  $('#cuerpoTablaJuegos tr').remove();

  //var fila = $(document.createElement('tr'));

  $('#tituloBusquedaJuegos').text('JUEGOS ENCONTRADOS');
        var formData= {
          id_mesa: $('#FiltroMesa').val(),
          nombre_juego:$('#FiltroNombre').val(),
          id_casino: $('#FiltroCasino').val(),
          id_tipo_mesa: $('#FiltroTipo').val()
        }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'juegos/buscarJuegos',
            data: formData,
            dataType: 'json',

            success: function (data){
            //  $('#tablaResultados tbody tr').remove();

              for (var i = 0; i < data.length; i++) {
                console.log('data',data.lenght);
                  var fila =  generarFilaJuegos(data[i]);
                  $('#cuerpoTablaJuegos').append(fila);
              }
              console.log('juego', data.lenght);

            },
            error: function(data){
            },
        });

})


//presiona el botón de nuevo juego
$('#btn-nuevo-juego').on('click', function(e){

  e.preventDefault();
  ocultarErrorValidacion($('#nombre_juego'));
  ocultarErrorValidacion($('#siglas_juego'));
  ocultarErrorValidacion($('#casino_juego'));
  ocultarErrorValidacion($('#tipo_mesa_juego'));



  $('#nombre_juego').val(" ");
  $('#siglas_juego').val(" ");
  $('#casino_juego').val("0");
  $('#tipo_mesa_juego').val("0");

  $('#modalAltaJuego').modal('show');

})

//presiona el btn guardar dentro del modal de alta de juego
$('#btn-guardar-juego').on('click', function(e){

  e.preventDefault();

  $('#mensajeExito').hide();
  var id_casino= $('#casino_mesa').val();

  var formData = {
    nombre_juego: $('#nombre_juego').val(),
    siglas: $('#siglas_juego').val(),
    id_tipo_mesa: $('#tipo_mesa_juego').val(),
    id_casino: $('#casino_juego').val(),
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'juegos/nuevoJuego/',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#mensajeErrorAlta').hide();
      $('#modalAltaJuego').modal('hide');
      $('#mensajeExito h3').text('ÉXITO DE CREACIÓN');
      $('#mensajeExito p').text('La mesa fue creada correctamente');
      $('#mensajeExito').show();
      $('#btn-buscarJuegos').trigger('click');

    },
    error:function(data){

      var response = data.responseJSON.errors;

      if(typeof response.nombre_juego !== 'undefined'){
        mostrarErrorValidacion($('#nombre_juego'),response.nombre_juego[0],false);
      }
      if(typeof response.siglas !== 'undefined'){
        mostrarErrorValidacion($('#siglas_juego'),response.siglas[0],false);
      }
      if(typeof response.id_casino !== 'undefined'){
        mostrarErrorValidacion($('#casino_juego'),response.casino[0],false);
      }
      if(typeof response.id_tipo_mesa !== 'undefined'){
        mostrarErrorValidacion($('#tipo_mesa_juego'),response.tipo_mesa[0],false);
      }
    }
  })
})

//ver detalles del juego
$(document).on('click','.modificarJuego',function(e){

  e.preventDefault();

  $('#mesasAsignadas tr').not('#moldeMod').remove();


  var id_juego= $(this).val();
  $('#btn-modificar-juego').val(id_juego);

  $.get('juegos/obtenerJuegoMesa/'+ id_juego, function(data){
    console.log(data);

    $('#modif_nom').val(data.juego.nombre_juego);
    $('#modif_siglas').val(data.juego.siglas);
    $('#modif_tipo').val(data.tipo_mesa.descripcion);
    $('#modif_cas').val(data.casino.nombre);


    for (var i = 0; i < data.mesas.length; i++) {

      var fila=$(document.createElement('tr'));

      fila.attr('id',data.id_juego_mesa)
          .append($('<td>')
          .text(data.mesas[i].nro_mesa).css('tect-align','center'))
          .append($('<td>')
          .text(data.mesas[i].descripcion).css('tect-align','center'))

      $('#mesasAsignadas').append(fila);
    }

  })
  $('#modalModificarJuego').modal('show');


});

//presiona el btn guardar dentro del modal MODIFICAR JUEGO
$('#btn-modificar-juego').on('click', function(e){

  e.preventDefault();

  $('#mensajeExito').hide();

  var formData = {
    id_juego_mesa: $(this).val(),
    nombre_juego: $('#modif_nom').val(),
    siglas: $('#modif_siglas').val(),

  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'juegos/modificarJuego',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#modalModificarJuego').modal('hide');
      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('La mesa fue modificada correctamente');
      $('#mensajeExito').show();
      $('#btn-buscarJuegos').trigger('click');

    },
    error:function(data){

      var response = data.responseJSON;

      if(typeof response.nombre_juego !== 'undefined'){
        mostrarErrorValidacion($('#modif_nom'),response.nombre_juego[0],false);
      }
      if(typeof response.siglas !== 'undefined'){
        mostrarErrorValidacion($('#modif_siglas'),response.siglas[0],false);
      }
    }
  })
})


//ELIMINAR Juego
$(document).on('click','.eliminarJuego',function(e){
  e.preventDefault();

  $('#mensajeExito').hide();
  var id_juego= $(this).val();
  $('#btn-eliminar-juego').val(id_juego);

  $.get('juegos/obtenerJuegoMesa/'+ id_juego, function(data){

    for (var i = 0; i < data.mesas.length; i++) {
      $('#eliminarJuego').text('Este juego esta asociado a las siguientes mesas: ' + data.mesas[i].nro_mesa + '- ');
    }
    $('#modalAlertaEliminar').modal('show');

  });
});


$('#btn-eliminar-juego').on('click', function(e){

  e.preventDefault();

  var id= $(this).val();
  $.get('juegos/bajaJuego/' + id  , function(data){

    $('#modAlertaEliminar').modal('hide');

    $('#mensajeExito h3').text('ÉXITO');
    $('#mensajeExito p').text('JUEGO ELIMINADO');
    $('#mensajeExito').show();

   $('#btn-buscarJuegos').trigger('click');

  })

});

//filas iniciales
function generarFilaJuegos(data){

  var fila=$(document.createElement('tr'));

  fila.attr('id',data.id_juego_mesa)
      .append($('<td>')
      .addClass('col-xs-3').addClass('f_nombre')
      .text(data.nombre_juego).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3').addClass('f_siglas')
      .text(data.siglas).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3').addClass('f_casino')
      .text(data.id_casino).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3')
      .append($('<span>').text(' '))
      .append($('<button>').addClass('btn btn-warning')
          .addClass('modificarJuego').val(data.id_juego_mesa)
              .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-pencil-alt'))).css('text-align','center')
              .append($('<button>').addClass('btn btn-danger')
              .addClass('eliminarJuego').val(data.id_juego_mesa)
                  .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-trash'))).css('text-align','center'))


  return fila;

}
