$(document).ready(function () {
  $("#barraMenu").attr("aria-expanded", "true");
  $(".tituloSeccionPantalla").text("Autoexcluidos");
  const input_fecha = {
    language: "es",
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: "dd/mm/yy",
    pickerPosition: "bottom-left",
    startView: 2,
    minView: 2,
    ignoreReadonly: true,
  };
  $("#rangofin").datetimepicker(input_fecha);
  $("#rangoinicio").datetimepicker(input_fecha);

  $("#btn-buscar").trigger("click");
});
//ACTIONS
$(document).on("click", "#btnBorrarNoticias", function (e) {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
      "X-API-Key": "base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI=",
    },
  });

  var url = "http://10.1.121.24:8000/api/resources/remove-news";
  var formData = new FormData();
  const id_noticia = $(this).val();
  formData.append("id", id_noticia);

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      console.log("Success: ", data);
      $("#mensajeExito h3").text("ENVIO EXITOSO");
      $("#mensajeExito p").text("Noticia se borro automaticamente");
      $("#modalSubirNoticia").modal("hide");
      $("#mensajeExito").show();
      $("#btn-buscar").click();
    },
    error: function (data) {
      console.log("Error: ", data);
      var errors = data.responseJSON;
      if (typeof errors.error !== "undefined") {
        $("#mensajeError .textoMensaje").empty();
        $("#mensajeError .textoMensaje").append(
          $("<h3></h3>").text("Ocurrió un error al borrar la noticia")
        );
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
      }
    },
  });
});

//PAGINACION
$("#btn-buscar").click(function (e, pagina, page_size, columna, orden) {
  e.preventDefault();

  const defaultSortBy = {
    column: $("#tablaNoticias .activa").attr("value"),
    order: $("#tablaNoticias .activa").attr("estado"),
  };

  const sort_by =
    columna != null ? { colum: columna, order: orden } : defaultSortBy;

  const page =
    pagina != null ? pagina : $("#herramientasPaginacion").getCurrentPage();

  const page_size_ed =
    page_size != null
      ? page_size
      : parseInt($("#herramientasPaginacion").getPageSize());

  const title = $("#buscarNoticia").val().toLowerCase();
  const abstract = $("#buscarAbstract").val().toLowerCase();

  var formData = new FormData();

  if (sort_by.colum) {
    formData.append("sort_by", JSON.stringify(sort_by));
  }
  formData.append("page_size", page_size_ed);

  if (title.length > 0) {
    formData.append("title", title);
  }
  if (abstract.length > 0) {
    formData.append("abstract", abstract);
  }
  formData.append("start_date", isoDate($("#rangoinicio")));
  formData.append("end_date", isoDate($("#rangofin")));

  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
      "X-API-Key": "base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI=",
    },
  });

  var queryString = "?page=" + encodeURIComponent(page);
  var url = "http://10.1.121.24:8000/api/resources/get-news-list" + queryString;

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,
    success: function (response) {
      console.log(response);
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

      $("#cuerpoTabla tr").not(".filaTabla").remove();
      for (var i = 0; i < response.data.length; i++) {
        $("#tablaNoticias tbody").append(generarFilaTabla(response.data[i]));
      }
    },
    error: function (data) {
      console.error("Error: ", data);
    },
  });
});

//Paginacion
$(document).on("click", "#tablaNoticias thead tr th[value]", function (e) {
  $("#tablaNoticias th").removeClass("activa");
  if ($(e.currentTarget).children("i").hasClass("fa-sort")) {
    $(e.currentTarget)
      .children("i")
      .removeClass("fa-sort")
      .addClass("fa fa-sort-desc")
      .parent()
      .addClass("activa")
      .attr("estado", "desc");
  } else {
    if ($(e.currentTarget).children("i").hasClass("fa-sort-desc")) {
      $(e.currentTarget)
        .children("i")
        .removeClass("fa-sort-desc")
        .addClass("fa fa-sort-asc")
        .parent()
        .addClass("activa")
        .attr("estado", "asc");
    } else {
      $(e.currentTarget)
        .children("i")
        .removeClass("fa-sort-asc")
        .addClass("fa fa-sort")
        .parent()
        .attr("estado", "");
    }
  }
  $("#tablaNoticias th:not(.activa) i")
    .removeClass()
    .addClass("fa fa-sort")
    .parent()
    .attr("estado", "");
  clickIndice(
    e,
    $("#herramientasPaginacion").getCurrentPage(),
    $("#herramientasPaginacion").getPageSize()
  );
});

