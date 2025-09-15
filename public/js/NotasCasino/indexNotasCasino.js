$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Expedientes");
});

//SETEO FECHA MINIMA CALENDARIOS
const hoy = new Date().toISOString().split("T")[0];
const FECHA_HOY = new Date(hoy);
FECHA_HOY.setHours(0, 0, 0, 0);
$("#fechaInicio").attr("min", hoy);
$("#fechaFinalizacion").attr("min", hoy);

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

function clearInputs() {
  const defaultText = "Ningún archivo seleccionado";
  const archivosText = [
    {
      idSpan: "#adjuntoPautasName",
      idButton: "#eliminarAdjuntoPautas",
    },
    {
      idSpan: "#adjuntoDisenioName",
      idButton: "#eliminarAdjuntoDisenio",
    },
    {
      idSpan: "#basesyCondicionesName",
      idButton: "#eliminarBasesyCondiciones",
    },
    {
      idSpan: "#adjuntoInfTecnicoName",
      idButton: "#eliminarAdjuntoInfTecnico",
    },
  ];
  $("#formulario")[0].reset();
  for (const { idSpan, idButton } of archivosText) {
    $(idSpan).text(defaultText);
    $(idButton).hide();
  }
}

function clearErrors() {
  const campos = [
    {
      id: "#nroNota",
      error: "#mensajeErrorNroNota",
    },
    {
      id: "#tipoNota",
      error: "#mensajeErrorTipoNota",
    },
    {
      id: "#anioNota",
      error: "#mensajeErrorAnioNota",
    },
    {
      id: "#nombreEvento",
      error: "#mensajeErrorNombreEvento",
    },
    {
      id: "#tipoEvento",
      error: "#mensajeErrorTipoEvento",
    },
    {
      id: "#categoria",
      error: "#mensajeErrorCategoria",
    },
    {
      id: "#fechaInicio",
      error: "#mensajeErrorFechaInicio",
    },
    {
      id: "#fechaFinalizacion",
      error: "#mensajeErrorFechaFinalizacion",
    },
  ];

  for (const { id, error } of campos) {
    $(id).removeClass("input-error");
    $(error).hide();
  }
}

//ACCIONES
//abrir modal nota
$(document).on("click", "#btn-agregar-nota", function (e) {
  e.preventDefault();
  clearInputs();
  clearErrors();
  colorBoton("#btn-guardar-nota");
  $("#modalSubirNota").modal("show");
});

//manejo carga de numero de nota
$("#nroNota").on("keydown", function (e) {
  if (
    e.key === "Backspace" ||
    e.key === "Tab" ||
    e.key === "ArrowLeft" ||
    e.key === "ArrowRight"
  ) {
    return;
  }
  if (!/^[0-9]$/.test(e.key)) {
    e.preventDefault();
  }
});

$("#nroNota").on("input", function () {
  this.value = this.value.replace(/[^0-9]/g, "");
});

//! SE PODRIA REFACTORIZAR CON UN FOR PARA QUE QUEDE MAS LIMPIO
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

//! SE PODRIA REFACTORIZAR CON UN FOR PARA QUE QUEDE MAS LIMPIO
//limito el tamaño de los elementos cargados
const MAX_SIZE_MB = 150;
const MAX_SIZE_BYTES = MAX_SIZE_MB * 1024 * 1024;

$("#adjuntoPautas").on("change", function (e) {
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorAdjuntoPautas").show();
    return;
  }
  $("#mensajeErrorAdjuntoPautas").hide();
});

$("#adjuntoDisenio").on("change", function (e) {
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorAdjuntoDisenio").show();
    return;
  }
  $("#mensajeErrorAdjuntoDisenio").hide();
});

$("#basesyCondiciones").on("change", function (e) {
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorBasesyCondiciones").show();
    return;
  }
  $("#mensajeErrorBasesyCondiciones").hide();
});

$("#adjuntoInfTecnico").on("change", function (e) {
  const archivo = this.files[0];
  if (archivo && archivo.size > MAX_SIZE_BYTES) {
    $("#mensajeErrorAdjuntoInfTecnico").show();
    return;
  }
  $("#mensajeErrorAdjuntoInfTecnico").hide();
});

//! SE PODRIA REFACTORIZAR CON UN FOR PARA QUE QUEDE MAS LIMPIO
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

