import '/js/Components/inputFecha.js';
import {AUX} from "/js/Components/AUX.js";

$(function(e){ $('[data-js-modal-relevamiento-sin-sistema]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e){
    $M('[name]').val('').change();
    ocultarErrorValidacion($M('[name]'));
    $M('[data-js-fecha]').data('datetimepicker').reset();
    M.modal('show');
  });
  
  $M('[data-js-usar-relevamiento-backup]').click(function(e){
    const formData = AUX.form_entries($M('form')[0]);
    ocultarErrorValidacion($M('[name]'));
    AUX.POST('relevamientos/usarRelevamientoBackUp',formData,
      function (data) {
        $('[data-js-buscar]').click();//@TODO: modularizar
        M.modal('hide');
      },
      function (data) {
        console.log(data);
        const response = data.responseJSON;
        AUX.mostrarErroresNames(M,response ?? {});
      }
    );
  });
})});
