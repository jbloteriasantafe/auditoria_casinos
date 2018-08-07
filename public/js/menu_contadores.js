$(document).ready(function(){
  $('#menu_maquinas').parent().children('a').children('.nombreMenu1').addClass('menuActivoBlue');
  //Muestra el menu desplegado
  $('#menu_maquinas').parent().children('a').children('.iconoMenu1').children('img').attr('src','img/logos/tragaperras_blue.png');

  $('#menu_procedimientos').parent().children('a').children('.nombreMenu2').addClass('menuActivoBlue');

  $('#menu_contadores').parent().children('a').children('.nombreMenu2').addClass('menuActivoBlue');

  $('#menu_maquinas').attr('aria-expanded','true');
  $('#menu2_maquinas').addClass('in');

  $('#menu_procedimientos').attr('aria-expanded','true');
  $('#menu3_procedimientos').addClass('in');

  $('#menu_contadores').attr('aria-expanded','true');
  $('#menu3_contadores').addClass('in');

  $('#menu_contadores').attr('aria-expanded','true');
  $('#menu4_contadores').addClass('in');


  var completes = document.querySelectorAll(".complete");
  var toggleButton = document.getElementById("toggleButton");


function toggleComplete(){
  var lastComplete = completes[completes.length - 1];
  lastComplete.classList.toggle('complete');
}


// toggleButton.onclick = toggleComplete;

$('[data-toggle="popover"]').popover();


});
