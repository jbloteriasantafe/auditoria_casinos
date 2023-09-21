import {AUX} from "/js/Components/AUX.js";
import '/js/Components/inputFecha.js';

$(function(){
  const  M = $('[data-js-regenerar-backup]');
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e){
    $M('[name="id_casino"]').val($M('[name="id_casino"] option:first').val());
    $M('[data-js-fecha]').data('datetimepicker').reset();
    M.modal('show');
  });
  
  $M('[data-js-regenerar]').click(function(e){
    AUX.POST('apuestas/regenerarBackup',AUX.extraerFormData(M),
      function(data){
        AUX.mensajeExito('');
        M.modal('hide');
        M.trigger('success');
      },
      function(data){
        console.log(data);
        const json = data.responseJSON ?? {};
        AUX.mensajeError(json?.errores_generales?.join(', ') ?? '');
        AUX.mostrarErroresNames(M,json);
      }
    );
  });
});
