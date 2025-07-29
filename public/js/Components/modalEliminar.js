import {AUX} from "/js/Components/AUX.js";
import "/js/Components/modal.js";

$(function(e){ $('[data-js-modal-eliminar]').each(function(){
  const  M = $(this);
  const $M = M.find.bind(M);
  
  let url = undefined;
  let success = null;
  let error = null;
  let url_params = {};
  
  M.on('mostrar.modal',function(e,params){
    url = params.url;
    if(typeof url == 'undefined') throw 'No se recibio una URL';
    
    success = params.success ?? function(data){};
    error = params.error ?? function(data){console.log(data);};
    url_params = params.url_params ?? {};
    
    $M('.mensaje').text(params.mensaje ?? '');
    M.modal('show');
  });

  $M('[data-js-modal-eliminar-click-eliminar]').click(function(){
    AUX.DELETE(url,url_params,function(data){
      AUX.mensajeExito('Eliminado con éxito');
      M.modal('hide');
      success(data);
    },function(data){
      AUX.mensajeError('Error al eliminar');
      error(data);
    });
  });
  
})});
