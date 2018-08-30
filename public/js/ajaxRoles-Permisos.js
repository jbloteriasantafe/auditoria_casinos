
//Resaltar la sección en el menú del costado
$(document).ready(function() {

  $('#barraUsuarios').attr('aria-expanded','true');
  $('#usuarios').removeClass();
  $('#usuarios').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Roles y permisos');
  $('#opcRolesPermisos').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcRolesPermisos').addClass('opcionesSeleccionado');

  $("#tablaRoles").tablesorter({
    headers: {
      1: {sorter:false}
    }
  });

  $("#tablaPermisos").tablesorter({
    headers: {
      1: {sorter:false}
    }
  });
});

$('#comment').focusin(function(){
  $(this).removeClass('alerta');
  $('#myModalRol #alertaDescripcion').hide();

})

$(".pop").hover(function(){
  $(this).popover('show');
})

$(".pop").mouseleave(function(){
  $(this).popover('hide');
})

$('#myModalRol input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-save-rol').click();
  }
})

$('#collapseFiltrosRoles input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#buscarRol').click();
  }
})

$('#collapseFiltrosPermisos input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#buscarPermiso').click();
  }
})

$('#myModalPermisos input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#btn-save-permiso').click();
  }
})

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

$('#btn-minimizarPermisos').click(function(){
  if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

/*
abrir modal eleminar
*/
$(document).on('click','.openEliminar',function(){
  var id = $(this).val();
  var tipo =$(this).data('tipo');

  $('.modal-title').text('ADVERTENCIA');

  $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');

  $('#btn-eliminar').val(id);

  $('#btn-eliminar').data("tipo", tipo);

  // $('#modalEliminar').show();

  $.get('/' + tipo + '/' + id, function (data) {

    console.log(data);
    $('#lista1').empty();
    $('#lista2').empty();
    if(tipo == 'rol'){
      var mensaje1 = '¿Está seguro que desea eliminar este Rol?' ;
      var mensaje2= 'Permisos';
      var mensaje3= 'Alerta';
      var renglon='';
      var usuarios='';
      for (var i = 0; i < data.permisos.length; i++) {
        renglon += '<li>'+data.permisos[i].descripcion+'</li>';
      }

      $('#lista1').append(renglon);
      $('#lista2').append('Existen ' + data.usuarios.length + ' usuarios asociados a ese Rol.');
      $('#modalEliminar #mensaje1').text(mensaje1);
      $('#modalEliminar #mensaje2').text(mensaje2);
      $('#modalEliminar #mensaje3').text(mensaje3);
      $('#mensaje3').show();
      $('#modalEliminar').modal('show');


    }
    else {
      console.log('entra a permisos');
      var mensaje1 = '¿Está seguro que desea eliminar este Permiso?' ;
      var mensaje2= 'Roles:';
      var renglon='';
      for (var i = 0; i < data.roles.length; i++) {
        renglon += '<li>'+data.roles[i].descripcion+'</li>';
      }
      $('#modalEliminar #mensaje1').text(mensaje1);
      $('#modalEliminar #mensaje2').text(mensaje2);
      $('#mensaje3').hide();
      $('#lista1').append(renglon);
      $('#modalEliminar').modal('show');
    }

  })

});

$('#btn-eliminar').click( function(e) {
  var id=$(this).val();
  var tipo =$(this).data('tipo');
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })
  $.ajax({
    type: "delete",
    url:  '/'+tipo+ '/' + id,
    success: function (data) {
      console.log(data);
      if(tipo == 'rol'){
        $('#cuerpoTablaRoles #' + id).remove();

        $('#conteinerRoles #permiso'+id).closest('div').remove();
        $("#tablaRoles").trigger("update");
      }
      else {
        $('#cuerpoTablaPermisos #' + id).remove();
        $('#conteinerPermisos #rol'+id).closest('div').remove();//el nombre es confuso, pero ya esta hecho asi
        $("#tablaPermisos").trigger("update");


        $('#cuerpoTablaRoles tr').each(function(){
          var id=$(this).attr("id");
          $.get('/rol/' + id, function (data) {
            var permisos='';
            for (var i = 0; i < data.permisos.length; i++) {
              permisos+= '· ' + data.permisos[i].descripcion + '<br>';

            }
            $('#cuerpoTablaRoles #'+data.rol.id_rol).find('a').remove();
            $('#cuerpoTablaRoles #'+data.rol.id_rol).find('td:first')
            .append($('<a>')
            .attr('id' , 'popoverData')
            .addClass('pop').addClass('btn')
            .attr('href' , "")
            .attr("data-content", permisos)
            .attr("data-placement" , "bottom")
            .attr("rel","popover")
            .attr("data-original-title" , "Permisos asociados")
            .attr("data-trigger" , "hover")
            .append($('<span>')
            .addClass("badge")
            .append($('<i>')
            .addClass("fa").addClass("fa-user")
          )
        )
      )

        $('.pop').popover({
          html:true
        });


        })
      })
    }
    $('.modal').modal('hide');

    },
    error: function (data) {
      console.log('Error:', data);
    }
    });


});

