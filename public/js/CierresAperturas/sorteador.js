import {AUX} from "./AUX.js";
import "./inputFecha.js";

$(function(e){
  const  M = $('[data-js-aperturas-sorteadas]');
  const $M = M.find.bind(M);
  
  const handleErr = (boton,data) => {
    boton.find('i.fa-spinner').remove();
    const errores = Object.keys(data.responseJSON ?? {}).map(function(k){
      return `${k}: ${data.responseJSON[k].join(', ')}`;
    });
    AUX.mensajeError(errores.join(' || '));
  };
  
  M.on('buscar',function(e){
    $M('[data-js-lista-aperturas-sorteadas]').empty();
    const formData = AUX.extraerFormData(M);
    AUX.GET('aperturas/obtenerAperturasSorteadas',formData,function(data){
      const mesas = data.mesas ?? [];
      mesas.forEach(function(m){
        $M('[data-js-lista-aperturas-sorteadas]').append(
          $('<option>').text(`${m.mesa} ${m.cargada? '✓' : ''}`)
          .val(m.id_mesa_de_panio)
          .attr('disabled',m.cargada)
          .addClass(m.cargada? 'cargada' : '')
        );
      });
      $M('[data-js-lista-aperturas-sorteadas]').attr('disabled',mesas.length == 0);
      const hoy = (new Date).toISOString().split('T')[0];
      $M('[data-js-sortear]').attr('disabled',!(mesas.length == 0 && formData.fecha_backup == hoy));
      $M('[data-js-descargar]').attr('disabled',!(mesas.length > 0));
      const hay_backup = data.hay_backup ?? 0;
      $M('[data-js-usar-backup]').attr('disabled',!(mesas.length == 0 && hay_backup));
    });
  });
  
  $M('[data-js-sortear]').click(function(e){
    const t = $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    const id_casino = AUX.extraerFormData(M).id_casino;
    AUX.GET('aperturas/sortearMesasSiNoHay/'+id_casino,{},
      function (data) {
        t.find('i.fa-spinner').remove();
        M.trigger('buscar');
      },
      function(data) { t.find('i.fa-spinner').remove();handleErr(t,data); }
    );
  });
  
  $M('[data-js-usar-backup]').click(function(e){
    const t = $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    const formData = AUX.extraerFormData(M);
    AUX.GET('aperturas/usarBackup',formData,
      function (data) {
        t.find('i.fa-spinner').remove();
        M.trigger('buscar');
      },
      function(data) { t.find('i.fa-spinner').remove();handleErr(t,data); }
    );
  });
  
  $M('[data-js-descargar]').click(function(e){
    const t = $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    AUX.GET('aperturas/generarRelevamiento', AUX.extraerFormData(M),
      function (data) {
        t.find('i.fa-spinner').remove();
        let iframe = document.getElementById("download-container");
        if (iframe === null){
          iframe = document.createElement('iframe');
          iframe.id = "download-container";
          iframe.style.visibility = 'hidden';
          document.body.appendChild(iframe);
        }
        iframe.src = 'aperturas/descargarZip/'+data.nombre_zip;
        M.trigger('buscar');
      },
      function(data) { t.find('i.fa-spinner').remove();handleErr(t,data); }
    );
  });
  
  $M('[data-js-cambio-casino],[data-js-cambio-fecha-backup]').change(function(e){
    M.trigger('buscar');
  });
});
