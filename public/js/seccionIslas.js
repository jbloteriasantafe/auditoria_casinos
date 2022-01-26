$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#gestionarMTM').removeClass();
  $('#gestionarMTM').addClass('subMenu2 collapse in');
  $('#gestionarMTM').siblings('div.opcionesHover').attr('aria-expanded','true');
  $('.tituloSeccionPantalla').text('Islas');
  $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcIslas').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcIslas').addClass('opcionesSeleccionado');

  limpiarModal();
  $('#btn-buscar').trigger('click');
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

$('.modal').on('hidden.bs.modal', function(){//se ejecuta cuando se oculta modal con clase .modal
  ocultarErrorValidacion($('#casino'));
  ocultarErrorValidacion($('#sector'));
  ocultarErrorValidacion($('#nro_isla'));
})

//Quitar eventos de la tecla Enter
$("#contenedorFiltros input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

//Quitar eventos de la tecla Enter
$("#modalIsla input").on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

$('#casino').on('change' , function(e,id_sector){
    var id_casino=$(this).val();
    if(id_casino == 0){
      $('#buscadorMaquina').borrarDataList();
    }else{
      $('#buscadorMaquina').generarDataList('http://' +window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);
      $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
      $('#sector option').remove();
      $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + id_casino, function(data){
        for (var i = 0; i < data.sectores.length; i++) {
          $('#sector').append($('<option>')
                            .val(data.sectores[i].id_sector)
                            .text(data.sectores[i].descripcion)
                        )
        }
        $('#sector').val(id_sector);
      })
    }
})

/**************************************************
  TODOS LOS EVENTOS DEL INPUT MÁQUINA
**************************************************
 Evento para pasarle el ID de la isla en el datalist al input
    Cada vez que se hace el input se controla si un option del datalist fue seleccionado.
    Si fue seleccionado se compara con el input para sacarle el id.
*/

//Botón Cancelar input Islas
$('#cancelarMaquina').click(function(){
    $('#buscadorMaquina').prop("readonly", false); //Se habilita el input
    $('#buscadorMaquina').setearElementoSeleccionado(0 , "");
});

function generarHistorialMov(id_isla){
  var estadosMov = $('<select>').addClass('form-control').attr('id','estadosMovimientos');

  $.get("logIsla/obtenerHistorial/" + id_isla, function(data){
        estadosMov.children().remove();
        for(i=0 ; i<data.estados.length ; i++){
          console.log(data.estados[i].id_estado_relevamiento);
          estadosMov.append($('<option>').val(data.estados[i].id_estado_relevamiento).text(data.estados[i].descripcion));
        }

        // console.log(selectMov);
        $('.columnaMovimientos').children().remove();
        for(i=0; i<data.historial.length; i++){
          console.log(data.historial[i]);
          $('.columnaMovimientos')
          .append($('<div>').addClass('unMovimiento')
          .append($('<div>').addClass('col-md-4').css('padding-bottom','15px')
          .append($('<span>').attr('value',data.historial[i].id_log_isla).text(data.historial[i].fecha)))
          .append($('<div>').addClass('col-md-8').css('padding-bottom','15px')
          .append(estadosMov.clone().val(data.historial[i].id_estado_relevamiento))))
          .append($('<br>'));
        }
  });

}

//Agregar Máquina
$('.agregarMaquina').click(function(){
      //Crear un item de la lista
      console.log('agregar maquina');
      var id = $('#buscadorMaquina').obtenerElementoSeleccionado(); // 25 - KONAMI (ASD-123)
      if(!existeEnDataList(id)){
       $.get('http://' + window.location.host +"/maquinas/obtenerMTMReducido/" + id, function(data) {

          agregarMaquina(data.id_maquina,data.nro_admin,data.marca,data.modelo);

          $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia buscador
        })
      }else {
        $('#buscadorMaquina').setearElementoSeleccionado(0 , "");//se limpia
      }
});

function existeEnDataList(id){
  var bandera = false;
  $('#listaMaquinas li').each(function(){
      if ($(this).val() ==  id)
        bandera = true;
  });

  return bandera;
}

$(document).on('click','.borrarMaquina',function(){
  $(this).parent().parent().remove();
});