/* Muestra ver más */

$(document).on('click','.detalleRol',function(){
  var id_rol = $(this).val();
  $('#myModalRol input').each(function(e){
    $(this).prop('readonly', true);

  })

  $.get('/rol/' + id_rol, function (data) {

    console.log(data);
    $('#comment').val(data.rol.descripcion);
    $('#id_rol').val(data.rol.id_rol);
    $('#conteinerPermisos input:checkbox').prop('checked',false);
    $('#conteinerPermisos input:checkbox').prop('disabled',true);
    for (var i = 0; i < data.permisos.length; i++) {

      $('#rol' + data.permisos[i].id_permiso ).prop('checked' ,true);
      // $('#rol' + data.permisos[i].id_permiso ).prop('disabled' ,true);
    }
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #fff');

    $('#btn-save-rol').hide();
    $('#boton-cancelar').text('SALIR');
    $('#myModalRol').modal('show');

    $('#comment').prop('readonly',true).removeClass('alerta');
    $('#myModalRol #alertaDescripcion').hide();
  })
})


/* Muestra ver más */
$(document).on('click','.detallePermiso',function(){
  var id_permiso = $(this).val();

  $('#myModalPermisos input').each(function(e){
    $(this).prop('readonly', true);

  })

  $.get('/permiso/' + id_permiso, function (data) {


    $('#commentPermiso').val(data.permiso.descripcion);
    $('#id_permiso').val(data.permiso.id_permiso);


    $('#conteinerRoles input:checkbox').prop('checked',false);
    $('#conteinerRoles input:checkbox').prop('disabled' ,true);


    for (var i = 0; i < data.roles.length; i++) {
      $('#permiso' + data.roles[i].id_rol ).prop('checked' ,true);
    }
    $('.modal-title').text('| VER MÁS');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #4FC3F7; color: #fff');

    $('#btn-save-permiso').hide();
    $('#cancelar_permiso').text('SALIR');
    $('#myModalPermisos').modal('show');

    $('#comment').prop('readonly',true);
    $('#commentPermiso').removeClass('alerta');
    $('#myModalPermisos #alertaDescripcion').hide();
  })
})

/*
get rol para modificar
*/
$(document).on('click','.modificarRol',function(){
  var id_rol = $(this).val();

  $('#myModalRol input').each(function(e){
    $(this).prop('readonly' , false);
  })
  $('#conteinerPermisos input:checkbox').prop('checked',false);
  $.get('/rol/' + id_rol, function (data) {

    console.log(data);
    $('#comment').val(data.rol.descripcion);
    $('#id_rol').val(data.rol.id_rol);
    for (var i = 0; i < data.permisos.length; i++) {
      $('#rol' + data.permisos[i].id_permiso ).prop('checked' ,true);
      $('#rol' + data.permisos[i].id_permiso ).prop('disabled',false);
    }
    $('.modal-title').text('| MODIFICAR ROL');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #FFB74D; color: #fff');
    $('#btn-save-rol').show();
    $('#btn-save-rol').text('ACEPTAR');
    $('#btn-save-rol').val('modificar');
    $('#boton-cancelar').text('CANCELAR');

    $('#comment').prop('readonly',false).removeClass('alerta');
    $('#myModalRol #alertaDescripcion').hide();


    $('#rol1').prop('disabled',false);

    $('#btn-save-rol').attr('class','btn btn-warningModificar');
    $('#myModalRol').modal('show');

  })
})

