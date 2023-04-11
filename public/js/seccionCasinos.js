$(document).ready(function(){

  $('.tituloSeccionPantalla').text('Administrar casinos');
  $('#opcCasino').attr('style','border-left: 6px solid #185891;');
  $('#opcCasino').addClass('opcionesSeleccionado');


  $("#tablaCasinos").tablesorter({
      headers: {
        2: {sorter:false}
      }
  });

})

// $('.btnConEspera').click(function() {
//   var boton = $(this);
//   boton.prop('disabled',true);
//
//   window.setTimeout(function() {
//     boton.prop('disabled',false);
//   }, 1000);
// });

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| ADMINISTRAR CASINO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Casino
$('#btn-nuevo').click(function(e){
  e.preventDefault();

  $('#mensajeExito').hide();

  $('.modal-title').text('| NUEVO CASINO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass('btn-warning');
  $('#btn-guardar').addClass('btn-successAceptar');
  $('#btn-guardar').text('ACEPTAR');

  $('#btn-guardar').val("nuevo");
  $('#frmCasino').trigger('reset');

  //Ocultar validaciones
  $('#nombre').removeClass('alerta');
  $('#codigo').removeClass('alerta');
  $('#alertaNombre').hide();
  $('#alertaCodigo').hide();

  $('#modalCasino').modal('show');
});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13 && $('#modalCasino').is(":visible")) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
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

//Mostrar modal con los datos del Casino cargados
$(document).on('click','.modificar',function(){
  $('#mensajeExito').hide();

  var id_casino = $(this).val();

  $('.modal-title').removeAttr('style');
  $('.modal-title').text('ADVERTENCIA');
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #FFB74D;');
  $('#btn-preModificar').removeClass('btn-successAceptar');
  $('#btn-preModificar').addClass('btn-warningModificar');

  $('#btn-preModificar').val(id_casino);
  $('#modalPreModificar').modal('show');
  // //Modificar los colores del modal
  //   $('.modal-title').text('| MODIFICAR CASINO');
  //   $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #ff9d2d; color: #fff');

  //   $('#btn-guardar').text('MODIFICAR');
  //
  //   var id_casino = $(this).val();
  //
  //   $.get("casinos/obtenerCasino/" + id_casino, function(data){
  //       $('#id_casino').val(data.casino.id_casino);
  //       $('#nombre').val(data.casino.nombre);
  //       $('#codigo').val(data.casino.codigo);
  //
  //       $('#btn-guardar').val("modificar");
  //       $('#modalCasino').modal('show');
  //   });
  //
  //   //Ocultar validaciones
  //   $('#nombre').removeClass('alerta');
  //   $('#alertaNombre').hide();
  //   $('#alertaCodigo').hide();
});

$('#btn-preModificar').click(function(){
    $('#modalPreModificar').modal('hide');

    //Modificar los colores del modal
      $('.modal-title').text('| MODIFICAR CASINO');
      $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #FFB74D; color: #FFB74D');
      $('#btn-guardar').removeClass('btn-successAceptar');
      $('#btn-guardar').addClass('btn-warningModificar');
      $('#btn-guardar').text('MODIFICAR');

    var id_casino = $(this).val();

    $.get("casinos/obtenerCasino/" + id_casino, function(data){
        $('#id_casino').val(data.casino.id_casino);
        $('#nombre').val(data.casino.nombre);
        $('#codigo').val(data.casino.codigo);

        $('#btn-guardar').val("modificar");
        $('#modalCasino').modal('show');
    });

    //Ocultar validaciones
    $('#nombre').removeClass('alerta');
    $('#codigo').removeClass('alerta');
    $('#alertaNombre').hide();
    $('#alertaCodigo').hide();
});

