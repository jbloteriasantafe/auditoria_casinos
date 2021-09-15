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

$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  //Fix error cuando librería saca los selectores
  let size = 10;
  if(!isNaN($('#herramientasPaginacion').getPageSize())){
    size = $('#herramientasPaginacion').getPageSize();
  }
  page_size = (page_size == null || isNaN(page_size))? size : page_size;
  const page_number = (pagina != null) ? pagina : $('#herramientasPaginacion').getCurrentPage();
  const sort_by = (columna != null) ? {columna: columna,orden: orden} : {columna: $('#tablaContadores .activa').attr('value'),orden: $('#tablaContadores .activa').attr('estado')} ;
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

$(document).on('click','#tablaContadores thead tr th[value]',function(e){
  $('#tablaContadores th').removeClass('activa');
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
  $('#tablaContadores th:not(.activa) i').removeClass().addClass('fa fa-sort').parent().attr('estado','');
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

$(document).on('click','.ver',function(){
  const fila = $(this).closest('tr');
  const casino = fila.find('.casino').text();
  const moneda = fila.find('.moneda').text();
  const fecha  = fila.find('.fecha').text();
  modalContadores($(this).val(),casino,moneda,fecha,'ver');
});

$(document).on('click','.validar',function(){
  const fila = $(this).closest('tr');
  const casino = fila.find('.casino').text();
  const moneda = fila.find('.moneda').text();
  const fecha  = fila.find('.fecha').text();
  modalContadores($(this).val(),casino,moneda,fecha,'validar');
});

function modalContadores(id_contador_horario,casino,moneda,fecha,modo){
  $('#casinoModal').val(casino);
  $('#monedaModal').val(moneda);
  $('#fechaModal').val(fecha);
  $('#verSoloAlertasModal').prop('checked',true);

  if(modo == 'validar'){
    $('#modalContadores .modal-header').css('background','rgb(60, 204, 134)')
    .find('.modal-title').text('VALIDAR ALERTAS DE CONTADORES');
    $('#observacionesModal').val('').attr('disabled',false);
    $('#btn-validar').attr('disabled',false).show();
  }
  else if(modo == 'ver'){
    $('#modalContadores .modal-header').css('background','#4FC3F7')
    .find('.modal-title').text('VER CONTADORES');
    $('#observacionesModal').val('').attr('disabled',true);
    $('#btn-validar').attr('disabled',true).hide();
  }
  else return;

  $('#detalleModal').hide();

  $.get('/contadores/obtenerDetalles/'+id_contador_horario,function(data){
    $('#maquinasModal').empty();
    for(const idx in data.detalles){
      const m = data.detalles[idx];
      const fila = $('#filaEjemploMaquina').clone().removeAttr('id');
      fila.find('.nro_admin').text(m.nro_admin);
      fila.find('button').val(m.id_detalle_contador_horario);
      $('#maquinasModal').append(fila);
    }
    $('#alertasModal').val(data.alertas);
    $('#modalContadores').modal('show');
  });
}

$(document).on('click','.verContadores',function(){
  $('#maquinaModal').val($(this).closest('tr').find('.nro_admin').text());
  $('#btn-validar').val($(this).val());
  $.get('/contadores/obtenerDetalleCompleto/'+$(this).val(),function(data){
    $('#tablaDetallesModal tbody').empty();
    for(const idx in data.detalles){
      const d = data.detalles[idx];
      const fila = $('#filaEjemploDetalle').clone().removeAttr('id');
      fila.find('.hora').text(d.hora);
      fila.find('.isla').text(d.isla);
      fila.find('.coinin').text(d.coinin);
      fila.find('.coinout').text(d.coinout);
      fila.find('.jackpot').text(d.jackpot);
      fila.find('.progresivo').text(d.progresivo);
      $('#tablaDetallesModal tbody').append(fila);
    }
    $('#tablaAlertasModal tbody').empty();
    for(const idx in data.alertas){
      const a = data.alertas[idx];
      const fila = $('#filaEjemploAlerta').clone().removeAttr('id');
      fila.find('.hora').text(a.hora);
      fila.find('.descripcion_alerta').text(a.descripcion);
      $('#tablaAlertasModal tbody').append(fila);
    }
    $('#estadoModal').val(data.estado);
    $('#observacionesModal').val(data.observaciones);
    $('#detalleModal').show();
  });
});

$('#btn-validar').click(function(){
  console.log($(this).val());
})