$('#buscadorCasino').on('change' , function (){
   if($(this).val() != 0){
     $.get('http://' + window.location.host + "/sectores/obtenerSectoresPorCasino/" + $(this).val(), function(data){
       $('#buscadorSector option').remove();
       $('#buscadorSector').append($('<option value="0">-Todos los sectores-</option>'));
       for (var i = 0; i < data.sectores.length; i++) {
         $('#buscadorSector').append($('<option>')
         .val(data.sectores[i].id_sector)
         .text(data.sectores[i].descripcion)
       )
     }
   })
   }
 })

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
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
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    nro_isla: $('#buscadorNroIsla').val(),
    cantidad_maquinas: $('#buscadorCantMaquinas').val(),
    casino: $('#buscadorCasino').val(),
    sector: $('#buscadorSector').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  console.log(formData);

  $.ajax({
      type: 'POST',
      url: 'http://' + window.location.host + '/islas/buscarIslas',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTabla tr').remove();

          for (var i = 0; i < resultados.data.length; i++){
            $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i],resultados.data[i].sector));
          }

          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.cantidad_maquinas !== 'undefined'){
          mostrarErrorValidacion($('#buscadorCantMaquinas') , response.cantidad_maquinas[0] , true);
        }
      }
    });
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();
	$('#modalAyuda').modal('show');
});

$('#btn-nuevo').click(function(e){
  $('#mensajeExito').hide();
  e.preventDefault();
  limpiarModal();
  reiniciarSector();
  habilitarControles(true);
  $('#btn-guardar').val("nuevo");
  $('#btn-guardar').show();
  $('.movimientos').hide();
  $('#modalIsla .modal-title').text('| NUEVA ISLA');
  $('#modalIsla .modal-header').attr('style','background-color: #6dc7be; color: #fff');
  $('#btn-guardar').removeClass();
  $('#btn-guardar').addClass('btn btn-successAceptar');
  $('#modalIsla').modal('show');
});

/* DIVIDIR ISLAS */
$(document).on('click','.dividir',function(){
    var nro_isla = $(this).attr('data-isla');
    var id_casino = $(this).attr('data-casino');
    var id_isla = $(this).val();

    //Setear datos al boton de enviar
    $('#btn-aceptarDividir').val(id_isla);
    $('#btn-aceptarDividir').attr('data-casino', id_casino);
    $('#btn-aceptarDividir').attr('data-isla', nro_isla);

    //Limpiar el modal
    $('.subisla').not('#moldeSubisla').remove();
    $('#selectSubisla option').remove();

    //Buscador de máquinas
    $('#inputMaquina').generarDataList('http://' + window.location.host + '/maquinas/buscarMaquinaPorNumeroMarcaYModelo/' + id_casino, "resultados","id_maquina" ,"nro_admin" , 2, true);

    $('#moldeSubisla .selectSector option').remove();

    //Setear sectores
    $.get('sectores/obtenerSectoresPorCasino/' + id_casino, function(data){
        for (var i = 0; i < data.sectores.length; i++) {
            var option = $('<option>').val(data.sectores[i].id_sector).text(data.sectores[i].descripcion);
            $('#moldeSubisla .selectSector').append(option);
        }
    });

    $.get('islas/obtenerIsla/'+ id_isla, function(data) {
      //Setear datos de la isla
      $('#d_nro_isla').val(data.isla.nro_isla);
      $('#d_casino').val(data.casino.nombre);
      $('#d_sector').val(data.sector.descripcion);
    });

    $.get('islas/listarMaquinasPorNroIsla/' + nro_isla + '/' + id_casino, function(data) {
        //Setear la nueva cantidad de subislas
        cantidad_subislas = parseInt(data.islas.length) + 1;

        console.log(data.islas.length);

        $('#d_maquinas').val(data.cantidad_maquinas);

        //Setear el select de agregar máquina
        var subisla;

        //Generar las subislas existentes en el modal
        for (var i = 1; i < cantidad_subislas; i++) {
            subisla = generarSubisla(i, data.islas[i-1]);
            $('#subislas').append(subisla);
        }

        //Generar la nueva subisla en el modal
        subisla = generarSubisla(cantidad_subislas);
        subisla.find('.contenedorSI').css('border-color','#FF9100');
        $('#subislas').append(subisla);

        var option = $('<option>').val(cantidad_subislas).text(cantidad_subislas);
        $('#selectSubisla').append(option);
    });
    $('#modalDividirIsla').modal('show');
});

$('#modalDividirIsla').on('hidden.bs.modal',function(){
    $('#inputMaquina').setearElementoSeleccionado(0 , "");
});

