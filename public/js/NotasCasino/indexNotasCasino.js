//seteo nombre de la seccion y traigo notas
$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text(" Expedientes");
  cargarNotas();
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
    {
      id: "#fechaReferencia",
      error: "#mensajeErrorFechaReferencia",
    },
  ];

  for (const { id, error } of campos) {
    $(id).removeClass("input-error");
    $(error).hide();
  }
}

//paginacion
//crear bien los links y ahora creo un controlador que se encargue de mostrar el pdf
function generarFilaTabla(nota) {
  let fila = $("#cuerpoTabla .filaTabla")
    .clone()
    .removeClass("filaTabla")
    .show();

  fila
    .find(".numero_nota")
    .text(nota.nronota_ev || "No hay información disponible")
    .attr("title", nota.nronota_ev || "No hay información disponible");
  fila
    .find(".nombre_evento")
    .text(nota.evento || "No hay información disponible")
    .attr("title", nota.evento || "No hay información disponible");
  //! ACA TENGO QUE AGREGAR BIEN EL PATH DE DONDE ESTAN LOS ARCHIVOS
  fila
    .find(".adjunto_pautas")
    .html(
      `${
        !nota.adjunto_pautas
          ? "No hay información disponible"
          : `<a href='cargar-notas/notas/archivo/${nota.idevento_enc}/pautas'>${nota.adjunto_pautas}</a>`
      }`
    )
    .attr("title", nota.adjunto_pautas || "No hay información disponible");
  fila
    .find(".adjunto_disenio")
    .html(
      `${
        !nota.adjunto_diseño
          ? "No hay información disponible"
          : `<a href='cargar-notas/notas/archivo/${nota.idevento_enc}/disenio'>${nota.adjunto_diseño}</a>`
      }`
    )
    .attr("title", nota.adjunto_diseño || "No hay información disponible");
  fila
    .find(".adjunto_basesycond")
    .html(
      `${
        !nota.adjunto_basesycond
          ? "No hay información disponible"
          : `<a href='cargar-notas/notas/archivo/${nota.idevento_enc}/basesycond'>${nota.adjunto_basesycond}</a>`
      }`
    )
    .attr("title", nota.adjunto_basesycond || "No hay información disponible");
  fila
    .find(".fecha_inicio_evento")
    .text(nota.fecha_evento || "No hay información disponible")
    .attr("title", nota.fecha_evento || "No hay información disponible");
  fila
    .find(".fecha_finalizacion_evento")
    .text(nota.fecha_finalizacion || "No hay información disponible")
    .attr("title", nota.fecha_finalizacion || "No hay información disponible");
  fila
    .find(".estado")
    .text(nota.estado || "No hay información disponible")
    .attr("title", nota.estado || "No hay información disponible");
  fila
    .find(".notas_relacionadas")
    .text(nota.notas_relacionadas || "No hay información disponible")
    .attr("title", nota.notas_relacionadas || "No hay información disponible");

  return fila;
}

function cargarNotas(
  page = 1,
  perPage = 5,
  nroNota,
  nombreEvento,
  fechaInicio,
  fechaFin
) {
  let formData = new FormData();
  formData.append("page", page);
  formData.append("perPage", perPage);

  if (nroNota) {
    formData.append("nroNota", nroNota);
  }
  if (nombreEvento) {
    formData.append("nombreEvento", nombreEvento);
  }
  if (fechaInicio) {
    formData.append("fechaInicio", fechaInicio);
  }
  if (fechaFin) {
    formData.append("fechaFin", fechaFin);
  }

  $.ajax({
    type: "POST",
    url: "/cargar-notas/paginar",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    success: function (response) {
      // Limpiar tabla
      $("#cuerpoTabla tr").not(".filaTabla").remove();

      // Llenar tabla
      response.data.forEach(function (nota) {
        $("#tablaNotas tbody").append(generarFilaTabla(nota));
      });

      // Actualizar paginación
      $("#herramientasPaginacion").generarTitulo(
        response.current_page,
        response.per_page,
        response.total,
        clickIndice
      );
      $("#herramientasPaginacion").generarIndices(
        response.current_page,
        response.per_page,
        response.total,
        clickIndice
      );
    },
    error: function (xhr, status, error) {
      // Manejar el error
      console.error("Error al cargar notas:", err);
    },
  });
}

