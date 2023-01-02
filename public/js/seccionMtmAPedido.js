$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass();
  $('#procedimientos').addClass('subMenu2 collapse in');
  $('#contadores').removeClass();
  $('#contadores').addClass('subMenu3 collapse in');

  $('.tituloSeccionPantalla').text('MTM a pedido');
  $('#opcMTMaPedido').attr('style','border-left: 6px solid #673AB7; background-color: #131836;');
  $('#opcMTMaPedido').addClass('opcionesSeleccionado');

  $('#btn-buscar').trigger('click');

});

$('.modal').on('hidden.bs.modal', function() {
  console.log('cerro');
  $('#nro_admin_m').removeClass('alerta').popover("destroy").val("");
  $('#B_fecha_inicio_m').removeClass('alerta').popover("destroy").val("");
  $('#B_fecha_fin_m').removeClass('alerta').popover("destroy").val("");
  $('#selectCasinos_m').removeClass('alerta').popover("destroy").val(0);

})

$(function(){

    $('#dtpFechaInicio').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#dtpFechaFin').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#dtpFechaInicio_m').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });

    $('#dtpFechaFin_m').datetimepicker({
      language:  'es',
      todayBtn:  1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'dd / mm / yyyy',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2
    });
});

$('input').on('focusin', function(e){
   $(this).popover('hide');
});

