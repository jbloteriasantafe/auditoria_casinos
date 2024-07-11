import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import './maquinasPorRelevamientos.js';
import './generarRelevamiento.js';
import './relevamientoSinSistema.js';
import './cargarRelevamiento.js';
import '/js/Components/cambioCasinoSelectSectores.js';

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Relevamientos');
  
  $('[data-js-filtro-tabla] [data-js-cambio-casino-select-sectores]').trigger('set_url',['relevamientos/obtenerSectoresPorCasino']);
  
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
    
    tbody.find('[data-js-click-id-link]').click(function(e){
      window.open($(this).attr('data-js-click-id-link') + $(this).val(),'_blank');
    });
    tbody.find('[data-js-mostrar-modal-carga]').click(function(e){
      const modo = $(this).attr('data-js-mostrar-modal-carga');
      const id_relevamiento = $(this).val();
      return $('[data-js-modal-cargar-relevamiento]').trigger('mostrar',[modo,id_relevamiento]);
    });
  }).trigger('buscar');
  
  $('[data-js-modal-generar-relevamiento]').on('creado',function(e,url_zip){
    $('[data-js-filtro-tabla]').trigger('buscar');
    $('[data-js-modal-generar-relevamiento]').modal('hide');

    let iframe = $('#download-container');
    if (iframe.length == 0){
      iframe = $('<iframe>').attr('id','download-container').css('visibility','hidden');
      $('body').append(iframe);
    }
    iframe.attr('src',url_zip);
  });
  
  $('[data-js-modal-relevamiento-sin-sistema]').on('creado',function(e){
    $('[data-js-filtro-tabla]').trigger('buscar');
    $('[data-js-modal-relevamiento-sin-sistema]').modal('hide');
  });
  
  $('[data-js-modal-cargar-relevamiento]').on('guardo finalizo valido',function(e){
    $('[data-js-filtro-tabla]').trigger('buscar');
    if(e.type != 'guardo'){
      $('[data-js-modal-cargar-relevamiento]').modal('hide');
    }
  });
  
  $('[data-js-mostrar-modal]').click(function(e){
    e.preventDefault();
    return $($(this).attr('data-js-mostrar-modal')).trigger('mostrar');
  });
});
