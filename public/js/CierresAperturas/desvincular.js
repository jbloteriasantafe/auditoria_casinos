import {AUX} from "./AUX.js";

$(function(e){
  const  M = $('[data-js-desvincular]');
  const $M = M.find.bind(M);
  M.on('mostrar',function(e,params){
    e.preventDefault();
    M.modal('show');
    $M('[data-js-desvincular-boton]').val(params.id);
  });
  $M('[data-js-desvincular-boton]').click(function(e){
    AUX.GET('aperturas/desvincularApertura/' + $(this).val(),{}, function(data){
      M.modal('hide');
      if(data==1){
        AUX.mensajeExito('Se ha desvinculado el cierre de esta Apertura.');
        M.trigger('success');
      }
      else{
        AUX.mensajeError('No es posible realizar esta acción, ya ha cerrado el periodo de producción correspondiente.');
      }
    });
  });
});
