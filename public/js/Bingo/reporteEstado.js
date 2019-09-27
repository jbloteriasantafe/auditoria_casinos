$(document).ready(function(){

  $('#barraMenu').attr('aria-expanded','true');
  // $('#maquinas').removeClass();
  // $('#maquinas').addClass('subMenu1 collapse in');
  $('#bingoMenu').removeClass();
  $('#bingoMenu').addClass('subMenu2 collapse in');

  $('#bingoMenu').siblings('div.opcionesHover').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Reportes de Estados');
  // $('#gestionarMaquinas').attr('style','border-left: 6px solid #3F51B5;');
  $('#opcReporteEstadoBingo').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcReporteEstadoBingo').addClass('opcionesSeleccionado');

  $('#dtpBuscadorFecha').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
    endDate: '+0d'
  });
  
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

//busqueda de reportes
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
  // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
  var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultadosPremio .activa').attr('value'),orden: $('#tablaResultadosPremio .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultadosPremios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  var formData = {
    fecha: $('#buscadorFecha').val(),
    casino: $('#buscadorCasino').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
      type: 'GET',
      url: 'buscarEstado',
      data: formData,
      dataType: 'json',
      success: function(resultados){

        $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
        $('#cuerpoTabla tr').remove();
        for (var i = 0; i < resultados.data.length; i++){
          $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
        }
        $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function(data){
        console.log('Error:', data);
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
function generarFilaTabla(estado){
  var importacion;
  if(estado.importacion == null || estado.importacion == 0) importacion = 'NO';
  else importacion = 'SI';

  var relevamiento;
  if(estado.relevamiento == null || estado.relevamiento == 0) relevamiento = 'NO';
  else relevamiento = 'SI';

  var sesion_cerrada;
  if(estado.sesion_cerrada == null || estado.sesion_cerrada == 0) sesion_cerrada = 'NO';
  else sesion_cerrada = 'SI';

  var visado;
  if(estado.visado == null || estado.visado == 0) visado = 'NO';
  else visado = 'SI';

  var fila = $(document.createElement('tr'));
      fila.attr('id', estado.id_reporte_estado)
        .append($('<td>')
        .addClass('col-xs-4')
            .text(estado.fecha_sesion)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(estado.nombre)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(importacion)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(relevamiento)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(sesion_cerrada)
        )
        .append($('<td>')
          .addClass('col-xs-2')
          .text(visado)
        )
        return fila;
}
