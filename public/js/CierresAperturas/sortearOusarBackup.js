import {AUX} from "/js/Components/AUX.js";
import "/js/Components/inputFecha.js";

$(function(e){
  const  M = $('[data-js-sortear-usar-backup]');
  const $M = M.find.bind(M);
  
  M.on('mostrar',function(e,tipo,id_casino,fecha){
    $M('[data-mostrar]').hide().filter(function(){
      return $(this)?.attr('data-mostrar')?.split(',')?.includes(tipo) ?? false;
    }).show();
    ocultarErrorValidacion($M('[name]'));
    $M('[name]').val('');
    $M('[name="id_casino"]').val(id_casino);
    $M('[data-js-fecha]').data('datetimepicker').reset();
    $M('[name="fecha_backup"]').closest('[data-js-fecha]').data('datetimepicker')
    .setDate(fecha ?? (new Date()));
    M.modal('show');
  });

  $M('[data-js-sortear]').click(function(e){
    const t = $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    try{
      const id_casino = AUX.extraerFormData(M).id_casino;
      AUX.GET('aperturas/sortearMesasSiNoHay/'+id_casino,{},
        function (data) {
          t.find('i.fa-spinner').remove();
          M.trigger('success');
          M.modal('hide');
          AUX.mensajeExito('Aperturas sorteadas');
        },
        function(data) { 
          t.find('i.fa-spinner').remove();
          const errores = Object.keys(data.responseJSON ?? {}).map(function(k){
            return `${k}: ${data.responseJSON[k].join(', ')}`;
          });
          AUX.mensajeError(errores.join(' || '));
        }
      );
    }
    catch (e){
      t.find('i.fa-spinner').remove();
      AUX.mensajeError();
    }
  });
  
  $M('[data-js-usar-backup]').click(function(e){
    const t = $(this).append('<i class="fa fa-spinner fa-spin"></i>');
    try{
      const formData = AUX.extraerFormData(M);
      AUX.GET('aperturas/usarBackup',formData,
        function (data) {
          t.find('i.fa-spinner').remove();
          M.trigger('success');
          M.modal('hide');
          AUX.mensajeExito('Aperturas restauradas del backup');
        },
        function(data) { 
          t.find('i.fa-spinner').remove();
          AUX.mostrarErroresNames(M,data.responseJSON ?? {});
        }
      );
    }
    catch (e){
      t.find('i.fa-spinner').remove();
      AUX.mensajeError();
    }
  });
});
