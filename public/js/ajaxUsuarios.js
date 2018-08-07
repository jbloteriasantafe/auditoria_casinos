var id_usuario; //variable global, se usa para saber el usuario que esta siendo modificado

//Resaltar la sección en el menú del costado
$(document).ready(function() {

  $('#barraUsuarios').attr('aria-expanded','true');
  $('#usuarios').removeClass();
  $('#usuarios').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar usuarios');
  $('#opcGestionarUsuarios').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcGestionarUsuarios').addClass('opcionesSeleccionado');

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

  $("#tablaUsuarios").tablesorter({
      headers: {
        3: {sorter:false}
      }
  });


});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| GESTIONAR USUARIOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

$('#btn-nuevo').click(function(e){
  e.preventDefault();

  $('#mensajeExito').hide();

  $('#usuario').removeClass('alerta');
  $('#nombre').removeClass('alerta');
  $('#password').removeClass('alerta');
  $('#email').removeClass('alerta');

  $('#alertaNombre').hide();
  $('#alertaUsuario').hide();
  $('#alertaEmail').hide();
  $('#alertaPassword').hide();

  $('.modal-title').text('| NUEVO USUARIO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  $('#btn-guardar').val("nuevo");

  $('#modalCrear').modal('show');
  $('#frmUsuario').trigger('reset');

})

$(document).on('keypress',function(e){
    if(e.which == 13 && $("#modalCrear").is(":visible")) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

$("#modalModificar input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-modificiar').click();
    }
});

$("#contenedorFiltros input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#buscar').click();
    }
});

$('#modalCrear').on('hidden.bs.modal', function (){
  $('#modalCrear #contenedorPermisos ul li').remove();
  id_usuario = '';
  ocultarErrorValidacion($('#btn-resetearPass'));
  $('#btn-resetearPass').prop('disabled' , false);
})


$('#modalModificar').on('hidden.bs.modal', function (){
  $('#modalModificar #contenedorPermisos ul li').remove();
  id_usuario = '';
  ocultarErrorValidacion($('#btn-resetearPass'));
  $('#btn-resetearPass').prop('disabled' , false);

})

/*
mostrar permisos de un rol
*/
$(document).on('change','#modalCrear #contenedorRoles input:checkbox', function(e){
  var roles = [];

  //Buscar todos los roles seleccionados
  $('#modalCrear #contenedorRoles input:checked').each(function(){
    roles.push($(this).val());
  })

  //Se muestran los permisos correspondientes a los roles seleccionados
  mostrarPermisos(roles, $('#modalCrear #contenedorPermisos'));
});

/*
mostrar permisos de un rol
*/
$(document).on('change','#modalModificar #contenedorRoles input:checkbox', function(e){
  var roles = [];

  //Buscar todos los roles seleccionados
  $('#modalModificar #contenedorRoles input:checked').each(function(){
    roles.push($(this).val());
  })

  //Se muestran los permisos correspondientes a los roles seleccionados
  mostrarPermisos(roles,$('#modalModificar #contenedorPermisos'));
})


$('#btn-resetearPass').click(function(e){
  $.ajaxSetup({
     headers: {
         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
     }
   });

   var formData={
     id_usuario:id_usuario,
   };

   $.ajax({
      type: "POST",
      url: 'usuarios/reestablecerContraseña',
      data: formData,
      dataType: 'json',
      success: function (data) {
        $('#btn-resetearPass').prop('disabled',true);
      },
      error: function(data){
        mostrarErrorValidacion($('#btn-resetearPass') , 'El usuario no tiene DNI en el sistema.' , true);
      }
    });
})

