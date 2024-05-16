import {AUX} from "/js/Components/AUX.js";

$(function(){ $('[data-js-cambio-casino-select-sectores]').each(function(){
  $(this).on('change',function(){
    const casino = $(this);
    
    const sectores = $(casino.attr('data-js-cambio-casino-select-sectores'));
    sectores.find('option:not([data-js-cambio-casino-mantener])').remove();
    
    if(casino.val() == '' || casino.val() == '0') return sectores.trigger('cambioSectores',[[]]);
    
    AUX.GET("relevamientos/obtenerSectoresPorCasino/"+casino.val(),{},function(data){
      data.sectores.forEach(function(s){
        sectores.append($('<option>').val(s.id_sector).text(s.descripcion));
      });
      sectores.trigger('cambioSectores',[data.sectores]);
    });
  });
});});
