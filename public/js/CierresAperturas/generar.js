import {AUX} from "./AUX.js";

$(function(e){
  const div = $('[data-js-generar-plantilla]');
  const G = div.find('[data-js-generar-plantilla-modal]');
  const R = div.find('[data-js-reintente]');
  div.on('mostrar',function(e,params){
    G.modal('show');
    AUX.POST('aperturas/generarRelevamiento',{},
      function (data) {
        G.modal('hide');
        var iframe;
        iframe = document.getElementById("download-container");
        if (iframe === null){
          iframe = document.createElement('iframe');
          iframe.id = "download-container";
          iframe.style.visibility = 'hidden';
          document.body.appendChild(iframe);
        }
        iframe.src = 'aperturas/descargarZip/'+data.nombre_zip;
      },
      function (data) {
        G.modal('hide');
        setTimeout(function(){
          R.modal('show');
        },500);
      }
    );
  });
});
