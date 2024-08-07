import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';
import '/js/Components/listasAutocompletar.js';
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
    
    $('[data-js-modal-mtm-a-p] [data-js-tabla-fechas-pedidas]').trigger('mostrar_mtm_a_pedido');
  });
}).trigger('buscar');

$('#filtrosRelevamientos[data-js-filtro-tabla]').on('busqueda',function(e,ret,_,molde){
  const M = $('[data-js-modal-detalle-maquina]');
  
  M.find('[name="casino"]').val(ret.maquina.casino);
  M.find('[name="sector"]').val(ret.maquina.sector);
  M.find('[name="nro_isla"]').val(ret.maquina.nro_isla);
  M.find('[name="nro_admin"]').val(ret.maquina.nro_admin);
  
  ret.detalles.forEach(function(d){
    const fila = molde.clone();
    const attrs = ['fecha','coinin','coinout','jackpot','progresivo','producido_importado','diferencia',
      ...Array.from({ length: CONTADORES }, (_, idx) => 'cont'+(idx+1))
    ];
    for(const a of attrs){
      fila.find('.'+a).text(d[a] ?? '-');
    }
    fila.find('.producido_calculado_relevado').text(d.tipos_causa_no_toma ?? d.producido_calculado_relevado ?? '-');
    fila.toggleClass('no_tomado',d.tipos_causa_no_toma != null);
    M.find('[data-js-modal-detalle-maquina-tabla-relevamientos] tbody').append(fila);
  });
  

  $('[data-js-modal-detalle-maquina]').modal('show');
})
.on('error_busqueda',function(e,ret,tbody,molde){
  ocultarErrorValidacion($(this).find('[name]'));
  
  const errores = ret.responseJSON ?? {};
  setTimeout(function(){
    errores.nro_admin = errores.nro_admin ?? errores.id_maquina ?? undefined;//@HACK: leaky abstraction
    AUX.mostrarErroresNames($(e.currentTarget).find('form'),errores);
  },100);
});

$('[data-js-modal-mtm-a-p]').on('creado',function(e,formData,ret){
  $('[data-js-filtro-tabla]:not(#filtrosRelevamientos)').trigger('buscar');
  $(this).find('[data-js-tabla-fechas-pedidas]').trigger('mostrar_mtm_a_pedido');
});

$('[data-js-modal-mtm-a-p] [data-js-tabla-fechas-pedidas]').on('mostrar_mtm_a_pedido',function(e,callback){
  const formData = AUX.form_entries($('[data-js-modal-mtm-a-p] form')[0]);
  const tbody = $(e.currentTarget).find('tbody');
  tbody.empty().append('<tr><td><i class="fa fa-spinner fa-spin"></td></tr>');
  AUX.GET('estadisticas_relevamientos/obtenerFechasMtmAPedido',formData,
    function(data){
      (callback ?? function(){})();
      const fechas = data.fechas.length? data.fechas : [{fecha: '-SIN-'}];
      tbody.empty();
      fechas.forEach(function(f){
        tbody.append(
          $('<tr>').append($('<td>').text(f.fecha))
        );
      });
    },
    function(data){
      console.log(data);
      tbody.empty().append('<tr><td>ERROR</td></tr>');
    }
  );
});

});
