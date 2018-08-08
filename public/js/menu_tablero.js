$(document).ready(function(){
  //Muestra el menu desplegado
    $('#menu_tablero').attr('aria-expanded','true');
    $('#menu2_tablero').addClass('in');
    $('#menu_tablero').parent().children('a').children('.nombreMenu1').addClass('menuActivoBlue');
    //Cambiar color del icono
    $('#menu_tablero').parent().children('a').children('.iconoMenu1').children('img').attr('src','img/logos/tablero_blue.png');
    //Agregar barra
    // $('#menu_expedientes').parent().css('border-right','6px solid #3fbbff');
});
