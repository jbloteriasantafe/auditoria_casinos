import "/js/bootstrap-datetimepicker.js";
import "/js/bootstrap-datetimepicker.es.js";

$(document).on('init.fecha','[data-js-fecha]',function(e){
  const tgt_obj = e.currentTarget;
  const tgt = $(tgt_obj);
  
  tgt_obj.disabled = function(disabled){
    tgt.find('input').attr('disabled',disabled? true : false);
    tgt.attr('data-disabled',disabled? 1 : 0);
  };
  tgt_obj.readonly = function(readonly){
    tgt.find('input').attr('readonly',readonly? true : false);
    tgt.attr('data-readonly',readonly? 1 : 0);
  };
  tgt.on('show',function(e){
    if(tgt.attr('data-disabled') == 1 || tgt.attr('data-readonly') == 1){
      tgt.data('datetimepicker').hide();
    }
  });
    
  if(typeof tgt.data('datetimepicker') != 'undefined') return;
    
  tgt.datetimepicker({
    language:  tgt.attr('data-date-language') ?? 'es',
    todayBtn:  tgt.attr('data-date-today-btn') ?? 1,
    autoclose: tgt.attr('data-autoclose') ?? 1,
    todayHighlight: tgt.attr('data-date-today-highlight') ?? 1,
    format: tgt.attr('data-date-format') ?? 'yyyy-mm-dd',
    linkFormat: tgt.attr('data-link-format') ?? 'yyyy-mm-dd',      
    pickerPosition: tgt.attr('data-picker-position') ?? "bottom-left",
    startView: tgt.attr('data-start-view') ?? 2,
    minView: tgt.attr('data-min-view') ?? 2,
    startDate: tgt.attr('data-startdate') ?? undefined,
    endDate: tgt.attr('data-enddate') ?? undefined,
  });
    
  let disabled = true;
  try {
    disabled = JSON.parse(tgt.attr('data-disabled'));
  }
  catch (e) {}
  
  let readonly = true;
  try {
    readonly = JSON.parse(tgt.attr('data-readonly'));
  }
  catch (e) {}
});

$(function(e){
  $('[data-js-fecha]').trigger('init.fecha');
});
