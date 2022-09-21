$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Reportes de Estados');
  $('#buscadorCasino').change();
});

$('#filtros .form-control').change(function(e){
  buscar(e);
});

//busqueda de reportes
function buscar(e,pagina,page_size,columna,orden){
  e.preventDefault();

  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaResultados .activa').attr('value'),orden: $('#tablaResultados .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
    type: 'GET',
    url: 'buscarEstado',
    data: {
      fecha: $('#buscadorFecha').val(),
      casino: $('#buscadorCasino').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
    },
    dataType: 'json',
    success: function(resultados){
      $('#cuerpoTabla').empty();
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      for (const i in resultados.data){
        $('#cuerpoTabla').append(generarFilaTabla(resultados.data[i]));
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function(data){
      console.log('Error:', data);
    }
  });
};

$(document).on('click','#tablaResultados thead tr th[value]',function(e){
  $('#tablaResultados th').removeClass('activa');
  if($(e.currentTarget).children('i').hasClass('fa-sort')){
    $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-down').parent().addClass('activa').attr('estado','desc');
  }
  else{
    if($(e.currentTarget).children('i').hasClass('fa-sort-down')){
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort-up').parent().addClass('activa').attr('estado','asc');
    }
    else{
      $(e.currentTarget).children('i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
    }
  }
  $('#tablaResultados th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaResultados .activa').attr('value');
  const orden = $('#tablaResultados .activa').attr('estado');
  buscar(e,pageNumber,tam,columna,orden);
}

//Generar fila con los datos de los premios
function generarFilaTabla(estado){
  function SI_NO(check){ return (check == null || check == 0)? 'NO' : 'SI'; };
  return $('<tr>').append($('<td>').addClass('col-xs-2 fecha_sesion').text(estado.fecha_sesion))
  .append($('<td>').addClass('col-xs-2 casino').text(estado.casino))
  .append($('<td>').addClass('col-xs-2 importacion').text(SI_NO(estado.importacion)))
  .append($('<td>').addClass('col-xs-2 relevamiento').text(SI_NO(estado.relevamiento)))
  .append($('<td>').addClass('col-xs-2 sesion_cerrada').text(SI_NO(estado.sesion_cerrada)))
  .append($('<td>').addClass('col-xs-2 visado').text(SI_NO(estado.visado)))
}
