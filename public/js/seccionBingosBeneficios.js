$(document).ready(function(){

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Informes de bingos');
  $('#opcInformesBingos').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesBingos').addClass('opcionesSeleccionado');

});
