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
    const maq1 = $('#maquinasMenor').val();
    const maq2 = $('#maquinasMayor').val();
    const islas = $('#islas').val();
    const params = {
      'anio' : $(this).attr('data-anio'),'mes' : $(this).attr('data-mes'), 'id_casino' : $(this).attr('data-casino'),
      'id_tipo_moneda' : $(this).attr('data-moneda'), 'pdev' : $(this).attr('data-pdev'),
      'nro_admin_min': (maq1.length > 0? maq1 : -1),'nro_admin_max': (maq2.length > 0? maq2 : -1), 'islas': islas,
    };
    if(maq1.length > 0 || maq2.length > 0){
      url += `generarPlanillaMaquinas`;
    }
    else if(islas){
      url += `generarPlanillaIsla`;
    }
    else{
      url += `generarPlanilla`;
    }
    window.open(url+'?'+encodeQueryData(params),'_blank');
});

$('#maquinasMenor,maquinasMayor,#islas').change(function(){
  const mmenor = $('#maquinasMenor').val().length > 0;
  const mmayor = $('#maquinasMayor').val().length > 0;
  const islas = $('#islas').val().length > 0;
  if(mmenor || mmayor){
    $('#islas').val("").attr('disabled',true);
    $('#maquinasMenor,#maquinasMayor').attr('disabled',false);
  }
  else if(islas){
    $('#maquinasMenor,#maquinasMayor').val("").attr('disabled',true);
    $('#islas').attr('disabled',false);
  }
  else{
    $('#maquinasMenor,#maquinasMayor,#islas').val("").attr('disabled',false);
  }
});