$('#btn-agregarMaquina').click(function(e){
  var indice = $('#selectSubisla').val();
  var id_maquina = $('#inputMaquina').obtenerElementoSeleccionado();

  $.get('maquinas/obtenerMTM/'+ id_maquina , function(data){
    if(!existeMaquina(data.maquina.id_maquina)){
      var maquina = generarMaquinaSI(indice, data.maquina);

      $('#inputMaquina').setearElementoSeleccionado(0 , "");
      $('.subisla[data-sub="'+ indice +'"] table tbody').append(maquina);
      $('#d_maquinas').val(parseInt($('#d_maquinas').val())+1);
    }else {
      mostrarErrorValidacion($('#inputMaquina') , 'Ya existe la maquina elegida.' , true);
    }
  });

});

function generarSubisla(indice, subisla) {
    console.log("Entró: ", subisla);
    var moldeSubisla = $('#moldeSubisla').clone().show();

    moldeSubisla.removeAttr('id');
    moldeSubisla.attr('data-sub', indice);
    moldeSubisla.find('span').text(indice);

    if (typeof subisla === "undefined") {
      moldeSubisla.attr('id', 0);
    }else {
      console.log(subisla.codigo);
      moldeSubisla.attr('id',subisla.id_isla);
      moldeSubisla.find('input.codigo_subisla').val(subisla.codigo);
      moldeSubisla.find('.selectSector').val(subisla.id_sector);

      for (var i = 0; i < subisla.maquinas.length; i++) {
        var maquina = generarMaquinaSI(indice, subisla.maquinas[i]);
        moldeSubisla.find('table tbody').append(maquina);
      }
    }


    return moldeSubisla;
}

function generarMaquinaSI(indice, maquina) {
    var moldeMaquinaSI = $('#moldeMaquinaSI').clone().show();

    moldeMaquinaSI.removeAttr('id');

    moldeMaquinaSI.attr('data-maquina', maquina.id_maquina);
    moldeMaquinaSI.find('.nro_admin').text(maquina.nro_admin);
    moldeMaquinaSI.find('.marca_juego').text(maquina.marca_juego);

    moldeMaquinaSI.find('button.mover_izquierda').val(indice);
    moldeMaquinaSI.find('button.mover_derecha').val(indice);

    return moldeMaquinaSI;
}

var cantidad_subislas = 3;

$(document).on('click','.mover_izquierda', function() {
  var tr = $(this).closest('tr');
  var nro_subisla_actual = parseInt($(this).val());
  var nro_nueva_subisla =  nro_subisla_actual - 1;

  if (nro_nueva_subisla < 1 ) nro_nueva_subisla = cantidad_subislas;

  $(this).val(nro_nueva_subisla);
  $(this).siblings('button').val(nro_nueva_subisla);

  var subislaNueva = $('#subislas').find('div[data-sub="'+ nro_nueva_subisla +'"]');

  tr.remove();
  subislaNueva.find('table tbody').append(tr);
});

$(document).on('click','.mover_derecha', function() {
    //Cortar la fila de la máquina y moverla a la sub indicada
    var tr = $(this).closest('tr');
    var nro_subisla_actual = parseInt($(this).val());
    var nro_nueva_subisla =  nro_subisla_actual + 1;


    if (nro_nueva_subisla > cantidad_subislas ) nro_nueva_subisla = 1;

    $(this).val(nro_nueva_subisla);
    $(this).siblings('button').val(nro_nueva_subisla);

    console.log("Isla actual: ", nro_subisla_actual);
    console.log("Isla nueva: ", nro_nueva_subisla);



    var subislaNueva = $('#subislas').find('div[data-sub="'+ nro_nueva_subisla +'"]');

    tr.remove();
    subislaNueva.find('table tbody').append(tr);
});

$(document).on('click','.borrarMaquinaSI', function() {
    $(this).parent().parent().remove();
    $('#d_maquinas').val(parseInt($('#d_maquinas').val())-1);
});

