$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Informes de tragamonedas');
  
  $('[data-js-dia-max-value').each(function(){
    $(this).attr('data-original-value',$(this).text());
  });
  $('[data-js-dia-max-value]').on('focusout',function(){
    const val = this.innerHTML;
    const orig_val  = $(this).attr('data-original-value');
    const max_val   = $(this).attr('data-js-dia-max-value');
    const es_numero = /^([1-9][0-9]?)$/.test(val);//Chequeo que sea un numero 1-99 y que sea menor al maximo
    const es_menor_al_max = parseInt(val) <= parseInt(max_val);
    if(!es_numero || !es_menor_al_max){
      event.preventDefault();
      return $(this).text(orig_val);
    }
    event.preventDefault();
    $(this).text(val);
  });
  
  function encodeQueryData(data){
    const ret = [];
    for (let d in data)
      ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
    return ret.join('&');
  }
  
  $('.planilla').click(function(){
      $('#alertaArchivo').hide();
      let url = 'informesMTM/';
      const maquinas = $('#maquinas').val();
      const islas = $('#islas').val();
      const dias = $(this).closest('tr').find('[data-js-dia-max-value]').map(function(){return this.innerHTML.replace(/!\d/g,'');}).toArray();
      const params = {
        'anio' : $(this).attr('data-anio'),'mes' : $(this).attr('data-mes'), 'id_casino' : $(this).attr('data-casino'),
        'id_tipo_moneda' : $(this).attr('data-moneda'), 'pdev' : $(this).attr('data-pdev'),
        'maquinas': maquinas, 'islas': islas,
        'dia1': dias[0],
        'dia2': dias[1],
      };
      if(maquinas.length > 0 || islas.length > 0){
        url += 'generarPlanillaIslasMaquinas';
      }
      else{
        url += 'generarPlanilla';
      }
      window.open(url+'?'+encodeQueryData(params),'_blank');
  });
  
  $('[data-toggle="popover"]').popover();
});