/*
get permiso para modificar
*/
$(document).on('click','.modificarPermiso',function(){

  var id_permiso = $(this).val();


  $.get('/permiso/' + id_permiso, function (data) {

    $('#myModalPermisos input').each(function(e){
      $(this).prop('checked', false);
      $(this).prop('disabled', false);
      $(this).prop('readonly' , false);
    })

    $('#conteinerRoles input:checkbox').prop('checked',false);
    console.log(data);
    $('#commentPermiso').val(data.permiso.descripcion);
    $('#id_permiso').val(data.permiso.id_permiso);

    for (var i = 0; i < data.roles.length; i++) {
      $('#permiso' + data.roles[i].id_rol ).prop('checked' ,true);
      $('#permiso' + data.roles[i].id_rol ).prop('disabled' ,false);
    }

    $('.modal-title').text('| MODIFICAR PERMISO');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #FFB74D; color: #fff');
    $('#btn-save-permiso').show();
    $('#btn-save-permiso').text('ACEPTAR');
    $('#btn-save-permiso').val('modificar');
    $('#cancelar_permiso').text('CANCELAR');

    $('#btn-save-permiso').attr('class','btn btn-warningModificar');
    $('#myModalPermisos').modal('show');
    $('#commentPermiso').removeClass('alerta');
    $('#myModalPermisos #alertaDescripcion').hide();

  });
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| ROLES Y PERMISOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

  $('#modalAyuda').modal('show');

});

$('#btn-add').click(function(e){
  e.preventDefault();

  $('#mensajeExito').hide();

  $('.modal-title').text('| NUEVO ROL');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-save-rol').show();
  $('#btn-save-rol').removeClass('btn btn-warningModificar');
  $('#btn-save-rol').addClass('btn btn-successAceptar');
  $('#btn-save-rol').val('add');
  $('#btn-save-rol').text('ACEPTAR');
  $('#boton-cancelar').text('CANCELAR');

  $('#myModalRol input').each(function(e){
    $(this).prop('checked', false);
    $(this).prop('readonly', false);
    $(this).prop('disabled', false);
  })

  $('#comment').removeClass('alerta');
  $('#myModalRol #alertaDescripcion').hide();


  $('#frmRol').trigger("reset");
  $('#myModalRol').modal('show');
});

