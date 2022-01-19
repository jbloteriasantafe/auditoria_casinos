$(document).ready(function() {
  $('#barraInformesMesas').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes Diarios de Control Ambiental');
  $('#opcInfoDiario').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInfoDiario').addClass('opcionesSeleccionado');

  $('#fechaInformeDiario').val(''),
  $('#select_casino_diario').val('0'),

  $('#dtpFechaInfD').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#buscar-info-diarios').trigger('click',[1,10,'fecha','desc']);
  $('#select_casino_diario').val('0');
  $('#B_fecha_diario').val('');
});

$('#buscar-info-diarios').on('click',function(e,pagina,page_size,columna,orden){
  e.preventDefault();

  let size = 10;
  //Fix error cuando librería saca los selectores
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }

  page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  let sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInfoDiarios .activa').attr('value'),orden: $('#tablaInfoDiarios .activa').attr('estado')};
  if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
    sort_by =  {columna: 'fecha',orden: 'desc'} ;
  }

  const formData = {
    fecha: $('#B_fecha_diario').val(),
    id_casino: $('#select_casino_diario').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });

  $.ajax({
    type: 'GET',
    url: 'http://' + window.location.host + '/informeControlAmbiental/buscarInformesControlAmbiental',
    data: formData,
    dataType: 'json',

    success: function (data){
      $('#tablaInfoDiarios tbody tr').remove();
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.diarios.total,clickIndice);
      for (let i = 0; i < data.diarios.data.length; i++) {
          const fila = generarFilaTablaInicial(data.diarios.data[i]);
          $('#tablaInfoDiarios').append(fila);
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,data.diarios.total,clickIndice);
    },
    error: function(data){ console.log(data); }
  });
});


$(document).on('click','.imprimirInfoDiario', function(e){
  e.preventDefault();
  window.open('informeControlAmbiental/imprimir/' + $(this).data('id_casino') + '/' + $(this).data('fecha'),'_blank');
})


function generarFilaTablaInicial(data){
  const fila = $('#moldeInfoDia').clone();

  fila.removeAttr('id');
  fila.find('.diario_fecha').text(data.fecha).css('text-align','center');
  fila.find('.diario_casino').text(data.casino).css('text-align','center');
  fila.find('.imprimirInfoDiario').data('id_casino',data.id_casino).data('fecha',data.fecha).css('text-align','center');
  fila.css('display','');
  $('#molde2').css('display','block');

  return fila;
}

/*****************PAGINACION******************/
$(document).on('click','#tablaInfoDiarios thead tr th[value]',function(e){

  $('#tablaInfoDiarios th').removeClass('activa');

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
  $('#tablaInfoDiarios th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});


function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }

  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaInfoDiarios .activa').attr('value');
  const orden = $('#tablaInfoDiarios .activa').attr('estado');
  $('#buscar-info-diarios').trigger('click',[pageNumber,tam,columna,orden]);
}
