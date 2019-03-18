$(document).ready(function() {
    $('#barraInformes').attr('aria-expanded','true');
    $('#informes').removeClass();
    $('#informes').addClass('subMenu1 collapse in');
    $('.tituloSeccionPantalla').text('Informes Diarios Mesas de Paño');
    $('#opcInfoDiario').attr('style','border-left: 6px solid #185891; background-color: #131836;');
    $('#opcInfoDiario').addClass('opcionesSeleccionado');

    $('#fechaInformeDiario').val(''),
    $('#CasInformeDiario').val('0'),

    $(function(){
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
    });

    $('#buscar-info-diarios').trigger('click',[1,10,'fecha','desc']);
    $('#select_casino_diario').val('0');
    $('#B_fecha_diario').val('');

});


$('#buscar-info-diarios').on('click',function(e,pagina,page_size,columna,orden){

  e.preventDefault();

    //Fix error cuando librería saca los selectores
    if(isNaN($('#herramientasPaginacion').getPageSize())){
      var size = 10; // por defecto
    }
    else {
      var size = $('#herramientasPaginacion').getPageSize();
    }

    var page_size = (page_size == null || isNaN(page_size)) ?size : page_size;
    // var page_size = (page_size != null) ? page_size : $('#herramientasPaginacion').getPageSize();
    var page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInfoDiarios .activa').attr('value'),orden: $('#tablaInfoDiarios .activa').attr('estado')} ;

    if(sort_by == null){ // limpio las columnas
      $('#tablaInfoDiarios th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }


    var formData= {
              fecha: $('#B_fecha_diario').val(),
              id_casino: $('#CasInformeDiario').val(),
              page: page_number,
              sort_by: sort_by,
              page_size: page_size,
            }

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            type: 'POST',
            url: 'informeDiario/buscar',
            data: formData,
            dataType: 'json',

            success: function (data){
                $('#tablaInfoDiarios tbody tr').remove();
                $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.total,clickIndice);

              for (var i = 0; i < data.diarios.data.length; i++) {

                  var fila=  generarFilaTablaInicial(data.diarios.data[i]);
                  $('#tablaInfoDiarios').append(fila);
              }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.total,clickIndice);

            },
            error: function(data){
            },
        })

});


$(document).on('click','.imprimirInfoDiario', function(e){

  e.preventDefault();

  var id=$(this).val();

  window.open('informeDiario/imprimir/' + id,'_blank');



})


function generarFilaTablaInicial(data){

  var fila = $('#moldeInfoDia').clone();

  fila.removeAttr('id');
  fila.attr('id',data.id_importacion_diaria_mesas);

  fila.find('.diario_fecha').text(data.fecha).css('text-align','center');
  fila.find('.diario_casino').text(data.nombre).css('text-align','center');
  fila.find('.imprimirInfoDiario').val(data.id_importacion_diaria_mesas).css('text-align','center');

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

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaInfoDiarios .activa').attr('value');
  var orden = $('#tablaInfoDiarios .activa').attr('estado');
  $('#buscar-info-diarios').trigger('click',[pageNumber,tam,columna,orden]);
}
