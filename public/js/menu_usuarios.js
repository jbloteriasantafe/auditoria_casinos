$(document).ready(function(){
  //Muestra el menu desplegado
    $('#menu_usuarios').attr('aria-expanded','true');
    $('#menu2_usuarios').addClass('in');
    $('#menu_usuarios').parent().children('a').children('.nombreMenu1').addClass('menuActivoBlue');
    //Cambiar color del icono
    $('#menu_usuarios').parent().children('a').children('.iconoMenu1').children('img').attr('src','img/logos/usuarios_blue.png');
    //Agregar barra
    // $('#menu_usuarios').parent().css('border-right','6px solid #3fbbff');
});
