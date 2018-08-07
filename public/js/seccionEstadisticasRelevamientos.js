$(document).ready(function(){

  $('#barraMaquinas').attr('aria-expanded','true');
  $('#maquinas').removeClass();
  $('#maquinas').addClass('subMenu1 collapse in');
  $('#informesMTM').removeClass();
  $('#informesMTM').addClass('subMenu2 collapse in');

  $('.tituloSeccionPantalla').text('Estadísticas de relevamientos');
  $('#opcEstadisticas').attr('style','border-left: 6px solid #25306b; background-color: #131836;');
  $('#opcEstadisticas').addClass('opcionesSeleccionado');

    habilitarDTP();
    $('#btn-buscarMaquina').popover({ html : true});
    $('#fecha_desde').popover({ html : true});
    $('#B_fecha_inicio_m').popover({ html : true});

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
$('#btn-minimizarDetalle').click(function(){
    if($(this).data("minimizar")==true){
    $('.modal-backdrop').css('opacity','0.1');
      $(this).data("minimizar",false);
  }else{
    $('.modal-backdrop').css('opacity','0.5');
    $(this).data("minimizar",true);
  }
});

$('#btn-ayuda').click(function(e){
  e.preventDefault();

  $('.modal-title').text('| ESTADÍSTICAS DE RELEVAMIENTOS');
  $('.modal-header').attr('style','font-family: Roboto-Black; background-color: #aaa; color: #fff');

	$('#modalAyuda').modal('show');

});

$('#seccionBusquedaPorMaquina input ,#seccionBusquedaPorMaquina select').on('focusin', function() {
    $('#btn-buscarMaquina').popover('hide');
})

$('input , button').on('focusin' , function(){
    $(this).popover('hide');
})

$('select').on('focusin', function(){
    $(this).removeClass('alerta');
})

$('#seccionBusquedaPorMaquina input').on('focusin', function() {
    $('#btn-buscarMaquina').popover('hide');
})

$('#b_casino').on('change',function(){
  var id_casino = $('#b_casino').val();

  $('#b_sector option').remove();
  $.get("sectores/obtenerSectoresPorCasino/" + id_casino, function(data){

    $('#b_sector').append($('<option>').val('').text('- Seleccione el sector -'));

    for (var i = 0; i < data.sectores.length; i++) {
      $('#b_sector')
          .append($('<option>')
              .val(data.sectores[i].id_sector)
              .text(data.sectores[i].descripcion)
          )
    }
  });
});

function generarFilaTabla(resultado){
  var fila = $('<tr>');

  var botonPedir = $('<button>').addClass('btn btn-success pedido').val(resultado.id_maquina)
                                .append($('<i>').addClass('fa fa-tag'));

  fila.append($('<td>').addClass('col-xs-3').text(resultado.casino));
  fila.append($('<td>').addClass('col-xs-2').text(resultado.sector));
  fila.append($('<td>').addClass('col-xs-2').text(resultado.isla));
  fila.append($('<td>').addClass('col-xs-2').text(resultado.maquina));
  fila.append($('<td>').addClass('col-xs-3').append(botonPedir));

  return fila;
}

//Filtro de busqueda
$('#btn-buscar').click(function(e,pagina,page_size,columna,orden){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

  var page_size = (page_size != null) ? page_size : 10;
  var page_number = (pagina != null) ? pagina : 1;
  var sort_by = (columna != null) ? {columna,orden} : null;
  if(sort_by == null){ // limpio las columnas
    $('#tablaResultados th i').removeClass().addClass('fa fa-sort').parent().removeClass('activa').attr('estado','');
  }

  var formData = {
      id_casino: $('#b_casino').val(),
      id_sector: $('#b_sector').val(),
      nro_isla: $('#b_isla').val(),
      fecha_desde: $('#fecha_desde_date').val(),
      fecha_hasta: $('#fecha_hasta_date').val(),
      page: page_number,
      sort_by: sort_by,
      page_size: page_size,
  }

  $.ajax({
      type: 'POST',
      url: 'estadisticas_relevamientos/buscarMaquinasSinRelevamientos',
      data: formData,
      dataType: 'json',
      success: function (resultados) {
          console.log(resultados);

          $('#herramientasPaginacion').generarTitulo(page_number,page_size,resultados.total,clickIndice);
          $('#cuerpoTabla tr').remove();
          console.log(resultados.data);
          for (var i = 0; i < resultados.data.length; i++) {
            var filaMTMPedido = generarFilaTabla(resultados.data[i]);
            $('#cuerpoTabla').append(filaMTMPedido)
          }
          $('#herramientasPaginacion').generarIndices(page_number,page_size,resultados.total,clickIndice);

      },
      error: function (data) {
        var response = JSON.parse(data.responseText);

        if(typeof response.id_casino !== 'undefined'){
          $('#b_casino').addClass('alerta');
        }

        if(typeof response.fecha_desde !== 'undefined'){
          $('#fecha_desde').popover('show');
          $('.popover').addClass('popAlerta');
        }
      }
    });
});

//Busqueda de máquina
$('#btn-buscarMaquina').click(function(e){
  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

    var formData = {
        id_casino: $('#b_casinoMaquina').val(),
        nro_admin: $('#b_adminMaquina').val(),
        cantidad_relevamientos: $('#b_cantidad_relevamientos').val(),
    }

  $.ajax({
      type: 'POST',
      url: 'estadisticas_relevamientos/obtenerUltimosRelevamientosPorMaquina',
      data: formData,
      dataType: 'json',
      success: function (data) {
          console.log(data);

          $('#casinoDetalle').val(data.maquina.casino);
          $('#sectorDetalle').val(data.maquina.sector);
          $('#islaDetalle').val(data.maquina.isla);
          $('#adminDetalle').val(data.maquina.nro_admin);

          $('#tablaRelevamientos tbody tr').remove();

          for (var i = 0; i < data.detalles.length; i++) {

            //Si NO hay una causa de no toma se calcula producido
            var producidoCalculado = data.detalles[i].tipos_causa_no_toma == null ?
                                     calcularProducido(data.formula, data.detalles[i]) : data.detalles[i].tipos_causa_no_toma;

            var producidoSistema = Math.round((
                                         parseFloat(data.detalles[i].coinin)
                                       - parseFloat(data.detalles[i].coinout)
                                       - parseFloat(data.detalles[i].jackpot)
                                       - parseFloat(data.detalles[i].progresivo)
                                   )*100)/100;

            var fila = $('<tr>');

            fila.append($('<td>').text(data.detalles[i].fecha))

            data.detalles[i].cont1 != null ? fila.append($('<td>').text(data.detalles[i].cont1)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont2 != null ? fila.append($('<td>').text(data.detalles[i].cont2)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont3 != null ? fila.append($('<td>').text(data.detalles[i].cont3)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont4 != null ? fila.append($('<td>').text(data.detalles[i].cont4)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont5 != null ? fila.append($('<td>').text(data.detalles[i].cont5)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont6 != null ? fila.append($('<td>').text(data.detalles[i].cont6)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont7 != null ? fila.append($('<td>').text(data.detalles[i].cont7)) : fila.append($('<td>').text('-'));
            data.detalles[i].cont8 != null ? fila.append($('<td>').text(data.detalles[i].cont8)) : fila.append($('<td>').text('-'));

            fila.append($('<td>').text(data.detalles[i].coinin))
            fila.append($('<td>').text(data.detalles[i].coinout))
            fila.append($('<td>').text(data.detalles[i].jackpot))
            fila.append($('<td>').text(data.detalles[i].progresivo))


            fila.append($('<td>').text(producidoCalculado))
            fila.append($('<td>').text(producidoSistema))

            if (data.detalles[i].tipos_causa_no_toma == null) diferencia = Math.round((producidoCalculado - producidoSistema)*100)/100;

            var diferencia = '-';
            fila.append($('<td>').text(diferencia))

            $('#tablaRelevamientos tbody').append(fila);
          }

          $('#modalDetalle').modal('show');
      },
      error: function (data) {
          var response = JSON.parse(data.responseText);

          if(typeof response.id_casino !== 'undefined' || typeof response.nro_admin !== 'undefined' || typeof response.cantidad_relevamientos !== 'undefined'){
            $('#btn-buscarMaquina').popover('show');
            $('.popover').addClass('popAlerta');
          }

      }
    });
});

function calcularProducido(formula, contadores){
  var producido = 0;
  var sinProducido = false;

  if (formula.cont1 != null) contadores.cont1 == null ? sinProducido = true : producido += parseFloat(contadores.cont1);

  for (var i = 2; i < 9; i++) {
    if (formula['cont'+i] != null)
        contadores['cont'+i] == null ?
            sinProducido = true : formula['operador'+i-1] == '+' ?
            producido += parseFloat(contadores['cont'+i]) : producido -= parseFloat(contadores['cont'+i]);

  }

  if (sinProducido) {
      return '';
  }else {
      return producido;
  }
}

function habilitarDTP(){
  $('#b_fecha_desde').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });

  $('#b_fecha_hasta').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'dd / mm / yyyy',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2
  });
}

function habilitarDTPpedido(){
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
}

function clickIndice(e,pageNumber,tam){
  if(e != null){
    e.preventDefault();
  }
  var tam = (tam != null) ? tam : $('#tituloTabla').getPageSize();
  var columna = $('#tablaResultados .activa').attr('value');
  var orden = $('#tablaResultados .activa').attr('estado');
  $('#btn-buscar').trigger('click',[pageNumber,tam,columna,orden]);
}

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
  clickIndice(e,$('#herramientasPaginacion').getCurrentPage(),$('#herramientasPaginacion').getPageSize());
});

//Modal para pedir máquina
$(document).on('click','.pedido',function(e){
    $('#modalPedido #frmPedido').trigger('reset');
    $('#modalPedido #id_maquina').val($(this).val());

    var id_maquina = $('#modalPedido #id_maquina').val();

    habilitarDTPpedido();

    $.get('estadisticas_relevamientos/obtenerFechasMtmAPedido/' + id_maquina, function(data){
        $('#nro_admin_pedido').val(data.maquina.nro_admin).attr('data-maquina', data.maquina.id_maquina).prop('readonly',true);
        $('#casino_pedido').val(data.casino.nombre).attr('data-casino', data.casino.id_casino).prop('readonly',true);

        $('#modalPedido #fechasPedido tbody tr').remove();

        if (data.fechas.length > 0) {
          for (var i = 0; i < data.fechas.length; i++) {
            var fila = $('<tr>').append($('<td>').text(data.fechas[i].fecha));
            $('#modalPedido #fechasPedido tbody').append(fila);
            $('#modalPedido #fechasPedido').show();
          }
        }else {
            $('#modalPedido #fechasPedido').hide();
        }

        $('#modalPedido').modal('show');
    });

});

//Ver más
$(document).on('click','.detalle',function(e){
    $('#modalDetalle').modal('show');
});

//Pedir máquina
$('#btn-pedido').click(function(e){

  $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

  e.preventDefault();

    var formData = {
        nro_admin: $('#nro_admin_pedido').val(),
        casino: $('#casino_pedido').attr('data-casino'),
        fecha_inicio: $('#fecha_inicio_m').val(),
        fecha_fin: $('#fecha_fin_m').val(),
    }

  $.ajax({
    type: 'POST',
    url: 'estadisticas_relevamientos/guardarMtmAPedido',
    data: formData,
    dataType: 'json',
    success: function (data) {
      console.log('Pedido: ', data);
    },
    error: function (data) {
      var response = JSON.parse(data.responseText);

      if(typeof response.fecha_inicio !== 'undefined'){
        $('#B_fecha_inicio_m').popover('show');
        $('.popover').addClass('popAlerta');
      }

    },
  });
});
