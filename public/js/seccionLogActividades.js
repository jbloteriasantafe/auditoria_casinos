$(document).ready(function() {

  $('#barraUsuarios').attr('aria-expanded','true');
  $('#usuarios').removeClass();
  $('#usuarios').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Log de Actividades');
  $('#opcLogActividades').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcLogActividades').addClass('opcionesSeleccionado');

  $('#usuarios').show();

  $('#btn-buscar').trigger('click');

});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| LOG DE ACTIVIDADES');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

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

//Quitar eventos de la tecla Enter
$('#collapseFiltros input').on('keypress',function(e){
    if(e.which == 13) {
      e.preventDefault();
      $('#btn-buscar').click();
    }
});

//DATETIMEPICKER de la fecha
$(function(){
    $('#dtpFecha').datetimepicker({
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
//Busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({
      headers: {
          'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
      }
  });

  e.preventDefault();

  var fecha;
  var fechaB;

  if($('#B_fecha').val() == ''){
    fecha = '';
  }else {
    fechaB = $('#B_fecha').val().split(" / ");
    fecha = fechaB[2]+"-"+fechaB[1]+"-"+fechaB[0];
  }
  if(isNaN($('#herramientasPaginacion').getPageSize())){
    var size = 10; // por defecto
  }else {
    var size = $('#herramientasPaginacion').getPageSize();
  }
  console.log(page_size);
  var page_size = (page_size == null || isNaN(page_size)) ? size : page_size;
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
    usuario: $('#B_usuario').val(),
    tabla: $('#B_tabla').val(),
    accion: $('#B_accion').val(),
    fecha: fecha,
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'logActividades/buscarLogActividades',
      data: formData,
      dataType: 'json',
      success: function(resultados){
        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTablaResultados(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
      }
    });
});

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fas fa-sort-down').parent().addClass('activa').attr('estado','desc');
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
  console.log('tamaño_pagina evento' , $('#herramientasPaginacion').getPageSize());
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  console.log('tamaño pagina', tam);
  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

function generarFilaTablaResultados(log){
      var fila = $(document.createElement('tr'));
      fila.attr('id','log' + log.id_log)
          .append($('<td>')
              .addClass('col-md-2 col-xs-3')
              .text(log.nombre)
          )
          .append($('<td>')
              .addClass('col-md-2 col-xs-4')
              .text(log.fecha)
          )
          .append($('<td>')
              .addClass('col-md-2 columnaOculta')
              .text(log.accion)
          )
          .append($('<td>')
              .addClass('col-md-2 col-xs-3')
              .text(log.tabla)
          )
          .append($('<td>')
              .addClass('col-md-2 columnaOculta')
              .text(log.id_entidad)
          )
          .append($('<td>')
              .addClass('col-md-2 col-xs-2')
              .append($('<button>')
                  .append($('<i>')
                      .addClass('fa')
                      .addClass('fa-search-plus')
                  )
                  .append($('<span>').text(' VER MÁS'))
                  .addClass('btn').addClass('btn-info').addClass('detalle')
                  .attr('value',log.id_log)
              )
          )
      return fila;
}

//Mostrar modal con los datos del Log
$(document).on('click','.detalle',function(){
    //Modificar los colores del modal
      $('.modal-title').text('| VER DETALLE LOG');
      $('.modal-header').attr('style','background: #4FC3F7','color: #fff');
    //Resetear formulario para llevar los datos
      $('#frmLog').trigger('reset');
      var id_log = $(this).val();

      $.get("logActividades/obtenerLogActividad/" + id_log, function(data){
          console.log(data);
          // var fechaAux = data.log.split(' ');
          // var fecha = fechaAux[0].split('-');
          // var hora = fechaAux[1].split(':');
          $('#fecha').val(data.log.fecha);
          $('#usuario').val(data.usuario);
          $('#accion').val(data.log.accion);
          $('#tabla').val(data.log.tabla);
          $('#id_entidad').val(data.log.id_entidad);

          $('#tablaDetalleLog tbody > tr').remove();

          for (var i = 0; i < data.detalles.length; i++) {
            $('#tablaDetalleLog tbody')
                .append($('<tr>')
                    .append($('<td>')
                        .addClass('col-xs-6')
                        .text(data.detalles[i].campo)
                    )
                    .append($('<td>')
                        .addClass('col-xs-6')
                        .text(data.detalles[i].valor)
                    )
                )
          }
          if(data.detalles.length == 0){
            $('#tablaDetalleLog').hide();
          }
          else {
            $('#tablaDetalleLog').show();
          }
          $('#modalLogActividad').modal('show');
      });
});