function clickIndice(e, pageNumber, tam) {
  if (e != null) {
    e.preventDefault();
  }
  var tam = tam != null ? tam : $("#herramientasPaginacion").getPageSize();
  var columna = $("#tablaNoticias .activa").attr("value");
  var orden = $("#tablaNoticias .activa").attr("estado");
  $("#btn-buscar").trigger("click", [pageNumber, tam, columna, orden]);
}

function generarFilaTabla(noticia) {
  let fila = $("#cuerpoTabla .filaTabla")
    .clone()
    .removeClass("filaTabla")
    .show();
  fila.attr("data-id", noticia.id);
  fila
    .find(".titulo_noticias")
    .text(noticia.title)
    .attr("title", noticia.title);
  fila
    .find(".abstract_noticias")
    .text(noticia.abstract)
    .attr("title", noticia.abstract);
  fila.find(".foto_noticias").text(noticia.url).attr("title", noticia.url);
  fila
    .find(".pdf_noticias")
    .text(noticia.file_path)
    .attr("title", noticia.file_path);

  fila.find("button").val(noticia.id);
  fila.find("button").attr("estado-nuevo", noticia.id);
  return fila;
}

//Opacidad del modal al minimizar
$("#btn-minimizar").click(function () {
  if ($(this).data("minimizar") == true) {
    $(".modal-backdrop").css("opacity", "0.1");
    $(this).data("minimizar", false);
  } else {
    $(".modal-backdrop").css("opacity", "0.5");
    $(this).data("minimizar", true);
  }
});

$("#columna input").focusin(function () {
  $(this).removeClass("alerta");
});

//Botón agregar nuevo AE
$("#btn-agregar-ae").click(function (e) {
  e.preventDefault();
  modalAgregarEditarAE("");
});

//Botón ver formularios AE
$("#btn-ver-formularios-ae").click(function (e) {
  e.preventDefault();
  //muestra modal
  $("#modalFormulariosAE").modal("show");
});

$("#btn-descargar-ae").click(function (e) {
  e.preventDefault();
  window.open("autoexclusion/BDCSV", "_blank");
});

function mensajeError(msg) {
  $("#mensajeError .textoMensaje").empty();
  $("#mensajeError .textoMensaje").append($("<h4>" + msg + "</h4>"));
  $("#mensajeError").hide();
  setTimeout(function () {
    $("#mensajeError").show();
  }, 250);
}

function mensajeExito(msg) {
  $("#mensajeExito p").text(msg);
  $("#mensajeExito").hide();
  setTimeout(function () {
    $("#mensajeExito").show();
  }, 250);
}

$("#contenedorFiltros input").on("keypress", function (e) {
  if (e.which == 13) {
    e.preventDefault();
    $("#btn-buscar").click();
  }
});

//Botón agregar nueva noticia AE
$("#btn-noticia").click(function (e) {
  e.preventDefault();
  $("#noticias").modal("show");
});

$("#btn-agregar-noticia").click(function (e) {
  e.preventDefault();

  colorBoton("#btn-guardar-noticia");

  $("#cargarNoticiaPDF").prop("disabled", false);
  $("#cargarNoticiaPDF")
    .fileinput("destroy")
    .fileinput({
      language: "es",
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: true,
      allowedFileExtensions: ["pdf"],
    });
  $("#cargarNoticiaIMG").prop("disabled", false);
  $("#cargarNoticiaIMG")
    .fileinput("destroy")
    .fileinput({
      language: "es",
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: true,
      allowedFileExtensions: ["jpg", "png", "webp", "jpeg"],
    });
  $("#modalSubirNoticia .link_archivo").removeAttr("href").hide();
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia").modal("show");
});