$('#selectCasinos_m').on('focusin' , function(){
  $(this).removeClass('alerta');
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

//Quitar eventos de la tecla Enter y guardar
$('#collapseFiltros').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

//Quitar eventos de la tecla Enter y guardar
$(document).on('keypress',function(e){
    if(e.which == 13 && $('#modalExpediente').is(':visible')) {
      e.preventDefault();
      $('#btn-guardar').click();
    }
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| MTM A PEDIDO');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

//Mostrar modal para agregar nuevo Expediente
$('#btn-nuevo').click(function(e){
    e.preventDefault();
    $('.modal-title').text('| NUEVO PEDIDO A MTM');
    $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #6dc7be; color: #fff');
    $('#btn-guardar').removeClass();
    $('#btn-guardar').addClass('btn btn-successAceptar');
    $('#btn-guardar').val("nuevo");
    $('#mensajeExito').hide();
    $('#modalMTM_a_pedir').modal('show');
});

//MOSTRAR LOS SECTORES ASOCIADOS AL CASINO SELECCIONADO
$('#selectCasinos').on('change',function(){
  var id_casino = $('#selectCasinos option:selected').attr('id');

  $('#selectSector option').remove();
  $.get("mtm_a_pedido/obtenerSectoresPorCasino/" + id_casino, function(data){

    $('#selectSector')
        .append($('<option>')
        .val(0)
        .text('Todos los sectores')
        )

    for (var i = 0; i < data.sectores.length; i++) {
      $('#selectSector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });

  // $('#maquinas_pedido').show();
});

//Crear nuevo Casino / actualizar si existe
$('#btn-guardar').click(function(e){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    });

    e.preventDefault();

    var fecha_fin;
    var fecha_fin1;
    var fecha_inicio;
    var fecha_inicio1;
    if($('#B_fecha_fin_m').val() == ''){
      fecha_fin = '';
    }else {
      fecha_fin1 = $('#B_fecha_fin_m').val().split(" / ");
      fecha_fin = fecha_fin1[2]+"-"+fecha_fin1[1]+"-"+fecha_fin1[0];
    }
    if($('#B_fecha_inicio_m').val() == ''){
      fecha_inicio = '';
    }else {
      fecha_inicio1 = $('#B_fecha_inicio_m').val().split(" / ");
      fecha_inicio = fecha_inicio1[2]+"-"+fecha_inicio1[1]+"-"+fecha_inicio1[0];
    }

    var state = $('#btn-guardar').val();
    var type = "POST";

    var url = ((state == "modificar") ? 'mtm_a_pedido/modificarrMtmAPedido':'mtm_a_pedido/guardarMtmAPedido');
    var formData = {
      nro_admin: $('#nro_admin_m').val(),
      casino: $('#selectCasinos_m').val(),
      sector: $('#selectSector_m').val(),
      isla: $('#nro_isla_m').val(),
      fecha_inicio: fecha_inicio,
      fecha_fin: fecha_fin,
    }

    console.log(formData);

    $.ajax({
        type: type,
        url: url,
        data: formData,
        dataType: 'json',
        success: function (data) {
            if (state == "nuevo"){ //Si está agregando
                $('#mensajeExito h4').text('El Pedido fue CREADO correctamente.');
                $('#mensajeExito div').css('background-color','#4DB6AC');
            }else{ //Si está modificando
                $('#mensajeExito h4').text('El Pedido fue MODIFICADO correctamente.');
                $('#mensajeExito div').css('background-color','#FFB74D');
            }
            $('#frmMTM_a_pedido').trigger("reset");
            $('#modalMTM_a_pedir').modal('hide');

            //mostrar exito
            $('#mensajeExito').show();
            //forzar click para que busque
            var columna = $('#tablaResultados .activa').attr('value');
            var orden = $('#tablaResultados .activa').attr('estado');

            $('#btn-buscar').trigger('click' ,[$('#herramientasPaginacion').getCurrentPage() ,$('#tituloTabla').getPageSize() ,columna , orden] );

        },
        error: function (data) {
            var response = JSON.parse(data.responseText);

            if(typeof response.nro_admin !== 'undefined'){
              mostrarErrorValidacion($('#nro_admin_m'),response.nro_admin[0] ,true);
            }
            if(typeof response.casino !== 'undefined'){
              mostrarErrorValidacion($('#selectCasinos_m'),response.nro_admin[0],true);
            }
            if(typeof response.fecha_fin !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_fin_m'),response.nro_admin[0],true);
            }
            if(typeof response.fecha_inicio !== 'undefined'){
              mostrarErrorValidacion($('#B_fecha_inicio_m') ,response.nro_admin[0],true);
            }

            console.log('Error:', data);
        }
    });
});

//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();
  
  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  //Fix error cuando librería saca los selectores
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }

  var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: "maquina_a_pedido.fecha",orden:"desc"} ; // pro defecto ordena por la mas nueva
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
 

  var formData = {
    nro_admin: $('#nro_admin').val(),
    id_casino: $('#selectCasinos').val(),
    sector: $('#selectSector').val(),
    isla: $('#nro_isla').val(),
    fecha_inicio: $('#fecha_inicio').val(),
    fecha_fin: $('#fecha_fin').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  console.log(formData);

  $.ajax({
      type: 'POST',
      url: 'mtm_a_pedido/buscarMTMaPedido',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

          $('#cuerpoTabla tr').remove();
          console.log(resultados.data);
          for (var i = 0; i < resultados.data.length; i++) {
            var filaMTMPedido = generarFilaTabla(resultados.data[i]);
            $('#cuerpoTabla').append(filaMTMPedido)
          }

      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});
//Ordenar tabla
$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  console.log('algo');
  $('#tablaResultados th').removeClass('activa');
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
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

//Borrar pedido y remover de la tabla
$(document).on('click','.eliminar',function(){
    var id = $(this).val();
    $('#btn-eliminarModal').val(id);
    $('#modalEliminar').modal('show');
});

$('#btn-eliminarModal').click(function (e) {
    var id = $(this).val();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        }
    })

    $.ajax({
        type: "DELETE",
        url: "mtm_a_pedido/eliminarMmtAPedido/" + id,
        success: function (data) {
          //Remueve de la tabla
          $('#cuerpoTabla #'+ id).remove();
          $("#tablaResultados").trigger("update");

          $('#modalEliminar').modal('hide');
          var columna = $('#tablaResultados .activa').attr('value');
          var orden = $('#tablaResultados .activa').attr('estado');

          $('#btn-buscar').trigger('click' ,[$('#herramientasPaginacion').getCurrentPage() ,$('#tituloTabla').getPageSize() ,columna , orden] );
        },
        error: function (data) {
          console.log('Error: ', data);
        }
    });
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

function generarFilaTabla(mtm_a_pedido){
      var fila = $(document.createElement('tr'));

      fila.attr('id','mtm_a_pedidos' + mtm_a_pedido.id_maquina_a_pedido)
          .append($('<td>')
              .addClass('col-xs-2')
              .text(mtm_a_pedido.nro_admin)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(mtm_a_pedido.fecha)
          )
          // .append($('<td>')
          //     .addClass('col-xs-2')
          //     .text(ubicacion)
          // )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(mtm_a_pedido.nombre)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(mtm_a_pedido.descripcion)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              .text(mtm_a_pedido.nro_isla)
          )
          .append($('<td>')
              .addClass('col-xs-2')
              // .append($('<button>')
              //     .append($('<i>')
              //         .addClass('fa').addClass('fa-fw').addClass('fa-search-plus')
              //     )
              //     .append($('<span>').text(' VER MÁS'))
              //     .addClass('btn').addClass('btn-info').addClass('detalle')
              //     .attr('value',mtm_a_pedido.id_maquina_a_pedido)
              // )
              .append($('<span>').text(' '))
              .append($('<button>')
                  .append($('<i>').addClass('fa').addClass('fa-fw').addClass('fa-trash-alt')
                  )
                  .append($('<span>').text(' ELIMINAR'))
                  .addClass('btn').addClass('btn-danger').addClass('eliminar')
                  .attr('value',mtm_a_pedido.id_maquina_a_pedido)
              )
          )
        return fila;
}