$('#btn-aceptarDividir').click(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var subislas = [];
    var subislasModal = $('.subisla').not('#moldeSubisla');

    $.each(subislasModal, function(i){
        var maquinasModal = $(this).find('table tbody .maquinaSI').not('#moldeMaquinaSI');
        var maquinas = [];

        $.each(maquinasModal, function(i) {
            var maquina={
              id_maquina: $(this).attr('data-maquina'),
            }
            maquinas.push(maquina);
        });

        var subisla = {
          id_sector: $(this).find('.selectSector').val() ,
          id_isla: $(this).attr('id'),
          codigo: $(this).find('input.codigo_subisla').val(),
          maquinas: maquinas,
        };

        subislas.push(subisla);
    });

    var formData = {
      nro_isla: $(this).attr('data-isla'),
      id_casino: $(this).attr('data-casino'),
      detalles: subislas,
    }

    $.ajax({
        type: 'POST',
        url: 'islas/actualizarListaMaquinas',
        data: formData,
        dataType: 'json',
        success: function (data) {
            $('#modalDividirIsla').modal('hide');
            //exito
            $('#mensajeExito h3').text('ÉXITO DE MODIFICACIÓN');
            $('#mensajeExito .cabeceraMensaje').addClass('modificar');
            $('#mensajeExito p').text("Se ha dividido correctamente la isla.");
            $('#mensajeExito').show();
            //actualizo tabla
            $('#btn-buscar').trigger('click');
        },
        error: function (data) {
          console.log('Error: ', data);
          var response = JSON.parse(data.responseText);

          var filaError = -1;
          var i= 0;
          var subislasModal = $('.subisla').not('#moldeSubisla');

          $(subislasModal).each(function(){
            if(typeof response['codigo.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('input.codigo_subisla'),response['codigo.'+ i][0],true);
              console.log(response['codigo.'+ i][0]);
            }
            if(typeof response['duplicado.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('input.codigo_subisla'),response['duplicado.'+ i][0],true);
            }
            if(typeof response['sector.'+ i] !== 'undefined'){
              filaError=i;
              mostrarErrorValidacion($(this).find('.selectSector'),response['sector.'+ i][0],true);
            }

            i++;
          })
          console.log(filaError);
          if(filaError >= 0)
          {
            var pos = subislasModal.eq(filaError).offset().top;
            $("#modalDividirIsla").animate({ scrollTop: pos }, "slow");
          }

        }
    });
});

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
    limpiarModal();
    reiniciarSector();
    $('#modalIsla .modal-title').text('| VER MÁS');
    $('#modalIsla .modal-header').attr('style','background: #4FC3F7');
    $('.movimientos').show();
    var id_isla = $(this).val();

    $.get( 'http://' + window.location.host + "/islas/obtenerIsla/" + id_isla, function(data){
        console.log(data);
        mostrarIsla(data.isla,data.sector,data.maquinas);
        habilitarControles(false);
        $('#btn-guardar').hide();
        $('#modalIsla').modal('show');
    });
});

//Modal para modificar una ISLA
$(document).on('click','.modificar',function(){
    limpiarModal();
    $('#modalIsla .modal-title').text('| MODIFICAR ISLA');
    $('#modalIsla .modal-header').attr('style','background: #ff9d2d');
    $('#mensajeExito').hide();
    $('#btn-guardar').addClass('btn btn-warningModificar');
    $('#btn-guardar').show();
    $('.movimientos').show();

    var id_isla = $(this).val();
    generarHistorialMov(id_isla);

    $.get('http://' + window.location.host + "/islas/obtenerIsla/" + id_isla, function(data){
        console.log(data);
        mostrarIsla(data.isla,data.sector,data.maquinas);
        habilitarControles(true);
        $('#btn-guardar').val("modificar");
        $('#modalIsla').modal('show');
    });
});