$("#cargarNoticiaPDF").on("fileclear", function (event) {
  $("#cargarNoticiaPDF").attr("data-borrado", "true");
  $("#cargarNoticiaPDF")[0].files[0] = null;
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#cargarNoticiaPDF").on("fileselect", function (event) {
  $("#cargarNoticiaPDF").attr("data-borrado", "false");
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#cargarNoticiaPDF").on("change", function (event) {
  $("#cargarNoticiaPDF").attr("data-borrado", "false");
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#cargarNoticiaIMG").on("fileclear", function (event) {
  $("#cargarNoticiaIMG").attr("data-borrado", "true");
  $("#cargarNoticiaIMG")[0].files[0] = null;
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#cargarNoticiaIMG").on("fileselect", function (event) {
  $("#cargarNoticiaIMG").attr("data-borrado", "false");
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#cargarNoticiaIMG").on("change", function (event) {
  $("#cargarNoticiaIMG").attr("data-borrado", "false");
  $("#modalSubirNoticia .no_visualizable").hide();
  $("#modalSubirNoticia .link_archivo").hide();
});

$("#borrarImagenBtn").on("click", function () {
  $("#cargarNoticiaIMG").val("");
  $("#nombreArchivoContainer").empty();
  $(this).hide();
});

$("#btn-guardar-noticia").click(function (e) {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
      "X-API-Key": "base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI=",
    },
  });

  var url = "http://10.1.121.24:8000/api/resources/post-pdf-document";
  var formData = new FormData();
  formData.append("title", $("#noticiaTitulo").val());
  formData.append("abstract", $("#noticiaAbstract").val());
  if ($("#cargarNoticiaIMG")[0].files[0] != null)
    formData.append("image", $("#cargarNoticiaIMG")[0].files[0]);
  if ($("#cargarNoticiaPDF")[0].files[0] != null)
    formData.append("pdf", $("#cargarNoticiaPDF")[0].files[0]);

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      console.log(data);

      $("#mensajeExito h3").text("ÉXITO DE CARGA");
      $("#mensajeExito p").text("La noticia se cargo correctamente");
      $("#modalSubirNoticia").modal("hide");
      $("#mensajeExito").show();
    },
    error: function (data) {
      console.log("Error:", data);
      var errors = data.responseJSON.errors;
      var oneError = false;

      if (typeof errors.title !== "undefined") {
        mostrarErrorValidacion(
          $("#noticiaTitulo"),
          "El titulo es Obligatorio",
          true
        );
      }

      if (typeof errors.abstract !== "undefined") {
        mostrarErrorValidacion(
          $("#noticiaAbstract"),
          "El Abstract es Obligatorio",
          true
        );
      }

      if (
        typeof errors.pdf !== "undefined" ||
        typeof errors.image !== "undefined"
      ) {
        $("#mensajeError .textoMensaje").empty();
        var textPdf =
          typeof errors.pdf !== "undefined"
            ? " El archivo PDF es obligatorio. "
            : "";
        var textImg =
          typeof errors.image !== "undefined"
            ? " El archivo Imagen es obligatorio."
            : "";
        var textError = textPdf + textImg;
        $("#mensajeError .textoMensaje").append($("<h3></h3>").text(textError));
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
      }
    },
  });
});

$(document).on("click", "#btnVerNoticia", function (e) {
  e.preventDefault()
  hideInputs();
  const id_noticia = $(this).val();
  mostrarModal(id_noticia);

  $("#modalVerNoticia").modal("show");
});

