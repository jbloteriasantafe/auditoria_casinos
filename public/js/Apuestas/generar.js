import {AUX} from "../CierresAperturas/AUX.js";

$(function(){
  $('#btn-generar').on('click', function(e){
    e.preventDefault();
    $('#modalRelevamiento').modal('show');
    AUX.GET('apuestas/consultarMinimo',{},function(data){
      if(data.apuestas.length == 0){
        return AUX.mensajeError('No existen valores minimos');
      }
      AUX.POST('apuestas/generarRelevamientoApuestas',{},
        function (data) {
          //@TODO: buscar
          $('#modalRelevamiento').modal('hide');
          var iframe;
          iframe = document.getElementById("download-container");
          if (iframe === null){
              iframe = document.createElement('iframe');
              iframe.id = "download-container";
              iframe.style.visibility = 'hidden';
              document.body.appendChild(iframe);
          }
          iframe.src = 'apuestas/descargarZipApuestas/'+data.nombre_zip;
        },
        function (data) {
          var ap = $.parseJSON(data.responseText);
          $('#modalRelevamiento').modal('hide');
          $('#modalErrorRelevamientoA').modal('show');
        }
      );
    });
  });
});
