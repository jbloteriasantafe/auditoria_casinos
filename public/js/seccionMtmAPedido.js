import '/js/Components/inputFecha.js';
import '/js/Components/FiltroTabla.js';
import '/js/Components/modalEliminar.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";
import '/js/Components/cambioCasinoSelectSectores.js';

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

$('[data-js-abrir-modal-mtm-a-p]').each(function(idx,bObj){ $(bObj).click(function(e){
  e.preventDefault();
  $('[data-js-modal-mtm-a-p]').trigger('mostrar');
}) });

$('[data-js-modal-mtm-a-p]').each(function(midx,Mobj){
  const M = $(Mobj);
  
  M.on('mostrar',function(e){
    ocultarErrorValidacion(M.find('[name]').val(''));
    M.find('[data-js-fecha]').each(function(fidx,fObj){
      $(fObj).data('datetimepicker').reset();
    });
    M.modal('show');
  });
  
  M.find('[data-js-aceptar]').click(function(e){
    ocultarErrorValidacion(M.find('[name]'));
    const fd = AUX.form_entries(M.find('form')[0]);

    AUX.POST('mtm_a_pedido/guardarMtmAPedido',fd,
      function(data){
        AUX.mensajeExito('El Pedido fue CREADO correctamente');
        $('[data-js-filtro-tabla]').trigger('buscar');
        M.modal('hide');
      },
      function(data){
        console.log(data);
        AUX.mostrarErroresNames(M.find('form'),data.responseJSON ?? {});
      }
    );
  });
});

});