// Función para manejar cambio de página
function clickIndice(e, pageNumber, page_size) {
  e && e.preventDefault();
  var page_size = $("#size").val() || 5;

  const nroNota = $("#buscarNroNota").val();
  const nombreEvento = $("#buscarNombreEvento").val();
  const fechaInicio = $("#fecha_nota_inicio").val();
  const fechaFin = $("#fecha_nota_fin").val();

  cargarNotas(
    pageNumber,
    page_size,
    nroNota,
    nombreEvento,
    fechaInicio,
    fechaFin
  );
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

$("#nroNota").on("blur", function () {
  let valor = this.value.trim();

  if (!/^\d+$/.test(valor)) {
    this.value = "";
    return;
  }

  let numero = parseInt(valor, 10);

  if (numero >= 1 && numero <= 99) {
    this.value = numero.toString().padStart(3, "0");
  } else {
    this.value = numero.toString();
  }
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

function validarCampos() {
  clearErrors();

  let esValido = true;

  const campos = [
    {
      id: "#nroNota",
      error: "#mensajeErrorNroNota",
      validar: (value) => value && value > 0 && value.length >= 3,
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
      validar: (value) => value && value.trim() !== "" && value.length <= 1000,
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
    {
      id: "#fechaReferenciaEvento",
      error: "#mensajeErrorFechaReferenciaEvento",
      validar: (value) => value?.length <= 500 || !value,
    },
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

  $("#btn-guardar-nota").prop("disabled", true).text("PROCESANDO...");
  let formData = new FormData();
  const data = {
    nroNota: $("#nroNota").val(),
    tipoNota: $("#tipoNota").val(),
    anioNota: $("#anioNota").val(),
    nombreEvento: $("#nombreEvento").val(),
    tipoEvento: $("#tipoEvento").val(),
    categoria: $("#categoria").val(),
    fechaInicio: $("#fechaInicio").val(),
    fechaFinalizacion: $("#fechaFinalizacion").val(),
    fechaReferencia: $("#fechaReferencia").val(),
  };
  for (let campo in data) {
    formData.append(campo, data[campo]);
  }

  if ($("#adjuntoPautas")[0].files.length > 0) {
    formData.append("adjuntoPautas", $("#adjuntoPautas")[0].files[0]);
  }
  if ($("#adjuntoDisenio")[0].files.length > 0) {
    formData.append("adjuntoDisenio", $("#adjuntoDisenio")[0].files[0]);
  }
  if ($("#basesyCondiciones")[0].files.length > 0) {
    formData.append("basesyCondiciones", $("#basesyCondiciones")[0].files[0]);
  }

  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
    },
  });

  $.ajax({
    url: "cargar-notas/subir",
    type: "POST",
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    success: function (response) {
      if (response.success) {
        $("#mensajeExito h3").text("ÉXITO DE CARGA");
        $("#mensajeExito p").text("La nota se cargó correctamente");
        $("#modalSubirNota").modal("hide");

        $("#mensajeExito").hide();
        $("#mensajeExito").removeAttr("hidden");

        setTimeout(function () {
          $("#mensajeExito").fadeIn();
        }, 100);

        $("#btn-guardar-nota").prop("disabled", false).text("ACEPTAR");

        clearInputs();
        clearErrors();
        cargarNotas();
      }
    },
    error: function (error) {
      $("#btn-guardar-nota").prop("disabled", false).text("ACEPTAR");

      $("#mensajeError .textoMensaje").empty();
      $("#mensajeError .textoMensaje").append(
        $("<h3></h3>").text(
          "Ocurrio un error al guardar la nota o ese número de nota ya existe, por favor intenta nuevamente."
        )
      );
      $("#mensajeError").hide();
      setTimeout(function () {
        $("#mensajeError").show();
      }, 250);
    },
  });
});

function clearErrorsFiltro() {
  $("#fecha_nota_inicio").removeClass("input-error");
  $("#mensajeErrorFechaInicioFiltro").hide();

  $("#fecha_nota_fin").removeClass("input-error");
  $("#mensajeErrorFechaFinFiltro").hide();
}

function validarCamposFiltro() {
  clearErrorsFiltro();

  let esValido = true;

  const fechaInicioStr = $("#fecha_nota_inicio").val();
  const fechaFinalizacionStr = $("#fecha_nota_fin").val();

  const fechaInicio = new Date(fechaInicioStr);
  const fechaFinalizacion = new Date(fechaFinalizacionStr);

  fechaInicio.setHours(0, 0, 0, 0);
  fechaFinalizacion.setHours(0, 0, 0, 0);

  if (fechaInicio > fechaFinalizacion) {
    $("#fecha_nota_inicio").addClass("input-error");
    $("#mensajeErrorFechaInicioFiltro").show();
    esValido = false;
  }

  if (fechaFinalizacion < fechaInicio) {
    $("#fecha_nota_fin").addClass("input-error");
    $("#mensajeErrorFechaFinFiltro").show();
    esValido = false;
  }

  return esValido;
}

$("#btn-buscar").on("click", function (e) {
  e.preventDefault();
  const valido = validarCamposFiltro();
  if (!valido) {
    return;
  }
  $("#btn-buscar").prop("disabled", true).text("BUSCANDO...");

  clearErrorsFiltro();

  const nroNota = $("#buscarNroNota").val();
  const nombreEvento = $("#buscarNombreEvento").val();
  const fechaInicio = $("#fecha_nota_inicio").val();
  const fechaFin = $("#fecha_nota_fin").val();

  cargarNotas(1, 5, nroNota, nombreEvento, fechaInicio, fechaFin);

  $("#btn-buscar").prop("disabled", false).text("BUSCAR");
});
