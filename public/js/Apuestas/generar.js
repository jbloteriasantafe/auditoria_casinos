import {AUX} from "../CierresAperturas/AUX.js";

$(function(){
  const  M = $('[data-js-generar-relevamiento]');
  const $M = M.find.bind(M);
  M.on('mostrar',function(e){
    $M('[data-js-fecha]').data('datetimepicker').setDate(new Date());
    $M('[data-js-fecha]')[0].disabled(true);
    $M('[name="id_casino"]').val($M('[name="id_casino"] option:first').val());
    ocultarErrorValidacion($M('[name]'));
    M.modal('show');
  });
  $M('[data-js-generar]').on('click', function(e){
    e.preventDefault();
    const t = $(this);
    t.append('<i class="fa fa-spinner fa-spin"></i>');
    AUX.POST('apuestas/generarRelevamientoApuestas',AUX.extraerFormData(M),
      function (data) {
        t.find('i.fa-spinner').remove();
        M.trigger('success');
        M.modal('hide');
        let iframe = document.getElementById("download-container");
        if (iframe === null){
          iframe = document.createElement('iframe');
          iframe.id = "download-container";
          iframe.style.visibility = 'hidden';
          document.body.appendChild(iframe);
        }
        iframe.src = 'apuestas/descargarZipApuestas/'+data.nombre_zip;
      },
      function (data) {
        t.find('i.fa-spinner').remove();
        const json = data.responseJSON;
        AUX.mostrarErroresNames(M,json);
        AUX.mensajeError(json?.errores_generales?.join(', ') ?? '');
      }
    );
  });
});
