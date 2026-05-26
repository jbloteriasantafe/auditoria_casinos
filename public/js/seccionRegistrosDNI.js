import "/js/Components/modal.js";
import "/js/Components/inputFecha.js";
import "/js/Components/FiltroTabla.js";
import '/js/Components/modalEliminar.js';
import {AUX} from "/js/Components/AUX.js";
import "/js/md5.js";

$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Registros DNIs');
  
  $('[data-js-change-visualizando]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-visualizando'));
    selec.attr('data-visualizando',$(o).val());
  }).trigger('change');
  
  $('[data-js-change-clear]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-clear'));  
    $(selec).val('').trigger('onlyset');
  });
  
  $('[data-js-change-set]').on('change onlyset',function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-set'));
    selec.val($(o).val());
  }).trigger('onlyset');//Setea los hidden inputs el trigger
  
  $('[data-js-change-trigger-buscar]').change(function(e){
    const o = e.currentTarget;
    const selec = $($(o).attr('data-js-change-trigger-buscar'));
    selec.trigger('buscar');
  }).eq(0).trigger('change');//Trigereo solo 1 change para que busque al iniciar la pantalla
    
  $('[data-js-click-mostrar-modal]').click(function(e){
    const o = e.currentTarget;
    const selec = $(o).attr('data-js-click-mostrar-modal');
    const params = JSON.parse($(o).attr('data-js-click-mostrar-modal-params') ?? '{}');
    $(selec).trigger('mostrar',params ?? {});
  });
  
  $('[data-js-modal-importar-registros-dni]').each(function(_,Mobj){
    const M = $(Mobj);
    M.find('#archivo').change(function(e){
      $(e.currentTarget).trigger('fileselect');//@HACK para que dispare en md5.js
    });
    M.on('mostrar',function(e,params){
      M.find('[name]:not([readonly])').val('');
      M.find('[data-js-fecha]').data('datetimepicker').reset();
      M.modal('show');
    });
  });
  
  $('[data-js-click-submit-form]').click(function(e){
    const o = e.currentTarget;
    const select = $(o).attr('data-js-click-submit-form');
    const $form = $(select);
    const form = $form?.[0] ?? undefined;
    const formData = new FormData(form);
    const ajax_params = JSON.parse($form.attr('data-ajax-params') ?? '{}') ?? {};
    const modal_cargando = $('[data-js-modal-cargando]').eq(0).modal('show');
    $.ajax({
      type: $form.attr('method'),
      url: $form.attr('action'),
      data: formData,
      ...ajax_params,
      success: function (data) {
        modal_cargando.modal('hide');
        $('[data-js-filtro-tabla]').trigger('buscar');
        AUX.mensajeExito(data?.mensaje ?? '');
        $(o).closest('.modal').modal('hide');
      },
      error: function (data) {
        modal_cargando.modal('hide');
        const json = data.responseJSON ?? {};
        AUX.mensajeError(json?.mensaje ?? '');
        AUX.mostrarErroresNames($form,json);
        console.log(data);
      }
    });
  });
  
  const f_click_borrar = function(e){
    const tgt = $(e.currentTarget);
    const url = tgt.attr('data-js-click-borrar');
    const id  = tgt.val();
    const modal_cargando = $('[data-js-modal-cargando]').eq(0);
    $('[data-js-modal-eliminar]').trigger('mostrar',[{
      url: url+'/'+id,
      url_params: {},
      mensaje: 'Esta seguro que desea eliminarlo',
      success: function(data){
        AUX.mensajeExito(data?.mensaje ?? '');
        $('[data-js-filtro-tabla]').trigger('buscar');
      },
      error: function (data) {
        const json = data.responseJSON ?? {};
        AUX.mensajeError(json?.mensaje ?? '');
        console.log(data);
      },
      ext_params: {
        beforeSend: function(){
          modal_cargando.modal('show');
        },
        complete: function(){
          modal_cargando.modal('hide');
        }
      }
    }]);
  };
  
  const f_click_asignar = function(e){
    const o = e.currentTarget;
    const md5 = $(o).find('[data-key="md5"]').text();
    $('input[name="md5"]').val(md5);
    $('[data-js-change-visualizando]').val('registros').trigger('change');
    $('[data-js-filtro-tabla]').trigger('buscar');
  };
  
  //Para el boton limpiar
  $('[data-js-click-asignar-md5]').click(f_click_asignar);
  
  $('[data-js-filtro-tabla]').each(function(idx,fObj){ $(fObj).on('busqueda',function(e,ret,tbody,molde){  
    ret.data.forEach(function(r){
      const fila = molde.clone();
      for(const k in r){
        fila.find(`[data-key="${k}"]`).text(r[k] ?? '-');
      }
      fila.find('button').val(r.id_registros_dni_importacion ?? r.id_registros_dni);
      fila.attr('data-table-key-id',r.id_registros_dni_importacion? 'id_registros_dni_importacion' : 'id_registros_dni');
      fila.attr('data-table-key-val',r.id_registros_dni_importacion ?? r.id_registros_dni);
      tbody.append(fila);
    });
    tbody.find('[data-js-click-borrar]').click(f_click_borrar);
    tbody.find('[data-js-click-asignar-md5]').click(f_click_asignar);
  }).trigger('buscar'); });
});
