$(document).ready(function(){
  //Muestra el menu desplegado
    $('#menu_informes').attr('aria-expanded','true');
    $('#menu2_informes').addClass('in');
    $('#menu_informes').parent().children('a').children('.nombreMenu1').addClass('menuActivoBlue');
    //Cambiar color del icono
    $('#menu_informes').parent().children('a').children('.iconoMenu1').children('img').attr('src','img/logos/informes_blue.png');
    //Agregar barra
    // $('#menu_expedientes').parent().css('border-right','6px solid #3fbbff');
});

$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaBeneficios th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
      id_casino: $('#selectCasinos').val(),
      fecha_desde: $('#fecha_desde').val(),
      fecha_hasta: $('#fecha_hasta').val(),
      id_tipo_moneda: $('#selectTipoMoneda').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'beneficios/buscarBeneficios',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          $('#tituloTablaResultados').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTablaResultados tr').remove();
          console.log(resultados.data);
          for (var i = 0; i < resultados.data.length; i++) {
            var filaBeneficio = generarFilaTabla(resultados.data[i]);
            $('#cuerpoTablaResultados').append(filaBeneficio);
          }
          $('#indicesPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
      },
      error: function (data) {
          console.log('Error:', data);
      }
    });
});
