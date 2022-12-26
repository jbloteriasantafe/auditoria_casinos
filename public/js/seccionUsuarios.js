$(document).ready(function() {
  //Resaltar la sección en el menú del costado
  $('#barraUsuarios').attr('aria-expanded','true');
  $('#usuarios').removeClass();
  $('#usuarios').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Gestionar usuarios');
  $('#opcGestionarUsuarios').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcGestionarUsuarios').addClass('opcionesSeleccionado');

  $("#tablaUsuarios").tablesorter({
      headers: {
        3: {sorter:false}
      }
  });

  $('#buscar').click();
});

//Opacidad del modal al minimizar
$('#btn-minimizar').click(function(){
  const estado = $(this).data("minimizar");
  $('#modalUsuario .modal-backdrop').css('opacity',estado ? '0.1' : '0.5');
  $(this).data("minimizar",!estado);
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
  $('#modalAyuda .modal-title').text('| GESTIONAR USUARIOS');
  $('#modalAyuda .modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');
	$('#modalAyuda').modal('show');
});

$(document).on('keypress',function(e){
  if(e.which == 13 && $("#modalUsuario").is(":visible")){
    e.preventDefault();
    $('#btn-guardar').click();
  }
});

$("#contenedorFiltros input").on('keypress',function(e){
  if(e.which == 13){
    e.preventDefault();
    $('#buscar').click();
  }
});

/*
mostrar permisos de los roles seleccionados
*/
$(document).on('change','#contenedorRoles input:checkbox', function(e){
  let roles = [];
  $('#contenedorRoles input:checked').each(function(){
    roles.push($(this).val());
  });
  mostrarPermisos(roles);
});

$('#btn-resetearPass').click(function(e){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "GET",
    url: 'usuarios/reestablecerContraseña/'+$('#btn-guardar').val(),
    success: function (data) {
      $('#btn-resetearPass').prop('disabled',true);
    },
    error: function(data){
      mostrarErrorValidacion($('#btn-resetearPass') , 'El usuario no tiene DNI en el sistema.' , true);
    }
  });
})