$(document).on("click", "#btnEditar", function (e) {
  e.preventDefault()
  colorBoton("#enviarNoticiasActualizacion");
  showInputs();
  clearImputs();
  $("#cargarNuevaNoticiaPDF").prop("disabled", false);
  $("#cargarNuevaNoticiaPDF")
    .fileinput("destroy")
    .fileinput({
      language: "es",
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: true,
      allowedFileExtensions: ["pdf"],
    });
  $("#cargarNuevaNoticiaIMG").prop("disabled", false);
  $("#cargarNuevaNoticiaIMG")
    .fileinput("destroy")
    .fileinput({
      language: "es",
      showRemove: false,
      showUpload: false,
      showCaption: false,
      showZoom: false,
      browseClass: "btn btn-primary",
      previewFileIcon: "<i class='glyphicon glyphicon-list-alt'></i>",
      overwriteInitial: false,
      initialPreviewAsData: true,
      dropZoneEnabled: true,
      allowedFileExtensions: ["jpg", "png", "webp", "jpeg"],
    });
  const id_noticia = $(this).val();
  $("#enviarNoticiasActualizacion").val(id_noticia);
  mostrarModal(id_noticia);
  $("#title-modal").text("| EDITAR NOTICIA");
  $("#modalVerNoticia").modal("show");
});

$("#cargarNuevaNoticiaPDF").on("fileclear", function (event) {
  $("#cargarNuevaNoticiaPDF").attr("data-borrado", "true");
  $("#cargarNuevaNoticiaPDF")[0].files[0] = null;
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#cargarNuevaNoticiaPDF").on("fileselect", function (event) {
  $("#cargarNuevaNoticiaPDF").attr("data-borrado", "false");
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#cargarNuevaNoticiaPDF").on("change", function (event) {
  $("#cargarNuevaNoticiaPDF").attr("data-borrado", "false");
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#cargarNuevaNoticiaIMG").on("fileclear", function (event) {
  $("#cargarNuevaNoticiaIMG").attr("data-borrado", "true");
  $("#cargarNuevaNoticiaIMG")[0].files[0] = null;
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#cargarNuevaNoticiaIMG").on("fileselect", function (event) {
  $("#cargarNuevaNoticiaIMG").attr("data-borrado", "false");
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#cargarNuevaNoticiaIMG").on("change", function (event) {
  $("#cargarNuevaNoticiaIMG").attr("data-borrado", "false");
  $("#modalVerNoticia .no_visualizable").hide();
  $("#modalVerNoticia .link_archivo").hide();
});

$("#enviarNoticiasActualizacion").on("click", function (event) {
  const id_noticia = $(this).val();
  enviarModificacion(id_noticia)
});

function mostrarModal(id_noticia) {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
      "X-API-Key": "base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI=",
    },
  });

  var url = "http://10.1.121.24:8000/api/resources/get-news-pdf";
  var formData = new FormData();
  formData.append("id", id_noticia);

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      console.log("Success: ", data);
      $("#text-titulo").text(data.title);
      $("#text-abstract").text(data.abstract);
      mostrarData(data);
      $("#cargaArchivo").parent().css({ display: "none" });
      $("#cargaArchivo").prop("disabled", true);
    },
    error: function (data) {
      console.log("Error: ", data);
      var errors = data.responseJSON;
      if (typeof errors.error !== "undefined") {
        $("#mensajeError .textoMensaje").empty();
        $("#mensajeError .textoMensaje").append(
          $("<h3></h3>").text("Ocurrió un error al borrar la noticia")
        );
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
      }
    },
  });
}

