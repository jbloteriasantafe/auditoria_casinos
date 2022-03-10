$(document).ready(function(){
  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes de tragamonedas');
  $('#opcInformesMTM').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesMTM').addClass('opcionesSeleccionado');
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
    const maq1 = $('#maquinasMenor').val();
    const maq2 = $('#maquinasMayor').val();
    const isla = $('#isla').val();
    const params = {
      'anio' : $(this).attr('data-anio'),'mes' : $(this).attr('data-mes'), 'id_casino' : $(this).attr('data-casino'),
      'id_tipo_moneda' : $(this).attr('data-moneda'), 'pdev' : $(this).attr('data-pdev'),
      'nro_admin_min': (maq1.length > 0? maq1 : -1),'nro_admin_max': (maq2.length > 0? maq2 : -1), 'nro_isla': isla,
    };
    if(maq1.length > 0 || maq2.length > 0){
      url += `generarPlanillaMaquinas`;
    }
    else if(isla){
      url += `generarPlanillaIsla`;
    }
    else{
      url += `generarPlanilla`;
    }
    window.open(url+'?'+encodeQueryData(params),'_blank');
});


$('#maquinasMenor,maquinasMayor,#isla').change(function(){
  const mmenor = $('#maquinasMenor').val().length > 0;
  const mmayor = $('#maquinasMayor').val().length > 0;
  const isla = $('#isla').val().length > 0;
  if(mmenor || mmayor){
    $('#isla').val("").attr('disabled',true);
    $('#maquinasMenor,#maquinasMayor').attr('disabled',false);
  }
  else if(isla){
    $('#maquinasMenor,#maquinasMayor').val("").attr('disabled',true);
    $('#isla').attr('disabled',false);
  }
  else{
    $('#maquinasMenor,#maquinasMayor,#isla').val("").attr('disabled',false);
  }
});