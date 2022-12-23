$(document).ready(function(e){

  $('.tituloSeccionPantalla').text('Configuración de Cuenta');

});

//Guardar solo la imagen de perfil
$('#btn-guardarImagen').click(function(e){
  $('#mensajeExito').hide();

  $.ajaxSetup({headers:{'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  //Guardar imagen
  $('#imagenPerfil').croppie('result',{
    type: 'canvas',
    size: 'viewport',
    format: 'jpeg',
    quality: 0.5,
    circle: false,
  }).then(function(blob){
    console.log(blob);

    var formData = new FormData();
    formData.append('id_usuario', $('#btn-guardarImagen').val());
    formData.append('imagen', blob);

    $.ajax({
        type: "POST",
        url: 'configCuenta/modificarImagen',
        data: formData,
        dataType: 'json',
        processData: false,
        contentType:false,
        cache:false,
        success: function (data) {
            console.log('Lo que viene de la BASE');
            console.log(data);

            $('#img_perfilBarra').attr('src', 'data:image/jpeg;base64,' + data.imagen);
            $('#C_imagen').attr('src', 'data:image/jpeg;base64,' + data.imagen);

            $('#modalCambiarImagen').modal('hide');

            $('#mensajeExito h3').text('ÉXITO DE CARGA');
            $('#mensajeExito .cabeceraMensaje').addClass('modificar');
            $('#mensajeExito p').text("Se han modificado correctamente sus datos.");
            $('#mensajeExito').show();
            //Mensaje de exito
        },
        error: function (data) {
            console.log(data);
        }
      });
  });

});

//Guardar solo los datos modificados de la cuenta (username, nombre completo y mail)
$('#btn-guardarDatos').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();

  $.ajaxSetup({headers:{'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

    var formData = new FormData();
    formData.append('id_usuario', $(this).val());
    // formData.append('nombre', $('#nombre').val());
    formData.append('user_name',$('#user_name').val());
    formData.append('email', $('#email').val());

  $.ajax({
      type: "POST",
      url: 'configCuenta/modificarDatos',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType:false,
      cache:false,
      success: function (data) {
          console.log(data);
          //MOSTRAR MENSAJE ÉXTIO
          // $('#imgPerfil').attr('src', 'data:image/jpeg;base64,' + data.imagen);
          $('#C_username').text("@" + data.user_name);
          $('.nombreUsuario h4').text("@" + data.user_name);

          $('#modalEditarDatos').modal('hide');
          $('#mensajeExito h3').text('ÉXITO DE CARGA');
          $('#mensajeExito .cabeceraMensaje').addClass('modificar');
          $('#mensajeExito p').text("Se han modificado correctamente sus datos.");
          $('#mensajeExito').show();
      },
      error: function (data) {
          console.log(data);

          var response = JSON.parse(data.responseText);

          ocultarErrorValidacion($('#user_name'));
          ocultarErrorValidacion($('#email'));

          if (typeof response.user_name != 'undefined') {
              mostrarErrorValidacion($('#user_name'), response.user_name[0], true);
          }
          if (typeof response.email != 'undefined') {
              mostrarErrorValidacion($('#email'), response.email[0], true);
          }
      }
    });

});

$('#btn-guardarNuevoPass').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();

  console.log('Intenta cambiar');

  $.ajaxSetup({headers:{'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData=new FormData();
  formData.append('id_usuario', $(this).val());
  formData.append('password_actual', $('#pass_actual').val());
  formData.append('password_nuevo',$('#pass_nueva').val());
  formData.append('password_nuevo_confirmation', $('#pass_repetida').val());

  $.ajax({
      type: "POST",
      url: 'configCuenta/modificarPassword',
      data: formData,
      dataType: 'json',
      processData: false,
      contentType:false,
      cache:false,
      success: function (data) {
          console.log(data);
          $('#modalCambiarPass').modal('hide');
          $('#mensajeExito h3').text('ÉXITO DE CARGA');
          $('#mensajeExito .cabeceraMensaje').addClass('modificar');
          $('#mensajeExito p').text("Se han modificado correctamente sus datos.");
          $('#mensajeExito').show();
          //MOSTRAR MENSAJE ÉXTIO
          // $('#imgPerfil').attr('src', 'data:image/jpeg;base64,' + data.imagen);
      },
      error: function (data) {
          console.log(data);
          var response = JSON.parse(data.responseText);

          ocultarErrorValidacion($('#pass_actual'));
          ocultarErrorValidacion($('#pass_nueva'));
          ocultarErrorValidacion($('#pass_repetida'));

          if (typeof response.password_actual != 'undefined') {
              mostrarErrorValidacion($('#pass_actual'), response.password_actual[0], true);
          }
          if (typeof response.password_nuevo != 'undefined') {
              mostrarErrorValidacion($('#pass_nueva'), response.password_nuevo[0], true);
          }
          if (typeof response.password_nuevo_confirmation != 'undefined') {
              mostrarErrorValidacion($('#pass_repetida'), response.password_nuevo_confirmation[0], true);
          }


          if (typeof response.password_incorrecta != 'undefined') {
              mostrarErrorValidacion($('#pass_actual'), response.password_incorrecta[0], true);
          }

          // if(typeof response.password_incorrecta !== 'undefined'){
          //   $('#password_actual').popover('show');
          //   $('.popover').addClass('popAlerta');
          // }
          // if(typeof response.password_nuevo !== 'undefined'){
          //   $('#password_nuevo_confirmation').popover('show');
          //   $('.popover').addClass('popAlerta');
          // }
      }
    });
});

//NUEVO
$('#btn-editarDatos').click(function() {
    var id_usuario = $(this).val();

    ocultarErrorValidacion($('#user_name'));
    ocultarErrorValidacion($('#email'));

    //Setear los datos
    $.get('configCuenta/buscarUsuario/' + id_usuario, function(data) {
        console.log(data);

        $('#user_name').val(data.usuario.user_name);
        $('#email').val(data.usuario.email);

        $('#modalEditarDatos').modal('show');
    });

});

$('#btn-cambiarPass').click(function() {
    $('#modalCambiarPass .password').val('');

    ocultarErrorValidacion($('#pass_actual'));
    ocultarErrorValidacion($('#pass_nueva'));
    ocultarErrorValidacion($('#pass_repetida'));

    $('#modalCambiarPass').modal('show');
});

$('#btn-cambiarImagen').click(function() {
    $('#modalCambiarImagen').modal('show');
});

$(document).on('change','#upload', function(e){

    console.log('Cambió la imagen');
    console.log(e);

    var reader = new FileReader();

    reader.onload = function (e) {
        $imagenPerfil.croppie('bind', {
            url: e.target.result
        }).then(function(){

          console.log('jQuery bind complete');

        });
    }

    reader.readAsDataURL(this.files[0]);
});

var $imagenPerfil;

$(document).on('shown.bs.modal','#modalCambiarImagen', function(){
  $imagenPerfil = $('#imagenPerfil').croppie('destroy').croppie({
    url: 'configCuenta/imagen',
    enableExif: true,
    viewport: {
      width: 250,
      height:250,
      type: 'circle',
    },
    boundary: {
      width: 300,
      height: 300,
    },
  });
});

$('.cancelar').on('click', function () {
   $('.modal:visible').modal('hide');
})


$('#modalCambiarPass').on('hidden.bs.modal', function() {

})

$('#modalCambiarImagen').on('hidden.bs.modal', function() {
  $('.password').val();
})

$('#modalEditarDatos').on('hidden.bs.modal', function() {
  $('#user_name').val($('#user_name').attr('data-user'));
  $('#email').val($('#email').attr('data-email'));

})
