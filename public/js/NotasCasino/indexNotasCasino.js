$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Expedientes");
});

//FUNCIONES AUXILIARES
//colorea boton modal
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
//abrir modal nota
$(document).on("click", "#btn-agregar-nota", function (e) {
  e.preventDefault();
  colorBoton("#btn-guardar-nota");
  $("#modalSubirNota").modal("show");
});

//manejo de carga de los adjuntos
$("#adjuntoPautasBtn").on("click", function (e) {
  $("#adjuntoPautas").click();
});

$("#adjuntoPautas").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#adjuntoPautasName").text(fileName);
    $("#eliminarAdjuntoPautas").hide();
    return;
  }
  const fileName = this.files[0].name;
  $("#adjuntoPautasName").text(fileName);
  $("#eliminarAdjuntoPautas").show();
});

$("#adjuntoDisenioBtn").on("click", function (e) {
  $("#adjuntoDisenio").click();
});

$("#adjuntoDisenio").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#adjuntoDisenioName").text(fileName);
    $("#eliminarAdjuntoDisenio").hide();
    return;
  }
  const fileName = this.files[0].name;
  $("#adjuntoDisenioName").text(fileName);
  $("#eliminarAdjuntoDisenio").show();
});

$("#basesyCondicionesBtn").on("click", function (e) {
  $("#basesyCondiciones").click();
});

$("#basesyCondiciones").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#basesyCondicionesName").text(fileName);
    $("#eliminarBasesyCondiciones").hide();
    return;
  }
  const fileName = this.files[0].name;
  $("#basesyCondicionesName").text(fileName);
  $("#eliminarBasesyCondiciones").show();
});

$("#adjuntoInfTecnicoBtn").on("click", function (e) {
  $("#adjuntoInfTecnico").click();
});

$("#adjuntoInfTecnico").on("change", function (e) {
  if (!this.files[0]) {
    const fileName = "Ningún archivo seleccionado";
    $("#adjuntoInfTecnicoName").text(fileName);
    $("#eliminarAdjuntoInfTecnico").hide();
    return;
  }
  const fileName = this.files[0].name;
  $("#adjuntoInfTecnicoName").text(fileName);
  $("#eliminarAdjuntoInfTecnico").show();
});

//manejo eliminacion adjuntos

$("#eliminarAdjuntoPautas").on("click", function (e) {
  $("#adjuntoPautas").val(null);
  $("#adjuntoPautasName").text("Ningún archivo seleccionado");
  $(this).hide();
});

$("#eliminarAdjuntoDisenio").on("click", function (e) {
  $("#adjuntoDisenio").val(null);
  $("#adjuntoDisenioName").text("Ningún archivo seleccionado");
  $(this).hide();
});

$("#eliminarBasesyCondiciones").on("click", function (e) {
  $("#basesyCondiciones").val(null);
  $("#basesyCondicionesName").text("Ningún archivo seleccionado");
  $(this).hide();
});

$("#eliminarAdjuntoInfTecnico").on("click", function (e) {
  $("#adjuntoInfTecnico").val(null);
  $("#adjuntoInfTecnicoName").text("Ningún archivo seleccionado");
  $(this).hide();
});

//funcion de validacion de campos
function clearErrors() {
  //TODO: REPLICAR ESTE COMPORTAMIENTO PARA TODOS LOS CAMPOS -> AGREGAR EL SPAN PARA MOSTRAR EL ERROR DEL MSJ
  $("#nroNota").removeClass("input-error");
  $("#mensajeErrorNroNota").hide();
}
function validarCampos() {
  clearErrors(); //TODO: AGREGAR TODOS LOS CAMPOS A LA FUNCION
  //TODO AGREGAR VALIDACION A LOS CAMPOS FALTATNES
  const numeroNota = $("#nroNota").val();
  if (!numeroNota || numeroNota <= 0) {
    $("#nroNota").addClass("input-error");
    $("#mensajeErrorNroNota").show();
    return false;
  }
}

//manejo posteo de la nota
$("#btn-guardar-nota").on("click", function (e) {
  e.preventDefault();
  const isValid = validarCampos();
  if (!isValid) {
    return;
  }
});
