import "/js/bootstrap-datetimepicker.js";
import "/js/bootstrap-datetimepicker.es.js";

$(function(e){
  $('[data-js-fecha]').each(function(_,d){
    $(d).datetimepicker({
      language:  $(d).attr('data-date-language') ?? 'es',
      todayBtn:  $(d).attr('data-date-today-btn') ?? 1,
      autoclose: $(d).attr('data-autoclose') ?? 1,
      todayHighlight: $(d).attr('data-date-today-highlight') ?? 1,
      format: $(d).attr('data-date-format') ?? 'yyyy-mm-dd',
      pickerPosition: $(d).attr('data-picker-position') ?? "bottom-left",
      startView: $(d).attr('data-start-view') ?? 2,
      minView: $(d).attr('data-min-view') ?? 2,
      startDate: $(d).attr('data-startdate') ?? undefined,
      endDate: $(d).attr('data-enddate') ?? undefined,
    });
  });
  $('[data-js-fecha]').each(function(){
    $(this)[0].disabled = function(disabled){
      $(this).find('input').attr('disabled',disabled);
      $(this).find('span').css('visibility',disabled? 'hidden' : 'visible');
    };
  });
});
