import {AUX} from "../CierresAperturas/AUX.js";

$(function(){
  const  M = $('[data-js-eliminar]');
  const $M = M.find.bind(M);
  M.on('mostrar',function(e,id){
    $M('[data-js-click-eliminar]').val(id);
    M.modal('show');
  });

  $M('[data-js-click-eliminar]').click(function(e){
    AUX.GET('apuestas/baja/' + $(this).val(),{},function(data){
      M.modal('hide');
      AUX.mensajeExito();
      M.trigger('success');
    });
  });
});
