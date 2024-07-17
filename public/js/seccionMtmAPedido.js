import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';
import '/js/Relevamientos/modalMtmAPedido.js';

$(document).ready(function(){
  
$('.tituloSeccionPantalla').text('MTM a pedido');

$('[data-js-cambio-casino-select-sectores]')
.trigger('set_url',['mtm_a_pedido/obtenerSectoresPorCasino'])
.trigger('change');
  
$('[data-js-filtro-tabla]').each(function(idx,fObj){ $(fObj).on('busqueda',function(e,ret,tbody,molde){  
  ret.data.forEach(function(r){
    const fila = molde.clone();
    fila.find('.nro_admin').text(r.nro_admin);
    fila.find('.fecha').text(convertirDate(r.fecha));
    fila.find('.casino').text(r.nombre);//@TODO: renombrar
    fila.find('.sector').text(r.descripcion);//@TODO: renombrar
    fila.find('.nro_isla').text(r.nro_isla);
    fila.find('button').val(r.id_maquina_a_pedido);
    tbody.append(fila);
  });
  
  tbody.find('[data-js-eliminar-mtm-a-p]').click(function(e){
    $('[data-js-modal-eliminar]').trigger('mostrar',[{
      url: 'mtm_a_pedido/eliminarMmtAPedido/'+$(e.currentTarget).val(),
      mensaje: 'Desea eliminar la MTM a pedido?',
      success: function(){$(fObj).trigger('buscar');},
    }]);
  });
}).trigger('buscar'); });

$('[data-js-abrir-modal-mtm-a-p]').click(function(e){
  e.preventDefault();
  $('[data-js-modal-mtm-a-p]').trigger('mostrar',[{url: 'mtm_a_pedido/guardarMtmAPedido'}]);
});

$('[data-js-modal-mtm-a-p]').on('creado',function(e){
  $('[data-js-filtro-tabla]').trigger('buscar');
  $(e.currentTarget).modal('hide');
});

});