$('#btn-add2').click(function(e){
  e.preventDefault();

  $('#mensajeExito').hide();

  $('#frmPermisos').trigger('reset');
  $('.modal-title').text('| NUEVO PERMISO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
  $('#btn-save-permiso').show();
  $('#btn-save-permiso').removeClass('btn btn-warningModificar');
  $('#btn-save-permiso').addClass('btn btn-successAceptar');
  $('#btn-save-permiso').val("add");
  $('#btn-save-permiso').text('ACEPTAR');
  $('#cancelar_permiso').text('CANCELAR');

  $('#commentPermiso').val('');

  $('#commentPermiso').removeClass('alerta');
  $('#myModalPermisos #alertaDescripcion').hide();

  $('#myModalPermisos input').each(function(e){
    $(this).prop('checked', false);
    $(this).prop('disabled', false);
    $(this).prop('readonly', false);
  })


  $('#myModalPermisos').modal('show');


});

$('#btn-save-rol').click(function(e){

  var permisos=[];
  var my_url='rol/guardar';
  var state=$('#btn-save-rol').val();

  $('#mensajeExito').hide();
  $('#mensajeExito .cabeceraMensaje').removeClass('modificar');

  $('#myModalRol input:checked').each(function(e){
    permisos.push($(this).val());
  })


  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })



  if (state == "modificar"){
    formData = {
      id: $('#id_rol').val(),
      permisos:  permisos,
      descripcion: $('#comment').val(),

    };
    my_url='rol/modificar' ;
  }
  else {
    formData={
      permisos:  permisos,
      descripcion: $('#comment').val(),
    }



  }

  console.log(formData);

  $.ajax({
    type: "POST",
    url: my_url,
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#frmRol').trigger("reset");
      $('#myModalRol').modal('hide');
      var permisos='';
      for (var i = 0; i < data.permisos.length; i++) {
        permisos+= '· ' + data.permisos[i].descripcion + '<br>';
      }

      var renglon='<tr id="'+ data.rol.id_rol +'"><td class="col-xs-7">' +  data.rol.descripcion +'<a id="popoverData" class="pop btn" href="" data-content="'+ permisos  +'" rel="popover" data-placement="bottom" data-original-title="Permisos asociados" data-trigger="hover"> <span class="badge"><i class="fa fa-user"></i></span></a></td>' +
      '<td class="col-xs-5"><button class="btn btn-info detalleRol" value="'+data.rol.id_rol +'"><i class="fa fa-fw fa-search-plus"></i></button>   <button class="btn btn-warning modificarRol" value="' + data.rol.id_rol +'"><i class="fa fa-pencil-alt"></i></button>  ' +
      '<button type="button" class="btn btn-danger openEliminar" data-tipo="rol" value="' + data.rol.id_rol + '"><i class="fa fa-trash-alt"></i></button></td></tr>';
      var checkbox=' <div class="checkbox"> <label><input id="permiso'+data.rol.id_rol+'" type="checkbox" value="'+ data.rol.id_rol +'">' +  data.rol.descripcion +'</label></div> ';

      $('#conteinerRoles #permiso'+data.rol.id_rol).closest('div').remove();
      $('#conteinerRoles').append(checkbox);


      if (state == "add"){
        $('#mensajeExito h3').text('Creación Exitosa');
        $('#mensajeExito p').text('Se ha creado correctamente el rol ' + data.rol.descripcion +  '.');


        $('#cuerpoTablaRoles').append(renglon);
        $('.pop').popover({
          html:true
        });
      }else{ //if user updated an existing record
        $('#mensajeExito h3').text('Modificación Exitosa');
        $('#mensajeExito p').text('Se ha modificado correctamente el rol ' + data.rol.descripcion +  '.');
        $('#mensajeExito .cabeceraMensaje').addClass('modificar');

        $("#cuerpoTablaRoles #"+ data.rol.id_rol).replaceWith(renglon);
        $("#cuerpoTablaRoles tr").each(function(){

        })
        $('.pop').popover({
          html:true
        })
      }

      $("#tablaRoles").trigger("update");
      $("#tablaRoles th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

      //Mostrar éxito


      $('#mensajeExito').show();

    },
    error: function (data) {

      $('#myModalRol #alertaDescripcion').hide();
      console.log('Error:', data);
      var response = JSON.parse(data.responseText);

      if(typeof response.existe !== 'undefined'){
        $('#myModalRol #alertaDescripcion span').text(response.existe[0]);
        $('#myModalRol #alertaDescripcion').show();
        $('#comment').addClass('alerta');
      }

      if(typeof response.descripcion !== 'undefined'){
        $('#myModalRol #alertaDescripcion span').text(response.descripcion[0]);
        $('#myModalRol #alertaDescripcion').show();
        $('#comment').addClass('alerta');

      }

    }


  });
});

