$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionBingo').removeClass();
  $('#gestionBingo').addClass('subMenu2 collapse in');

  $('#gestionBingo').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Gestión de Premios');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcGestionarBingo').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcGestionarBingo').addClass('opcionesSeleccionado');

  $('#btn-buscar-premio').trigger('click');

  $('#btn-buscar-canon').trigger('click');

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

//Opacidad del modal al minimizar
$('#btn-minimizarMaquinas').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
    $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

//si presiono enter y el modal esta abierto se manda el formulario
$(document).on("keypress" , function(e){
  if(e.which == 13 && $('#modalFormula').is(':visible')) {
    e.preventDefault();
    $('#btn-guardar').click();
  }
})
//enter en buscador
$('contenedorFiltros input').on("keypress" , function(e){
  if(e.which == 13) {
    e.preventDefault();
    $('#').click();
  }
})

$('#columna input').on('focusout' , function(){
  if ($(this).val() == ''){
    mostrarErrorValidacion($(this) , 'El campo no puede estar en blanco' , false);
  }
});

$('#columna input').focusin(function(){
  $(this).removeClass('alerta');

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| FÓRMULAS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//busqueda premio
$('#btn-buscar-premio').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosPremio .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosPremios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData = {
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'buscarPremio',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTablaPremios tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTablaPremios').append(generarFilaTablaPremio(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

//busqueda canon
$('#btn-buscar-canon').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosCanon .activa').attr('value'),orden: $('#tablaResultadosCanon .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosCanon th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData = {

    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'buscarCanon',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTablaCanon tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTablaCanon').append(generarFilaTablaCanon(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

//Mostrar modal para agregar premio
$('#btn-nuevo-premio').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();
  $('#btn-guardar-premio').val("nuevo");
  $('#frmNuevoPremio').trigger('reset');
  $('.terminoNuevoPremio').remove();
  $('#btn-guardar-premio').addClass('btn btn-successAceptar');
  $('.modal-title').text('| AGREGAR NUEVO PREMIO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  $('#modalNuevoPremio').modal('show');

});

//Mostrar modal para agregar canon
$('#btn-nuevo-canon').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();
  $('#btn-guardar-canon').val("nuevo");
  $('#frmCanon').trigger('reset');
  $('.terminoCanon').remove();
  $('#btn-guardar-canon').addClass('btn btn-successAceptar');
  $('.modal-title').text('| AGREGAR CANON');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');

  $('#modalCanon').modal('show');

});

//Mostrar modal editar premio
$(document).on('click','.modificar-premio',function(){
    $('#mensajeExito').hide();
    $('#frmNuevoPremio').trigger('reset');
    $('.modal-title').text('| MODIFICAR PREMIO');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d; color: #fff;');
    var id_premio = $(this).val();
    $.get("obtenerPremio/" + id_premio, function(data){
        console.log(data);
        $('#id_premio').val(id_premio);//campo oculto
        $('#btn-guardar-premio').val("modificar");
        $('#modalNuevoPremio').modal('show');
        $('#nombre_premio').val(data.nombre_premio);
        $('#porcentaje_premio').val(data.porcentaje);
        $('#bola_tope').val(data.bola_tope);
        $('#tipo_premio').val(data.tipo_premio);

      });

});

//Mostrar modal editar canon
$(document).on('click','.modificar-canon',function(){
    $('#mensajeExito').hide();
    $('#frmCanon').trigger('reset');
    $('.modal-title').text('| MODIFICAR CANON');
    $('.modal-header').attr('style','font-family: Roboto-Black; background: #ff9d2d; color: #fff;');
    var id_canon = $(this).val();
    $.get("obtenerCanon/" + id_canon, function(data){
        console.log(data);
        $('#id_canon').val(id_canon);//campo oculto
        $('#btn-guardar-canon').val("modificar");
        $('#modalCanon').modal('show');
        $('#fecha_inicio').val(data.fecha_inicio);
        $('#porcentaje_canon').val(data.porcentaje);
      });
});

