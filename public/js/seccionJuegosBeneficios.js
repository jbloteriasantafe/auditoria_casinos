$(document).ready(function(){

  $('#barraInformes').attr('aria-expanded','true');
  $('#informes').removeClass();
  $('#informes').addClass('subMenu1 collapse in');

  $('.tituloSeccionPantalla').text('Informes de mesas de paño');
  $('#opcInformesMesas').attr('style','border-left: 6px solid #185891; background-color: #131836;');
  $('#opcInformesMesas').addClass('opcionesSeleccionado');

});