$('#btn-save-permiso').click(function(e){
  var roles=[];
  var state=$('#btn-save-permiso').val();
  var my_url= 'permiso/guardar';

  $('#mensajeExito').hide();
  $('#mensajeExito .cabeceraMensaje').removeClass('modificar');

  $('#myModalPermisos input:checked').each(function(e){
    roles.push($(this).val());
  })

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  formData={
    roles:  roles,
    descripcion: $('#commentPermiso').val(),
  }


  if(state == "modificar"){
    formData = {
      id: $("#id_permiso").val(),
      roles:  roles,
      descripcion: $('#commentPermiso').val(),
    } ;
    my_url= 'permiso/modificar';
  };


  console.log(formData);
  $.ajax({
    type: "POST",
    url: my_url,
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#frmResultado').trigger("reset");
      $('#myModalPermisos').modal('hide');

      var renglon='<tr id="'+ data.permiso.id_permiso +'"><td class="col-xs-7">' +  data.permiso.descripcion +'</td><td class="col-xs-5"><button class="btn btn-info detallePermiso" value="'+data.permiso.id_permiso +'"><i class="fa fa-fw fa-search-plus"></i></button><button class="btn btn-warning modificarPermiso" value="' +data.permiso.id_permiso +'"><i class="fa fa-fw fa-pencil-alt"></i></button>' +
      '<button class="btn btn-danger openEliminar" data-tipo="permiso" value="' + data.permiso.id_permiso +'"><i class="fa fa-fw fa-trash-alt"></i></button></td></tr>';
      var checkbox=' <div class="checkbox"> <label><input id="rol'+ data.permiso.id_permiso +'" type="checkbox" value="'+  data.permiso.id_permiso +'">' +   data.permiso.descripcion +'</label></div> ';
      $('#conteinerPermisos #rol'+data.permiso.id_permiso).closest('div').remove();
      $('#conteinerPermisos').append(checkbox);
      var roles='';

      if (state == "modificar"){
        $('#mensajeExito h3').text('Modificación Exitosa');
        $('#mensajeExito p').text('Se ha modificado correctamente el permiso ' + data.permiso.descripcion +  '.');
        $('#mensajeExito .cabeceraMensaje').addClass('modificar');
        // $('#mensajeExito').css('background-color','#FFB74D');

        $("#cuerpoTablaPermisos #"+ data.permiso.id_permiso).replaceWith(renglon);

        /*
        opcion 1
        */
        $('#cuerpoTablaRoles tr').each(function(){
          var id=$(this).attr("id");
          $.get('/rol/' + id, function (data) {
            var permisos='';
            for (var i = 0; i < data.permisos.length; i++) {
              permisos+= '· ' + data.permisos[i].descripcion + '<br>';

            }
            $('#cuerpoTablaRoles #'+data.rol.id_rol).find('a').remove();
            $('#cuerpoTablaRoles #'+data.rol.id_rol).find('td:first')
            .append($('<a>')
            .attr('id' , 'popoverData')
            .addClass('pop').addClass('btn')
            .attr('href' , "")
            .attr("data-content", permisos)
            .attr("data-placement" , "bottom")
            .attr("rel","popover")
            .attr("data-original-title" , "Permisos asociados")
            .attr("data-trigger" , "hover")
            .append($('<span>')
            .addClass("badge")
            .append($('<i>')
            .addClass("fa").addClass("fa-user")
          )
        )
      )

      $('.pop').popover({
        html:true
      });


    })


  })





}else{ //if user updated an existing record
  $('#cuerpoTablaPermisos').append(renglon);

  $('#mensajeExito h3').text('Creación Exitosa');
  $('#mensajeExito p').text('Se ha creado correctamente el permiso ' + data.permiso.descripcion +  '.');
  // $('#mensajeExito div').css('background-color','#4DB6AC');

  for (var i = 0; i < data.roles.length; i++) {
    var contenido =$('#cuerpoTablaRoles #' + data.roles[i].id_rol + ' a').data("content");
    roles= contenido + '· ' + data.permiso.descripcion + '<br>';

    $('#cuerpoTablaRoles #' + data.roles[i].id_rol + ' a').remove();
    $('#cuerpoTablaRoles #' + data.roles[i].id_rol ).find('td:first')
    .append($('<a>')
    .attr('id' , 'popoverData')
    .addClass('pop').addClass('btn')
    .attr('href' , "")
    .attr("data-content", roles)
    .attr("data-placement" , "bottom")
    .attr("rel","popover")
    .attr("data-original-title" , "Permisos asociados")
    .attr("data-trigger" , "hover")
    .append($('<span>')
    .addClass("badge")
    .append($('<i>')
    .addClass("fa").addClass("fa-user")
  )
  )
)

$('.pop').popover({
  html:true
});

}
}

$("#tablaPermisos").trigger("update");
$("#tablaPermisos th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

//Mostrar éxito
$('#mensajeExito').show();


},
error: function (data) {

  $('#myModalPermisos #alertaDescripcion').hide();
  console.log('Error:', data);
  var response = JSON.parse(data.responseText);

  if(typeof response.existe !== 'undefined'){
    $('#myModalPermisos #alertaDescripcion span').text(response.existe[0]);
    $('#myModalPermisos #alertaDescripcion').show();
    $('#commentPermiso').addClass('alerta');

  }

  if(typeof response.descripcion !== 'undefined'){
    $('#myModalPermisos #alertaDescripcion span').text(response.descripcion[0]);
    $('#myModalPermisos #alertaDescripcion').show();
    $('#commentPermiso').addClass('alerta');

  }

}

});
});

