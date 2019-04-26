$(document).ready(function() {
  $('#barraInfoFisca').attr('aria-expanded','true');

  $('.tituloSeccionPantalla').text('Informes de Fiscalización');
  $('#barraInfoFisca').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#barraInfoFisca').addClass('opcionesSeleccionado');

    $('#fechaInformeDiario').val('');
    $('#CasInformeDiario').val('0');

    $(function(){
      $('#dtpFechaInf').datetimepicker({
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

    $('#buscar-informes-diarios').trigger('click',[1,10,'fecha','desc']);
});



//btn BUSCAR INFORMES, con paginación
$('#buscar-informes-diarios').click(function(e,pagina,page_size,columna,orden){

  e.preventDefault();

    $('#tablaInformesDiarios tbody tr').remove();

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
    var sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaInformesDiarios .activa').attr('value'),orden: $('#tablaInformesDiarios .activa').attr('estado')} ;

    if(typeof sort_by['columna'] == 'undefined'){ // limpio las columnas
      var sort_by =  {columna: 'fecha',orden: 'desc'} ;

      //$('#tablaInicial th i').removeClass().addClass('fas fa-sort').parent().removeClass('activa').attr('estado','');
    }

        var formData= {
          fecha: $('#fechaInformeDiario').val(),
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
            url: '/informeDiarioBasico/buscarInformes',
            data: formData,
            dataType: 'json',

            success: function (data){

                  $('#herramientasPaginacion').generarTitulo(page_number,page_size,data.informes.total,clickIndice);

                    for (var i = 0; i < data.informes.data.length; i++) {

                        var fila=  generarFila(data.informes.data[i]);

                        $('#tablaInformesDiarios tbody').append(fila);
                    }

              $('#herramientasPaginacion').generarIndices(page_number,page_size,data.informes.total,clickIndice);

            },
            error: function(data){
            },
        })


})

$(document).on('click','.imprimirDiario',function(){

  var id=$(this).val();

  window.open('informeDiarioBasico/imprimir/' + id,'_blank');

})


function generarFila(data){

  // var fila = $('#moldeInfoDia').clone();
  // fila.removeAttr('id');
  // fila.attr('id',data.informe_fiscalizadores)
  //
  //  fila.find('.d_fecha').text(data.fecha);
  //  fila.find('.d_casino').text(data.nombre);
  //  fila.find('.imprimirDiario').val(data.informe_fiscalizadores);
  var fila = $(document.createElement('tr'));

  fila.attr('id',data.id_informe_fiscalizadores)
      .append($('<td>').addClass('col-xs-4').text(data.fecha).css('text-align','center'))
      .append($('<td>').addClass('col-xs-4').text(data.nombre).css('text-align','center'))
      .append($('<td>').addClass('col-xs-4').append($('<button>').addClass('btn btn-successAceptar imprimirDiario').val(data.id_informe_fiscalizadores)
                          .append($('<i>').addClass('fa fa-fw fa-print').append($('</i>')
                          .append($('</button>'))))))
    // fila.css('display','block');
    // $('#molde1').css('display','block');
      return fila;
}


$(document).on('click','#tablaInformesDiarios thead tr th[value]',function(e){

  $('#tablaInformesDiarios th').removeClass('activa');

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
  $('#tablaInformesDiarios th:not(.activa) i').removeClass().addClass('fas fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

function clickIndice(e,pageNumber,tam){

  if(e != null){
    e.preventDefault();
  }

  var tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  var columna = $('#tablaInformesDiarios .activa').attr('value');
  var orden = $('#tablaInformesDiarios .activa').attr('estado');
  $('#buscar-informes-diarios').trigger('click',[pageNumber,tam,columna,orden]);
}
