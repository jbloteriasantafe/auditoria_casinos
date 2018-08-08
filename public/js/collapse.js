/*
    Para cada nivel del menú de la barra izquierda,
    si se despliaga alguno se cierran los otros del mismo nivel.
*/

$('#seccionMenu').on('show.bs.collapse','.collapseNivel1', function (e) {
  $('#seccionMenu .collapseNivel1').not(this).collapse("hide");
});

$('#seccionMenu').on('show.bs.collapse','.collapseNivel2', function (e) {
  $('#seccionMenu .collapseNivel2').not(this).collapse("hide");
});

$('#seccionMenu').on('show.bs.collapse','.collapseNivel3', function (e) {
  $('#seccionMenu .collapseNivel3').not(this).collapse("hide");
});