/*
busqueda de roles
*/
$('#buscarRol').click(function(e){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })

  formData={
    rol: $('#buscadorRol').val(),
  }
  console.log(formData);
  $.ajax({
    type: "POST",
    url: 'roles/buscar',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#cuerpoTablaRoles').empty();
      var resultado='';
      if(data.roles.length ==0){
        $('#tituloBusquedaRoles').text('No se encontraron roles');
        $('#cuerpoTablaRoles').empty();
      }else{
        $('#tituloBusquedaRoles').text('Se encontraron ' + data.roles.length + ' roles');
        for (var i = 0; i < data.roles.length; i++) {
          var permisos='';


          for(var j=0; j <data.roles[i].permisos.length; j++){
            permisos+= '· ' + data.roles[i].permisos[j] + '<br>';

          }

          $('#cuerpoTablaRoles')
          .append($('<tr>')
          .attr("id" , data.roles[i].rol.id_rol)
          .append($('<td>')
          .addClass('col-xs-7')
          .text(data.roles[i].rol.descripcion)
          .append($('<a>')
          .attr('id' , 'popoverData')
          .addClass('pop').addClass('btn')
          .attr('href' , "")
          .attr("data-content", permisos)
          .attr("data-placement" , "bottom")
          .attr("rel","popover")
          .attr("data-original-title" , "Permisos asociados")
          .attr("data-trigger" , "hover")
          .append($('<span>')
          .addClass("badge")
          .append($('<i>')
          .addClass("fa").addClass("fa-user")
        )
      )
    )

  )
  .append($('<td>')
  .addClass('col-xs-5')
  .addClass('accionesTD')
  .append($('<button>')
  .append($('<i>')
  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
)
.append($('<span>').text(' VER MÁS'))
.addClass('btn').addClass('btn-info').addClass('detalleRol')
.val(data.roles[i].rol.id_rol)
)
.append($('<span>').text(' '))
.append($('<button>')
.append($('<i>')
.addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
)
.append($('<span>').text(' MODIFICAR'))
.addClass('btn').addClass('btn-warning').addClass('modificarRol')
.val(data.roles[i].rol.id_rol)
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
.val(data.roles[i].rol.id_rol)
)
)

)

$('.pop').popover({
  html:true
});


}
}

$("#tablaRoles").trigger("update");
$("#tablaRoles th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');


},
error: function (data) {
  console.log('Error:', data);
}


});
});


/*
busqueda de permisos
*/
$('#buscarPermiso').click(function(e){
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
    }
  })
  formData={
    permiso: $('#buscadorPermiso').val(),
  }
  console.log(formData);
  $.ajax({
    type: "POST",
    url: 'permisos/buscar',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log(data);
      $('#cuerpoTablaPermisos').empty();
      var resultado='';
      if(data.permiso.length ==0){
        $('#tituloBusquedaPermisos').text('No se encontraron roles');
        $('#cuerpoTablaPermisos').empty();
      }else{
        $('#tituloBusquedaPermisos').text('Se encontraron ' + data.permiso.length + ' permisos');
        for (var i = 0; i < data.permiso.length; i++) {

          $('#cuerpoTablaPermisos')
          .append($('<tr>')
          .attr('id',data.permiso[i].id_permiso)
          .append($('<td>')
          .addClass('col-xs-7')
          .text(data.permiso[i].descripcion)
        )
        .append($('<td>')
        .addClass('col-xs-5')
        .append($('<button>')
        .append($('<i>')
        .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
      )
      .append($('<span>').text(' VER MÁS'))
      .addClass('btn').addClass('btn-info').addClass('detallePermiso').addClass('info')
      .val(data.permiso[i].id_permiso)
    )
    .append($('<span>').text(' '))
    .append($('<button>')
    .append($('<i>')
    .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
  )
  .append($('<span>').text(' MODIFICAR'))
  .addClass('btn').addClass('btn-warning').addClass('modificarPermiso')
  .val(data.permiso[i].id_permiso)
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
.val(data.permiso[i].id_permiso)
.attr('data-tipo','permiso')
)
)
)
}
}
$("#tablaPermisos").trigger("update");
$("#tablaPermisos th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fa').addClass('fa-sort');

},
error: function (data) {
  console.log('Error:', data);
}


});
});


/** EVENTOS PARA VALIDAR LOS CAMPOS ***/
$('#comment').focusin(function(){
  $(this).removeClass('alerta');
  $('#myModalRol #alertaDescripcion').hide();
});
$('#commentPermiso').focusin(function(){
  $(this).removeClass('alerta');
  $('#myModalPermisos #alertaDescripcion').hide();
});
