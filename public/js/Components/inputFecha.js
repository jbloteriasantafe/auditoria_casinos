import "/js/bootstrap-datetimepicker.js";
import "/js/bootstrap-datetimepicker.es.js";

$(function(e){
  $('[data-js-fecha]').each(function(_,d){
    d.disabled = function(disabled){
      $(d).find('input').attr('disabled',disabled? true : false);
      $(d).attr('data-disabled',disabled? 1 : 0);
    };
  })
  .each(function(_,d){
    console.log($(d).attr('data-start-view'));
    $(d).datetimepicker({
      language:  $(d).attr('data-date-language') ?? 'es',
      todayBtn:  $(d).attr('data-date-today-btn') ?? 1,
      autoclose: $(d).attr('data-autoclose') ?? 1,
      todayHighlight: $(d).attr('data-date-today-highlight') ?? 1,
      format: $(d).attr('data-date-format') ?? 'yyyy-mm-dd',
      linkFormat: $(d).attr('data-link-format') ?? 'yyyy-mm-dd',      
      pickerPosition: $(d).attr('data-picker-position') ?? "bottom-left",
      startView: $(d).attr('data-start-view') ?? 2,
      minView: $(d).attr('data-min-view') ?? 2,
      startDate: $(d).attr('data-startdate') ?? undefined,
      endDate: $(d).attr('data-enddate') ?? undefined,
    });
    
    let disabled = true;
    try {
      disabled = JSON.parse($(d).attr('data-disabled'));
    }
    catch (e) {}
    d.disabled(!!disabled);
  });
});
