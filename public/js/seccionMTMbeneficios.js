$(document).ready(function(){
  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');
  $('.tituloSeccionPantalla').text('Informes de tragamonedas');
  $('#opcInformesMTM').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesMTM').addClass('opcionesSeleccionado');
});

//MUESTRA LA PLANILLA
$(document).on('click','.planilla',function(){
    $('#alertaArchivo').hide();
    window.open('informesMTM/generarPlanilla/' + $(this).attr('data-anio') +"/"+ $(this).attr('data-mes') +"/"+ $(this).attr('data-casino') +"/"+ $(this).attr('data-moneda'),'_blank');
});