//envio de datos a servidor (guardar / modificar) PREMIO
$('#btn-guardar-premio').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var formData = {
      nombre_premio: $('#nombre_premio').val(),
      porcentaje_premio: $('#porcentaje_premio').val(),
      bola_tope: $('#bola_tope').val(),
      tipo_premio: $('#tipo_premio').val(),
      id_premio: $('#id_premio').val(),
      casino_premio:$('#casino_premio').val(),
    }

    var state = $('#btn-guardar-premio').val();
    var type = "POST";

    var url; //url de destino, dependiendo si se esta creando o modificando una sesión

    state == 'nuevo' ? url =  'guardarPremio' : url = 'modificarPremio';

    console.log(formData);

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (state == "nuevo"){//si se esta creando guarda en tabla
              $('#mensajeExito P').text('El nuevo premio fue AGREGADO correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              $('#cuerpoTablaPremios').append(generarFilaTablaPremio(data.premio));
            }else{ //Si está modificando
              $('#cuerpoTablaPremios #' +data.premio.id_premio ).replaceWith(generarFilaTablaPremio(data.premio));
              $('#mensajeExito p').text('El premio fue MODIFICADO correctamente');
              $('#mensajeExito div').css('background-color','#FFB74D');
            }
            $('#frmNuevoPremio').trigger("reset");
            $('#modalNuevoPremio').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = data.responseJSON.errors;

            $('#columna .row').each(function(index,value){

              if(typeof response.nombre_premio !== 'undefined'){
                  mostrarErrorValidacion($('#nombre_premio'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.porcentaje_premio !== 'undefined'){
                    mostrarErrorValidacion($('#porcentaje_premio'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.bola_tope !== 'undefined'){
                      mostrarErrorValidacion($('#bola_tope'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.tipo_premio !== 'undefined'){
                        mostrarErrorValidacion($('#tipo_premio'),'El campo no puede estar en blanco.' ,true);
                }
                if(typeof response.casino_premio !== 'undefined'){
                          mostrarErrorValidacion($('#casino_premio'),'El campo no puede estar en blanco.' ,true);
                  }
            })

        }
    });
});

//envio de datos a servidor (guardar / modificar) CANON
$('#btn-guardar-canon').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var formData = {
      fecha_inicio: $('#fecha_inicio').val(),
      id_casino: $('#casino_canon').val(),
      porcentaje_canon: $('#porcentaje_canon').val(),
      id_canon: $('#id_canon').val(),
    }

    var state = $('#btn-guardar-canon').val();
    var type = "POST";

    var url; //url de destino, dependiendo si se esta creando o modificando una sesión

    state == 'nuevo' ? url =  'guardarCanon' : url = 'modificarCanon';

    console.log(formData);

    $.ajax({
        type: "POST",
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            console.log(data);
            if (state == "nuevo"){//si se esta creando guarda en tabla
              $('#mensajeExito P').text('El canon fue AGREGADO correctamente.');
              $('#mensajeExito div').css('background-color','#4DB6AC');
              $('#cuerpoTablaCanon').append(generarFilaTablaCanon(data.canon));
            }else{ //Si está modificando
              $('#cuerpoTablaCanon #' +data.canon.id_canon ).replaceWith(generarFilaTablaCanon(data.canon));
              $('#mensajeExito p').text('El canon fue MODIFICADO correctamente');
              $('#mensajeExito div').css('background-color','#FFB74D');
            }
            $('#frmCanon').trigger("reset");
            $('#modalCanon').modal('hide');
            //Mostrar éxito
            $('#mensajeExito').show();
        },
        error: function (data) {
            var response = data.responseJSON.errors;

            $('#columna2 .row').each(function(index,value){

              if(typeof response.fecha_inicio !== 'undefined'){
                  mostrarErrorValidacion($('#fecha_inicio'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.porcentaje_canon !== 'undefined'){
                    mostrarErrorValidacion($('#porcentaje_canon'),'El campo no puede estar en blanco.' ,true);
                }
              if(typeof response.id_casino !== 'undefined'){
                    mostrarErrorValidacion($('#casino_canon'),'El campo no puede estar en blanco.' ,true);
                }
            })

        }
    });
});

//Modal de eliminar un premio
$(document).on('click','.eliminar-premio',function(){
    var id = $(this).val();
    console.log(id);
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    $('#btn-eliminar-premio').val(id);
    $('#modalEliminarPremio').modal('show');
    $('#mensajeEliminarPremio').text('¿Seguro que desea eliminar el premio "' + $(this).parent().parent().find('td:first').text()+'"?');

});

//Elimina un premio
$('#btn-eliminar-premio').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "eliminarPremio/" + id,
        success: function (data) {

          //Remueve de la tabla
          $('#cuerpoTablaPremios #'+ id).remove();
          $("#tablaResultadosPremios").trigger("update");

          $('#modalEliminarPremio').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});


