$(document).ready(function(){
  //Muestra el menu desplegado
    $('#menu_expedientes').attr('aria-expanded','true');
    $('#menu2_expedientes').addClass('in');
    $('#menu_expedientes').parent().children('a').children('.nombreMenu1').addClass('menuActivoBlue');
    //Cambiar color del icono
    $('#menu_expedientes').parent().children('a').children('.iconoMenu1').children('img').attr('src','img/logos/expedientes_blue.png');
    //Agregar barra
    // $('#menu_expedientes').parent().css('border-right','6px solid #3fbbff');
});
