import '/js/Components/inputFecha.js';
import '/js/Components/modal.js';
import {AUX} from "/js/Components/AUX.js";

$('[data-js-modal-mtm-a-p]').each(function(midx,Mobj){
  const M = $(Mobj);
  let url = undefined;
  M.on('mostrar',function(e,params){
    ocultarErrorValidacion(M.find('[name]').val(''));
    M.find('[data-js-fecha]').each(function(fidx,fObj){
      $(fObj).data('datetimepicker').reset();
    });
    for(const k in (params.vals ?? {})){
      const sel = M.find(`[name=${k}]`);
      const fecha = sel.parent().filter('[data-js-fecha]');
      const val = params.vals[k];
      if(fecha.length){
        fecha.data('datetimepicker').setDate(val)
      }
      else{
        sel.val(val);
      }
    }
    for(const k in (params.readonly ?? {})){
      const sel = M.find(`[name=${k}]`);
      const fecha = sel.parent().filter('[data-js-fecha]');
      const readonly = params.readonly[k];
      if(fecha.length){
        fecha[0].readonly(readonly);
      }
      else{
        if(readonly){
          sel.attr('readonly',true);
          sel.filter('select').css('pointer-events','none');//Para los select tengo que deshabilitarlo asi...
        }
        else{
          sel.removeAttr('readonly');
        }
      }
    }
    url = params.url;
    M.modal('show');
  });
  
  M.find('[data-js-aceptar]').click(function(e){
    ocultarErrorValidacion(M.find('[name]'));
    const fd = AUX.form_entries(M.find('form')[0]);

    AUX.POST(url,fd,
      function(data){
        AUX.mensajeExito('El Pedido fue CREADO correctamente');
        M.trigger('creado',[fd,data]);
      },
      function(data){
        console.log(data);
        AUX.mostrarErroresNames(M.find('form'),data.responseJSON ?? {});
        M.trigger('error');
      }
    );
  });
});