//Borrar Isla y remover de la tabla
$(document).on('click','.eliminar',function(){
  $('#btn-eliminarModal').val($(this).val());
  $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    var id_isla = $(this).val();

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "islas/eliminarIsla/" + id_isla,
        success: function (data) {
          console.log(data);
          $('#isla' + id_isla).remove();
          $("#tablaIslas").trigger("update");

          $('#modalEliminar').modal('hide');
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
});

//Crear nueva Isla / actualizar si existe
$('#btn-guardar').click(function (e) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    var maquinas = [];

    $('#listaMaquinas li').each(function(){
      maquinas.push($(this).val());
    });

    var historial = [];
    $('.columnaMovimientos .unMovimiento').each(function(){
        var movimiento = {
          id_log_isla: $(this).find('span').attr('value'),
          id_estado_relevamiento: $(this).find('#estadosMovimientos option:selected').val(),
        }
        historial.push(movimiento);
    });

    var cantidad = $('#listaMaquinas li').length;

    var state = $('#btn-guardar').val();
    var type = "POST";
    var url = ((state == "modificar") ? 'http://' + window.location.host + '/islas/modificarIsla':'http://' + window.location.host + '/islas/guardarIsla');

    var formData = {
      id_isla: $('#id_isla').val(),
      nro_isla: $('#nro_isla').val(),
      cant_maquinas:  cantidad,
      casino: $('#casino').val(),
      sector: $('#sector').val(),
      codigo: $('#ncodigo').val(),
      maquinas: maquinas,
      historial: historial,
    }
    console.log(formData);
    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            var isla = generarFilaTabla(data.isla , data.sector.descripcion);

            if (state == "nuevo"){ //Si está agregando
              console.log('NUEVO');
                $('#cuerpoTablaIsla').append(isla);

                $('#mensajeExito h3').text('ÉXITO DE CARGA');
                $('#mensajeExito p').text("Se ha creado correctamente la isla.");
                $('#mensajeExito .cabeceraMensaje').removeClass('modificar');

            }else{ //Si está modificando
              console.log('MODIFICA');
                $('#isla' + data.isla.id_isla).replaceWith(isla);
                $('#mensajeExito h3').text('ÉXITO DE MODIFICACIÓN');
                $('#mensajeExito p').text("Se ha modificado la isla correctamente.");
                $('#mensajeExito .cabeceraMensaje').addClass('modificar');

            }
            $('#frmIsla').trigger("reset");
            $('#modalIsla').modal('hide');
            $("#tablaIslas").trigger("update");
            $("#tablaIslas th").removeClass('headerSortDown').removeClass('headerSortUp').children('i').removeClass().addClass('fas').addClass('fa-sort');

            //Mostrar éxito
            $('#mensajeExito').show();

        },
        error: function (data) {
            //var response = JSON.parse(data.responseText);
            var response = JSON.parse(data.responseText);

            if(typeof response.nro_isla !== 'undefined'){

              mostrarErrorValidacion($('#nro_isla') , response.nro_isla[0] , true);
            }
            if(typeof response.casino !== 'undefined'){
              mostrarErrorValidacion($('#casino') , response.casino[0] , true);
            }
            if(typeof response.sector !== 'undefined'){
              mostrarErrorValidacion($('#sector') , response.sector[0] , true);
            }
            if(typeof response.codigo !== 'undefined'){
              mostrarErrorValidacion($('#ncodigo') , response.codigo[0] , true);
            }

        }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function existeMaquina(id_maquina){ // devuelve true si ya existe esa maquina entre las subisla
  var subislasModal = $('.subisla').not('#moldeSubisla');
  var bandera = false;
  $.each(subislasModal, function(i){
      var maquinasModal = $(this).find('table tbody .maquinaSI').not('#moldeMaquinaSI');
      var maquinas = [];

      $.each(maquinasModal, function(i) {//true -> ya existe la maquina en alguna sub isla
          if(parseInt($(this).attr('data-maquina')) === parseInt(id_maquina)){
            console.log('true');
            bandera = true;
          }
      });

  });
  return bandera;
}

function generarFilaTabla(isla,sector){
  console.log('44',isla);
  var fila = $(document.createElement('tr'));
  var codigo;
  isla.codigo == null ? codigo = '-' : codigo= isla.codigo;

  if(isla.id_casino != 3){
  fila.attr('id','isla' + isla.id_isla)
      .append($('<td>')
          .addClass('col-xs-1')
          .text(isla.nro_isla)
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text(codigo).css('text-align','center')
      )

      fila.append($('<td>')
          .addClass('col-xs-2')
          .text(isla.casino)
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text(sector)
      )
      .append($('<td>')
          .addClass('col-xs-2')
          .text(isla.cantidad_maquinas).css('text-align','center')
      )

      fila.append($('<td>')
          .addClass('col-xs-3')
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
              )
              .append($('<span>').text(' VER MÁS'))
              .addClass('btn').addClass('btn-info').addClass('detalle')
              .attr('value', isla.id_isla)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>')
                  .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
              )
              .append($('<span>').text(' MODIFICAR'))
              .addClass('btn').addClass('btn-warning').addClass('modificar')
              .attr('value', isla.id_isla)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-unlink')
              )
              .append($('<span>').text(' DIVIDIR'))
              .addClass('btn').addClass('btn-warning').addClass('dividir')
              .attr('value', isla.id_isla)
              .attr('data-isla', isla.nro_isla)
              .attr('data-casino', isla.id_casino)
          )
          .append($('<span>').text(' '))
          .append($('<button>')
              .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
              )
              .append($('<span>').text(' ELIMINAR'))
              .addClass('btn').addClass('btn-danger').addClass('eliminar')
              .attr('value', isla.id_isla)
          )
      )}
      else{
        fila.attr('id','isla' + isla.id_isla)
            .append($('<td>')
                .addClass('col-xs-1')
                .text(isla.nro_isla)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(' - ').css('text-align','center')
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(isla.casino)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(sector)
            )
            .append($('<td>')
                .addClass('col-xs-2')
                .text(isla.cantidad_maquinas).css('text-align','center')
            )

          .append($('<td>')
            .addClass('col-xs-3')
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
                )
                .append($('<span>').text(' VER MÁS'))
                .addClass('btn').addClass('btn-info').addClass('detalle')
                .attr('value', isla.id_isla)
            )
            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>')
                    .addClass('fa').addClass('fa-fw').addClass('fa-pencil-alt')
                )
                .append($('<span>').text(' MODIFICAR'))
                .addClass('btn').addClass('btn-warning').addClass('modificar')
                .attr('value', isla.id_isla)
            )

            .append($('<span>').text(' '))
            .append($('<button>')
                .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                )
                .append($('<span>').text(' ELIMINAR'))
                .addClass('btn').addClass('btn-danger').addClass('eliminar')
                .attr('value', isla.id_isla)
            )
        )
      }
    return fila;
}

