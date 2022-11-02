$(document).ready(function(){
  $('.tituloSeccionPantalla').text('Informes de tragamonedas');
});

function encodeQueryData(data){
  const ret = [];
  for (let d in data)
    ret.push(encodeURIComponent(d) + '=' + encodeURIComponent(data[d]));
  return ret.join('&');
}

//MUESTRA LA PLANILLA
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
    let url = 'informesMTM/';
    const maquinas = $('#maquinas').val();
    const islas = $('#islas').val();
    const params = {
      'anio' : $(this).attr('data-anio'),'mes' : $(this).attr('data-mes'), 'id_casino' : $(this).attr('data-casino'),
      'id_tipo_moneda' : $(this).attr('data-moneda'), 'pdev' : $(this).attr('data-pdev'),
      'maquinas': maquinas, 'islas': islas,
    };
    if(maquinas.length > 0 || islas.length > 0){
      url += 'generarPlanillaIslasMaquinas';
    }
    else{
      url += 'generarPlanilla';
    }
    window.open(url+'?'+encodeQueryData(params),'_blank');
});
