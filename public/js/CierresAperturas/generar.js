import {AUX} from "./AUX.js";
import "./modal.js";

$(function(e){
  const div = $('[data-js-generar-plantilla]');
  const G = div.find('[data-js-generar-plantilla-modal]');
  const R = div.find('[data-js-reintente]');
  div.on('mostrar',function(e,params){
    G.find('[data-js-generar-spinner]').hide();
    ocultarErrorValidacion(G.find('[name]'));
    const id_casino = G.find('[name="id_casino"]');
    id_casino.val('');
    const primer_casino = id_casino.find('option[value!=""]');
    if(primer_casino.length == 1){
      id_casino.val(primer_casino.val());
      setTimeout(function(){
        G.find('[data-js-generar-aperturas]').click();
      },500);
    }
    G.modal('show');
  });
  G.find('[data-js-generar-aperturas]').click(function(e){
    G.find('[data-js-generar-spinner]').show();
    AUX.POST('aperturas/generarRelevamiento/'+G.find('[name="id_casino"]').val(),{},
      function (data) {
        G.find('[data-js-generar-spinner]').hide();
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
        G.find('[data-js-generar-spinner]').hide();
        AUX.mostrarErroresNames(G,data.responseJSON ?? {});
        if(data.responseJSON.apertura){
          setTimeout(function(){
            R.find('.mensaje').html(data.responseJSON.apertura.join('<br>'));
            R.modal('show');
          },500);
        }
      }
    );
  });
});