/*
busqueda de usuarios
*/
$('#buscar').click(function(e){
  $.ajaxSetup({
     headers: {
         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
     }
   });

  formData={
  nombre: $('#buscadorNombre').val(),
  usuario: $('#buscadorUsuario').val(),
  email: $('#buscadorEmail').val(),
}

  console.log(formData);

  $.ajax({
     type: "POST",
     url: 'usuarios/buscar',
     data: formData,
     dataType: 'json',
     success: function (data) {
        console.log(data);
      $('#cuerpoTabla').empty();
        var resultado='';
        if(data.usuarios.length ==0){
          $('#tituloBusqueda').text('No se encontraron Usuarios');
          $('#cuerpoTabla').empty();
        }else{
        $('#tituloBusqueda').text('Se encontraron ' + data.usuarios.length + ' Usuarios');
        for (var i = 0; i < data.usuarios.length; i++) {

                      $('#cuerpoTabla')
                          .append($('<tr>')
                              .attr('id',data.usuarios[i].id_usuario)
                              .append($('<td>')
                                  .addClass('col-xl-3')
                                  .addClass('col-md-3')
                                  .text(data.usuarios[i].user_name)
                              )
                              .append($('<td>')
                                  .addClass('col-xl-3')
                                  .addClass('col-md-3')
                                  .text(data.usuarios[i].nombre)
                              )
                              .append($('<td>')
                                  .addClass('col-xl-3')
                                  .addClass('col-md-3')
                                  .text(data.usuarios[i].email)
                              )
                              .append($('<td>')
                                  .addClass('col-xl-3')
                                  .addClass('col-md-3')
                                  .append($('<button>')
                                      .append($('<i>')
                                          .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                                      )
                                      .append($('<span>').text(' VER MÁS'))
                                      .addClass('btn').addClass('btn-info').addClass('detalle').addClass('info')
                                      .val(data.usuarios[i].id_usuario)
                                  )
                                  .append($('<span>').text(' '))
                                  .append($('<button>')
                                      .append($('<i>')
                                          .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                                      )
                                      .append($('<span>').text(' MODIFICAR'))
                                      .addClass('btn').addClass('btn-warning').addClass('modificar')
                                      .val(data.usuarios[i].id_usuario)
                                  )
                                  .append($('<span>').text(' '))
                                  .append($('<button>')
                                      .append($('<i>')
                                          .addClass('fa')
                                          .addClass('fa-fw')
                                          .addClass('fa-trash-alt')
                                      )
                                      .append($('<span>').text(' ELIMINAR'))
                                      .addClass('btn').addClass('btn-danger').addClass('openEliminar')
                                      .val(data.usuarios[i].id_usuario)
                                  )
                              )
                          )
        }

      }

      //Ordenar la tabla con el nuevo dato
      // var sorting = [[0,0]]; //Ordenar la [primeraColumna, ordenAscendente(0) | ordenAscendente(1)]
      $("#tablaUsuarios").trigger("update");
      $("#tablaUsuarios th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');
     },
     error: function (data) {
         console.log('Error:', data);
     }


   });
});


$('#btn-guardar').on('click',function (e) {
  console.log('algo');
  var roles=[];
  var casinos=[];
  $('#modalCrear #contenedorRoles input:checked').each(function(e){
    roles.push($(this).val());
  })
  $('#modalCrear #contenedorCasino input:checked').each(function(e){
    casinos.push($(this).val());
  })

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })

  e.preventDefault();

  my_url='usuarios/guardarUsuario';

  var formData = {
      nombre: $('#nombre').val(),
      usuario: $('#usuario').val(),
      contraseña: $('#password').val(),
      email: $('#email').val(),
      roles: roles,
      casinos: casinos,

  }

  $.ajax({
      type: "POST",
      url: my_url,
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);
          var renglon= '';
          renglon += '<tr id="usuario'+ data.usuario.id_usuario +'"><td class="col-xs-3">' +  data.usuario.user_name + '</td> <td class="col-xs-3">'+ data.usuario.nombre+'</td>';
          renglon += ' <td class="col-xs-3">'+ data.usuario.email +'</td><td class="col-xs-3"> <button type="button" class="btn btn-info info" value="'+data.usuario.id_usuario +'"><i class="fa fa-fw fa-search-plus"> </i><span>VER MÁS </span></button>';
          renglon += ' <button type="button" class="btn btn-warning modificar" value="' + data.usuario.id_usuario +'"><i class="fa fa-fw fa-pencil-alt"> </i> <span>MODIFICAR </span></button>';
          renglon += ' <button type="button" class="btn btn-danger openEliminar" value="' + data.usuario.id_usuario + '"><i class="fa fa-fw fa-trash-alt"> </i> <span>ELIMINAR </span></button>';
          renglon += '</td> </tr>';


          $('#cuerpoTabla').append(renglon);

          $('#mensajeExito p').text('El Usuario fue CREADO correctamente.');
          $('#mensajeExito h3').text('Creación Exitosa.');
          $('#mensajeExito .cabeceraMensaje').css('background-color','#4DB6AC');


          $('#modalCrear').modal('hide');
          $('#frmUsuario').trigger('reset');

          //Ordenar la tabla con el nuevo dato
          var sorting = [[0,0]]; //Ordenar la [primeraColumna, ordenAscendente(0) | ordenAscendente(1)]
          $("#tablaUsuarios").trigger("update");
          $("#tablaUsuarios th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');
          //Mostrar éxito
          $('#mensajeExito').show();

        },

      error: function (data) {

        var response = JSON.parse(data.responseText);

        if(typeof response.usuario !== 'undefined'){
          $('#usuario').addClass('alerta');
          $('#alertaUsuario span').text(response.usuario[0]);
          $('#alertaUsuario').show();
        }

        if(typeof response.email !== 'undefined'){
          $('#email').addClass('alerta');
          $('#alertaEmail span').text(response.email[0]);
          $('#alertaEmail').show();
        }

        if(typeof response.nombre !== 'undefined'){
          $('#nombre').addClass('alerta');
          $('#alertaNombre span').text(response.nombre[0]);
          $('#alertaNombre').show();
        }

        if(typeof response.contraseña !== 'undefined'){
          $('#password').addClass('alerta');
          $('#alertaPassword span').text(response.contraseña[0]);
          $('#alertaPassword').show();
        }


        console.log('Error:', data);
      }
  });
});