$('#buscar').click(function(e){
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const formData = {
    nombre: $('#buscadorNombre').val(),
    usuario: $('#buscadorUsuario').val(),
    email: $('#buscadorEmail').val(),
    id_casino: $('#buscadorCasino').val()
  };

  $('#cuerpoTabla').empty();
  $.ajax({
     type: "POST",
     url: 'usuarios/buscar',
     data: formData,
     dataType: 'json',
     success: function (data) {
      $('#tituloBusqueda').text(data.usuarios.length == 0? 'No se encontraron Usuarios' : `Se encontraron ${data.usuarios.length} Usuarios`);
      for (let i = 0; i < data.usuarios.length; i++) {
        const u = data.usuarios[i];
        const fila = $('#filaEjemploUsuario').clone().attr('id',u.id_usuario);
        fila.find('.user_name').text(u.user_name);
        fila.find('.nombre').text(u.nombre);
        fila.find('.email').text(u.email);
        fila.find('button').val(u.id_usuario);
        $('#cuerpoTabla').append(fila);
      }

      //Ordenar la tabla con el nuevo dato
      $("#tablaUsuarios").trigger("update");
      $("#tablaUsuarios th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');
     },
     error: function (data) {
      console.log('Error:', data);
     }
   });
});


$('#btn-guardar').on('click',function (e) {
  e.preventDefault();
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const roles = $('#contenedorRoles input:checked').map(function(){ return $(this).val(); }).get();
  //Si tiene para elegir con checkboxes (superadmin), chequea ahi, si no les manda las que tiene asociado el usuario
  const plats = $('#contenedorCasino input').length > 0? $('#contenedorCasino input:checked') :  $('#buscadorCasino option');
  const casinos = plats.map(function(){ return $(this).val(); }).get();

  const formData = {
    id_usuario: $(this).val(),
    nombre: $('#nombre').val(),
    user_name: $('#usuario').val(),
    password: $('#password').val(),
    email: $('#email').val(),
    roles: roles,
    casinos: casinos,
  };

  $.ajax({
      type: "POST",
      url: 'usuarios/guardarUsuario',
      data: formData,
      dataType: 'json',
      success: function (data) {
        console.log(data);
        $('#buscar').click();
        $('#mensajeExito p').text('El Usuario fue '+(formData.id_usuario.length > 0?'MODIFICADO':'CREADO')+' correctamente.');
        $('#mensajeExito h3').text('');
        $('#mensajeExito .cabeceraMensaje').css('background-color','#4DB6AC');
        $('#modalUsuario').modal('hide');
        $('#mensajeExito').show();
      },
      error: function (data) {
        console.log('Error:', data);
        const response = JSON.parse(data.responseText);
        if(typeof response.user_name !== 'undefined'){
          mostrarErrorValidacion($('#usuario'),response.user_name[0],true);
        }
        if(typeof response.email !== 'undefined'){
          mostrarErrorValidacion($('#email'),response.email[0],true);
        }
        if(typeof response.nombre !== 'undefined'){
          mostrarErrorValidacion($('#nombre'),response.nombre[0],true);
        }
        if(typeof response.password !== 'undefined'){
          mostrarErrorValidacion($('#password'),response.password[0],true);
        }
        if(typeof response.roles !== 'undefined'){
          mostrarErrorValidacion($('#contenedorRoles'),response.roles[0],true);
        }
        if(typeof response.casinos !== 'undefined'){
          mostrarErrorValidacion($('#contenedorCasino'),response.casinos[0],true);
        }
      }
  });
});

function cargarUsuario(id_usuario,modo){
  //LIMPIAR
  $('#modalUsuario input:checked').prop('checked' ,false);
  $('#modalUsuario input[type!=checkbox]').val('');
  $('#frmUsuario').trigger('reset');
  $('#btn-guardar').val('');
  $('#btn-resetearPass').prop('disabled',false);
  mostrarPermisos([]);
  ocultarErrorValidacion($('#modalUsuario input,button'));
  ocultarErrorValidacion($('#contenedorRoles'));
  ocultarErrorValidacion($('#contenedorCasino'));

  //PONER COLORES Y COSAS ESPECIFICAS
  if(modo == "mostrar"){
    $('#modalUsuario .modal-header').attr('style','background-color: #4FC3F7; color: #fff');
    $('#modalUsuario .modal-title').text('| VER MÁS');
    $('#modalUsuario input').attr('disabled',true);
    $('#password').show();$('#btn-resetearPass').hide();
    $('#btn-guardar').val("-1").hide();
  }
  else if(modo == "nuevo"){
    $('#modalUsuario .modal-title').text('| NUEVO USUARIO');
    $('#modalUsuario .modal-header').attr('style','background-color: #6dc7be; color: #fff');
    $('#modalUsuario input').attr('disabled',false);
    $('#password').show();$('#btn-resetearPass').hide();
    $('#btn-guardar').val("").show();
  }
  else if(modo == "modificar"){
    $('#modalUsuario .modal-header').attr('style','background-color: #FFB74D;color: #fff;');
    $('#modalUsuario .modal-title').text('| MODIFICAR USUARIO');
    $('#modalUsuario input').attr('disabled',false);
    $('#password').hide();$('#btn-resetearPass').show();
    $('#btn-guardar').val(id_usuario).show();
  }

  if (modo != "nuevo"){
    $.get('/usuarios/buscar/' + id_usuario, function (data) {
      $('#usuario').val(data.usuario.user_name);
      $('#nombre').val(data.usuario.nombre);
      $('#email').val(data.usuario.email);
      $('#password').val(modo == "mostrar" ? '**************' : '');

      for (let i = 0; i < data.casinos.length; i++) {
        $(`#casino${data.casinos[i].id_casino}`).prop('checked' ,true);
      }

      for (let i = 0; i < data.roles.length; i++) {
        $(`#rol${data.roles[i].id_rol}`).prop('checked' ,true);
      }

      mostrarPermisos(data.roles);
      $('#modalUsuario').modal('show');
    });
  }
  else $('#modalUsuario').modal('show');
}

$(document).on('click','.info',function(){
  cargarUsuario($(this).val(),"mostrar");
});

$(document).on('click','.modificar',function(){
  cargarUsuario($(this).val(),"modificar");
});

$('#btn-nuevo').click(function(e){
  e.preventDefault();
  cargarUsuario($(this).val(),"nuevo");
});

$(document).on('click','.eliminar',function(){
  $('#btn-eliminar').val($(this).val());
  $('#modalEliminar').modal('show');
});

$('#btn-eliminar').click( function(e) {
  e.preventDefault();
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  $.ajax({
    type: "delete",
    url:  '/usuarios/eliminarUsuario/'+$(this).val(),
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#modalEliminar').modal('hide');
      $('#buscar').click();
    },
    error: function (data) {
      console.log('Error:', data);
    }
  });
});

//Función que obtiene los permisos de acuerdo a los roles seleccionados
function mostrarPermisos(roles) {
  $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});
  const cont = $('#contenedorPermisos');
  cont.empty();
  $.ajax({
      type: "POST",
      url: 'usuarios/buscarPermisosPorRoles',
      data:  {
        roles: roles,
      },
      dataType: 'json',
      success: function (data) {
        console.log(data);
        cont.parent().find('h5').text("PERMISOS (" + data.permisos.length + ")");
        const permiso = $('<div>').addClass('tagPermiso');
        for (let i = 0; i < data.permisos.length; i++) {
          cont.append(permiso.clone().attr('id', data.permisos[i].id_permiso).text(data.permisos[i].descripcion));
        }
      },
      error: function (error) {
        console.log(error);
      },
  });
}