function validarFechas() {
  let esValido = true;

  const fechaInicioStr = $("#fechaInicio").val();
  const fechaFinalizacionStr = $("#fechaFinalizacion").val();

  const fechaInicio = new Date(fechaInicioStr);
  const fechaFinalizacion = new Date(fechaFinalizacionStr);

  fechaInicio.setHours(0, 0, 0, 0);
  fechaFinalizacion.setHours(0, 0, 0, 0);

  //existencia
  if (!fechaInicioStr) {
    $("#fechaInicio").addClass("input-error");
    $("#mensajeErrorFechaInicio").show();
    esValido = false;
  }
  //valdidez
  if (fechaInicio.getTime() < FECHA_HOY.getTime()) {
    $("#fechaInicio").addClass("input-error");
    $("#mensajeErrorFechaInicio").text(
      "La fecha de inicio no puede ser anterior a la fecha actual."
    );
    $("#mensajeErrorFechaInicio").show();
    esValido = false;
  }
  //existencia
  if (!fechaFinalizacionStr) {
    $("#fechaFinalizacion").addClass("input-error");
    $("#mensajeErrorFechaFinalizacion").show();
    esValido = false;
  }
  //validez
  if (fechaFinalizacion.getTime() < FECHA_HOY.getTime()) {
    $("#fechaFinalizacion").addClass("input-error");
    $("#mensajeErrorFechaFinalizacion").text(
      "La fecha de finalización no puede ser anterior a la fecha actual."
    );
    $("#mensajeErrorFechaFinalizacion").show();
    esValido = false;
  }
  //sentido entre si
  if (fechaInicio.getTime() > fechaFinalizacion.getTime()) {
    $("#fechaInicio").addClass("input-error");
    $("#mensajeErrorFechaInicio").text(
      "La fecha de inicio no puede ser posterior a la fecha de finalización."
    );
    $("#mensajeErrorFechaInicio").show();
  }

  if (fechaFinalizacion.getTime() < fechaInicio.getTime()) {
    $("#fechaFinalizacion").addClass("input-error");
    $("#mensajeErrorFechaFinalizacion").text(
      "La fecha de finalización no puede ser anterior a la fecha de inicio."
    );
    $("#mensajeErrorFechaFinalizacion").show();
    esValido = false;
  }

  return esValido;
}

function validarArchivos() {
  let esValido = true;
  const archivos = [
    { input: "#adjuntoPautas", error: "#mensajeErrorAdjuntoPautas" },
    { input: "#adjuntoDisenio", error: "#mensajeErrorAdjuntoDisenio" },
    { input: "#basesyCondiciones", error: "#mensajeErrorBasesyCondiciones" },
    { input: "#adjuntoInfTecnico", error: "#mensajeErrorAdjuntoInfTecnico" },
  ];

  for (const { input, error } of archivos) {
    const archivo = $(input)[0].files[0];
    if (archivo && archivo.size > MAX_SIZE_BYTES) {
      $(error).show();
      esValido = false;
      continue;
    }
    $(error).hide();
  }

  return esValido;
}

//TODO: FALTA AGREGAR VALIDACION DE CANTIDAD DE CARACTERES A LOS AÑOS, A NOMBRE EVENTO, FECHA REFERENCIA
//TODO: PUEDO AGREGAR PLACE HOLDERS QUE DIGAN ESE MAXIMO
//TODO: MANEJAR EL POSTEO DEL FORMULARIO
function validarCampos() {
  clearErrors();

  let esValido = true;

  const campos = [
    {
      id: "#nroNota",
      error: "#mensajeErrorNroNota",
      validar: (value) => value && value > 0,
    },
    {
      id: "#tipoNota",
      error: "#mensajeErrorTipoNota",
      validar: (value) => value,
    },
    {
      id: "#anioNota",
      error: "#mensajeErrorAnioNota",
      validar: (value) => value && value > 0,
    },
    {
      id: "#nombreEvento",
      error: "#mensajeErrorNombreEvento",
      validar: (value) => value && value.trim() !== "",
    },
    {
      id: "#tipoEvento",
      error: "#mensajeErrorTipoEvento",
      validar: (value) => value,
    },
    {
      id: "#categoria",
      error: "#mensajeErrorCategoria",
      validar: (value) => value,
    },
    /*     {
      id: "#fechaReferenciaEvento",
      error: "#mensajeErrorFechaReferenciaEvento",
      validar: (value) => value,
    },
    {
      id: "#mesReferenciaEvento",
      error: "#mensajeErrorMesReferenciaEvento",
      validar: (value) => value && value > 0,
    }, */
  ];

  for (const { id, error, validar } of campos) {
    const value = $(id).val();
    if (!validar(value)) {
      $(id).addClass("input-error");
      $(error).show();
      esValido = false;
    }
  }

  const validacionArchivos = validarArchivos();
  if (!validacionArchivos) {
    esValido = false;
  }

  const validacionFechas = validarFechas();
  if (!validacionFechas) {
    esValido = false;
  }

  return esValido;
}

//manejo posteo de la nota
$("#btn-guardar-nota").on("click", function (e) {
  e.preventDefault();
  const isValid = validarCampos();
  if (!isValid) {
    return;
  }
  //SI SE POSTEO CORRECTAMENTE
  clearInputs();
  clearErrors();
});