function limpiarModal(){
  $('#frmIsla').trigger('reset');
  $('#id_isla').val(0);
  $('#listaMaquinas li').remove();
  limpiarAlertas();
}

function reiniciarSector(){
  $('#sector option').remove();
  $('#sector').append($('<option>').val(0).text('-Sectores del casino-'));
}

function limpiarAlertas(){
  $('#nro_isla').removeClass('alerta');
  $('#cant_maquinas').removeClass('alerta');

  $('#alerta-nro_isla').text('');
  $('#alerta-nro_isla').hide();
  $('#alerta-cant_maquinas').text('');
  $('#alerta-cant_maquinas').hide();
}

function mostrarIsla(isla,sector,maquinas){
  $('#id_isla').val(isla.id_isla);
  $('#nro_isla').val(isla.nro_isla);
  $('#cant_maquinas').val(isla.cantidad_maquinas);
  $('#casino').val(sector.id_casino).trigger('change', [sector.id_sector]);
  $('#ncodigo').val(isla.codigo);
  $('#orden').val(isla.orden).closest('div').toggle(isla.id_casino == 3);
  $('#nro_islote').val(isla.nro_islote).closest('div').toggle(isla.id_casino == 3);

  for (var i = 0; i < maquinas.length; i++) {
      agregarMaquina(maquinas[i].id_maquina, maquinas[i].nro_admin, maquinas[i].marca, maquinas[i].modelo);
  }
}

function agregarMaquina(id_maquina, nro_admin,nombre,modelo){
  $('#listaMaquinas')
     .append($('<li>')
        .val(id_maquina)
        .addClass('row')
        .css('list-style','none').css('padding','5px 0px')
        //Columna de NUMERO ADMIN
        .append($('<div>')
            .addClass('col-xs-2')
            .text(nro_admin)
        )
        //Columna de NOMBRE
        .append($('<div>')
            .addClass('col-xs-4')
            .text(nombre)
        )
        //Columna de MODELO
        .append($('<div>')
            .addClass('col-xs-4')
            .text(modelo)
        )
        //Columna BOTON QUITAR
        .append($('<div>')
            .addClass('col-xs-2')
            .append($('<button>')
                .addClass('btn').addClass('btn-danger').addClass('borrarFila').addClass('borrarMaquina')
                .append($('<i>')
                    .addClass('fa').addClass('fa-trash')
                )
            )
        )
    );
}

function habilitarControles(valor){
  $('#nro_isla').prop('readonly',!valor);
  $('#cant_maquinas').prop('readonly',!valor);
  $('#buscadorMaquina').prop('readonly',!valor);
  $('#ncodigo').prop('readonly',!valor);
  $('#casino').prop('disabled',!valor);
  $('#sector').prop('disabled',!valor);
  $('#nro_islote').prop('readonly',!valor);
  $('#orden').prop('readonly',!valor);
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
