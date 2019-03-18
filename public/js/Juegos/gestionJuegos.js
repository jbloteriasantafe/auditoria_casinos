$(document).ready(function(e) {

  $('#barraJuegosSectores').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').hide();
  $('#barraJuegosSectores').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraJuegosSectores').addClass('opcionesSeleccionado');
  //$('.tituloSeccionPantalla').text('Gestionar Juegos');
  var fila= $('#filaFichasClon').clone();

  $('#juegosSec').show();
  $('#juegosSec').css('display','inline-block');

  //$('#juegosSec').find('#b_juego').addClass('active');

  $('#btn-ayuda').hide();
  $('#opcJuegos').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcJuegos').addClass('opcionesSeleccionado');

  $('#s_descr').val("");
  $('#s_casino').val("0");
  $('#s_mesa').val("");
  $('#s_tipo').val("");

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  $('#FiltroMesa').val("");
  $('#FiltroNombre').val("");
  $('#FiltroCasino').val(0);
  $('#FiltroTipo').val(0);

//pestañas
  $(".tab_content").hide(); //Hide all content
  	$("ul.juegosSec li:first").addClass("active").show(); //Activate first tab
  	$(".tab_content:first").show(); //Show first tab content


}); //fin document ready

//PESTAÑAS
$("ul.juegosSec li").click(function() {

    $("ul.juegosSec li").removeClass("active"); //Remove any "active" class
    $(this).addClass("active"); //Add "active" class to selected tab
    $(".tab_content").hide(); //Hide all tab content

    var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to
                //identify the active tab + content
                console.log(activeTab);
    if(activeTab == '#pant_sectores'){
      $('#btn-buscarSectores').trigger('click');
    }
    $(activeTab).fadeIn(); //Fade in the active ID content
    return false;
});

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
            url: 'mesas-juegos/buscarJuegos',
            data: formData,
            dataType: 'json',

            success: function (data){
            //  $('#tablaResultados tbody tr').remove();
            if(data.length > 0){
                $('#tituloBusquedaJuegos').text('Se han encontrado ' + data.length + ' juegos');
            }
            else{
              $('#tituloBusquedaJuegos').text('No han encontrado resultados');

            }
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

$('#btn-buscarSectores').click(function(e){

  e.preventDefault();

  $('#cuerpoTablaSectores tr').remove();

  //var fila = $(document.createElement('tr'));

  var formData= {

    nro_mesa: $('#s_mesa').val(),
    id_tipo_mesa: '0',
    descripcion_sector: $('#s_descr').val(),
    casino: $('#s_casino').val()
  }

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  $.ajax({
      type: 'POST',
      url: 'sectores-mesas/buscarSectores',
      data: formData,
      dataType: 'json',

      success: function (data){
        $('#cuerpoTablaSectores tr').remove();

        if(data.sectores.length > 0){
          $('#tituloBusquedaSectores').text('Se han encontrado ' + data.sectores.length + ' sectores');
        }
        else{
          $('#tituloBusquedaSectores').text('No se han encontrado sectores');
        }

        for (var i = 0; i < data.sectores.length; i++) {
          console.log('data',data.sectores.length);
            var fila =  generarFilaSectores(data.sectores[i]);
            $('#cuerpoTablaSectores').append(fila);
        }
        console.log('juego', data.sectores.length);

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
  ocultarErrorValidacion($('#posicionesJuego'));

  $('#nombre_juego').val(" ");
  $('#siglas_juego').val(" ");
  $('#casino_juego').val("0");
  $('#tipo_mesa_juego').val("0");
  $('#posicionesJuego').val(" ");
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
    posiciones: $('#posicionesJuego').val()
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'mesas-juegos/nuevoJuego',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#mensajeErrorAlta').hide();
      $('#modalAltaJuego').modal('hide');
      $('#mensajeExito h3').text('ÉXITO DE CREACIÓN');
      $('#mensajeExito p').text('El juego fue creado correctamente');
      $('#mensajeExito').show();
      $('#btn-buscarJuegos').trigger('click');

    },
    error:function(data){

      var response = data.responseJSON.errors;
      console.log('data',response);

      if(typeof response.nombre_juego !== 'undefined'){
        mostrarErrorValidacion($('#nombre_juego'),response.nombre_juego[0],false);
      }
      if(typeof response.siglas !== 'undefined'){
        mostrarErrorValidacion($('#siglas_juego'),response.siglas[0],false);
      }
      if(typeof response.id_casino !== 'undefined'){
        mostrarErrorValidacion($('#casino_juego'),response.id_casino[0],false);
      }
      if(typeof response.id_tipo_mesa !== 'undefined'){
        mostrarErrorValidacion($('#tipo_mesa_juego'),response.id_tipo_mesa[0],false);
      }
      if(typeof response.posiciones !== 'undefined'){
        mostrarErrorValidacion($('#posicionesJuego'),response.posiciones[0],false);
      }
    }
  })
})

//NUEVO sector
$('#btn-nuevo-sector').on('click', function(e){

  e.preventDefault();
  ocultarErrorValidacion($('#nombre_sector'));
  ocultarErrorValidacion($('#casino_sector'));
  $('#mensajeExito').hide();


  $('#nombre_sector').val(" ");
  $('#casino_sector').val("0");

  $('#modalAltaSector').modal('show');

})

//guardar sector
$('#btn-guardar-sector').on('click', function(e){

  e.preventDefault();

  var formData = {
    descripcion: $('#nombre_sector').val(),
    id_casino: $('#casino_sector').val(),
  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'sectores-mesas/guardar',
    data: formData,
    dataType: 'json',

    success: function (data){

      $('#modalAltaSector').modal('hide');
      $('#mensajeExito h3').text('ÉXITO DE CREACIÓN');
      $('#mensajeExito p').text('El sector fue creado correctamente');
      $('#mensajeExito').show();
      $('#btn-buscarSectores').trigger('click');

    },
    error:function(data){

      var response = data.responseJSON.errors;

      if(typeof response.descripcion !== 'undefined'){
        mostrarErrorValidacion($('#nombre_sector'),response.descripcion[0],false);
      }
      if(typeof response.id_casino !== 'undefined'){
        mostrarErrorValidacion($('#casino_sector'),response.id_casino[0],false);
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

  $.get('mesas-juegos/obtenerJuego/'+ id_juego, function(data){
    console.log(data);

    $('#modif_nom').val(data.juego.nombre_juego);
    $('#modif_siglas').val(data.juego.siglas);
    $('#modif_tipo').val(data.tipo_mesa.descripcion);
    $('#modif_cas').val(data.casino.nombre);
    $('#modif_pos').val(data.juego.posiciones);


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
    posiciones: $('#modif_pos').val()

  }

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  });

  $.ajax({
    type: 'POST',
    url: 'mesas-juegos/modificarJuego',
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

      var response = data.responseJSON.errors;

      if(typeof response.nombre_juego !== 'undefined'){
        mostrarErrorValidacion($('#modif_nom'),response.nombre_juego[0],false);
      }
      if(typeof response.siglas !== 'undefined'){
        mostrarErrorValidacion($('#modif_siglas'),response.siglas[0],false);
      }
      if(typeof response.posiciones !== 'undefined'){
        mostrarErrorValidacion($('#modif_pos'),response.posiciones[0],false);
      }
    }
  })
})


//ELIMINAR Juego
$(document).on('click','.eliminarJuego',function(e){
  e.preventDefault();

  $('#mensajeExito').hide();
  $('#mensajeError').hide();

  var id_juego= $(this).val();
  $('#btn-eliminar-juego').val(id_juego);

  $.get('mesas-juegos/obtenerJuego/'+ id_juego, function(data){

    for (var i = 0; i < data.mesas.length; i++) {
      $('#msjeliminarJuego').text('Este juego esta asociado a las siguientes mesas: ' + data.mesas[i].nro_mesa + '- ');
    }
    $('#modalAlertaEliminar').modal('show');

  });
});


$('#btn-eliminar-juego').on('click', function(e){

  e.preventDefault();

  var id= $(this).val();
  $.get('mesas-juegos/bajaJuego/' + id  , function(data){

    if(data==0){
      $('#modAlertaEliminar').modal('hide');
      $('#mensajeError h3').text('ERROR');
      $('#mensajeError p').text('No es posible eliminar este Juego, posee mesas asociadas.');
      $('#mensajeError').show();
    }
    else{
      $('#modAlertaEliminar').modal('hide');

      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('JUEGO ELIMINADO');
      $('#mensajeExito').show();
    }


   $('#btn-buscarJuegos').trigger('click');

  })

});


//ELIMINAR SERCTOR
$(document).on('click','.eliminarSector',function(e){
  e.preventDefault();
  $('#modalAlertaSector').modal('show');

  $('#mensajeExito').hide();
  $('#mensajeError').hide();
  $('#btn-baja-sector').val($(this).val());
});


$('#btn-baja-sector').on('click',function(){

  var id_sector= $(this).val();

  $.get('sectores-mesas/eliminarSector/'+ id_sector, function(data){

    if(data==0){
      $('#modalAlertaSector').modal('hide');

      $('#mensajeError h3').text('ERROR');
      $('#mensajeError p').text('No es posible eliminar este sector, posee mesas asociadas.');
      $('#mensajeError').show();
      $('#btn-buscarSectores').trigger('click');
    }
    else{
      $('#modalAlertaSector').modal('hide');

      $('#mensajeExito h3').text('ÉXITO');
      $('#mensajeExito p').text('El sector fue eliminado correctamente');
      $('#mensajeExito').show();
      $('#btn-buscarSectores').trigger('click');

    }
  });
})

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
      .text(data.nombre).css('text-align','center'))
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

function generarFilaSectores(data){

  var fila=$(document.createElement('tr'));
  fila.attr('id',data.sector_casino.id_sector_mesas)
      .append($('<td>')
      .addClass('col-xs-3')
      .text(data.sector_casino.descripcion).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3')
      .text(data.sector_casino.nombre).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3')
      .text(data.mesas).css('text-align','center'))
      .append($('<td>')
      .addClass('col-xs-3')
      .append($('<span>').text(' '))
      .append($('<button>').addClass('btn btn-warning')
          .addClass('eliminarSector').val(data.sector_casino.id_sector_mesas)
          .append($('<i>').addClass('fas').addClass('fa-fw').addClass('fa-trash'))).css('text-align','center'))


  return fila;

}
