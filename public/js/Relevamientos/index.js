import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import './maquinasPorRelevamientos.js';
import './generarRelevamiento.js';
import './relevamientoSinSistema.js';
import './cargarRelevamiento.js';
import './validarRelevamiento.js';

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Relevamientos');
  
  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(r){
      const fila = molde.clone();
      fila.find('.fecha').text(convertirDate(r.fecha));
      fila.find('.casino').text(r.casino);
      fila.find('.sector').text(r.sector);
      fila.find('.subrelevamiento').text(r.subrelevamiento ?? '');
      fila.find('[data-id-estado-relevamiento]').filter(function(){
        const list = $(this).attr('data-id-estado-relevamiento').split(',');
        return list.includes(r.id_estado_relevamiento+'');
      }).show(); 
      fila.find('button').val(r.id_relevamiento); 
      tbody.append(fila);
    });
  }).trigger('buscar');
  
  $('[data-js-cambio-casino-select-sectores]').each(function(){
    $(this).on('change',function(){
      const casino = $(this);
      
      const sectores = $(casino.attr('data-js-cambio-casino-select-sectores'));
      sectores.find('option:not([data-js-cambio-casino-mantener])').remove();
      
      if(casino.val() == '' || casino.val() == '0') return sectores.trigger('cambioSectores',[[]]);
      
      AUX.GET("relevamientos/obtenerSectoresPorCasino/"+casino.val(),{},function(data){
        data.sectores.forEach(function(s){
          sectores.append($('<option>').val(s.id_sector).text(s.descripcion));
        });
        sectores.trigger('cambioSectores',[data.sectores]);
      });
    });
  });
});

$('[data-toggle][data-minimizar]').click(function(){
  const minimizar = !!$(this).data('minimizar');
  $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
  $(this).data('minimizar',!minimizar);
});

$('#btn-nuevoRelevamiento').click(function(e){
  e.preventDefault();
  return $('[data-js-modal-generar-relevamiento]').trigger('mostrar');
});

$(document).on('click','.validado',function(){
  window.open('relevamientos/generarPlanillaValidado/' + $(this).val(),'_blank');
})

$(document).on('click','.imprimir',function(){
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

$(document).on('click','.planilla',function(){
  window.open('relevamientos/generarPlanilla/' + $(this).val(),'_blank');
});

$('#btn-relevamientoSinSistema').click(function(e) {
  e.preventDefault();
  return $('[data-js-modal-relevamiento-sin-sistema]').trigger('mostrar');
});

$('#btn-maquinasPorRelevamiento').click(function(e) {
  e.preventDefault();
  return $('[data-js-modal-maquinas-por-relevamiento]').trigger('mostrar');
});

$(document).on('click','.pop',function(e){
  e.preventDefault();
});

$(document).on('click','.pop',function(e){
    e.preventDefault();
    $('.pop').not(this).popover('hide');
    $(this).popover('show');
});