//Modal de eliminar canon
$(document).on('click','.eliminar-canon',function(){
    var id = $(this).val();
    console.log(id);
    $('.modal-title').text('ADVERTENCIA');
    $('.modal-header').attr('style','font-family: Roboto-Black; color: #EF5350');
    $('#btn-eliminar-canon').val(id);
    $('#modalEliminarCanon').modal('show');
    $('#mensajeEliminarCanon').text('¿Seguro que desea eliminar el canon de la fecha "' + $(this).parent().parent().find('td:first').text()+'"?');

});

//Elimina un premio
$('#btn-eliminar-canon').click(function (e) {
    var id = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "eliminarCanon/" + id,
        success: function (data) {

          //Remueve de la tabla
          $('#cuerpoTablaCanon #'+ id).remove();
          $("#tablaResultadosCanon").trigger("update");

          $('#modalEliminarCanon').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});


$(document).on('click','#tablaResultadosPremio thead tr th[value]',function(e){
  $('#tablaResultadosPremio th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultadosPremio th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$(document).on('click','#tablaResultadosCanon thead tr th[value]',function(e){
  $('#tablaResultadosCanon th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-desc').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-desc')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-asc').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultadosCanon th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

//Generar fila con los datos de los premios
function generarFilaTablaPremio(premio){
  var fila = $(document.createElement('tr'));
      fila.attr('id', premio.id_premio)
        .append($('<td>')
        .addClass('col-xs-2')
            .text(premio.nombre_premio)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(premio.porcentaje)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(premio.bola_tope)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(premio.tipo_premio)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(premio.nombre)
        )
        .append($('<td>')
          .addClass('col-xs-2')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                )
                .append($('<span>').text(' MODIFICAR'))
                .addClass('btn').addClass('btn-warning').addClass('btn-detalle').addClass('modificar-premio')
                .attr('value',premio.id_premio)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa')
                    .addClass('fa-trash-alt')
                )
                .append($('<span>').text(' ELIMINAR'))
                .addClass('btn').addClass('btn-danger').addClass('btn-borrar').addClass('eliminar-premio')
                .attr('value',premio.id_premio)
            )
        )


        return fila;
}
//Generar fila con los datos de canon
function generarFilaTablaCanon(canon){
  var fila = $(document.createElement('tr'));
      fila.attr('id', canon.id_canon)
        .append($('<td>')
        .addClass('col-xs-4')
            .text(canon.fecha_inicio)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(canon.porcentaje)
        )
        .append($('<td>')
          .addClass('col-xs-4')
          .text(canon.nombre)
        )
        .append($('<td>')
          .addClass('col-xs-2')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                )
                .append($('<span>').text(' MODIFICAR'))
                .addClass('btn').addClass('btn-warning').addClass('btn-detalle').addClass('modificar-canon')
                .attr('value',canon.id_canon)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa')
                    .addClass('fa-trash-alt')
                )
                .append($('<span>').text(' ELIMINAR'))
                .addClass('btn').addClass('btn-danger').addClass('btn-borrar').addClass('eliminar-canon')
                .attr('value',canon.id_canon)
            )
        )


        return fila;
}
