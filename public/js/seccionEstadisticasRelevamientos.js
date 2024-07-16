import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';

$(function() {
  
$('.tituloSeccionPantalla').text('Estadísticas de relevamientos');

$('[data-js-cambio-casino-select-sectores]')
.trigger('set_url',['estadisticas_relevamientos/obtenerSectoresPorCasino'])
.trigger('change');

$('[data-js-filtro-tabla]:not(#filtrosRelevamientos)').on('busqueda',function(e,ret,tbody,molde){  
  ret.data.forEach(function(r){
    const fila = molde.clone();
    fila.find('.nro_admin').text(r.nro_admin);
    fila.find('.casino').text(r.casino);
    fila.find('.sector').text(r.sector);
    fila.find('.nro_isla').text(r.nro_isla);
    fila.find('button').val(r.id_maquina);
    tbody.append(fila);
  });
  
  tbody.find('[data-js-pedir]').click(function(e){
    console.log('Pedir: ',$(e.currentTarget).val());
  });
}).trigger('buscar');

$('[data-listas-maquinas]').each(function(lidx,lObj){
  const L = $(lObj);
  const filtro_id_casino = $(L.attr('data-listas-maquinas-sacar-id_casino'));
  const filtro_str       = $(L.attr('data-listas-maquinas-sacar-str'));
  const setear_id_maquina = $(L.attr('data-listas-maquinas-setear-id_maquina'));
  const origen_todas     = L.find('[data-lista-maquina-todas]');
  const origen_cas       = L.find('[data-lista-maquina-cas]');
  const origen_str       = L.find('[data-lista-maquina-str]');
  const origen_str_id    = origen_str.attr('id');
    
  filtro_id_casino.change(function(e){
    const id_casino = filtro_id_casino.val();
    
    const options = origen_todas.find('option').filter(id_casino == ''? 'option' : `option[data-id_casino="${id_casino}"]`);
    
    origen_cas.empty().append(options.map(function(oidx,op){
      const op2 = $(op).clone();
      const cod_casino = id_casino == ''? op2.attr('data-codigo-casino') : '';
      op2.val(op2.attr('data-nro_admin')+cod_casino);
      op2.text(op2.val());
      return op2[0];
    }));
    
    filtro_str.trigger('input');
  });

  filtro_str.on('input',function(e){
    const str = filtro_str.val();
    $(this).attr('list', '');
    
    origen_str.empty().append(origen_cas.find('option').filter(function(idx,op){
      return $(op).val().substr(0, str.length) === str;
    }).clone());
    
    $(this).attr('list', origen_str_id);
    $(this).focus();
    
    const str_option = origen_str.find('option').eq(0);
    setear_id_maquina.val(str == str_option.val()? str_option.attr('data-id_maquina') : '');
  });
  
  filtro_id_casino.trigger('change');
});

$('#filtrosRelevamientos[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
  $('#casinoDetalle').val(ret.maquina.casino);
  $('#sectorDetalle').val(ret.maquina.sector);
  $('#islaDetalle').val(ret.maquina.isla);
  $('#adminDetalle').val(ret.maquina.nro_admin);
  
  // se cambia pora no recalcular, el relevamiento ya se hizo, por lo que se traen los valores calculados en su momento, sin alteraciones 
  ret.detalles.forEach(function(d){
    const fila = $('<tr>')
    .append($('<td>').text(d.fecha))
    .append($('<td>').text(d.cont1 ?? '-'))
    .append($('<td>').text(d.cont2 ?? '-'))
    .append($('<td>').text(d.cont3 ?? '-'))
    .append($('<td>').text(d.cont4 ?? '-'))
    .append($('<td>').text(d.cont5 ?? '-'))
    .append($('<td>').text(d.cont6 ?? '-'))
    .append($('<td>').text(d.cont7 ?? '-'))
    .append($('<td>').text(d.cont8 ?? '-'))
    .append($('<td>').text(d.coinin ?? '-'))
    .append($('<td>').text(d.coinout ?? '-'))
    .append($('<td>').text(d.jackpot ?? '-'))
    .append($('<td>').text(d.progresivo ?? '-'));

    fila.append($('<td>').text(d.tipos_causa_no_toma ?? d.producido_calculado_relevado ?? '-'));
    fila.toggleClass('no_tomado',d.tipos_causa_no_toma != null);

    fila.append($('<td>').text(d.producido_importado ?? '-'));
    fila.append($('<td>').text(d.diferencia ?? '-'));

    $('#tablaRelevamientos tbody').append(fila);
  });

  $('#modalDetalle').modal('show');
})
.on('error_busqueda',function(e,ret,tbody,molde){
  ocultarErrorValidacion($(this).find('[name]'));
  
  const errores = ret.responseJSON ?? {};
  setTimeout(function(){
    AUX.mostrarErroresNames($(e.currentTarget).find('form'),errores);
    if(errores.id_maquina !== undefined){
      mostrarErrorValidacion($(e.currentTarget).find('[name="nro_admin"]'),errores.id_maquina.join(', '),true);
    }
  },100);
});

function habilitarDTPPedido() {
    $('#dtpFechaFin_m').datetimepicker({
      language: 'es',
      todayBtn: 1,
      autoclose: 1,
      todayHighlight: 1,
      format: 'yyyy-mm-dd',
      pickerPosition: "bottom-left",
      startView: 4,
      minView: 2,
      minDate: 1,
      startDate: new Date()
    });

    const mañana = new Date((new Date()).getTime() + (24 * 60 * 60 * 1000));

    $('#B_fecha_inicio_m').val(dateToString(mañana));
    $('#dtpFechaFin_m').datetimepicker("setDate", mañana);
}

function dateToString(date) {
  const to_2dig = function(d) { return (d > 9? '' : '0')+d; };
  return date.getFullYear()+'-'+to_2dig(date.getMonth())+'-'+to_2dig(date.getDate());
}

//Modal para pedir máquina
$(document).on('click', '.pedido', function(e) {
  $('#modalPedido #frmPedido').trigger('reset');
  $('#modalPedido #id_maquina').val($(this).val());

  const id_maquina = $('#modalPedido #id_maquina').val();
  habilitarDTPPedido();

  $.get('estadisticas_relevamientos/obtenerFechasMtmAPedido/' + id_maquina, function(data) {
    $('#nro_admin_pedido').val(data.maquina.nro_admin).attr('data-maquina', data.maquina.id_maquina).prop('readonly', true);
    $('#casino_pedido').val(data.casino.nombre).attr('data-casino', data.casino.id_casino).prop('readonly', true);

    $('#modalPedido #fechasPedido tbody tr').remove();
    $('#modalPedido #fechasPedido').toggle(data.fechas.length > 0);
    data.fechas.forEach(function(f){
      $('#modalPedido #fechasPedido tbody').append($('<tr>').append($('<td>').text(f.fecha)));
    });
    
    $('#modalPedido').modal('show');
  });
});

//Pedir máquina
$('#btn-pedido').click(function(e) {
  e.preventDefault();

  const formData = {
    nro_admin: $('#nro_admin_pedido').val(),
    casino: $('#casino_pedido').attr('data-casino'),
    fecha_inicio: $('#B_fecha_inicio_m').val(),
    fecha_fin: $('#B_fecha_fin_m').val(),
  };

  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $.ajax({
      type: 'POST',
      url: 'estadisticas_relevamientos/guardarMtmAPedido',
      data: formData,
      dataType: 'json',
      success: function(data) {
          console.log('Pedido: ', data);
          let mañana = new Date();
          const hoy = new Date();
          mañana.setDate(mañana.getDate() + 1);
          mañana.setHours(0, 0, 0, 0);
          const fecha_retorno = new Date(data.fecha.split('-').join('/'));

          console.log('Hoy: ', hoy, 'Mañana: ', mañana, 'Devuelta: ', fecha_retorno);
          for (var f = mañana; f <= fecha_retorno; f.setDate(f.getDate() + 1)) {
              var fila = $('<tr>').append($('<td>').text(dateToString(f)));
              console.log('Agregando ' + f.toString());
              $('#modalPedido #fechasPedido tbody').append(fila);
              $('#modalPedido #fechasPedido').show();
          }
      },
      error: function(data) {
          var response = JSON.parse(data.responseText);

          if (typeof response.fecha_inicio !== 'undefined') {
              $('#B_fecha_inicio_m').popover('show');
              $('.popover').addClass('popAlerta');
          }

      },
  });
});

});
