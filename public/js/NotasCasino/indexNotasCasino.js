$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Expedientes");
});

//FUNCIONES AUXILIARES
function colorBoton(boton) {
  $(boton).removeClass();
  $(boton).addClass("btn").addClass("btn-successAceptar");
  $(boton).css("cursor", "pointer");
  $(boton).text("Subir Nota");
  $(boton).show();
  $(boton).val("nuevo");
}
function clearInputs() {}

//ACCIONES
$(document).on("click", "#btn-agregar-nota", function (e) {
  e.preventDefault();
  colorBoton("#btn-guardar-nota");
  $("#modalSubirNota").modal("show");
});
