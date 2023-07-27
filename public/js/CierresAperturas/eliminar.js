import {AUX} from "./AUX.js";

$(function(e){
  const  M = $('[data-js-alerta-baja]');
  const $M = M.find.bind(M);
  
  $M('[data-js-eliminar-apertura]').on('mostrar',function(e,params){
    $M('[data-js-eliminar]').attr('data-url','aperturas/bajaApertura');
    $M('[data-js-eliminar]').val(params.id);
    $M('.mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTA APERTURA?')
    M.modal('show');
  });

  $M('[data-js-eliminar-cierre]').on('mostrar',function(e){
    $M('[data-js-eliminar]').attr('data-url','cierres/bajaCierre');
    $M('[data-js-eliminar]').val(params.id);
    $M('.mensaje').text('¿ESTA SEGURO QUE DESEA ELIMINAR ESTE CIERRE?')
    M.modal('show');
  });

  $M('[data-js-eliminar]').click(function(){
    const url = $(this).attr('data-url')+'/'+$(this).val();
    AUX.GET(url,{},function(data){
      AUX.mensajeExito('Eliminado con éxito');
      M.trigger('success');
      M.modal('hide');
    });
  });
});