function enviarModificacion(id_noticia) {
  $.ajaxSetup({
    headers: {
      "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content"),
      "X-API-Key": "base64:TzNlfRUDBa4hqNFm4jSHM60cW+oPhVtGHx6VFYqnrsI=",
    },
  });

  var url = "http://10.1.121.24:8000/api/resources/update-news";
  var formData = new FormData();
  formData.append("id", id_noticia);

  if($("#noticiaNuevoTitulo").val() != ""){
    formData.append("title", $("#noticiaNuevoTitulo").val());
  }
  if($("#noticiaNuevoAbstract").val() != ""){
    formData.append("abstract", $("#noticiaNuevoAbstract").val());
  }
  if ($("#cargarNuevaNoticiaIMG")[0].files[0] != null)
    formData.append("image", $("#cargarNuevaNoticiaIMG")[0].files[0]);
  if ($("#cargarNuevaNoticiaPDF")[0].files[0] != null)
    formData.append("pdf", $("#cargarNuevaNoticiaPDF")[0].files[0]);

  $.ajax({
    type: "POST",
    url: url,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    cache: false,
    success: function (data) {
      console.log("Success: ", data);
      var message = data.message;
      if (message === "News updated") {
        $("#mensajeExito h3").text("ÉXITO DE CARGA");
        $("#mensajeExito p").text("La noticia se actualizo correctamente");
        $("#modalSubirNoticia").modal("hide");
        $("#mensajeExito").show();
      }
    },
    error: function (data) {
      console.log("Error: ", data);
      var errors = data.responseJSON;
      if (typeof errors.error !== "undefined") {
        $("#mensajeError .textoMensaje").empty();
        $("#mensajeError .textoMensaje").append(
          $("<h3></h3>").text("Ocurrió un error al borrar la noticia")
        );
        $("#mensajeError").hide();
        setTimeout(function () {
          $("#mensajeError").show();
        }, 250);
      }
    },
  });
}

function mostrarData(data) {
  $("#pdfViewer").attr("src", "http://10.1.121.24:8000/" + data.pdf);
  $("#pdf_url")
    .attr("href", "http://10.1.121.24:8000/" + data.pdf)
    .show();

  $("#imagen").attr("src", "http://10.1.121.24:8000/" + data.imagen);
  $("#imagen_url")
    .attr("href", "http://10.1.121.24:8000/" + data.imagen)
    .show();

  $("#modalVerNoticia .no_visualizable").hide();
}

function colorBoton(boton) {
  $(boton).removeClass();
  $(boton).addClass("btn").addClass("btn-successAceptar");
  $(boton).text("ACEPTAR");
  $(boton).show();
  $(boton).val("nuevo");
}

function showInputs(){
  $("#noticiaNuevoTitulo").removeClass("no_visualizable").removeAttr("style");
  $("#titleEditShow").removeClass("no_visualizable").removeAttr("style");
  
  $("#noticiaNuevoAbstract").removeClass("no_visualizable").removeAttr("style");
  $("#abstractEditShow").removeClass("no_visualizable").removeAttr("style");

  $("#div-pdf").removeClass("no_visualizable").removeAttr("style");
  $("#cargarNuevaNoticiaPDF").removeClass("no_visualizable").removeAttr("style");

  $("#div-img").removeClass("no_visualizable").removeAttr("style");
  $("#cargarNuevaNoticiaIMG").removeClass("no_visualizable").removeAttr("style");

  $("#enviarNoticiasActualizacion").removeClass("no_visualizable").removeAttr("style");
}

function clearImputs() {
  $("#titleEditShow").val("");
  $("#noticiaNuevoAbstract").val("");
  $("#cargarNuevaNoticiaPDF").val(null);
  $("#cargarNuevaNoticiaIMG").val(null);
}

function hideInputs(){
  $("#noticiaNuevoTitulo").addClass("no_visualizable");
  $("#titleEditShow").addClass("no_visualizable");

  $("#noticiaNuevoAbstract").addClass("no_visualizable");
  $("#abstractEditShow").addClass("no_visualizable");

  $("#div-pdf").addClass("no_visualizable");
  $("#cargarNuevaNoticiaPDF").addClass("no_visualizable");

  $("#div-img").addClass("no_visualizable");
  $("#cargarNuevaNoticiaIMG").addClass("no_visualizable");

  $("#enviarNoticiasActualizacion").addClass("no_visualizable");
}