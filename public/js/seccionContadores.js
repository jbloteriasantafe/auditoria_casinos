$(document).ready(function(){
  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass().addClass('subMenu1 collapse in');
  $('#procedimientos').removeClass().addClass('subMenu2 collapse in');
  $('#contadores').removeClass().addClass('subMenu3 collapse in');
  $('.tituloSeccionPantalla').text('Contadores');
  $('#opcContadores').attr('style','border-left: 6px solid #673AB7; background-color: #131836;').addClass('opcionesSeleccionado');

  $('#dtpFechaDesde').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    showClear: true,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#dtpFechaHasta').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#btn-buscar').trigger('click');
});

//Filtro de búsqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }
  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna,orden} : {columna: $('#tablaBeneficios .activa').attr('value'),orden: $('#tablaBeneficios .activa').attr('estado')} ;
  if(sort_by == null){ // limpio las columnas
    $('#tablaContadores th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  const formData = {
    id_casino: $('#selectCasinos').val(),
    fecha_desde: $('#fecha_desde').val(),
    fecha_hasta: $('#fecha_hasta').val(),
    id_tipo_moneda: $('#selectTipoMoneda').val(),
    validado: $('#selectValidado').val(),
    page: page_number,
    sort_by: sort_by,
    page_size: page_size,
  }

  $.ajax({
    type: 'POST',
    url: 'contadores/buscarContadores',
    data: formData,
    dataType: 'json',
    success: function (resultados) {
      $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
      $('#cuerpoTablaResultados').empty();
      for(let i = 0;i<resultados.data.length;i++){
        const fila = $('#filaEjemploResultado').clone().removeAttr('id');
        const c = resultados.data[i];
        fila.find('.fecha').text(c.fecha);
        fila.find('.casino').text(c.casino);
        fila.find('.moneda').text(c.moneda);
        fila.find('.validado').find(c.alertas_validadas? '.fa-check' : '.fa-times').hide();
        if(c.alertas_validadas) fila.find('.validar').remove();

        fila.find('button').val(c.id_contador_horario);
        $('#cuerpoTablaResultados').append(fila);
      }
      $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);
    },
    error: function (data) {
      console.log(data);
    }
  });
});

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  tam = (tam != null) ? tam : $('#herramientasPaginacion').getPageSize();
  const columna = $('#tablaContadores .activa').attr('value');
  const orden = $('#tablaContadores .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}