//Borrar Casino y remover de la tabla
$(document).on('click','.eliminar',function(){
  var id_casino = $(this).val();

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  $.ajax({
      type: "DELETE",
      url: "casinos/eliminarCasino/" + id_casino,
      success: function (data) {
        //Remueve de la tabla
        $('#casino' + id_casino).remove();
        $("#tablaCasinos").trigger("update");
        $('#modalEliminar').modal('hide');
      },
      error: function (data) {
        console.log('Error: ', data);
      }
  });
    // $('.modal-title').removeAttr('style');
    // $('.modal-title').text('ADVERTENCIA');
    // $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    //
    // var id_casino = $(this).val();
    // $('#btn-eliminarModal').val(id_casino);
    // $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    var id_casino = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "casinos/eliminarCasino/" + id_casino,
        success: function (data) {
          //Remueve de la tabla
          $('#casino' + id_casino).remove();
          $("#tablaCasinos").trigger("update");
          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//Crear nuevo Casino / actualizar si existe
$('#btn-guardar').click(function (e) {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var formData = {
      nombre: $('#nombre').val(),
      codigo: $('#codigo').val(),
    }

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = 'casinos/guardarCasino';
    var id_casino = $('#id_casino').val();

    if (state == "modificar") {
      var formData = {
        id_casino: $('#id_casino').val(),
        nombre: $('#nombre').val(),
        codigo: $('#codigo').val(),
      }
      url = 'casinos/modificarCasino';
    }

    console.log(formData);

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            var casino = '';
            casino += '<tr id="casino' + data.casino.id_casino + '"><td class="col-xs-3">' + data.casino.nombre + '</td><td class="col-xs-3">' + data.casino.codigo + '</td>';
            casino += '<td class="col-xs-6 accionesTH"> <button class="btn btn-warning btn-detalle modificar" value="' + data.casino.id_casino + '"><i class="fa fa-fw fa-pencil-alt"></i><span> MODIFICAR<span></button>  ';
            casino += '<button class="btn btn-danger btn-eliminar eliminar" value="' + data.casino.id_casino + '"><i class="fa fa-fw fa-trash"></i><span> ELIMINAR<span></button>';
            casino += '</td></tr>';

            if (state == "nuevo"){ //Si está agregando
                //AGREGAR EN LA TABLA
                $('#tablaCasinos').append(casino);
                $('#mensajeExito h4').text('El CASINO fue CREADO correctamente.');
                $('#mensajeExito div').css('background-color','#4DB6AC');
            }else{ //Si está modificando
                $('#casino' + id_casino).replaceWith(casino);
                $('#mensajeExito h4').text('El CASINO fue MODIFICADO correctamente.');
                $('#mensajeExito div').css('background-color','#FFB74D');
            }
            $('#frmCasino').trigger("reset");
            $('#modalCasino').modal('hide');

            //Ordenar la tabla con el nuevo dato
            // var sorting = [[0,0]]; //Ordenar la [primeraColumna, ordenAscendente(0) | ordenAscendente(1)]
            $("#tablaCasinos").trigger("update");
            // $("#tablaCasinos").trigger("sorton",[sorting]);

            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {


            var response = data.responseJSON.errors;
            $('#alertaNombre').hide();
            $('#alertaNombre').text("");
            $('#alertaCodigo').hide();
            $('#alertaCodigo').text("");

            if(typeof response.nombre !== 'undefined'){
              $('#nombre').addClass('alerta');
              $('#alertaNombre').text(response.nombre[0]);
              $('#alertaNombre').show();
            }

            if(typeof response.codigo !== 'undefined'){
              $('#codigo').addClass('alerta');
              $('#alertaCodigo').text(response.codigo[0]);
              $('#alertaCodigo').show();
            }

            console.log('Error:', data);


        }
    });
});

/** EVENTOS PARA VALIDAR LOS CAMPOS ***/
// $('#nombre').focusout(function(){
//   if ($(this).val() == ''){
//       $(this).addClass('alerta');
//       $('#alertaNombre').text('El campo nombre no puede estar en blanco.');
//       $('#alertaNombre').show();
//   }
// });
$('#nombre').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaNombre').hide();
});
$('#codigo').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaCodigo').hide();
});