/* ver mas muestra modal cargado */

$(document).on('click','.info',function(){
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #fff');

  var id_usuario = $(this).val();
    $('#modalVer input:checked').prop('checked' ,false);

  $.get('/usuarios/buscar/' + id_usuario, function (data) {
    console.log(data);

    $('#infoNombre').val(data.usuario.nombre);
    $('#infoPass').val(data.usuario.password);
    $('#infoUsuario').val(data.usuario.user_name);
    $('#infoEmail').val(data.usuario.email);
    $('#btn-info').val(data.usuario.id_usuario);
    // $('#modalVer #contenedorPermisos ul').empty();

    //Setear los roles del usuario
    for (var i = 0; i < data.roles.length; i++) {
      $('#modalVer #rol' + data.roles[i].id_rol ).prop('checked' ,true);
    }

    //Setear los permisos del usuario
    mostrarPermisos(data.roles, $('#modalVer #contenedorPermisos'))  ;

    //Setear los casinos del usuario
    for (var i = 0; i < data.casinos.length; i++) {
      $('#modalVer #casino' + data.casinos[i].id_casino ).prop('checked' ,true);
    }


    $('#modalVer').modal('show');
  });
});

/* fin modal cargado */


$(document).on('click','.modificar',function(){
  $('#modNombre').removeClass('alerta');
  $('#modUsuario').removeClass('alerta');
  $('#modEmail').removeClass('alerta');
  $('#modPassword').removeClass('alerta');

  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #FFB74D; color: #fff');

  $('#mensajeExito').hide();
  $('#modAlertaNombre').hide();
  $('#modAlertaUsuario').hide();
  $('#modAlertaEmail').hide();
  $('#modAlertaPassword').hide();

    id_usuario = $(this).val();

    $('#modalModificar input:checked').prop('checked' ,false);

  $.get('/usuarios/buscar/' + id_usuario, function (data) {

    console.log(data);
    $('#modNombre').val(data.usuario.nombre);
    $('#modPassword').val(data.usuario.password);
    $('#modUsuario').val(data.usuario.user_name);
    $('#modEmail').val(data.usuario.email);
    $('#btn-modificiar').val(data.usuario.id_usuario);
    // $('#modalModificar #contenedorPermisos').empty();

    //Setear los roles del usuario
    for (var i = 0; i < data.roles.length; i++) {
      $('#modalModificar #rol' + data.roles[i].id_rol ).prop('checked' ,true);
    }

    //Setear los permisos del usuario
    mostrarPermisos(data.roles,$('#modalModificar #contenedorPermisos'))  ;

    //Setear los casinos del usuario
    for (var i = 0; i < data.casinos.length; i++) {
      $('#modalModificar #casino' + data.casinos[i].id_casino ).prop('checked' ,true);
    }

    $('#modalModificar').modal('show');
  });
});

