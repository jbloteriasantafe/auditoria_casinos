import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';
import '/js/Relevamientos/modalMtmAPedido.js';

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
    fila.find('button').val(r.id_maquina).attr('data-id_casino',r.id_casino).attr('data-nro_admin',r.nro_admin);
    tbody.append(fila);
  });
  
  tbody.find('[data-js-pedir]').click(function(e){
    e.preventDefault();
    $('[data-js-modal-mtm-a-p] [data-js-tabla-fechas-pedidas]').hide().find('tbody').empty();
    $('[data-js-modal-mtm-a-p]').trigger('mostrar',[{
      vals: {
        id_casino: $(e.currentTarget).attr('data-id_casino'),
        nro_admin: $(e.currentTarget).attr('data-nro_admin'),
        fecha_inicio: new Date()
      },
      readonly: {
        id_casino: true,
        nro_admin: true,
        fecha_inicio: true
      },
      url: 'estadisticas_relevamientos/guardarMtmAPedido'
    }]);
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

$('#filtrosRelevamientos[data-js-filtro-tabla]').on('busqueda',function(e,ret,_,molde){
  $('#casinoDetalle').val(ret.maquina.casino);
  $('#sectorDetalle').val(ret.maquina.sector);
  $('#islaDetalle').val(ret.maquina.isla);
  $('#adminDetalle').val(ret.maquina.nro_admin);
  
  // se cambia pora no recalcular, el relevamiento ya se hizo, por lo que se traen los valores calculados en su momento, sin alteraciones 
  ret.detalles.forEach(function(d){
    const fila = molde.clone();
    fila.find('.fecha').text(d.fecha ?? '-');
    fila.find('.cont1').text(d.cont1 ?? '-');
    fila.find('.cont2').text(d.cont2 ?? '-');
    fila.find('.cont3').text(d.cont3 ?? '-');
    fila.find('.cont4').text(d.cont4 ?? '-');
    fila.find('.cont5').text(d.cont5 ?? '-');
    fila.find('.cont6').text(d.cont6 ?? '-');
    fila.find('.cont7').text(d.cont7 ?? '-');
    fila.find('.cont8').text(d.cont8 ?? '-');
    fila.find('.coinin').text(d.coinin ?? '-');
    fila.find('.coinout').text(d.coinout ?? '-');
    fila.find('.jackpot').text(d.jackpot ?? '-');
    fila.find('.progresivo').text(d.progresivo ?? '-');
    fila.find('.producido_calculado_relevado').text(d.tipos_causa_no_toma ?? d.producido_calculado_relevado ?? '-');
    fila.find('.producido_importado').text(d.producido_importado ?? '-');
    fila.find('.diferencia').text(d.diferencia ?? '-');
    fila.toggleClass('no_tomado',d.tipos_causa_no_toma != null);
    $('#tablaRelevamientos tbody').append(fila);
  });
  
  $('#modalDetalle').modal('show');
})
.on('error_busqueda',function(e,ret,tbody,molde){
  ocultarErrorValidacion($(this).find('[name]'));
  
  const errores = ret.responseJSON ?? {};
  setTimeout(function(){
    AUX.mostrarErroresNames($(e.currentTarget).find('form'),errores);
    if(errores.id_maquina !== undefined){//@HACK: leaky abstraction
      mostrarErrorValidacion($(e.currentTarget).find('[name="nro_admin"]'),errores.id_maquina.join(', '),true);
    }
  },100);
});

$('[data-js-modal-mtm-a-p]').on('creado',function(e,formData,ret){
  $('[data-js-filtro-tabla]:not(#filtrosRelevamientos)').trigger('buscar');
  const fecha_inicio = new Date(formData.fecha_inicio+'T00:00');
  const fecha_fin    = new Date(
    ((formData.fecha_fin && formData.fecha_fin != '')? formData.fecha_fin : formData.fecha_inicio)
    +'T00:00'
  );
  $('[data-js-modal-mtm-a-p] [data-js-tabla-fechas-pedidas]').show().find('tbody').empty();
  for(const f=fecha_inicio;f<=fecha_fin;f.setDate(f.getDate()+1)){
    $(e.currentTarget).find('[data-js-tabla-fechas-pedidas] tbody').append(
      $('<tr>').append($('<td>').text(f.toISOString().split('T')[0]))
    );
  }
});

});
