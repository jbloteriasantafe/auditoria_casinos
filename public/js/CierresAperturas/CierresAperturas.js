import "./FiltroTabla.js";
import "./generar.js";
import "./eliminar.js";
import "./aperturasAPedido.js";
import "./verCierreApertura.js";
import "./validarApertura.js";
import "./desvincular.js";
import "./cmApertura_cmvCierre.js";

import "/js/bootstrap-datetimepicker.js";
import "/js/bootstrap-datetimepicker.es.js";

$(function() {
  $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') } });
  $('.tituloSeccionPantalla').text('Cierres y Aperturas');
  $('[data-js-fecha]').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
  });
  
  $(document).on('click','[data-js-mostrar]',function(e){
    e.preventDefault();
    $($(this).attr('data-js-mostrar')).trigger(
      'mostrar',[JSON.parse($(this).attr('data-js-mostrar-params') ?? '{}')]
    );
  });
  
  $('[data-js-mostrar]').each(function(i,o){
    const modal = $($(o).attr('data-js-mostrar'));
    modal.on('success',function(e){
      $('.tab_content:visible [data-js-buscar]').click();
    });
  });
  
  $('[data-minimizar]').click(function() {
    const minimizar = $(this).data('minimizar');
    $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
    $(this).data("minimizar", !minimizar);
  });

  $('.modal').on('shown.bs.modal',function(){
    const min = $(this).find('[data-minimizar]');
    if(!min.data('minimizar')){
      setTimeout(function(){
        min.click();
      },250);
    }
  });
  
  $('[data-js-filtro-tabla]').on('busqueda',function(e,ret,tbody,molde){
    ret.data.forEach(function(obj){
      const fila = molde.clone();
      Object.keys(obj).forEach(function(k){
        fila.find('.'+k).text(obj[k]);
      });
      fila.find('button').val(obj.id).attr('data-js-mostrar-params',JSON.stringify({
        id: obj.id
      })).filter(function(idx,o){
        return !$(o).attr('data-estados').split(',').includes(obj.estado+'');
      }).remove();
      fila.find('.estado').empty().append(
        $(`#iconosEstados i[data-linkeado=${obj.linkeado}][data-estado=${obj.estado}]`).clone()
      );
      tbody.append(fila);
    });
  });
  
  $('[data-js-tabs]').each(function(_,tab_group){
    $(tab_group).find('[data-js-tab]').click(function(e){
      $(tab_group).find('[data-js-tab]').removeClass("active");
      $(this).addClass('active');
      const tab = $($(this).attr('data-tab-target')); //Find the href attribute value to
      tab.find('.filtro_tabla_filtro [name]').val('');//Limpio los filtros
      tab.find('[data-js-buscar]').click();
      $('.tab_content').hide();
      tab.show();
      
      setTimeout(function(){//@HACK: nose porque scrollea cuando tabea...
        $(tab_group).find('[data-js-tab]').get(0).scrollIntoView();
      },50);
    }).eq(0).click();
  });
});