/*
modificar usuario
*/
$("#btn-modificiar").click(function (e) {
  var roles=[];
  var casinos=[];
  $('#modalModificar #contenedorRoles input:checked').each(function(e){
    roles.push($(this).val());
  })
  $('#modalModificar #contenedorCasino input:checked').each(function(e){
    casinos.push($(this).val());
  })

  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  })


  my_url='usuarios/modificarUsuario';

  var formData = {
      id_usuario: $(this).val(),
      nombre: $('#modNombre').val(),
      user_name: $('#modUsuario').val(),
      password: $('#modPassword').val(),
      email: $('#modEmail').val(),
      roles: roles,
      casinos: casinos,
      origen: 1,

  }

  console.log(formData);
  $.ajax({
      type: "POST",
      url: my_url,
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);

              $('#cuerpoTabla #usuario' + data.usuario.id_usuario).replaceWith(
              $('<tr>').attr('id', 'usuario' + data.usuario.id_usuario)
                  .append($('<td>').addClass('col-xs-3')
                      .text(data.usuario.user_name)
                  )
                  .append($('<td>').addClass('col-xs-3')
                      .text(data.usuario.nombre)
                  )
                  .append($('<td>').addClass('col-xs-3')
                      .text(data.usuario.email)
                  )
                  .append($('<td>')
                      .addClass('col-xs-3')
                      .append($('<button>')
                          .append($('<i>')
                              .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                          )
                          .append($('<span>').text(' VER MÁS'))
                          .addClass('btn').addClass('btn-info').addClass('info')
                          .attr('value',data.usuario.id_usuario)
                      )
                      .append($('<span>').text(' '))
                      .append($('<button>')
                          .append($('<i>')
                              .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                          )
                          .append($('<span>').text(' MODIFICAR'))
                          .addClass('btn').addClass('btn-warning').addClass('modificar')
                          .attr('value',data.usuario.id_usuario)
                      )
                      .append($('<span>').text(' '))
                      .append($('<button>')
                          .append($('<i>')
                              .addClass('fa')
                              .addClass('fa-fw')
                              .addClass('fa-trash-alt')
                          )
                          .append($('<span>').text(' ELIMINAR'))
                          .addClass('btn').addClass('btn-danger').addClass('openEliminar')
                          .attr('value',data.usuario.id_usuario)
                      )
                  )
        );

          $('#modalModificar').modal('hide');

          $('#mensajeExito h3').text('El Usuario fue MODIFICADO correctamente.');
          $('#mensajeExito p').text('El Usuario fue MODIFICADO correctamente.');
          $('#mensajeExito .cabeceraMensaje').css('background-color','#FFB74D');

          //Mostrar éxito
          $('#mensajeExito').show();
        },

      error: function (data) {

        var response = JSON.parse(data.responseText);

        $('#modAlertaNombre').hide();
        $('#modAlertaUsuario').hide();
        $('#modAlertaPassword').hide();
        $('#modAlertaEmail').hide();

        if(typeof response.user_name !== 'undefined'){
          $('#modUsuario').addClass('alerta');
          $('#modAlertaUsuario').text(response.user_name[0]);
          $('#modAlertaUsuario').show();
        }

        if(typeof response.email !== 'undefined'){
          $('#modEmail').addClass('alerta');
          $('#modAlertaEmail').text(response.email[0]);
          $('#modAlertaEmail').show();
        }

        if(typeof response.nombre !== 'undefined'){
          $('#modNombre').addClass('alerta');
          $('#modAlertaNombre').text(response.nombre[0]);
          $('#modAlertaNombre').show();
        }

        if(typeof response.password !== 'undefined'){
          $('#modPassword').addClass('alerta');
          $('#modAlertaPassword').text(response.password[0]);
          $('#modAlertaPassword').show();
        }



        console.log('Error:', data);
      }
  });
});

/* modal Ver Mas*/
/*
abre modal eliminar
*/
$(document).on('click','.openEliminar',function(){
  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

  $('#modalEliminar').modal('show');
  $('#btn-eliminar').val($(this).val());
});

/*
eliminar
*/
$('#btn-eliminar').click( function(e) {
  e.preventDefault();
  var id=$(this).val();

  formData={
    id: id,
  }

  $.ajaxSetup({
     headers: {
         'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
     }
 })
 $.ajax({
     type: "delete",
     url:  '/usuarios/eliminarUsuario',
     data: formData,
    dataType: 'json',

     success: function (data) {
        console.log(data);

         $('#cuerpoTabla #usuario' + id).remove();
         $("#tablaUsuarios").trigger("update");
         $('#modalEliminar').modal('hide');

     },
     error: function (data) {
         console.log('Error:', data);
     }
 });


});

//Función que obtiene los permisos de acuerdo a los roles seleccionados
function mostrarPermisos(roles, modal) {
  modal.empty();
  // $('#modalModificar #contenedorPermisos').empty();

  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  var formData = {
      roles: roles,
  }

  $.ajax({
      type: "POST",
      url: 'permiso/buscarPermisosPorRoles',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);

        modal.parent().find('h5').text("PERMISOS (" + data.permisos.length + ")");

        for (var i = 0; i < data.permisos.length; i++) {
          modal.append($('<div>')
                  .addClass('tagPermiso')
                  .attr('id', data.permisos[i].id_permiso)
                  .text(data.permisos[i].descripcion)
                )
        }

      },
      error: function (error) {
        console.log(error);
      },
  });
}


/** EVENTOS PARA VALIDAR LOS CAMPOS ***/
$('#usuario').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaUsuario').hide();
});
$('#password').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaPassword').hide();
});
$('#nombre').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaNombre').hide();
});
$('#email').focusin(function(){
  $(this).removeClass('alerta');
  $('#alertaEmail').hide();
});


$('#modUsuario').focusin(function(){
  $(this).removeClass('alerta');
  $('#modAlertaUsuario').hide();
});
$('#modPassword').focusin(function(){
  $(this).removeClass('alerta');
  $('#modAlertaPassword').hide();
});
$('#modNombre').focusin(function(){
  $(this).removeClass('alerta');
  $('#modAlertaNombre').hide();
});
$('#modEmail').focusin(function(){
  $(this).removeClass('alerta');
  $('#modAlertaEmail').hide();
});
