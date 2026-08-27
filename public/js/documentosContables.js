$.ajaxSetup({
  headers: {
    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
  },
});


$(document)
  .on("show.bs.modal", ".modal", function () {
    var z = 1040 + 10 * $(".modal:visible").length;
    $(this).css("z-index", z);
    setTimeout(function () {
      $(".modal-backdrop")
        .not(".modal-stack")
        .css("z-index", z - 1)
        .addClass("modal-stack");
    }, 0);
  })
  .on("hidden.bs.modal", ".modal", function () {
    if ($(".modal:visible").length) {
      $("body").addClass("modal-open");
    }
  });

function obtenerIconoValidacion(valido, tipo, id) {
  var icono = valido == 1 ? "fa-check" : "fa-times";
  var colorHex = valido == 1 ? "#00ca07ff" : "#d60e00ff";
  var btnClass = valido == 1 ? "btn-success" : "btn-danger";
  var title = valido == 1 ? "VALIDADO" : "NO VALIDADO";

  var btn = $("<button>")
    .addClass("btn btn-sm validarDocumento " + btnClass)
    .attr("type", "button")
    .attr("data-tipo", tipo)
    .attr("data-id", id)
    .attr("data-valido", valido || 0)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", title);

  btn.append(
    '<i class="fa ' +
      icono +
      '" style="color: ' +
      colorHex +
      ' !important; font-size: 1.5em !important; text-shadow: none !important;"></i>'
  );

  // El botón de historial sale junto al de validar porque es el único punto por el que pasan las
  // 28 tablas del módulo y ya trae el tipo y el id del documento.
  return btn.add(botonHistorialDoc(tipo, id));
}

// Botón que abre el historial del documento (quién lo tocó y cuándo).
function botonHistorialDoc(tipo, id) {
  return $("<button>")
    .addClass("btn btn-sm btn-default btn-historial-doc")
    .attr("type", "button")
    .attr("data-tipo", tipo)
    .attr("data-id", id)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "VER HISTORIAL")
    .append('<i class="fa fa-history"></i>');
}

/**
 * Calcula % en vivo: porcentaje = (parte / base) * 100
 * opts = {
 *   base:    '#idBase',        // denominador
 *   parte:   '#idParte',       // numerador
 *   target:  '#idPorcentaje',  // salida (ej: alícuota)
 *   decimales: 2,              // cantidad de decimales a mostrar
 *   blankIfZeroBase: true      // si base = 0, deja vacío el target
 * }
 */
function instalarAutoPorcentajeAR(opts) {
  var o = $.extend(
    {
      base: null,
      parte: null,
      target: null,
      decimales: 2,
      blankIfZeroBase: true,
    },
    opts || {}
  );
  if (!o.base || !o.parte || !o.target) return;

  function leerNum(sel) {
    var $el = $(sel);
    if (!$el.length) return null;
    // Manda el valor que se ve. data("num") solo se actualiza cuando el usuario tipea, así que al
    // abrir un registro para editar (los campos se llenan con .val()) quedaba con el número del
    // registro anterior y los campos calculados no se actualizaban.
    return _smartParseNum($el.val());
  }

  function recalc() {
    var base = leerNum(o.base);
    var parte = leerNum(o.parte);

    var pct = null;
    if (base != null && Math.abs(base) > 0) {
      pct = (Number(parte || 0) / Number(base)) * 100;
    }

    if (pct == null && o.blankIfZeroBase) {
      $(o.target).val("").data("num", null).trigger("num:changed", [null]);
    } else {
      $(o.target)
        .val(formatoAR(pct || 0, o.decimales))
        .data("num", pct || 0)
        .trigger("num:changed", [pct || 0]);
    }
  }

  $(document).on(
    "input change num:changed",
    [o.base, o.parte].join(","),
    recalc
  );

  recalc();
}

/**
 * Calcula impuesto (= base * alícuota)
 * options = {
 *   base: '#idBase',
 *   alicuota: '#idAlicuota',
 *   impuesto: '#idImpuesto',
 *   total: '#idTotal' (opcional),
 *   decBase: 2, decImp: 2, decTotal: 2,
 *   aliEsPorcentaje: true   // ⬅️ NUEVO: 6 -> 6%, 0,95 -> 0,95%
 * }
 */
function instalarAutoImpuestoAR(options) {
  var o = $.extend(
    {
      base: null,
      alicuota: null,
      impuesto: null,
      total: null,
      decBase: 2,
      decImp: 2,
      decTotal: 2,
      aliEsPorcentaje: true,
    },
    options || {}
  );
  if (!o.base || !o.alicuota || !o.impuesto) return;

  function leerNum(sel) {
    var $el = $(sel);
    if (!$el.length) return null;
    // Manda el valor que se ve. data("num") solo se actualiza cuando el usuario tipea, así que al
    // abrir un registro para editar (los campos se llenan con .val()) quedaba con el número del
    // registro anterior y los campos calculados no se actualizaban.
    return _smartParseNum($el.val());
  }

  function recalc() {
    var base = leerNum(o.base) || 0;
    var aliRaw = leerNum(o.alicuota) || 0;
    var ali = o.aliEsPorcentaje ? aliRaw / 100 : aliRaw;
    var imp = o.esIndirecto ? (base * ali) / (1 + ali) : base * ali;
    var tot = base + imp;

    $(o.impuesto)
      .val(formatoAR(imp, o.decImp))
      .data("num", imp)
      .trigger("num:changed", [imp]);
    if (o.total) {
      $(o.total)
        .val(formatoAR(tot, o.decTotal))
        .data("num", tot)
        .trigger("num:changed", [tot]);
    }
  }

  $(document).on(
    "input change num:changed",
    [o.base, o.alicuota].join(","),
    recalc
  );
  recalc();
}

function _smartParseNum(v) {
  if (v == null) return null;
  var s = String(v).trim();
  if (!s) return null;

  // Sanear: conservar solo números, puntos, comas y signo menos
  var neg = /^[^\d]*-/.test(s);
  s = s.replace(/[^\d.,]/g, "");
  if (!s) return null;

  var lastDot = s.lastIndexOf(".");
  var lastComma = s.lastIndexOf(",");

  var numStr = "";

  if (lastComma !== -1 && lastDot !== -1) {
    // Si existen ambos, el que está más a la derecha es el decimal
    if (lastDot > lastComma) {
      // US: 1,234.56 -> Quitar comas
      numStr = s.replace(/,/g, "");
    } else {
      // AR: 1.234,56 -> Quitar puntos, cambiar coma por punto
      numStr = s.replace(/\./g, "").replace(",", ".");
    }
  } else if (lastComma !== -1) {
    // Solo comas: 1,000,000 (US) o 1.000,50 (AR sin puntos)
    var commas = (s.match(/,/g) || []).length;
    if (commas > 1) {
      // Miles US
      numStr = s.replace(/,/g, "");
    } else {
      // Decimal AR
      numStr = s.replace(",", ".");
    }
  } else if (lastDot !== -1) {
    // Solo puntos: 1.000.000 (AR) o 1000.50 (Standard)
    var dots = (s.match(/\./g) || []).length;
    var digitosDespues = s.length - lastDot - 1;
    if (dots > 1) {
      // Miles AR
      numStr = s.replace(/\./g, "");
    } else if (digitosDespues === 3 && lastDot > 0) {
      // Un solo punto con exactamente 3 dígitos detrás: en formato AR es separador de miles,
      // no decimal ("1.000" es mil). El decimal en esta pantalla se escribe con coma ("1000,50").
      numStr = s.replace(/\./g, "");
    } else {
      // Decimal Standard
      numStr = s;
    }
  } else {
    numStr = s;
  }

  var n = Number((neg ? "-" : "") + numStr);
  return isFinite(n) ? n : null;
}
function instalarNumeroFlexibleAR(selector, opts) {
  var o = $.extend({ decimales: 2, permitirNegativos: false }, opts || {});

  // Guardar config por elemento (permite override por campo específico)
  $(selector).each(function () {
    $(this).data("_numflex_dec", o.decimales);
    $(this).data("_numflex_neg", o.permitirNegativos);
  });

  $(document).off(
    "input.numflex blur.numflex paste.numflex focus.numflex",
    selector
  );

  $(document).on("focus.numflex", selector, function () {
    $(this).removeData("normalized");
  });

  $(document).on("input.numflex", selector, function () {
    $(this).removeData("normalized");
    // Guardar config si es elemento nuevo (dinámico)
    if ($(this).data("_numflex_dec") == null) {
      $(this).data("_numflex_dec", o.decimales);
      $(this).data("_numflex_neg", o.permitirNegativos);
    }
    var neg = $(this).data("_numflex_neg");
    var n = _smartParseNum(this.value);
    if (n != null && !neg && n < 0) n = Math.abs(n);
    $(this).data("num", n).trigger("num:changed", [n]);
  });

  $(document).on("paste.numflex", selector, function () {
    var el = this;
    setTimeout(function () {
      $(el).trigger("input");
    }, 0);
  });

  $(document).on("blur.numflex", selector, function () {
    var $el = $(this);
    // Leer config del elemento (override específico gana)
    var decs = $el.data("_numflex_dec");
    if (decs == null) decs = o.decimales;
    var neg = $el.data("_numflex_neg");
    if (neg == null) neg = o.permitirNegativos;

    if ($el.data("normalized") === true) {
      var nCanon = _smartParseNum(this.value);
      if (nCanon != null && !neg && nCanon < 0) nCanon = Math.abs(nCanon);
      $el.data("num", nCanon).trigger("num:changed", [nCanon]);
      return;
    }

    var n = _smartParseNum(this.value);
    if (n == null) {
      $el.data("num", null).trigger("num:changed", [null]);
      return;
    }
    if (!neg && n < 0) n = Math.abs(n);

    // `decimales` es el MÍNIMO a mostrar, no un relleno fijo: si el valor tiene más decimales se
    // respetan. Antes, un campo configurado con 5 mostraba "1.500,00000" para un monto redondo.
    // decs === 0 (conteos, ej. RRHH) sí fuerza entero.
    var formatted = formatoAR(n, decs === 0 ? 0 : decimalesUtiles(n, Math.min(decs, 2)));
    if (this.value !== formatted) {
      this.value = formatted;
    }
    $el.data("num", n).trigger("num:changed", [n]);
  });
}
function aplicarNumerico(selector, opts) {
  instalarNumeroFlexibleAR(selector, opts);
}

function instalarAutoSumaAR(opts) {
  var o = $.extend({ sources: [], target: null, decimales: 2 }, opts || {});
  if (!o.target || !o.sources.length) return;

  function leerNum($el) {
    // Ídem: manda el valor visible, no el data("num") que puede haber quedado del registro anterior.
    return Number(_smartParseNum($el.val()) || 0);
  }

  function signoDe(sel) {
    return /^\s*-/.test(sel) ? -1 : 1;
  }
  function limpio(sel) {
    return String(sel).replace(/^\s*[+-]\s*/, "");
  }

  function recalc() {
    var total = 0;
    o.sources.forEach(function (s) {
      var sgn = signoDe(s);
      var sel = limpio(s);
      var $els = $(sel);
      if (!$els.length) return;
      $els.each(function () {
        total += sgn * leerNum($(this));
      });
    });
    $(o.target)
      .val(formatoAR(total, o.decimales))
      .data("num", total)
      .trigger("num:changed", [total]);
  }

  var triggers = Array.from(new Set(o.sources.map(limpio))).join(",");
  if (triggers) $(document).on("input change num:changed", triggers, recalc);

  recalc();
}

function parseNumeroFlexible(input) {
  var n = _smartParseNum(input);
  if (n == null) return null;
  return { num: n, str: String(n) };
}

// Cuántos decimales mostrar para un número: mínimo 2, y hasta los que tenga de verdad (máx. 6,
// para no arrastrar la basura de punto flotante).
function decimalesUtiles(n, minimo) {
  var min = minimo == null ? 2 : minimo;
  // toFixed y no n*1e6: multiplicar pierde precisión con montos grandes (269481731462.25 * 1e6 se
  // pasa del entero seguro de JS y terminaba mostrando 5 decimales).
  var redondeado = Number(Number(n).toFixed(6));
  var dec = (String(redondeado).split(".")[1] || "").length;
  return Math.max(min, Math.min(dec, 6));
}

function formatoAR(value, decs = null) {
  if (value === null || value === undefined || value === "") return "";
  const n = Number(value);
  if (!isFinite(n)) return "";
  // Sin decimales explícitos: se muestran 2 y se respetan los que el valor realmente tenga.
  // El redondeo previo saca los ceros de relleno que llegan del back ("1500.00000" => 1500) y la
  // basura de punto flotante (0.30000000000000004 => 0.3), que antes hacían mostrar 5 o más ceros.
  const frac = decs === null ? decimalesUtiles(n) : decs;
  return n.toLocaleString("es-AR", {
    minimumFractionDigits: frac,
    maximumFractionDigits: frac,
  });
}

// Al subir, el archivo se guarda como "<timestamp>_<6 al azar>_<nombre original>" para que dos
// archivos con el mismo nombre no se pisen. Esto saca ese prefijo para MOSTRAR, así se ve el
// nombre que subió el usuario.
// OJO: es solo cosmético. El href y el borrado tienen que seguir usando el nombre REAL guardado,
// que es el que existe en disco.
// Si el nombre no tiene exactamente esa forma (archivos viejos, subidos con otra convención) se
// devuelve tal cual, para no recortar de más.
function nombreVisibleArchivo(nombre) {
  return String(nombre == null ? "" : nombre).replace(/^\d+_[A-Za-z0-9]{6}_/, "");
}

function iconoExt(nombre) {
  var n = (nombre || "").toLowerCase();
  if (/\.(pdf)$/.test(n)) return "📄";
  if (/\.(xlsx?|csv)$/.test(n)) return "📊";
  if (/\.(docx?|rtf|txt)$/.test(n)) return "📝";
  if (/\.(png|jpe?g|gif|bmp|webp|svg)$/.test(n)) return "🖼️";
  if (/\.(zip|rar|7z|tar|gz)$/.test(n)) return "🗜️";
  return "📎";
}

function attachYYYYMMDDFormatter(sel) {
  $(document).on("input", sel, function () {
    let v = this.value.replace(/\D/g, "").slice(0, 8);
    if (v.length > 4) v = v.slice(0, 4) + "-" + v.slice(4);
    if (v.length > 7) v = v.slice(0, 7) + "-" + v.slice(7);
    this.value = v;
  });

  $(document).on("blur", sel, function () {
    const m = this.value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) {
      const y = +m[1];
      let mm = Math.min(12, Math.max(1, +m[2]));
      const md = new Date(y, mm, 0).getDate();
      let dd = Math.min(md, Math.max(1, +m[3]));
      this.value =
        String(y).padStart(4, "0") +
        "-" +
        String(mm).padStart(2, "0") +
        "-" +
        String(dd).padStart(2, "0");
    }
  });
}

function attachYYYYMMFormatter(sel) {
  $(document).on("input", sel, function () {
    let v = this.value.replace(/\D/g, "").slice(0, 6);
    this.value = v.length > 4 ? v.slice(0, 4) + "-" + v.slice(4) : v;
  });
  $(document).on("blur", sel, function () {
    const m = this.value.match(/^(\d{4})-(\d{1,2})$/);
    if (m) {
      let mm = Math.min(12, Math.max(1, +m[2]));
      this.value = m[1] + "-" + String(mm).padStart(2, "0");
    }
  });
}

function convertirMesAno(input) {
  if (!input) return null;

  const meses = [
    "",
    "ENE",
    "FEB",
    "MAR",
    "ABR",
    "MAY",
    "JUN",
    "JUL",
    "AGO",
    "SEP",
    "OCT",
    "NOV",
    "DIC",
  ];

  const [fechaPart] = input.split(" ");
  const [year, month] = fechaPart.split("-");

  const mesAbrev = meses[parseInt(month, 10)];

  return `${mesAbrev} ${year}`;
}

function validarCampoNumSiHayValor($input, contexto, mensaje, valid) {
  if (!$input || !$input.length) return valid ? 1 : 0;
  var v = $input.val();
  if (v == null || String(v).trim() === "") return valid ? 1 : 0;
  return validarCampoNum($input, contexto, mensaje, valid);
}

function validarCampoNum(selector, contexto, mensaje, valid) {
  var $input = $(selector);
  var valor = ($input.val() || "").trim();

  var $ctx = $input.closest(contexto);
  $ctx.removeClass("has-error").find(".help-block.js-error").remove();

  if (!valor) {
    $ctx
      .addClass("has-error")
      .append('<span class="help-block js-error">' + mensaje + "</span>");
    return 0;
  }

  // Usar smart parse que ya maneja ambos formatos
  var n = _smartParseNum(valor);
  if (n == null) {
    $ctx
      .addClass("has-error")
      .append(
        '<span class="help-block js-error">Introduce un número válido</span>'
      );
    return 0;
  }

  // Sincronizar data('num')
  $input.data("num", n);

  // Si no está normalizado (es decir, el usuario acaba de escribirlo),
  // lo formateamos a AR para que sea consistente visualmente.
  // Pero ojo: si estamos en medio de un input, no conviene cambiar el value
  // porque salta el cursor. El formateo real suele ocurrir en el blur.
  // Aquí solo marcamos que es válido.

  if (!valid) return 0;
  return 1;
}

function validarCampo(selector, contexto, mensaje, valid) {
  var $input = $(selector);
  var valor = $input.val().trim();
  if (!valor) {
    $input
      .closest(contexto)
      .addClass("has-error")
      .append('<span class="help-block js-error">' + mensaje + "</span>");
    return 0;
  }
  if (!valid) return 0;
  return 1;
}

$(document).on("click", "#btn-eliminarArchivo", function () {
  const $modal = $("#modalEliminarArchivo");

  const fileId = $modal.data("id");
  const regId = $modal.data("regId");
  const scope = $modal.data("scope");
  // Contenedor exacto donde estaba el archivo (lo guarda el hook de abajo). Hace falta porque en el
  // modal unificado cada sub-documento tiene su propia lista (#listaArchivos_U_*) y resolver el
  // destino solo por tipo terminaba refrescando la lista del modal suelto (#listaArchivos_*).
  const contenedor = $modal.data("contenedor") || null;

  $.getJSON("/documentosContables/eliminarArchivo", { id: fileId })
    .done(function (r) {
      if (r && r.success) {
        const reloaders = {
          Iva: (id, target) => cargarArchivosIvaLista(id, target),
          iibb: (id, target) => cargarArchivosiibbLista(id, target),
          DREI: (id, target) => cargarArchivosDREILista(id, target),
          TGI: (id, target) => cargarArchivosTGILista(id, target),
          IMP_AP_MTM: (id, target) => cargarArchivosIMP_AP_MTMLista(id, target),
          IMP_AP_OL: (id, target) => cargarArchivosIMP_AP_OLLista(id, target),
          Ganancias: (id, target) => cargarArchivosGananciasLista(id, target),
          Ganancias_periodo: (id, target) => cargarArchivosGanancias_periodoLista(id, target),
          Patentes: (id, target) => cargarArchivosPatentesLista(id, target),
          ImpInmobiliario: (id, target) => cargarArchivosImpInmobiliarioLista(id, target),
          ContribEnteTuristico: (id, target) => cargarArchivosContribEnteTuristicoLista(id, target),
          DerechoAcceso: (id, target) => cargarArchivosDerechoAccesoLista(id, target),
          DeudaEstado: (id, target) => cargarArchivosDeudaEstadoLista(id, target),
          AutDirectores: (id, target) => cargarArchivosAutDirectoresLista(id, target),
          PremiosMTM: (id, target) => cargarArchivosPremiosMTMLista(id, target),
          PromoTickets: (id, target) => cargarArchivosPromoTicketsLista(id, target),
          PozosAcumuladosLinkeados: (id, target) => cargarArchivosPozosAcumuladosLinkeadosLista(id, target),
          JackpotsPagados: (id, target) => cargarArchivosJackpotsPagadosLista(id, target),
          PremiosPagados: (id, target) => cargarArchivosPremiosPagadosLista(id, target),
          PagosMayoresMesas: (id, target) => cargarArchivosPagosMayoresMesasLista(id, target),
          RegistrosContables: (id, target) => cargarArchivosRegistrosContablesLista(id, target),
          AportesPatronales: (id, target) => cargarArchivosAportesPatronalesLista(id, target),
          RRHH: (id, target) => cargarArchivosRRHHLista(id, target),
          ReporteYLavado: (id, target) => cargarArchivosReporteYLavadoLista(id, target),
          Seguros: (id, target) => cargarArchivosSegurosLista(id, target),
          publicoCasino: (id, target) => cargarArchivosPublicoCasinoLista(id, target),
          EstadoContable: (id, target) => cargarArchivosEstadoContableLista(id, target),
        };

        if (reloaders[scope]) {
          reloaders[scope](regId, contenedor);
          var activeTab = $("[data-js-tabs] a.active").attr("data-js-tab");
          if (!activeTab) activeTab = $("[data-js-tabs] a").first().attr("data-js-tab");
          buscarPestana(activeTab, true);
        } else {
          $(`[data-archivo-id="${fileId}"]`).remove();
        }

        $("#modalEliminarArchivo")
          .modal("hide")
          .removeData("id regId scope contenedor");
      } else {
        alert("No se pudo eliminar");
      }
    })
    .fail(function () {
      alert("Error al eliminar");
    });
});

$(document).ready(function () {
  $(".tituloSeccionPantalla").text("Documentos Contables");
  
  // Inicializar popovers de la vista
  $('body').popover({
    selector: '[data-toggle="popover"]',
    html: true,
    trigger: 'hover'
  });
});

//IIBB

$(function () {
  instalarNumeroFlexibleAR(
    [
      ".js-num-ar",
      "#dif_miniibb",
      "#saldo_iibb",
      "#total_impuesto_iibb",
    ].join(", "),
    { decimales: 2, permitirNegativos: true }
  );

  instalarAutoSumaAR({
    sources: [".iibb-imp"],
    target: "#total_impuesto_iibb",
    decimales: 2,
  });

  instalarAutoSumaAR({
    sources: ["#total_impuesto_iibb", "#dif_miniibb", "-#deduccionesiibb"],
    target: "#saldo_iibb",
    decimales: 2,
  });
});

function cargarArchivosiibbLista(id, target) {
  var $list = $(targetArchivosDoc("iibb", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosiibb/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/iibb/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="iibb"  class="btn btn-sm btn-danger btn-del-archivo-iibb" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-iibb", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function appendIibbFilaEditar(b, index, container) {
  container = container || "#contenedor-inputs-iibb-cargar";

  var id = b && b.id != null ? b.id : "";
  var obs = b && b.obs ? b.obs : "";

  var montoNum =
    b && b.monto != null
      ? parseNumeroFlexible(b.monto)
        ? parseNumeroFlexible(b.monto).num
        : null
      : null;
  var aliNum =
    b && b.alicuota != null
      ? parseNumeroFlexible(b.alicuota)
        ? parseNumeroFlexible(b.alicuota).num
        : null
      : null;
  var impNum =
    b && b.imp != null
      ? parseNumeroFlexible(b.imp)
        ? parseNumeroFlexible(b.imp).num
        : null
      : null;

  if (impNum == null && montoNum != null && aliNum != null) {
    impNum = montoNum * (aliNum / 100);
  }

  var montoFmt = montoNum != null ? formatoAR(montoNum, 2) : "";
  var aliFmt = aliNum != null ? formatoAR(aliNum, 5) : "";
  var impFmt = impNum != null ? formatoAR(impNum, 2) : "";

  var montoId = "monto_iibb_" + index;
  var aliId = "alicuota_iibb_" + index;
  var impId = "imp_iibb_" + index;

  var html =
    "" +
    '<div class="bases-iibb-cargar" data-id="' +
    index +
    '" data-base-id="' +
    id +
    '">' +
    '  <div class="row">' +
    '    <div class="col-md-3"><h5>Base imponible...</h5></div>' +
    '    <div class="col-md-3"><h5>Monto</h5></div>' +
    '    <div class="col-md-2"><h5>Alicuota (%)</h5></div>' +
    '    <div class="col-md-3"><h5>Impuesto Determinado</h5></div>' +
    '    <div class="col-md-1"></div>' +
    "  </div>" +
    '  <div class="row">' +
    '    <div class="col-md-3">' +
    '      <textarea name="base[]" class="form-control" rows="2" maxlength="4000"' +
    '        placeholder="Completar con el concepto">' +
    obs +
    "</textarea>" +
    "    </div>" +
    '    <div class="col-md-3">' +
    '      <input type="text" id="' +
    montoId +
    '" name="monto[]" class="form-control js-num-ar iibb-monto"' +
    '             placeholder="$" inputmode="decimal" value="' +
    montoFmt +
    '">' +
    "    </div>" +
    '    <div class="col-md-2">' +
    '      <input type="text" id="' +
    aliId +
    '" name="alicuota[]" class="form-control js-num-ar iibb-ali"' +
    '             placeholder="%" inputmode="decimal" value="' +
    aliFmt +
    '">' +
    "    </div>" +
    '    <div class="col-md-3">' +
    '      <input type="text" id="' +
    impId +
    '" name="impuesto[]" class="form-control js-num-ar iibb-imp"' +
    '             inputmode="decimal" value="' +
    impFmt +
    '" >' +
    "    </div>" +
    '    <div class="col-md-1">' +
    '      <button type="button" class="btn btn-danger btn-sm eliminar-bloque-iibb-cargar">X</button>' +
    "    </div>" +
    "  </div>" +
    "</div>";

  $(container).append(html);

  instalarNumeroFlexibleAR("#" + montoId, {
    decimales: 2,
    permitirNegativos: true,
  });
  instalarNumeroFlexibleAR("#" + aliId, {
    decimales: 5,
    permitirNegativos: true,
  });
  instalarNumeroFlexibleAR("#" + impId, {
    decimales: 2,
    permitirNegativos: true,
  });
  instalarAutoImpuestoAR({
    base: "#" + montoId,
    alicuota: "#" + aliId,
    impuesto: "#" + impId,
    aliEsPorcentaje: true,
    decImp: 2,
  });

  $("#" + montoId).trigger("input");
}

var contadorDeduccionIibb = 0;

function appendDeduccionIibbFila(d, index) {
  d = d || {};
  var id = d.id != null ? d.id : "";
  var label = d.label || "";
  var montoNum = d.monto != null ? (parseNumeroFlexible(d.monto) ? parseNumeroFlexible(d.monto).num : null) : null;
  var montoFmt = montoNum != null ? formatoAR(montoNum, 2) : "";
  var montoId = "deduccion_monto_iibb_" + index;

  // Si es la primera fila, agregar headers
  if ($("#contenedor-deducciones-iibb .deduccion-iibb-fila").length === 0) {
    $("#contenedor-deducciones-iibb").append(
      '<div class="row deduccion-iibb-headers">' +
      '  <div class="col-md-5"><h5>Detalle</h5></div>' +
      '  <div class="col-md-5"><h5>Monto deducción</h5></div>' +
      '  <div class="col-md-2"></div>' +
      '</div>'
    );
  }

  var html =
    '<br/><div class="deduccion-iibb-fila" data-ded-id="' + id + '">' +
    '  <div class="row">' +
    '    <div class="col-md-5">' +
    '      <input type="hidden" name="deduccion_id[]" value="' + id + '">' +
    '      <input type="text" name="deduccion_label[]" class="form-control" placeholder="Concepto deducción" value="' + label + '">' +
    '    </div>' +
    '    <div class="col-md-5">' +
    '      <input type="text" id="' + montoId + '" name="deduccion_monto[]" class="form-control js-num-ar deduccion-monto-iibb" placeholder="$" inputmode="decimal" value="' + montoFmt + '">' +
    '    </div>' +
    '    <div class="col-md-2">' +
    '      <button type="button" class="btn btn-danger btn-sm eliminar-deduccion-iibb">X</button>' +
    '    </div>' +
    '  </div>' +
    '</div>';

  $("#contenedor-deducciones-iibb").append(html);

  instalarNumeroFlexibleAR("#" + montoId, { decimales: 2, permitirNegativos: true });
  $("#" + montoId).trigger("input");
}

function abrirModaliibbEditar(id) {
  resetFormiibb();
  $("#iibb_modo").val("edit");
  $("#id_registroiibb").val(id);
  $("#modalCargariibb .modal-title").text("| EDITAR REGISTRO DE IIBB");
  $("#guardarRegistroiibb").text("ACTUALIZAR");
  $("#modalCargariibb").modal("show");

  $.getJSON("/documentosContables/llenariibbEdit/" + id, function (d) {
    const ym = String(d.fecha || "").slice(0, 7);
    const ymd = String(d.fecha_pres || "").slice(0, 10);

    $('#fechaiibb input[name="fecha_iibb"]').val(ym).trigger("change");
    $('#fechaiibbPres input[name="fecha_iibbPres"]').val(ymd).trigger("change");
    $("#casinoiibb").val(d.casino).trigger("change");

    $("#dif_miniibb").val(formatoAR(d.diferencia ?? ""));
    $("#saldo_iibb").val(formatoAR(d.saldo));
    $("#total_impuesto_iibb").val(formatoAR(d.impuesto_total));

        const $cont = $("#contenedor-inputs-iibb-cargar").empty();
    const bases = Array.isArray(d.bases) ? d.bases : [];
    let idx = 0;
    if (bases.length) {
      bases.forEach((b) =>
        appendIibbFilaEditar(b, ++idx, "#contenedor-inputs-iibb-cargar")
      );
    } else {
      appendIibbFilaEditar({}, ++idx, "#contenedor-inputs-iibb-cargar");
    }

    // Cargar deducciones
    $("#contenedor-deducciones-iibb").empty();
    contadorDeduccionIibb = 0;
    var deds = Array.isArray(d.deducciones_list) ? d.deducciones_list : [];
    if (deds.length) {
      deds.forEach(function(dd) {
        contadorDeduccionIibb++;
        appendDeduccionIibbFila(dd, contadorDeduccionIibb);
      });
    }
    // Actualizar hidden con suma
    recalcularSumaDeduccionesIibb();

    $(".iibb-imp").first().trigger("input");

    $(document).trigger("iibb:set-contador", [idx]);
  }).fail(function (xhr) {
    console.error("[iibb editar] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro de IIBB.");
  });
}

function resetFormiibb() {
  var $f = $("#formNuevoRegistroiibb");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameiibb").val("");
    $("#uploadiibb").val("");
  $("#uploadsiibbContainer").empty();
  $("#uploadsiibbTable tbody").empty();
  $("#uploadsiibbWrap").hide();
  $("#fileNameiibb").val("");
  $("#contenedor-deducciones-iibb").empty();
  contadorDeduccionIibb = 0;
  $("#deduccionesiibb").val(0);
}

function recalcularSumaDeduccionesIibb() {
  var suma = 0;
  $("#contenedor-deducciones-iibb .deduccion-monto-iibb").each(function() {
    var n = $(this).data("num");
    if (n !== undefined && n !== null) suma += parseFloat(n) || 0;
    else {
      var parsed = parseNumeroFlexible($(this).val());
      if (parsed && parsed.num !== null) suma += parsed.num;
    }
  });
  $("#deduccionesiibb").val(suma).data("num", suma).trigger("num:changed", [suma]);
}

function abrirModaliibbCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormiibb();
  $("#iibb_modo").val("create");
  $("#id_registroiibb").val("");
  $("#modalCargariibb .modal-title").text("| NUEVO REGISTRO DE IIBB");
  $("#guardarRegistroiibb").text("GENERAR");
  $("#modalCargariibb").modal("show");
}

$(document).on("click", "#iibb_nuevo", function () {
  abrirModaliibbCrear();
});

$(document).on("click", ".btn-edit-iibb", function () {
  var id = $(this).data("id");
  abrirModaliibbEditar(id);
});

$(document).on("click", "#guardarRegistroiibb", function (e) {
  var $form = $("#formNuevoRegistroiibb");
  var modo = $("#iibb_modo").val() || "create";
  var id = $("#id_registroiibb").val() || "";
  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    'select[name="casinoiibb"]',
    ".col-md-4",
    "El casino es requerido.",
    valid
  );

  if ($("#contenedor-inputs-iibb-cargar .bases-iibb-cargar").length === 0) {
    $("#agregar-bloque-iibb-cargar").trigger("click");
    valid = 0;
  }

  $("#contenedor-inputs-iibb-cargar .bases-iibb-cargar").each(function () {
    const $fila = $(this);
    const $base = $fila.find('textarea[name="base[]"]');
    const $monto = $fila.find('input[name="monto[]"]');
    const $alicuota = $fila.find('input[name="alicuota[]"]');
    const $imp = $fila.find('input[name="impuesto[]"]');

    valid = validarCampo($base, ".col-md-3", "La base es requerida.", valid);
    valid = validarCampoNum(
      $monto,
      ".col-md-3",
      "El monto es requerido",
      valid
    );
    valid = validarCampoNum(
      $alicuota,
      ".col-md-2",
      "La alicuota es requerida.",
      valid
    );
    valid = validarCampoNum(
      $imp,
      ".col-md-3",
      "El impuesto determinado es requerido",
      valid
    );
  });

  $("#contenedor-deducciones-iibb .deduccion-iibb-fila").each(function () {
    const $fila = $(this);
    const $monto = $fila.find('input[name="deduccion_monto[]"]');
    valid = validarCampoNumSiHayValor(
      $monto,
      ".col-md-5",
      "El monto de deducción debe ser numérico.",
      valid
    );
  });

  valid = validarCampoNum(
    'input[name="dif_miniibb"]',
    ".col-md-4",
    "La diferencia es requerida",
    valid
  );

  valid = validarCampo(
    'input[name="fecha_iibb"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  );
  valid = validarCampo(
    'input[name="fecha_iibbPres"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  );
  valid = validarCampoNum(
    'input[name="saldo_iibb"]',
    ".col-md-6",
    "El saldo es requerido.",
    valid
  );

  valid = validarCampoNum(
    'input[name="total_impuesto_iibb"]',
    ".col-md-6",
    "El saldo es requerido.",
    valid
  );

  if (!valid) return 0;

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $('#uploadsiibbContainer input[type="file"][name="uploadiibb[]"]').each(
    function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadiibb[]", files[i]);
      }
    }
  );

  var cur = document.getElementById("uploadiibb");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadiibb[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizariibb/" + id
      : "/documentosContables/guardariibb";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    success: function () {
      cargariibb({
        page: $("#herramientasPaginacioniibb").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacioniibb").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargariibb").modal("hide"), 1000);
      resetFormiibb();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function cargariibb({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasiibb",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaiibb").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaiibb").append(generarFilaiibb(item));
      });

      $("#herramientasPaginacioniibb").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceiibb
      );
      $("#herramientasPaginacioniibb").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceiibb
      );
    },
    error(err) {
      console.error("Error cargando IIBB:", err);
    },
  });
}

function clickIndiceiibb(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargariibb({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function generarFilaiibb(iibb, controlador) {
  const fila = $("<tr>").attr("id", iibb.id_registroiibb);

  const fecha = convertirMesAno(iibb.fecha_iibb) || "-";
  const pres = iibb.fecha_presentacion || "-";
  const casino = iibb.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").html(pres))
    .append($("<td>").addClass("col-xs-3").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");



  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-iibb")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", iibb.id_registroiibb)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteiibb")
    .attr("id", iibb.id_registroiibb)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO IIBB")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(iibb.valido, "iibb", iibb.id_registroiibb)
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteiibb", function () {
  const id = $(this).attr("id");
  $("#btn-eliminariibb").attr("data-id", id);
  $("#modalEliminariibb").modal("show");
});

$("#btn-eliminariibb").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminariibb/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminariibb").modal("hide");
        cargariibb({
          page: $("#herramientasPaginacioniibb").getCurrentPage(),
          perPage: $("#herramientasPaginacioniibb").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargariibbCsvRegistros").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargariibb .has-error").removeClass("has-error");
  $("#collapseDescargariibb .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargariibbCsvRegistros?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargariibbCsvActividades").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargariibb .has-error").removeClass("has-error");
  $("#collapseDescargariibb .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargariibbCsvActividades?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargariibbExcel").on("click", function (e) {
  $("#collapseDescargariibb .has-error").removeClass("has-error");
  $("#collapseDescargariibb .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargariibbXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargariibbXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

// DREI

$(function () {
  instalarNumeroFlexibleAR(
    [
      "#garageDREI",
      "#bromatologiaDREI",
      "#base_imponible_comDREI",
      "#base_imponible_gasDREI",
      "#base_imponible_explDREI",
      "#base_imponible_apyjDREI",
      "#monto_pagado_melDREI",
      "#base_imponible_melDREI",
      "#alicuota_melDREI",
      "#base_imponibleO_melDREI",
      "#alicuotaO_melDREI",
      "#monto_pagado_roDREI",
      "#base_imponible_roDREI",
      "#alicuota_roDREI",
      "#base_imponibleO_roDREI",
      "#alicuotaO_roDREI",
      "#total_roDREI",
      "#publicidadDREI",
      "#retDREI",
      "#minDREI",
      "#rect1DREI",
      "#rect2DREI",
      "#interesesDREI",
      "#deduccionesDREI",
    ].join(", "),
    { decimales: 2, permitirNegativos: true }
  );
  instalarNumeroFlexibleAR(
    [
      "#alicuota_comDREI",
      "#alicuota_gasDREI",
      "#alicuota_explDREI",
      "#alicuota_apyjDREI",
    ].join(", "),
    { decimales: 5, permitirNegativos: true }
  );

  instalarAutoImpuestoAR({
    base: "#base_imponible_comDREI",
    alicuota: "#alicuota_comDREI",
    impuesto: "#imp_det_comDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponible_gasDREI",
    alicuota: "#alicuota_gasDREI",
    impuesto: "#imp_det_gasDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponible_apyjDREI",
    alicuota: "#alicuota_apyjDREI",
    impuesto: "#imp_det_apyjDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponible_explDREI",
    alicuota: "#alicuota_explDREI",
    impuesto: "#imp_det_explDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponible_melDREI",
    alicuota: "#alicuota_melDREI",
    impuesto: "#imp_det_melDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponibleO_melDREI",
    alicuota: "#alicuotaO_melDREI",
    impuesto: "#imp_det0_melDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
  instalarAutoImpuestoAR({
    base: "#base_imponible_roDREI",
    alicuota: "#alicuota_roDREI",
    impuesto: "#imp_det_roDREI",
    decImp: 2,
    aliEsPorcentaje: true,
  });
});
instalarAutoSumaAR({
  sources: [
    "#imp_det_comDREI",
    "#imp_det_gasDREI",
    "#imp_det_explDREI",
    "#imp_det_apyjDREI",
    "#garageDREI",
    "#bromatologiaDREI",
    "#interesesDREI",
    "#contenedor-inputs-drei-extra-csf .drei-imp",
  ],
  target: "#imp_tot_csfDREI",
  decimales: 2,
});
instalarAutoSumaAR({
  sources: ["#imp_tot_csfDREI", "-#deduccionesDREI"],
  target: "#saldoDREI",
  decimales: 2,
});
instalarAutoSumaAR({
  sources: [
    "#imp_det0_melDREI",
    "#imp_det_melDREI",
    "#contenedor-inputs-drei-extra-mel .drei-imp",
  ],
  target: "#total_imp_det_melDREI",
  decimales: 2,
});
instalarAutoSumaAR({
  sources: [
    "#imp_det0_melDREI",
    "#imp_det_melDREI",
    "-#monto_pagado_melDREI",
    "#contenedor-inputs-drei-extra-mel .drei-imp",
  ],
  target: "#saldo_melDREI",
  decimales: 2,
});

function cargarArchivosDREILista(id, target) {
  var $list = $(targetArchivosDoc("DREI", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosDREI/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/drei/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="DREI"  class="btn btn-sm btn-danger btn-del-archivo-DREI" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-DREI", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
var contadorDreiExtra = 0;

function appendDreiExtraFila(b, index, container) {
  container = container || "#contenedor-inputs-drei-extra-csf";

  var id      = b && b.id   != null ? b.id   : "";
  var obs     = b && b.obs  ? b.obs  : "";

  var montoNum = b && b.monto    != null ? (parseNumeroFlexible(b.monto)    ? parseNumeroFlexible(b.monto).num    : null) : null;
  var aliNum   = b && b.alicuota != null ? (parseNumeroFlexible(b.alicuota) ? parseNumeroFlexible(b.alicuota).num : null) : null;
  var impNum   = b && b.imp      != null ? (parseNumeroFlexible(b.imp)      ? parseNumeroFlexible(b.imp).num      : null) : null;

  if (impNum == null && montoNum != null && aliNum != null) {
    impNum = montoNum * (aliNum / 100);
  }

  var montoFmt = montoNum != null ? formatoAR(montoNum, 2) : "";
  var aliFmt   = aliNum   != null ? formatoAR(aliNum, 5)   : "";
  var impFmt   = impNum   != null ? formatoAR(impNum, 2)   : "";

  var montoId = "monto_drei_" + index;
  var aliId   = "ali_drei_"   + index;
  var impId   = "imp_drei_"   + index;

  var html =
    '<div class="bases-drei-extra" data-id="' + index + '" data-base-id="' + id + '" style="margin-top:12px; border-top:1px solid #eee; padding-top:10px;">' +
    '  <input type="hidden" name="base_id_drei[]" value="' + id + '">' +
    '  <div class="row">' +
    '    <div class="col-md-3"><h5>Concepto</h5></div>' +
    '    <div class="col-md-3"><h5>Base imponible</h5></div>' +
    '    <div class="col-md-2"><h5>Alicuota (%)</h5></div>' +
    '    <div class="col-md-3"><h5>Impuesto Determinado</h5></div>' +
    '    <div class="col-md-1"></div>' +
    '  </div>' +
    '  <div class="row">' +
    '    <div class="col-md-3">' +
    '      <textarea name="label_drei[]" class="form-control" rows="2" maxlength="255" placeholder="Concepto">' + obs + '</textarea>' +
    '    </div>' +
    '    <div class="col-md-3">' +
    '      <input type="text" id="' + montoId + '" name="monto_drei[]" class="form-control js-num-ar drei-monto" placeholder="$" inputmode="decimal" value="' + montoFmt + '">' +
    '    </div>' +
    '    <div class="col-md-2">' +
    '      <input type="text" id="' + aliId + '" name="alicuota_drei[]" class="form-control js-num-ar drei-ali" placeholder="%" inputmode="decimal" value="' + aliFmt + '">' +
    '    </div>' +
    '    <div class="col-md-3">' +
    '      <input type="text" id="' + impId + '" name="impuesto_drei[]" class="form-control js-num-ar drei-imp" inputmode="decimal" value="' + impFmt + '">' +
    '    </div>' +
    '    <div class="col-md-1">' +
    '      <button type="button" class="btn btn-danger btn-sm eliminar-bloque-drei">X</button>' +
    '    </div>' +
    '  </div>' +
    '</div>';

  $(container).append(html);

  instalarNumeroFlexibleAR("#" + montoId, { decimales: 2, permitirNegativos: true });
  instalarNumeroFlexibleAR("#" + aliId,   { decimales: 5, permitirNegativos: true });
  instalarNumeroFlexibleAR("#" + impId,   { decimales: 2, permitirNegativos: true });

  instalarAutoImpuestoAR({
    base:           "#" + montoId,
    alicuota:       "#" + aliId,
    impuesto:       "#" + impId,
    aliEsPorcentaje: true,
    decImp:          2,
  });

  $("#" + montoId).trigger("input");
}

$(document).on("click", ".agregar-bloque-drei", function (e) {
  e.preventDefault();
  contadorDreiExtra++;
  var container = $(this).data("container");
  appendDreiExtraFila({}, contadorDreiExtra, container);
});

$(document).on("click", ".eliminar-bloque-drei", function () {
  $(this).closest(".bases-drei-extra").remove();
});

function abrirModalDREIEditar(id) {
  resetFormDREI();
  $("#DREI_modo").val("edit");
  $("#id_registroDREI").val(id);
  $("#modalCargarDREI .modal-title").text("| EDITAR REGISTRO DE DREI");
  $("#guardarRegistroDREI").text("ACTUALIZAR");
  $("#modalCargarDREI").modal("show");

  $.getJSON("/documentosContables/llenarDREIEdit/" + id, function (d) {
    var ym = String(d.fecha || d.fecha_drei || d.fecha_DREI || "").slice(0, 7);
    var ymd = String(
      d.fecha_pres ||
        d.fecha_presentacion ||
        d.fecha_DREIPres ||
        d["fecha pres"] ||
        ""
    ).slice(0, 10);

    $('#fechaDREI input[name="fecha_DREI"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $('#fechaDREIPres input[name="fecha_DREIPres"]')
      .val(ymd)
      .trigger("input")
      .trigger("change");

    var dp1 = $("#fechaDREI").data("datepicker");
    if (dp1 && $("#fechaDREI").datepicker) {
      $("#fechaDREI").datepicker(
        "setDate",
        new Date((ym || "1970-01") + "-01")
      );
      $("#fechaDREI").datepicker("update");
    }
    var dp2 = $("#fechaDREIPres").data("datepicker");
    if (dp2 && $("#fechaDREIPres").datepicker) {
      $("#fechaDREIPres").datepicker("setDate", new Date(ymd || "1970-01-01"));
      $("#fechaDREIPres").datepicker("update");
    }
    var dtp1 = $("#fechaDREI").data("DateTimePicker");
    if (dtp1 && dtp1.date)
      dtp1.date(
        window.moment
          ? moment((ym || "1970-01") + "-01", "YYYY-MM-DD")
          : new Date((ym || "1970-01") + "-01")
      );
    var dtp2 = $("#fechaDREIPres").data("DateTimePicker");
    if (dtp2 && dtp2.date)
      dtp2.date(
        window.moment
          ? moment(ymd || "1970-01-01", "YYYY-MM-DD")
          : new Date(ymd || "1970-01-01")
      );

    $("#casinoDREI")
      .val(d.casino || d.id_casino)
      .trigger("change");

    $("#obsDREI").val(d.obs || d.observacion || "");

    var tipo = String(d.casino || "");
    if (tipo === "2") {
      // CSF
      $("#bromatologiaDREI").val("$" + formatoAR(d.bromatologia ?? ""));
      $("#interesesDREI").val("$" + formatoAR(d.intereses ?? ""));
      $("#deduccionesDREI").val("$" + formatoAR(d.deducciones ?? ""));
      $("#saldoDREI").val("$" + formatoAR(d.saldo ?? ""));

      $("#base_imponible_comDREI").val(
        "$" + formatoAR(d.com_base_imponible ?? "")
      );
      $("#alicuota_comDREI").val(formatoAR(d.com_alicuota ?? "") + " %");
      $("#imp_det_comDREI").val("$ " + formatoAR(d.com_subt_imp_det ?? ""));

      $("#base_imponible_gasDREI").val(
        "$" + formatoAR(d.gas_base_imponible ?? "")
      );
      $("#alicuota_gasDREI").val(formatoAR(d.gas_alicuota ?? "") + " %");
      $("#imp_det_gasDREI").val("$ " + formatoAR(d.gas_imp_det ?? ""));

      $("#base_imponible_explDREI").val(
        "$" + formatoAR(d.expl_base_imponible ?? "")
      );
      $("#alicuota_explDREI").val(formatoAR(d.expl_alicuota ?? "") + " %");
      $("#imp_det_explDREI").val("$ " + formatoAR(d.expl_imp_det ?? ""));

      $("#base_imponible_apyjDREI").val(
        "$" + formatoAR(d.apyju_base_imponible ?? "")
      );
      $("#alicuota_apyjDREI").val(formatoAR(d.apyju_alicuota ?? "") + " %");
      $("#imp_det_apyjDREI").val("$ " + formatoAR(d.apyju_imp_det ?? ""));

      $("#imp_tot_csfDREI").val("$ " + formatoAR(d.imp_tot_csfDREI));
    } else if (tipo === "1") {
      // MEL
      $("#monto_pagado_melDREI").val("$" + formatoAR(d.monto_pagado ?? ""));

      $("#base_imponible_melDREI").val(
        "$" + formatoAR(d.com_base_imponible ?? "")
      );
      $("#alicuota_melDREI").val(formatoAR(d.com_alicuota ?? "") + " %");
      $("#imp_det_melDREI").val("$ " + formatoAR(d.com_subt_imp_det ?? ""));

      $("#base_imponibleO_melDREI").val(
        "$" + formatoAR(d.gas_base_imponible ?? "")
      );
      $("#alicuotaO_melDREI").val(formatoAR(d.gas_alicuota ?? "") + " %");
      $("#imp_det0_melDREI").val("$ " + formatoAR(d.gas_imp_det ?? ""));

      var total_imp_det_mel_edit = (parseFloat(d.com_subt_imp_det) || 0) + (parseFloat(d.gas_imp_det) || 0);
      if (d.bases && d.bases.length) {
        d.bases.forEach(function(b) {
          total_imp_det_mel_edit += parseFloat(b.imp) || 0;
        });
      }
      $("#total_imp_det_melDREI").val("$ " + formatoAR(total_imp_det_mel_edit));

      $("#saldo_melDREI").val("$ " + formatoAR(d.saldo));
    } else if (tipo === "3") {
      // RO
      $("#monto_pagado_roDREI").val("$" + formatoAR(d.monto_pagado ?? ""));

      $('#fechaDREIVenc input[name="fecha_DREIVenc"]')
        .val(String(d.vencimiento_previsto || "").slice(0, 10))
        .trigger("input")
        .trigger("change");

                              $("#total_roDREI").val("$ " + formatoAR(d.saldo));
    }

    // Cargar bases extra dinámicas
    var containerMap = { "1": "#contenedor-inputs-drei-extra-mel", "2": "#contenedor-inputs-drei-extra-csf", "3": "#contenedor-inputs-drei-extra-ro" };
    var container = containerMap[tipo] || "#contenedor-inputs-drei-extra-csf";
    $("#contenedor-inputs-drei-extra-csf, #contenedor-inputs-drei-extra-mel, #contenedor-inputs-drei-extra-ro").empty();
    contadorDreiExtra = 0;
    if (d.bases && d.bases.length) {
      d.bases.forEach(function (b) {
        contadorDreiExtra++;
        appendDreiExtraFila(b, contadorDreiExtra, container);
      });
    }
  });
}

function resetFormDREI() {
  var $f = $("#formNuevoRegistroDREI");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameDREI").val("");
    $("#uploadDREI").val("");
  $("#uploadsDREIContainer").empty();
  $("#uploadsDREITable tbody").empty();
  $("#uploadsDREIWrap").hide();
  $("#fileNameDREI").val("");
  $("#contenedor-inputs-drei-extra-csf, #contenedor-inputs-drei-extra-mel, #contenedor-inputs-drei-extra-ro").empty();
  contadorDreiExtra = 0;
}

function abrirModalDREICrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormDREI();
  $("#DREI_modo").val("create");
  $("#id_registroDREI").val("");
  $("#modalCargarDREI .modal-title").text("| NUEVO REGISTRO DE DREI");
  $("#guardarRegistroDREI").text("GENERAR");
  // Limpiar bases extra y resetear casino
  $("#contenedor-inputs-drei-extra-csf, #contenedor-inputs-drei-extra-mel, #contenedor-inputs-drei-extra-ro").empty();
  contadorDreiExtra = 0;
  $("#casinoDREI").val("").trigger("change");
  $("#modalCargarDREI").modal("show");
}

$(document).on("click", "#DREI_nuevo", function () {
  abrirModalDREICrear();
});

$(document).on("click", ".btn-edit-DREI", function () {
  var id = $(this).data("id");
  abrirModalDREIEditar(id);
});

$("#btn-descargarDREICsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;

  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  $("#collapseDescargarDREI .has-error").removeClass("has-error");
  $("#collapseDescargarDREI .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDREICsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarDREIExcel").on("click", function (e) {
  $("#collapseDescargarDREI .has-error").removeClass("has-error");
  $("#collapseDescargarDREI .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarDREIXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarDREIXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$(document).on("click", ".btn-deleteDREI", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarDREI").attr("data-id", id);
  $("#modalEliminarDREI").modal("show");
});

$("#btn-eliminarDREI").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarDREI/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarDREI").modal("hide");
        cargarDREI({
          page: $("#herramientasPaginacionDREI").getCurrentPage(),
          perPage: $("#herramientasPaginacionDREI").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

function clickIndiceDREI(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDREI({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarDREI({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasDREI",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaDREI").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaDREI").append(generarFilaDREI(item));
      });

      $("#herramientasPaginacionDREI").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDREI
      );
      $("#herramientasPaginacionDREI").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDREI
      );
    },
    error(err) {
      console.error("Error cargando DREI:", err);
    },
  });
}

function generarFilaDREI(drei, controlador) {
  const fila = $("<tr>").attr("id", drei.id_registroDREI);

  const fecha = convertirMesAno(drei.fecha_drei) || "-";
  const pres = drei.fecha_presentacion || "-";
  const casino = drei.casino || "-";
  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").html(pres))
    .append($("<td>").addClass("col-xs-3").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-DREI")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", drei.id_registroDREI)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteDREI")
    .attr("id", drei.id_registroDREI)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO DREI")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(drei.valido, "drei", drei.id_registroDREI)
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", "#guardarRegistroDREI", function (e) {
  var $form = $("#formNuevoRegistroDREI");
  var modo = $("#DREI_modo").val() || "create";
  var id = $("#id_registroDREI").val() || "";

  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    'select[name="casinoDREI"]',
    ".col-md-4",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    'input[name="fecha_DREI"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  );
  valid = validarCampo(
    'input[name="fecha_DREIPres"]',
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  const visibleFormId = $(".formulario-DREI:visible").attr("id");

  if (visibleFormId === "formularioCSF") {
    valid = validarCampoNum(
      'input[name="bromatologiaDREI"]:visible',
      ".col-md-5",
      "La bromatología es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponible_comDREI"]:visible',
      ".col-md-4",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuota_comDREI"]:visible',
      ".col-md-4",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponible_gasDREI"]:visible',
      ".col-md-4",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuota_gasDREI"]:visible',
      ".col-md-4",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponible_explDREI"]:visible',
      ".col-md-4",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuota_explDREI"]:visible',
      ".col-md-4",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponible_apyjDREI"]:visible',
      ".col-md-4",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuota_apyjDREI"]:visible',
      ".col-md-4",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="interesesDREI"]:visible',
      ".col-md-4",
      "Los intereses son requeridos.",
      valid
    );

    valid = validarCampoNum(
      'input[name="deduccionesDREI"]:visible',
      ".col-md-4",
      "La deducción es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="imp_det_comDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="imp_det_gasDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="imp_det_explDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="imp_det_apyjDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="imp_tot_csfDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );

//     valid = validarCampoNum(
//       'input[name="saldoDREI"]:visible',
//       ".col-md-4",
//       "El saldo es requerido.",
//       valid
//     );
  } else if (visibleFormId === "formularioMEL") {
    valid = validarCampoNum(
      'input[name="monto_pagado_melDREI"]:visible',
      ".col-md-6",
      "El monto pagado es requerido.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponible_melDREI"]:visible',
      ".col-md-6",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuota_melDREI"]:visible',
      ".col-md-6",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="base_imponibleO_melDREI"]:visible',
      ".col-md-6",
      "La base es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="alicuotaO_melDREI"]:visible',
      ".col-md-6",
      "La alicuota es requerida.",
      valid
    );

    valid = validarCampoNum(
      'input[name="imp_det_melDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="imp_det0_melDREI"]:visible',
      ".col-md-4",
      "El impuesto es requerido.",
      valid
    );
    valid = validarCampoNum(
      'input[name="saldo_melDREI"]:visible',
      ".col-md-4",
      "El saldo es requerido.",
      valid
    );
  } else if (visibleFormId === "formularioRO") {
    valid = validarCampoNumSiHayValor(
      $('input[name="monto_pagado_roDREI"]:visible'),
      ".col-md-6",
      "El monto pagado es requerido.",
      valid
    );

    valid = validarCampoNumSiHayValor(
      $('input[name="total_roDREI"]:visible'),
      ".col-md-6",
      "El total es requerido.",
      valid
    );

    const $fechavenc = $form.find('input[name="fecha_DREIVenc"]');
    if (!$fechavenc.val()) {
      $fechavenc
        .closest(".col-md-6")
        .addClass("has-error")
        .append(
          '<span class="help-block js-error">La fecha es requerida.</span>'
        );
      valid = false;
    }
  }

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $('#uploadsDREIContainer input[type="file"][name="uploadDREI[]"]').each(
    function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadDREI[]", files[i]);
      }
    }
  );

  var cur = document.getElementById("uploadDREI");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadDREI[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarDREI/" + id
      : "/documentosContables/guardarDREI";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarDREI({
        page: $("#herramientasPaginacionDREI").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionDREI").getPageSize(),
        casino: $("#filtro_global_casino").val(),
      });
      setTimeout(() => $("#modalCargarDREI").modal("hide"), 1000);
      resetFormDREI();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

//IVA
function cargarArchivosIvaLista(id, target) {
  var $list = $(targetArchivosDoc("Iva", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosIva/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/iva/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="Iva" class="btn btn-sm btn-danger btn-del-archivo-iva" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-iva", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

$(document).on("shown.bs.modal", "#modalCargarPremiosIVA", function () {
  $("#saldoIva").trigger("input");
});

instalarNumeroFlexibleAR("#saldoIva", {
  decimales: 2,
  permitirNegativos: true,
});

function clickIndiceIva(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIva({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function resetFormIva() {
  var $f = $("#formNuevoRegistroIva");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameIva").val("");
    $("#uploadIva").val("");
  $("#uploadsIvaContainer").empty();
  $("#uploadsIvaTable tbody").empty();
  $("#uploadsIvaWrap").hide();
  $("#fileNameIva").val("");
}

function abrirModalIvaCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormIva();
  $("#iva_modo").val("create");
  $("#id_registroIva").val("");
  $("#modalCargarIva .modal-title").text("| NUEVO REGISTRO DE IVA");
  $("#guardarRegistroIva").text("GENERAR");
  $("#modalCargarIva").modal("show");
}

function abrirModalIvaEditar(id) {
  resetFormIva();
  $("#iva_modo").val("edit");
  $("#id_registroIva").val(id);
  $("#modalCargarIva .modal-title").text("| EDITAR REGISTRO DE IVA");
  $("#guardarRegistroIva").text("ACTUALIZAR");
  $("#modalCargarIva").modal("show");

  $.getJSON("/documentosContables/llenarIvaEdit/" + id, function (d) {
    var ym = String(d.fecha || d.fecha_iva || "").slice(0, 7);
    var ymd = String(
      d.fecha_pres ||
        d.fecha_presentacion ||
        d.fecha_ivaPres ||
        d["fecha pres"] ||
        ""
    ).slice(0, 10);

    $("#casinoIva").val(d.casino);
    $("#saldoIva").val(formatoAR(d.saldo));
    $('[name="obsiva"]').val(d.obs || d.observacion || "");

    $('#fechaIva input[name="fecha_iva"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $('#fechaIvaPres input[name="fecha_ivaPres"]')
      .val(ymd)
      .trigger("input")
      .trigger("change");

    var dp1 = $("#fechaIva").data("datepicker");
    if (dp1 && $("#fechaIva").datepicker) {
      $("#fechaIva").datepicker("setDate", new Date(ym + "-01"));
      $("#fechaIva").datepicker("update");
    }
    var dp2 = $("#fechaIvaPres").data("datepicker");
    if (dp2 && $("#fechaIvaPres").datepicker) {
      $("#fechaIvaPres").datepicker("setDate", new Date(ymd));
      $("#fechaIvaPres").datepicker("update");
    }
    var dtp1 = $("#fechaIva").data("DateTimePicker");
    if (dtp1 && dtp1.date)
      dtp1.date(
        window.moment ? moment(ym + "-01", "YYYY-MM-DD") : new Date(ym + "-01")
      );
    var dtp2 = $("#fechaIvaPres").data("DateTimePicker");
    if (dtp2 && dtp2.date)
      dtp2.date(window.moment ? moment(ymd, "YYYY-MM-DD") : new Date(ymd));
  }).fail(function (xhr) {
    console.error("[IVA] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro de IVA.");
  });
}

$(document).on("click", "#iva_nuevo", function () {
  abrirModalIvaCrear();
});

$(document).on("click", ".btn-edit-iva", function () {
  var id = $(this).data("id");
  abrirModalIvaEditar(id);
});
$(document).on("click", "#guardarRegistroIva", function () {
  var $form = $("#formNuevoRegistroIva");
  var modo = $("#iva_modo").val() || "create";
  var id = $("#id_registroIva").val() || "";
  var valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoIva']",
    ".col-md-4",
    "El casino es requerido.",
    valid
  );

  valid = validarCampoNum(
    "input[name='saldoIva']",
    ".col-md-4",
    "El saldo es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_iva']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );
  valid = validarCampo(
    "input[name='fecha_ivaPres']",
    ".col-md-5",
    "La fecha es requerida.",
    valid
  );

  if (!valid) return;

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $('#uploadsIvaContainer input[type="file"][name="uploadIva[]"]').each(
    function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadIva[]", files[i]);
      }
    }
  );

  var cur = document.getElementById("uploadIva");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadIva[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarIva/" + id
      : "/documentosContables/guardarIva";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") },
    success: function () {
      cargarIva({
        page: $("#herramientasPaginacionIVA").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionIVA").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      $("#modalCargarIva").modal("hide");
      resetFormIva();
    },
    error: function () {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger">Ocurrió un error.</span>'
      );
    },
  });
});

function cargarIva({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasIva",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaIVA").empty();
      res.registros.forEach((item) => {
        $("#cuerpoTablaIVA").append(generarFilaIva(item));
      });

      $("#herramientasPaginacionIVA").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIva
      );
      $("#herramientasPaginacionIVA").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIva
      );
    },
    error(err) {
      console.error("Error cargando IVA:", err);
    },
  });
}

function generarFilaIva(iva, controlador) {
  const fila = $("<tr>").attr("id", iva.id_registroIva);

  const fecha = convertirMesAno(iva.fecha_iva) || "-";
  const pres = iva.fecha_presentacion || "-";
  const casino = iva.casino || "-";
  const saldo = "$ " + formatoAR(iva.saldo);

  fila
    .append($("<td>").addClass("col-xs-2").html(fecha))
    .append($("<td>").addClass("col-xs-3").html(pres))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-2").text(saldo));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");

  // Always add VER REGISTRO IVA button

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-iva")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", iva.id_registroIva)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);
  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteIva")
    .attr("id", iva.id_registroIva)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO IVA")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(obtenerIconoValidacion(iva.valido, "iva", iva.id_registroIva));

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteIva", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarIva").attr("data-id", id);
  $("#modalEliminarIva").modal("show");
});

$("#btn-eliminarIva").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarIva/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarIva").modal("hide");
        cargarIva({
          page: $("#herramientasPaginacionIVA").getCurrentPage(),
          perPage: $("#herramientasPaginacionIVA").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$(document).on("click", "#btn-ObsIva", function (e) {
  const texto = $(this).data("obs");
  $("#obsIvaContent").text(texto);

  $("#modalObsIva").modal("show");
});

$("#btn-descargarIvaCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarIva .has-error").removeClass("has-error");
  $("#collapseDescargarIva .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIvaCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarIvaExcel").on("click", function (e) {
  $("#collapseDescargarIva .has-error").removeClass("has-error");
  $("#collapseDescargarIva .js-error").remove();
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarIvaXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarIvaXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

//TGI

var TGI_SUPPRESS_CASINO_CHANGE = false;

$(function () {
  instalarNumeroFlexibleAR(
    ["#Cocheras_importeTGI", "#Hotel_importeTGI", "#Cocheras_importeTGI"].join(
      ", "
    ),
    { decimales: 2, permitirNegativos: true }
  );
});

function cargarArchivosTGILista(id, target) {
  var $list = $(targetArchivosDoc("TGI", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosTGI/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/TGI/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="TGI"  class="btn btn-sm btn-danger btn-del-archivo-TGI" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-TGI", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalTGIEditar(id) {
  resetFormTGI();
  $("#TGI_modo").val("edit");
  $("#id_registroTGI").val(id);
  $("#modalCargarTGI .modal-title").text("| EDITAR REGISTRO DE TGI");
  $("#guardarRegistroTGI").text("ACTUALIZAR");
  $("#modalCargarTGI").modal("show");

  TGI_SUPPRESS_CASINO_CHANGE = true;

  $.getJSON("/documentosContables/llenarTGIEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);

    $('#fechaTGI input[name="fecha_TGI"]').val(ym);
    $("#casinoTGI").val(d.casino);

    const $cont = $("#pagosTGIContainer").empty();
    TGI_PAGOS_IDX = 0;

    var seen = {};
    if (Array.isArray(d.pagos) && d.pagos.length) {
      d.pagos.forEach(function (p) {
        var key =
          String(p.partida_id || "") +
          "|" +
          String(p.fecha_vencimiento || "") +
          "|" +
          String(p.fecha_pago || "") +
          "|" +
          String(p.importe || "");
        if (seen[key]) return;
        seen[key] = 1;

        var $row = renderFilaPagoTGI({
          id: p.partida_id || "",
          partida: p.partida || "",
        });
        $row.find("input.pago-cuota").val(p.cuota || "");
        $row.find("textarea.pago-observacion").val(p.observacion || "");
        $row
          .find("input.pago-importe")
          .val(p.importe || "")
          .trigger("blur");
        $row
          .find("input.pago-vto")
          .val(p.fecha_vencimiento || "")
          .trigger("change");
        $row
          .find("input.pago-pago")
          .val(p.fecha_pago || "")
          .trigger("change");
      });
    }
  })
    .fail(function (xhr) {
      console.error("[TGI editar] GET FAIL", xhr.status, xhr.responseText);
      alert("No se pudo cargar el TGI.");
    })
    .always(function () {
      TGI_SUPPRESS_CASINO_CHANGE = false;
    });
}

function resetFormTGI() {
  const $f = $("#formNuevoRegistroTGI");
  if ($f[0]) $f[0].reset();

  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();

  $(".formulario-TGI")
    .hide()
    .find("input,textarea,select")
    .val("")
    .trigger("change");

    $("#uploadsTGITable tbody").empty();
  $("#uploadsTGIWrap").hide();
  $("#uploadsTGIContainer").empty();
  $("#fileNameTGI").val("");

}

function abrirModalTGICrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormTGI();
  $("#TGI_modo").val("create");
  $("#id_registroTGI").val("");
  $("#modalCargarTGI .modal-title").text("| NUEVO REGISTRO DE TGI");
  $("#guardarRegistroTGI").text("GENERAR");
  $("#modalCargarTGI").modal("show");
}

$(document).on("click", "#TGI_nuevo", function () {
  abrirModalTGICrear();
});

$(document).on("click", ".btn-edit-TGI", function () {
  var id = $(this).data("id");
  abrirModalTGIEditar(id);
});

$(document).on("click", "#TGI_nueva_partida", function (e) {
  $("#modalCargarTGI_partida").modal("show");
});

$(document).on("click", "#TGI_partida_gestionar", function () {
  $("#modalTGI_partida_gestionar").modal("show");
  cargarTGI_partidaGestion();
});

function renderFilaTGI_partida(d) {
  var estado = d.estado
    ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
    : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return (
    "" +
    '<tr data-estado="' +
    d.estado +
    '" data-id="' +
    d.id +
    '" data-casino"' +
    escapeAttr(d.casino_id || "") +
    '"  data-partida="' +
    escapeAttr(d.partida || "") +
    '">' +
    '<td class="col-md-8">' +
    escapeHtml(d.partida || "") +
    "</td>" +
    '<td class="col-md-1">' +
    escapeHtml(d.casino_nombre || "") +
    "</td>" +
    '<td class="col-md-2">' +
    estado +
    "</td>" +
    '<td class="col-md-1">' +
    '<button type="button" class="btn btn-sm btn-primary btn-editTGI_partida" title="MODIFICAR PARTIDA">' +
    '<i class="fa fa-pencil-alt"></i>' +
    "</button> " +
    '<button type="button" class="btn btn-sm btn-danger btn-elimTGI_partida" title="ELIMINAR PARTIDA">' +
    '<i class="fa fa-trash"></i>' +
    "</button>" +
    "</td>" +
    "</tr>"
  );
}

let TGI_PAGOS_IDX = 0;

function renderFilaPagoTGI(p) {
  const idx = ++TGI_PAGOS_IDX;

  const $row = $(
    '<div class="row pago-row" data-idx="' +
      idx +
      '" style="margin-bottom:10px;">\
      <input type="hidden" name="pago_partida[]" value="' +
      (p.id || "") +
      '">\
      <div class="col-md-2">\
        <h5>Partida</h5>\
        <div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">' +
      (p.partida || "") +
      '</div>\
      </div>\
      <div class="col-md-1">\
        <h5>Cuota</h5>\
        <input type="text" class="form-control pago-cuota" id="pago_cuota_' +
      idx +
      '" name="pago_cuota[]" >\
      </div>\
      <div class="col-md-2">\
        <h5>Importe</h5>\
        <input type="text" class="form-control pago-importe" id="pago_importe_' +
      idx +
      '" name="pago_importe[]" placeholder="$">\
      </div>\
      <div class="col-md-2">\
        <h5>Fecha pago</h5>\
        <div class="input-group date" id="pago_pago_' +
      idx +
      '">\
          <input type="text" class="form-control pago-pago" name="pago_pago[]" placeholder="yyyy-mm-dd" autocomplete="off">\
          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>\
          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>\
        </div>\
      </div>\
      <div class="col-md-2">\
        <h5>Vencimiento</h5>\
        <div class="input-group date" id="pago_vto_' +
      idx +
      '">\
          <input type="text" class="form-control pago-vto" name="pago_vencimiento[]" placeholder="yyyy-mm-dd" autocomplete="off">\
          <span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>\
          <span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>\
        </div>\
      </div>\
      <div class="col-md-3">\
        <h5>Observaciones</h5>\
        <textarea class="form-control pago-observacion" id="pago_observacion_' +
      idx +
      '" name="pago_observacion[]" ></textarea>\
      </div>\
    </div>'
  );

  $("#pagosTGIContainer").append($row);
  initPagoRow(idx);

  return $row;
}

function initPagoRow(idx) {
  const $modal = $("#modalCargarTGI");
  const $vto = $("#pago_vto_" + idx);
  const $pago = $("#pago_pago_" + idx);

  if (typeof instalarNumeroFlexibleAR === "function") {
    instalarNumeroFlexibleAR("#pago_importe_" + idx, {
      decimales: 2,
      permitirNegativos: true,
    });
  }
  if (typeof attachYYYYMMDDFormatter === "function") {
    attachYYYYMMDDFormatter("#pago_vto_" + idx + " input");
    attachYYYYMMDDFormatter("#pago_pago_" + idx + " input");
  }

  if (!$.fn.datetimepicker) {
    console.warn("[TGI] Falta $.fn.datetimepicker");
    return;
  }

  try {
    $vto.datetimepicker("remove");
  } catch (e) {}
  try {
    $pago.datetimepicker("remove");
  } catch (e) {}

  const opts = {
    language: "es",
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: "yyyy-mm-dd",
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false,
  };

  $vto.datetimepicker(opts);
  $pago.datetimepicker(opts);

  [$vto, $pago].forEach(function ($wrap) {
    $wrap.off("show.dp.reloc").on("show.dp.reloc", function () {
      const inst = $wrap.data("datetimepicker");
      const $widget =
        inst && inst.picker ? inst.picker : $(".datetimepicker:visible").last();
      if ($widget.length && !$modal.has($widget).length) {
        $modal.append($widget);
        try {
          inst.place();
        } catch (e) {}
      }
      $widget
        .find(".timepicker, .datetimepicker-hours, .datetimepicker-minutes")
        .hide();
      $widget.find(".datepicker, .datetimepicker-days").show();
    });
  });

  $vto.off("changeDate.dpHide").on("changeDate.dpHide", function () {
    try {
      $vto.datetimepicker("hide");
    } catch (e) {}
  });
  $pago.off("changeDate.dpHide").on("changeDate.dpHide", function () {
    try {
      $pago.datetimepicker("hide");
    } catch (e) {}
  });

  $modal
    .off(
      "click.dpOpen",
      "#pago_vto_" +
        idx +
        " .input-group-addon:last-child, #pago_pago_" +
        idx +
        " .input-group-addon:last-child"
    )
    .on(
      "click.dpOpen",
      "#pago_vto_" +
        idx +
        " .input-group-addon:last-child, #pago_pago_" +
        idx +
        " .input-group-addon:last-child",
      function () {
        $(this).closest(".input-group.date").datetimepicker("show");
      }
    )
    .off(
      "click.dpClear",
      "#pago_vto_" +
        idx +
        " .input-group-addon:first-child, #pago_pago_" +
        idx +
        " .input-group-addon:first-child"
    )
    .on(
      "click.dpClear",
      "#pago_vto_" +
        idx +
        " .input-group-addon:first-child, #pago_pago_" +
        idx +
        " .input-group-addon:first-child",
      function () {
        $(this)
          .closest(".input-group.date")
          .find("input")
          .val("")
          .trigger("input")
          .trigger("change");
        try {
          $(this).closest(".input-group.date").datetimepicker("hide");
        } catch (e) {}
      }
    );
}

(function injectPickerFixes() {
  if (document.getElementById("dtp-fixes-css")) return;
  const css = `
    #modalCargarTGI .datetimepicker{ z-index: 20000 !important; }
    #modalCargarTGI .datetimepicker .timepicker,
    #modalCargarTGI .datetimepicker .datetimepicker-hours,
    #modalCargarTGI .datetimepicker .datetimepicker-minutes{ display:none !important; }
    #modalCargarTGI .datetimepicker .datepicker,
    #modalCargarTGI .datetimepicker .datetimepicker-days{ display:block !important; }
  `;
  const style = document.createElement("style");
  style.id = "dtp-fixes-css";
  style.type = "text/css";
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();

function cargarPagosPorCasino(casinoId) {
  const $cont = $("#pagosTGIContainer").empty();
  TGI_PAGOS_IDX = 0;
  if (!casinoId) return;

  $.getJSON("/documentosContables/getTGI_partidaPorCasino", {
    casino: casinoId,
  })
    .done(function (data) {
      (data || []).forEach(function (p) {
        $cont.append(renderFilaPagoTGI(p));
      });
    })
    .fail(function () {
      $cont.append(
        '<div class="alert alert-danger">Error al cargar partidas.</div>'
      );
    });
}

$(document)
  .off("change.tgiPagos", "#casinoTGI")
  .on("change.tgiPagos", "#casinoTGI", function () {
    if (TGI_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasino($(this).val());
  });

$(document).on("click", ".btn-del-pago", function () {
  $(this).closest(".pago-row").remove();
});

$(document).on("click", ".btn-del-pago", function () {
  $(this).closest(".pago-row").remove();
});

function cargarTGI_partidaGestion(casinoId) {
  $("#dir-list-loading_TGI_partida").show();
  $("#tabla-TGI_partida").closest(".table-responsive").hide();
  $("#tabla-TGI_partida tbody").empty();

  var url = "/documentosContables/getTGI_partida";

  $.getJSON(url, function (data) {
    var rows = "";
    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      rows += renderFilaTGI_partida(d);
    }
    $("#tabla-TGI_partida tbody").html(rows);
    $("#dir-list-loading_TGI_partida").hide();
    $("#tabla-TGI_partida").closest(".table-responsive").show();
  }).fail(function (xhr) {
    $("#dir-list-loading_TGI_partida").text(
      "Error cargando elementos patentables."
    );
    console.error(xhr.responseText);
  });
}

$(document).on("click", ".btn-elimTGI_partida", function () {
  const id = $(this).closest("tr").data("id");

  $("#btn-eliminarTGI_partida").attr("data-id", id);
  $("#modalEliminarTGI_partida").modal("show");
});

$(document).on("click", ".btn-editTGI_partida", function () {
  const $tr = $(this).closest("tr");
  const id = $(this).closest("tr").data("id");
  const partida = $(this).closest("tr").data("partida");
  const estado = $(this).closest("tr").data("estado");
  $("#ModifId_TGI_partida").val(id);
  $("#ModifEstadoTGI_partida").val(estado);
  $("#ModifTGI_partida_partida").val(partida);

  $("#modalModificarTGI_partida").modal("show");
});

$(document).on("click", "#guardarModifRegistroTGI_partida", function () {
  const $btn = $(this).prop("disabled", true);
  const $form = $("#formModificarRegistroTGI_partida");

  valid = true;
  valid = validarCampo(
    "input[name='ModifTGI_partida_partida']",
    ".col-md-12",
    "El tipo es requerido.",
    valid
  );

  if (!valid) return 0;

  $.ajax({
    url: "/documentosContables/modificarTGI_partida",
    method: "POST",
    data: $form.serialize(),
    success: function (res) {
      cargarTGI_partidaGestion();
      $("#modalModificarTGI_partida").modal("hide");
    },
    error: function (xhr) {
      console.error(xhr.responseText || xhr);
    },
    complete: function () {
      $btn.prop("disabled", false);
    },
  });
});

$(document).on("click", "#btn-eliminarTGI_partida", function () {
  const id = $(this).attr("data-id");
  const $tr = $('#tabla-TGI_partida tr[data-id="' + id + '"]');

  function avisoPartida(msg) {
    // Se cierra el de confirmación y recién ahí se muestra el aviso, para no encimar backdrops.
    $("#modalEliminarTGI_partida")
      .one("hidden.bs.modal", function () {
        $("#texto-aviso-validacion").text(msg);
        $("#modalAvisoValidacion").modal("show");
      })
      .modal("hide");
  }

  $.get("/documentosContables/TGIEliminarPartida/" + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $("#modalEliminarTGI_partida").modal("hide");
    } else {
      // Antes esto no hacía nada: el modal quedaba abierto y no se sabía por qué no borraba.
      avisoPartida(res.msg || "No se pudo eliminar la partida.");
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
    avisoPartida(
      (xhr.responseJSON && xhr.responseJSON.msg) ||
        "No se pudo eliminar la partida."
    );
  });
});

$(document).on("click", "#guardarRegistroTGI_partida", function (e) {
  var $form = $("#formNuevoRegistroTGI_partida");
  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='CasinoTGI_partida']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='nombre_TGI_partida']",
    ".col-md-9",
    "El nombre de la partida es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: "/documentosContables/guardarRegistroTGI_partida",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      setTimeout(() => $("#modalCargarTGI_partida").modal("hide"), 1000);
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

$(document).on("click", "#guardarRegistroTGI", function (e) {
  var $form = $("#formNuevoRegistroTGI");
  let valid = true;
  var id = $("#id_registroTGI").val() || "";
  var modo = $("#TGI_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  var $casino = $form.find('select[name="casinoTGI"]');
  if (!$casino.val()) {
    $casino
      .closest(".col-md-4")
      .addClass("has-error")
      .append(
        '<span class="help-block js-error">El casino es requerido.</span>'
      );
    valid = false;
  }

  valid = validarCampo(
    'input[name="fecha_TGI"]',
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  const visibleFormId = $(".formulario-TGI:visible").attr("id");

  var $obs = $form.find('textarea[name="obsTGI"]');
  var obsText = $obs.val() ? $obs.val().trim() : "";
  if (obsText.length > 4000) {
    $obs.closest(".col-md-4").find(".help-block.js-error").remove();

    $obs
      .closest(".col-md-4")
      .addClass("has-error")
      .append(
        '<span class="help-block js-error text-danger">El máximo de caracteres es de 4000.</span>'
      );
    valid = false;
  }

  $("#pagosTGIContainer .pago-row").each(function () {
    var $row = $(this);
    var $imp = $row.find("input.pago-importe");
    var $vto = $row.find("input.pago-vto");
    var $pago = $row.find("input.pago-pago");

    var vImp = ($imp.val() || "").trim();
    var vVto = ($vto.val() || "").trim();
    var vPago = ($pago.val() || "").trim();

    if (!vImp && !vVto && !vPago) return;

    valid = validarCampoNumSiHayValor(
      $imp,
      ".col-md-2",
      "El importe es requerido.",
      valid
    );
    valid = validarCampo(
      $vto,
      ".col-md-3",
      "El vencimiento es requerido.",
      valid
    );
    valid = validarCampo(
      $pago,
      ".col-md-3",
      "La fecha de pago es requerida.",
      valid
    );
  });

  if (!valid) return;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $('#uploadsTGIContainer input[type="file"][name="uploadTGI[]"]').each(
    function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadTGI[]", files[i]);
      }
    }
  );

  var cur = document.getElementById("uploadTGI");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadTGI[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarTGI/" + id
      : "/documentosContables/guardarTGI";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarTGI({
        page: $("#herramientasPaginacionTGI").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionTGI").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      resetFormTGI();

      setTimeout(() => $("#modalCargarTGI").modal("hide"), 1000);
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceTGI(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarTGI({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarTGI({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasTGI",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaTGI").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaTGI").append(generarFilaTGI(item));
      });

      $("#herramientasPaginacionTGI").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceTGI
      );
      $("#herramientasPaginacionTGI").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceTGI
      );
    },
    error(err) {
      console.error("Error cargando TGI:", err);
    },
  });
}

function generarFilaTGI(TGI, controlador) {
  const fila = $("<tr>").attr("id", TGI.id_registroTGI);
  const fecha = convertirMesAno(TGI.fecha_TGI) || "-";
  const fechaPres = TGI.fechaPres || "-";
  const casino = TGI.casino || "-";
  const file = TGI.archivo;

  fila
    .append($("<td>").addClass("col-xs-4").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-4 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-TGI")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", TGI.id_registroTGI)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteTGI")
    .attr("id", TGI.id_registroTGI)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO TGI")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(obtenerIconoValidacion(TGI.valido, "tgi", TGI.id_registroTGI));

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteTGI", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarTGI").attr("data-id", id);
  $("#modalEliminarTGI").modal("show");
});

$("#btn-eliminarTGI").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarTGI/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarTGI").modal("hide");
        cargarTGI({
          page: $("#herramientasPaginacionTGI").getCurrentPage(),
          perPage: $("#herramientasPaginacionTGI").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {
      console.log("error");
    });
});
let VER_TGI_PAGOS_IDX = 0;

$("#btn-descargarTGICsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarTGI .has-error").removeClass("has-error");
  $("#collapseDescargarTGI .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarTGICsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarTGIExcel").on("click", function (e) {
  $("#collapseDescargarTGI .has-error").removeClass("has-error");
  $("#collapseDescargarTGI .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarTGIXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarTGIXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

//IMPUESTOS A APUESTAS ONLINE IMP_AP_OL

$(function () {
  instalarNumeroFlexibleAR(
    [
      "#monto_pagadoIMP_AP_OL",
      "#monto_apuestasIMP_AP_OL",
      "#impuesto_determinadoIMP_AP_OL",
    ].join(", "),
    { decimales: 2 }
  );
  instalarNumeroFlexibleAR("#alicuotaIMP_AP_OL", { decimales: 5 });

  instalarAutoImpuestoAR({
    base: "#monto_apuestasIMP_AP_OL",
    alicuota: "#alicuotaIMP_AP_OL",
    impuesto: "#impuesto_determinadoIMP_AP_OL",
    decImp: 2,
    aliEsPorcentaje: 1,
    esIndirecto: true,
  });

  $("#casinoIMP_AP_OL").on("change", function () {
    var casinoId = $(this).val();
    if (!casinoId || $("#IMP_AP_OL_modo").val() !== "create") return;
    $.getJSON("/documentosContables/ultimasAlicuotasIMP_AP_OL", { casino_id: casinoId }, function (data) {
      if (data.alicuota == null) return;
      var n = parseFloat(data.alicuota) || 0;
      var decs = $("#alicuotaIMP_AP_OL").data("_numflex_dec") || 5;
      $("#alicuotaIMP_AP_OL").val(formatoAR(n, decs)).data("num", n).trigger("num:changed", [n]);
    });
  });
});

function cargarArchivosIMP_AP_OLLista(id, target) {
  var $list = $(targetArchivosDoc("IMP_AP_OL", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosIMP_AP_OL/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/IMP_AP_OL/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="IMP_AP_OL"  class="btn btn-sm btn-danger btn-del-archivo-IMP_AP_OL" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-IMP_AP_OL", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalIMP_AP_OLEditar(id) {
  resetFormIMP_AP_OL();
  $("#IMP_AP_OL_modo").val("edit");
  $("#id_registroIMP_AP_OL").val(id);
  $("#modalCargarIMP_AP_OL .modal-title").text(
    "| EDITAR REGISTRO DE IMPUESTO A APUESTAS ONLINE"
  );
  $("#guardarRegistroIMP_AP_OL").text("ACTUALIZAR");
  $("#modalCargarIMP_AP_OL").modal("show");

  $.getJSON("/documentosContables/llenarIMP_AP_OLEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);
    var ymd = String(d.fecha_pres || "").slice(0, 10);
    var ymdp = String(d.fecha_pago || "").slice(0, 10);

    $('#fechaIMP_AP_OL input[name="fecha_IMP_AP_OL"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $('#fechaIMP_AP_OLPres input[name="fecha_IMP_AP_OLPres"]')
      .val(ymd)
      .trigger("input")
      .trigger("change");
    $('input[name="fecha_pago_IMP_AP_OL"]')
      .val(ymdp)
      .trigger("input")
      .trigger("change");

    $("#casinoIMP_AP_OL").val(d.casino).trigger("change");

    $('select[name="qnaIMP_AP_OL"]')
      .val(d.qna ?? "")
      .trigger("change");
    $('input[name="monto_pagadoIMP_AP_OL"]').val(
      formatoAR(d.monto_pagado ?? "")
    );
    $('input[name="monto_apuestasIMP_AP_OL"]').val(
      formatoAR(d.monto_apuestas ?? "")
    );
    $('input[name="alicuotaIMP_AP_OL"]').val(formatoAR(d.alicuota ?? ""));
    $('input[name="impuesto_determinadoIMP_AP_OL"]').val(
      formatoAR(d.impuesto_determinado ?? "")
    );
    $('[name="obsIMP_AP_OL"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[IMP_AP_OL] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormIMP_AP_OL() {
  var $f = $("#formNuevoRegistroIMP_AP_OL");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameIMP_AP_OL").val("");
    $("#uploadIMP_AP_OL").val("");
  $("#uploadsIMP_AP_OLContainer").empty();
  $("#uploadsIMP_AP_OLTable tbody").empty();
  $("#uploadsIMP_AP_OLWrap").hide();
  $("#fileNameIMP_AP_OL").val("");
}

function abrirModalIMP_AP_OLCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormIMP_AP_OL();
  $("#IMP_AP_OL_modo").val("create");
  $("#id_registroIMP_AP_OL").val("");
  $("#modalCargarIMP_AP_OL .modal-title").text(
    "| NUEVO REGISTRO DE IMPUESTO A APUESTAS ONLINE"
  );
  $("#guardarRegistroIMP_AP_OL").text("GENERAR");
  $("#modalCargarIMP_AP_OL").modal("show");
}

$(document).on("click", "#IMP_AP_OL_nuevo", function () {
  abrirModalIMP_AP_OLCrear();
});

$(document).on("click", ".btn-edit-IMP_AP_OL", function () {
  var id = $(this).data("id");
  abrirModalIMP_AP_OLEditar(id);
});

$(document).on("click", "#guardarRegistroIMP_AP_OL", function (e) {
  var $form = $("#formNuevoRegistroIMP_AP_OL");
  let valid = true;
  var id = $("#id_registroIMP_AP_OL").val() || "";
  var modo = $("#IMP_AP_OL_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    'select[name="casinoIMP_AP_OL"]',
    ".col-md-3",
    "El casino es requerido",
    valid
  );
  valid = validarCampoNum(
    'input[name="alicuotaIMP_AP_OL"]',
    ".col-md-4",
    "La alicuota es requerida.",
    valid
  );
  valid = validarCampoNum(
    'input[name="monto_pagadoIMP_AP_OL"]',
    ".col-md-4",
    "El monto es requerido.",
    valid
  );
  valid = validarCampoNum(
    'input[name="monto_apuestasIMP_AP_OL"]',
    ".col-md-4",
    "El monto es requerido",
    valid
  );
  valid = validarCampoNum(
    'input[name="impuesto_determinadoIMP_AP_OL"]',
    ".col-md-4",
    "El monto es requerido.",
    valid
  );

  valid = validarCampo(
    'input[name="fecha_IMP_AP_OL"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  );
  // Fix: IMP_AP_OL selector roto
  valid = validarCampo(
    'input[name="fecha_pago_IMP_AP_OL"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  ); // antes faltaba comilla/corchete
  valid = validarCampo(
    'input[name="fecha_IMP_AP_OLPres"]',
    ".col-md-5",
    "La fecha es requerida",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsIMP_AP_OLContainer input[type="file"][name="uploadIMP_AP_OL[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadIMP_AP_OL[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadIMP_AP_OL");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadIMP_AP_OL[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarIMP_AP_OL/" + id
      : "/documentosContables/guardarIMP_AP_OL";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarIMP_AP_OL({
        page: $("#herramientasPaginacionIMP_AP_OL").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionIMP_AP_OL").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarIMP_AP_OL").modal("hide"), 1000);
      resetFormIMP_AP_OL();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceIMP_AP_OL(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIMP_AP_OL({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarIMP_AP_OL({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasIMP_AP_OL",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaIMP_AP_OL").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaIMP_AP_OL").append(generarFilaIMP_AP_OL(item));
      });

      $("#herramientasPaginacionIMP_AP_OL").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_OL
      );
      $("#herramientasPaginacionIMP_AP_OL").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_OL
      );
    },
    error(err) {
      console.error("Error cargando IMP_AP_OL:", err);
    },
  });
}

function generarFilaIMP_AP_OL(IMP_AP_OL, controlador) {
  const fila = $("<tr>").attr("id", IMP_AP_OL.id_registroIMP_AP_OL);

  const fecha = convertirMesAno(IMP_AP_OL.fecha_IMP_AP_OL) || "-";
  const pres = IMP_AP_OL.fecha_presentacion || "-";
  const casino = IMP_AP_OL.casino || "-";
  const qna = IMP_AP_OL.qna + "°" || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").html(pres))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-2").html(qna));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-IMP_AP_OL")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", IMP_AP_OL.id_registroIMP_AP_OL)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteIMP_AP_OL")
    .attr("id", IMP_AP_OL.id_registroIMP_AP_OL)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO IMPUESTO A APUESTAS ONLINE")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      IMP_AP_OL.valido,
      "imp_ap_ol",
      IMP_AP_OL.id_registroIMP_AP_OL
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteIMP_AP_OL", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarIMP_AP_OL").attr("data-id", id);
  $("#modalEliminarIMP_AP_OL").modal("show");
});

$("#btn-eliminarIMP_AP_OL").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarIMP_AP_OL/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarIMP_AP_OL").modal("hide");
        cargarIMP_AP_OL({
          page: $("#herramientasPaginacionIMP_AP_OL").getCurrentPage(),
          perPage: $("#herramientasPaginacionIMP_AP_OL").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarIMP_AP_OLExcel").on("click", function (e) {
  $("#collapseDescargarIMP_AP_OL .has-error").removeClass("has-error");
  $("#collapseDescargarIMP_AP_OL .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarIMP_AP_OLXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarIMP_AP_OLXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarIMP_AP_OLCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarIMP_AP_OL .has-error").removeClass("has-error");
  $("#collapseDescargarIMP_AP_OL .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIMP_AP_OLCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//IMPUESTOS A APUESTAS ONLINE IMP_AP_MTM

$(function () {
  instalarNumeroFlexibleAR(
    [
      "#monto_pagadoIMP_AP_MTM",
      "#monto_apuestasIMP_AP_MTM",
      "#impuesto_determinadoIMP_AP_MTM",
    ].join(", "),
    { decimales: 2 }
  );
  instalarNumeroFlexibleAR("#alicuotaIMP_AP_MTM", { decimales: 5 });

  instalarAutoImpuestoAR({
    base: "#monto_apuestasIMP_AP_MTM",
    alicuota: "#alicuotaIMP_AP_MTM",
    impuesto: "#impuesto_determinadoIMP_AP_MTM",
    decImp: 2,
    aliEsPorcentaje: 1,
    esIndirecto: true,
  });

  $("#casinoIMP_AP_MTM").on("change", function () {
    var casinoId = $(this).val();
    if (!casinoId || $("#IMP_AP_MTM_modo").val() !== "create") return;
    $.getJSON("/documentosContables/ultimasAlicuotasIMP_AP_MTM", { casino_id: casinoId }, function (data) {
      if (data.alicuota == null) return;
      var n = parseFloat(data.alicuota) || 0;
      var decs = $("#alicuotaIMP_AP_MTM").data("_numflex_dec") || 5;
      $("#alicuotaIMP_AP_MTM").val(formatoAR(n, decs)).data("num", n).trigger("num:changed", [n]);
    });
  });
});

function cargarArchivosIMP_AP_MTMLista(id, target) {
  var $list = $(targetArchivosDoc("IMP_AP_MTM", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosIMP_AP_MTM/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/IMP_AP_MTM/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="IMP_AP_MTM"  class="btn btn-sm btn-danger btn-del-archivo-IMP_AP_MTM" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-IMP_AP_MTM", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalIMP_AP_MTMEditar(id) {
  resetFormIMP_AP_MTM();
  $("#IMP_AP_MTM_modo").val("edit");
  $("#id_registroIMP_AP_MTM").val(id);
  $("#modalCargarIMP_AP_MTM .modal-title").text(
    "| EDITAR REGISTRO DE IMPUESTO A APUESTAS MTM"
  );
  $("#guardarRegistroIMP_AP_MTM").text("ACTUALIZAR");
  $("#modalCargarIMP_AP_MTM").modal("show");

  $.getJSON("/documentosContables/llenarIMP_AP_MTMEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);
    var ymd = String(d.fecha_pres || "").slice(0, 10);
    var ymdp = String(d.fecha_pago || "").slice(0, 10);

    $('#fechaIMP_AP_MTM input[name="fecha_IMP_AP_MTM"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $('#fechaIMP_AP_MTMPres input[name="fecha_IMP_AP_MTMPres"]')
      .val(ymd)
      .trigger("input")
      .trigger("change");
    $('input[name="fecha_pago_IMP_AP_MTM"]')
      .val(ymdp)
      .trigger("input")
      .trigger("change");

    $("#casinoIMP_AP_MTM").val(d.casino).trigger("change");

    $('select[name="qnaIMP_AP_MTM"]')
      .val(d.qna ?? "")
      .trigger("change");
    $('input[name="monto_pagadoIMP_AP_MTM"]').val(
      formatoAR(d.monto_pagado ?? "")
    );
    $('input[name="monto_apuestasIMP_AP_MTM"]').val(
      formatoAR(d.monto_apuestas ?? "")
    );
    $('input[name="alicuotaIMP_AP_MTM"]').val(formatoAR(d.alicuota ?? ""));
    $('input[name="impuesto_determinadoIMP_AP_MTM"]').val(
      formatoAR(d.impuesto_determinado ?? "")
    );
    $('input[name="cantMTM_IMP_AP_MTM"]').val(formatoAR(d.cant_mtm ?? ""));
    $('[name="obsIMP_AP_MTM"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[IMP_AP_MTM] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormIMP_AP_MTM() {
  var $f = $("#formNuevoRegistroIMP_AP_MTM");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameIMP_AP_MTM").val("");
    $("#uploadIMP_AP_MTM").val("");
  $("#uploadsIMP_AP_MTMContainer").empty();
  $("#uploadsIMP_AP_MTMTable tbody").empty();
  $("#uploadsIMP_AP_MTMWrap").hide();
  $("#fileNameIMP_AP_MTM").val("");
}

function abrirModalIMP_AP_MTMCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormIMP_AP_MTM();
  $("#IMP_AP_MTM_modo").val("create");
  $("#id_registroIMP_AP_MTM").val("");
  $("#modalCargarIMP_AP_MTM .modal-title").text(
    "| NUEVO REGISTRO DE IMPUESTO APUESTAS MTM"
  );
  $("#guardarRegistroIMP_AP_MTM").text("GENERAR");
  $("#modalCargarIMP_AP_MTM").modal("show");
}

$(document).on("click", "#IMP_AP_MTM_nuevo", function () {
  abrirModalIMP_AP_MTMCrear();
});

$(document).on("click", ".btn-edit-IMP_AP_MTM", function () {
  var id = $(this).data("id");
  abrirModalIMP_AP_MTMEditar(id);
});

$(document).on("click", "#guardarRegistroIMP_AP_MTM", function (e) {
  var $form = $("#formNuevoRegistroIMP_AP_MTM");
  let valid = true;
  var id = $("#id_registroIMP_AP_MTM").val() || "";
  var modo = $("#IMP_AP_MTM_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    'select[name="casinoIMP_AP_MTM"]',
    ".col-md-3",
    "El casino es requerido",
    valid
  );
  valid = validarCampoNum(
    'input[name="alicuotaIMP_AP_MTM"]',
    ".col-md-4",
    "La alicuota es requerida.",
    valid
  );
  valid = validarCampoNum(
    'input[name="monto_pagadoIMP_AP_MTM"]',
    ".col-md-4",
    "El monto es requerido.",
    valid
  );
  valid = validarCampoNum(
    'input[name="cantMTM_IMP_AP_MTM"]',
    ".col-md-4",
    "El monto es requerido",
    valid
  );
  valid = validarCampoNum(
    'input[name="monto_apuestasIMP_AP_MTM"]',
    ".col-md-4",
    "El monto es requerido",
    valid
  );
  valid = validarCampoNum(
    'input[name="impuesto_determinadoIMP_AP_MTM"]',
    ".col-md-4",
    "El monto es requerido.",
    valid
  );

  valid = validarCampo(
    'input[name="fecha_IMP_AP_MTM"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  );
  // Fix: IMP_AP_MTM selector roto
  valid = validarCampo(
    'input[name="fecha_pago_IMP_AP_MTM"]',
    ".col-md-4",
    "La fecha es requerida",
    valid
  ); // antes faltaba comilla/corchete
  valid = validarCampo(
    'input[name="fecha_IMP_AP_MTMPres"]',
    ".col-md-5",
    "La fecha es requerida",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsIMP_AP_MTMContainer input[type="file"][name="uploadIMP_AP_MTM[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadIMP_AP_MTM[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadIMP_AP_MTM");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadIMP_AP_MTM[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarIMP_AP_MTM/" + id
      : "/documentosContables/guardarIMP_AP_MTM";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarIMP_AP_MTM({
        page: $("#herramientasPaginacionIMP_AP_MTM").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionIMP_AP_MTM").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarIMP_AP_MTM").modal("hide"), 1000);
      resetFormIMP_AP_MTM();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceIMP_AP_MTM(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarIMP_AP_MTM({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarIMP_AP_MTM({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasIMP_AP_MTM",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaIMP_AP_MTM").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaIMP_AP_MTM").append(generarFilaIMP_AP_MTM(item));
      });

      $("#herramientasPaginacionIMP_AP_MTM").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_MTM
      );
      $("#herramientasPaginacionIMP_AP_MTM").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceIMP_AP_MTM
      );
    },
    error(err) {
      console.error("Error cargando IMP_AP_MTM:", err);
    },
  });
}

function generarFilaIMP_AP_MTM(IMP_AP_MTM, controlador) {
  const fila = $("<tr>").attr("id", IMP_AP_MTM.id_registroIMP_AP_MTM);

  const fecha = convertirMesAno(IMP_AP_MTM.fecha_IMP_AP_MTM) || "-";
  const pres = IMP_AP_MTM.fecha_presentacion || "-";
  const casino = IMP_AP_MTM.casino || "-";
  const qna = IMP_AP_MTM.qna + "°" || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").html(pres))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-2").html(qna));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-IMP_AP_MTM")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", IMP_AP_MTM.id_registroIMP_AP_MTM)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteIMP_AP_MTM")
    .attr("id", IMP_AP_MTM.id_registroIMP_AP_MTM)
    .attr("title", "ELIMINAR REGISTRO IMPUESTO A APUESTAS MTM")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      IMP_AP_MTM.valido,
      "imp_ap_mtm",
      IMP_AP_MTM.id_registroIMP_AP_MTM
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteIMP_AP_MTM", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarIMP_AP_MTM").attr("data-id", id);
  $("#modalEliminarIMP_AP_MTM").modal("show");
});

$("#btn-eliminarIMP_AP_MTM").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarIMP_AP_MTM/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarIMP_AP_MTM").modal("hide");
        cargarIMP_AP_MTM({
          page: $("#herramientasPaginacionIMP_AP_MTM").getCurrentPage(),
          perPage: $("#herramientasPaginacionIMP_AP_MTM").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarIMP_AP_MTMExcel").on("click", function (e) {
  $("#collapseDescargarIMP_AP_MTM .has-error").removeClass("has-error");
  $("#collapseDescargarIMP_AP_MTM .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarIMP_AP_MTMXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarIMP_AP_MTMXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarIMP_AP_MTMCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarIMP_AP_MTM .has-error").removeClass("has-error");
  $("#collapseDescargarIMP_AP_MTM .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarIMP_AP_MTMCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//IMPUESTOS A PAGOS MAYORES DE MESAS DE PAÑO

instalarNumeroFlexibleAR("#importe_pesos_PagosMayoresMesas", {
  decimales: 2,
  permitirNegativos: true,
});
instalarNumeroFlexibleAR("#importe_dolares_PagosMayoresMesas", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosPagosMayoresMesasLista(id, target) {
  var $list = $(targetArchivosDoc("PagosMayoresMesas", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPagosMayoresMesas/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/PagosMayoresMesas/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="PagosMayoresMesas"  class="btn btn-sm btn-danger btn-del-archivo-PagosMayoresMesas" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-PagosMayoresMesas", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalPagosMayoresMesasEditar(id) {
  resetFormPagosMayoresMesas();
  $("#PagosMayoresMesas_modo").val("edit");
  $("#id_registroPagosMayoresMesas").val(id);
  $("#modalCargarPagosMayoresMesas .modal-title").text(
    "| EDITAR REGISTRO DE PagosMayoresMesas"
  );
  $("#guardarRegistroPagosMayoresMesas").text("ACTUALIZAR");
  $("#modalCargarPagosMayoresMesas").modal("show");

  $.getJSON(
    "/documentosContables/llenarPagosMayoresMesasEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      $('#fechaPagosMayoresMesas input[name="fecha_PagosMayoresMesas"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoPagosMayoresMesas").val(d.casino).trigger("change");
      $('input[name="cant_pagos_PagosMayoresMesas"]').val(d.cant_pagos ?? "");
      $('input[name="importe_pesos_PagosMayoresMesas"]').val(
        formatoAR(d.importe_pesos ?? "")
      );
      $('input[name="importe_dolares_PagosMayoresMesas"]').val(
        formatoAR(d.importe_usd ?? "")
      );
    }
  ).fail(function (xhr) {
    console.error("[PagosMayoresMesas] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormPagosMayoresMesas() {
  var $f = $("#formNuevoRegistroPagosMayoresMesas");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNamePagosMayoresMesas").val("");
    $("#uploadPagosMayoresMesas").val("");
  $("#uploadsPagosMayoresMesasContainer").empty();
  $("#uploadsPagosMayoresMesasTable tbody").empty();
  $("#uploadsPagosMayoresMesasWrap").hide();
  $("#fileNamePagosMayoresMesas").val("");
}

function abrirModalPagosMayoresMesasCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormPagosMayoresMesas();
  $("#PagosMayoresMesas_modo").val("create");
  $("#id_registroPagosMayoresMesas").val("");
  $("#modalCargarPagosMayoresMesas .modal-title").text(
    "| NUEVO REGISTRO DE PagosMayoresMesas"
  );
  $("#guardarRegistroPagosMayoresMesas").text("GENERAR");
  $("#modalCargarPagosMayoresMesas").modal("show");
}

$(document).on("click", "#PagosMayoresMesas_nuevo", function () {
  abrirModalPagosMayoresMesasCrear();
});

$(document).on("click", ".btn-edit-PagosMayoresMesas", function () {
  var id = $(this).data("id");
  abrirModalPagosMayoresMesasEditar(id);
});

$(document).on("click", "#guardarRegistroPagosMayoresMesas", function (e) {
  var $form = $("#formNuevoRegistroPagosMayoresMesas");
  let valid = true;
  var id = $("#id_registroPagosMayoresMesas").val() || "";
  var modo = $("#PagosMayoresMesas_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoPagosMayoresMesas']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_PagosMayoresMesas']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='importe_pesos_PagosMayoresMesas']",
    ".col-md-4",
    "El importe en pesos es requerido.",
    valid
  );

  valid = validarCampoNum(
    "input[name='importe_dolares_PagosMayoresMesas']",
    ".col-md-4",
    "El importe en dolares es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsPagosMayoresMesasContainer input[type="file"][name="uploadPagosMayoresMesas[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadPagosMayoresMesas[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadPagosMayoresMesas");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadPagosMayoresMesas[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarPagosMayoresMesas/" + id
      : "/documentosContables/guardarPagosMayoresMesas";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarPagosMayoresMesas({
        page: $("#herramientasPaginacionPagosMayoresMesas").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionPagosMayoresMesas").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarPagosMayoresMesas").modal("hide"), 1000);
      resetFormPagosMayoresMesas();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndicePagosMayoresMesas(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPagosMayoresMesas({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPagosMayoresMesas({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasPagosMayoresMesas",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPagosMayoresMesas").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaPagosMayoresMesas").append(
          generarFilaPagosMayoresMesas(item)
        );
      });

      $("#herramientasPaginacionPagosMayoresMesas").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePagosMayoresMesas
      );
      $("#herramientasPaginacionPagosMayoresMesas").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePagosMayoresMesas
      );
    },
    error(err) {
      console.error("Error cargando PagosMayoresMesas:", err);
    },
  });
}

function generarFilaPagosMayoresMesas(PagosMayoresMesas, controlador) {
  const fila = $("<tr>").attr(
    "id",
    PagosMayoresMesas.id_registroPagosMayoresMesas
  );
  const fecha =
    convertirMesAno(PagosMayoresMesas.fecha_PagosMayoresMesas) || "-";
  const casino = PagosMayoresMesas.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-4").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-4 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-PagosMayoresMesas")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", PagosMayoresMesas.id_registroPagosMayoresMesas)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deletePagosMayoresMesas")
    .attr("id", PagosMayoresMesas.id_registroPagosMayoresMesas)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR PAGO MAYOR DE MESA DE PAÑO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deletePagosMayoresMesas", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarPagosMayoresMesas").attr("data-id", id);
  $("#modalEliminarPagosMayoresMesas").modal("show");
});

$("#btn-eliminarPagosMayoresMesas").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarPagosMayoresMesas/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarPagosMayoresMesas").modal("hide");
        cargarPagosMayoresMesas({
          page: $("#herramientasPaginacionPagosMayoresMesas").getCurrentPage(),
          perPage: $("#herramientasPaginacionPagosMayoresMesas").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarPagosMayoresMesasExcel").on("click", function (e) {
  $("#collapseDescargarPagosMayoresMesas .has-error").removeClass("has-error");
  $("#collapseDescargarPagosMayoresMesas .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarPagosMayoresMesasXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPagosMayoresMesasXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPagosMayoresMesasCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarPagosMayoresMesas .has-error").removeClass("has-error");
  $("#collapseDescargarPagosMayoresMesas .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPagosMayoresMesasCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//IMPUESTOS A DEUDA ESTADO

function cargarArchivosDeudaEstadoLista(id, target) {
  var $list = $(targetArchivosDoc("DeudaEstado", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosDeudaEstado/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/DeudaEstado/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="DeudaEstado"  class="btn btn-sm btn-danger btn-del-archivo-DeudaEstado" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-DeudaEstado", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalDeudaEstadoEditar(id) {
  resetFormDeudaEstado();
  $("#DeudaEstado_modo").val("edit");
  $("#id_registroDeudaEstado").val(id);
  $("#modalCargarDeudaEstado .modal-title").text(
    "| EDITAR REGISTRO DE DEUDA CON EL ESTADO"
  );
  $("#guardarRegistroDeudaEstado").text("ACTUALIZAR");
  $("#modalCargarDeudaEstado").modal("show");

  $.getJSON("/documentosContables/llenarDeudaEstadoEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);

    $('#fechaDeudaEstado input[name="fecha_DeudaEstado"]')
      .val(ym)
      .trigger("input")
      .trigger("change");

    $("#casinoDeudaEstado").val(d.casino).trigger("change");

    $("#regIncumDeudaEstado")
      .val(String(d.registra_incumplimiento ?? ""))
      .trigger("change");

    $('[name="incumDeudaEstado"]').val(d.incumplimiento ?? "");

    $('#fechaDeudaEstadoPres input[name="fecha_DeudaEstadoPres"]')
      .val(d.fecha_consulta || "")
      .trigger("input")
      .trigger("change");

    $('[name="obsDeudaEstado"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[DeudaEstado] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormDeudaEstado() {
  var $f = $("#formNuevoRegistroDeudaEstado");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameDeudaEstado").val("");
    $("#uploadDeudaEstado").val("");
  $("#uploadsDeudaEstadoContainer").empty();
  $("#uploadsDeudaEstadoTable tbody").empty();
  $("#uploadsDeudaEstadoWrap").hide();
  $("#fileNameDeudaEstado").val("");
}

function abrirModalDeudaEstadoCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormDeudaEstado();
  $("#DeudaEstado_modo").val("create");
  $("#id_registroDeudaEstado").val("");
  $("#modalCargarDeudaEstado .modal-title").text(
    "| NUEVO REGISTRO DE DeudaEstado"
  );
  $("#guardarRegistroDeudaEstado").text("GENERAR");
  $("#modalCargarDeudaEstado").modal("show");
}

$(document).on("click", "#DeudaEstado_nuevo", function () {
  abrirModalDeudaEstadoCrear();
});

$(document).on("click", ".btn-edit-DeudaEstado", function () {
  var id = $(this).data("id");
  abrirModalDeudaEstadoEditar(id);
});

$(document).on("click", "#guardarRegistroDeudaEstado", function (e) {
  var $form = $("#formNuevoRegistroDeudaEstado");
  let valid = true;
  var id = $("#id_registroDeudaEstado").val() || "";
  var modo = $("#DeudaEstado_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    'select[name="casinoDeudaEstado"]',
    ".col-md-3",
    "El  es requerido.",
    valid
  );

  valid = validarCampo(
    'input[name="fecha_DeudaEstadoPres"]',
    ".col-md-5",
    "La  es requerida.",
    valid
  );

  valid = validarCampo(
    'input[name="fecha_DeudaEstado"]',
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsDeudaEstadoContainer input[type="file"][name="uploadDeudaEstado[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadDeudaEstado[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadDeudaEstado");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadDeudaEstado[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarDeudaEstado/" + id
      : "/documentosContables/guardarDeudaEstado";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarDeudaEstado({
        page: $("#herramientasPaginacionDeudaEstado").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionDeudaEstado").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarDeudaEstado").modal("hide"), 1000);
      resetFormDeudaEstado();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceDeudaEstado(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDeudaEstado({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarDeudaEstado({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasDeudaEstado",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaDeudaEstado").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaDeudaEstado").append(generarFilaDeudaEstado(item));
      });

      $("#herramientasPaginacionDeudaEstado").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDeudaEstado
      );
      $("#herramientasPaginacionDeudaEstado").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDeudaEstado
      );
    },
    error(err) {
      console.error("Error cargando DeudaEstado:", err);
    },
  });
}

function generarFilaDeudaEstado(DeudaEstado, controlador) {
  const fila = $("<tr>").attr("id", DeudaEstado.id_registroDeudaEstado);

  const fecha = convertirMesAno(DeudaEstado.fecha_DeudaEstado) || "-";
  const pres = DeudaEstado.fecha_presentacion || "-";
  const casino = DeudaEstado.casino || "-";
  const incum = DeudaEstado.incumplimiento == 1 ? "Si" : "No";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-2").html(pres))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-3").html(incum));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-DeudaEstado")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", DeudaEstado.id_registroDeudaEstado)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteDeudaEstado")
    .attr("id", DeudaEstado.id_registroDeudaEstado)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR DEUDA CONSOLIDADA CON EL ESTADO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      DeudaEstado.valido,
      "deuda_estado",
      DeudaEstado.id_registroDeudaEstado
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteDeudaEstado", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarDeudaEstado").attr("data-id", id);
  $("#modalEliminarDeudaEstado").modal("show");
});

$("#btn-eliminarDeudaEstado").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarDeudaEstado/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarDeudaEstado").modal("hide");
        cargarDeudaEstado({
          page: $("#herramientasPaginacionDeudaEstado").getCurrentPage(),
          perPage: $("#herramientasPaginacionDeudaEstado").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarDeudaEstadoExcel").on("click", function (e) {
  $("#collapseDescargarDeudaEstado .has-error").removeClass("has-error");
  $("#collapseDescargarDeudaEstado .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarDeudaEstadoXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarDeudaEstadoXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarDeudaEstadoCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarDeudaEstado .has-error").removeClass("has-error");
  $("#collapseDescargarDeudaEstado .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDeudaEstadoCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//REPORTE DE OPERACIONES Y LAVADO DE ACTIVOS

function cargarArchivosReporteYLavadoLista(id, target) {
  var $list = $(targetArchivosDoc("ReporteYLavado", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosReporteYLavado/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/ReporteYLavado/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="ReporteYLavado"  class="btn btn-sm btn-danger btn-del-archivo-ReporteYLavado" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-ReporteYLavado", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalReporteYLavadoEditar(id) {
  resetFormReporteYLavado();
  $("#ReporteYLavado_modo").val("edit");
  $("#id_registroReporteYLavado").val(id);
  $("#modalCargarReporteYLavado .modal-title").text(
    "| EDITAR REGISTRO DE REPORTES DE OPERACIONES - LAVADO DE ACTIVOS"
  );
  $("#guardarRegistroReporteYLavado").text("ACTUALIZAR");
  $("#modalCargarReporteYLavado").modal("show");

  $.getJSON(
    "/documentosContables/llenarReporteYLavadoEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      $('#fechaReporteYLavado input[name="fecha_ReporteYLavado"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoReporteYLavado").val(d.casino).trigger("change");
      $('input[name="reporte_sistematico_ReporteYLavado"]').val(
        d.reporte_sistematico ?? ""
      );
      $('input[name="reporte_operaciones_ReporteYLavado"]').val(
        d.reporte_operaciones ?? ""
      );
    }
  ).fail(function (xhr) {
    console.error("[ReporteYLavado] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro Reporte y Lavado.");
  });
}

function resetFormReporteYLavado() {
  var $f = $("#formNuevoRegistroReporteYLavado");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameReporteYLavado").val("");
    $("#uploadReporteYLavado").val("");
  $("#uploadsReporteYLavadoContainer").empty();
  $("#uploadsReporteYLavadoTable tbody").empty();
  $("#uploadsReporteYLavadoWrap").hide();
  $("#fileNameReporteYLavado").val("");
}

function abrirModalReporteYLavadoCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormReporteYLavado();
  $("#ReporteYLavado_modo").val("create");
  $("#id_registroReporteYLavado").val("");
  $("#modalCargarReporteYLavado .modal-title").text(
    "| NUEVO REGISTRO DE REPORTE DE OPERACIONES - LAVADO ACTIVOS"
  );
  $("#guardarRegistroReporteYLavado").text("GENERAR");
  $("#modalCargarReporteYLavado").modal("show");
}

$(document).on("click", "#ReporteYLavado_nuevo", function () {
  abrirModalReporteYLavadoCrear();
});

$(document).on("click", ".btn-edit-ReporteYLavado", function () {
  var id = $(this).data("id");
  abrirModalReporteYLavadoEditar(id);
});

$(document).on("click", "#guardarRegistroReporteYLavado", function (e) {
  var $form = $("#formNuevoRegistroReporteYLavado");
  let valid = true;
  var id = $("#id_registroReporteYLavado").val() || "";
  var modo = $("#ReporteYLavado_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoReporteYLavado']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_ReporteYLavado']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='reporte_sistematico_ReporteYLavado']",
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='reporte_operaciones_ReporteYLavado']",
    ".col-md-7",
    "La cantidad es requerida.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let val = p.value;
    if (["reporte_sistematico_ReporteYLavado", "reporte_operaciones_ReporteYLavado"].includes(p.name)) {
      val = parseNumeroFlexible(val)?.num ?? val;
    }
    fd.append(p.name, val);
  });

  $(
    '#uploadsReporteYLavadoContainer input[type="file"][name="uploadReporteYLavado[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadReporteYLavado[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadReporteYLavado");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadReporteYLavado[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarReporteYLavado/" + id
      : "/documentosContables/guardarReporteYLavado";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarReporteYLavado({
        page: $("#herramientasPaginacionReporteYLavado").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionReporteYLavado").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarReporteYLavado").modal("hide"), 1000);
      resetFormReporteYLavado();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

// -----------------------------------------------------------------------------
// LOGICA DE FILTROS GLOBALES Y NAVEGACIÓN
// -----------------------------------------------------------------------------

$(document).ready(function () {
  // Búsqueda inicial
  // Buscar al presionar el botón de GLobal
  $("#btn-buscar-global").on("click", function (e) {
    e.preventDefault();
    buscarGlobal();
  });

  // Buscar al presionar Enter en los filtros globales
  $(
    "#filtro_global_casino, #filtro_global_desde_input, #filtro_global_hasta_input"
  ).on("keypress", function (e) {
    if (e.which == 13) {
      buscarGlobal();
    }
  });

  function validarBotonVerValidados() {
    var casino = $("#filtro_global_casino").val() || "";
    var desde = $("#filtro_global_desde_input").val() || "";
    var hasta = $("#filtro_global_hasta_input").val() || "";

    if (casino && desde && hasta) {
      $("#btn-ver-validados").prop("disabled", false);
      $("#btn-control-documentos").prop("disabled", false);
    } else {
      $("#btn-ver-validados").prop("disabled", true);
      $("#btn-control-documentos").prop("disabled", true);
    }
  }

  // AL cambiar cualquier filtro global, disparar búsqueda
  $("#filtro_global_casino").on("change", function () {
    validarBotonVerValidados();
    buscarGlobal();
  });
  $("#filtro_global_desde").on("changeDate", function () {
    validarBotonVerValidados();
    buscarGlobal();
  });
  $("#filtro_global_hasta").on("changeDate", function () {
    validarBotonVerValidados();
    buscarGlobal();
  });

  $("#btn-eliminar-filtros").on("click", function (e) {
    e.preventDefault();
    $("#filtro_global_casino").val("");
    $("#filtro_global_desde_input").val("");
    $("#filtro_global_hasta_input").val("");
    
    // Some datepickers require calling datetimepicker('update', '') or clearing the widget
    $("#filtro_global_desde").datetimepicker('update', '');
    $("#filtro_global_hasta").datetimepicker('update', '');

    // el boton tambien devuelve las tablas a su orden por defecto
    if (typeof limpiarOrdenDocs === "function") limpiarOrdenDocs();

    validarBotonVerValidados();
    buscarGlobal();
  });

  // Inicializar: simular click en la primera pestaña
  setTimeout(function () {
    $("[data-js-tabs] a").first().click();
  }, 100);

  // Initializaciones de datepickers globales
  $("#filtro_global_desde").datetimepicker({
    language: "es",
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: "yyyy-mm",
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
    container: $("main section"),
  });

  $("#filtro_global_hasta").datetimepicker({
    language: "es",
    todayBtn: 1,
    autoclose: 1,
    todayHighlight: 1,
    format: "yyyy-mm",
    pickerPosition: "bottom-left",
    startView: 3,
    minView: 3,
    ignoreReadonly: true,
    container: $("main section"),
  });

  $("#btn-ver-validados").on("click", function (e) {
    e.preventDefault();
    var casino = $("#filtro_global_casino").val();
    var desde = $("#filtro_global_desde_input").val();
    var hasta = $("#filtro_global_hasta_input").val();

    if (!casino || !desde || !hasta) return;

    $("[data-js-tabs] a").removeClass("active");
    $('[id^="pant_"]').attr("hidden", true);

    // Show the new tab container if it was hidden, and activate the tab manually
    $("#div_tab_validados").removeAttr("hidden");
    $("[data-js-tab='#pant_validados']").addClass("active");
    $("#pant_validados").removeAttr("hidden");

    $("#cuerpoTablaValidados").empty().append('<tr><td colspan="4" class="text-center">Cargando...</td></tr>');

    $.ajax({
      url: "documentosContables/obtenerDocumentosValidados",
      method: "GET",
      data: {
        id_casino: casino,
        desde: desde,
        hasta: hasta
      },
      success: function (res) {
        $("#cuerpoTablaValidados").empty();
        if (res.success && res.data.length > 0) {
          res.data.forEach(function (doc) {
            var icon = doc.valido == 1 
              ? '<i class="fa fa-check icono-validado" data-toggle="tooltip" title="Validado"></i>' 
              : '<i class="fa fa-times icono-no-validado" data-toggle="tooltip" title="No Validado"></i>';
            
            var row = $('<tr>')
              .append($('<td>').addClass('col-xs-4').text(doc.documento))
              .append($('<td>').addClass('col-xs-3').text(doc.mes))
              .append($('<td>').addClass('col-xs-3').text(doc.anio))
              .append($('<td>').addClass('col-xs-2 text-center').html(icon));
            
            $("#cuerpoTablaValidados").append(row);
          });
          $('[data-toggle="tooltip"]')// .tooltip();
        } else {
          $("#cuerpoTablaValidados").append('<tr><td colspan="4" class="text-center">No se encontraron documentos en este período.</td></tr>');
        }
      },
      error: function () {
        $("#cuerpoTablaValidados").empty().append('<tr><td colspan="4" class="text-center text-danger">Error al cargar los documentos validados.</td></tr>');
      }
    });

  });

  // CONTROL DE DOCUMENTOS: matriz documento x mes (válido / no válido / no subido)
  var filtrosControlDocumentos = null;

  $("#btn-control-documentos").on("click", function (e) {
    e.preventDefault();
    var casino = $("#filtro_global_casino").val();
    var desde = $("#filtro_global_desde_input").val();
    var hasta = $("#filtro_global_hasta_input").val();

    if (!casino || !desde || !hasta) return;

    $("[data-js-tabs] a").removeClass("active");
    $('[id^="pant_"]').attr("hidden", true);

    $("#div_tab_control_documentos").removeAttr("hidden");
    $("[data-js-tab='#pant_control_documentos']").addClass("active");
    $("#pant_control_documentos").removeAttr("hidden");

    var casinoNombre = $("#filtro_global_casino option:selected").text();
    $("#subtituloControlDocumentos").text(casinoNombre + " | " + desde + " a " + hasta);
    $("#btn-descargarControlDocumentos").prop("disabled", true);
    filtrosControlDocumentos = null;

    $("#cabeceraTablaControlDocumentos").empty();
    $("#cuerpoTablaControlDocumentos").empty().append('<tr><td class="text-center">Cargando...</td></tr>');

    $.ajax({
      url: "documentosContables/obtenerControlDocumentos",
      method: "GET",
      data: {
        id_casino: casino,
        desde: desde,
        hasta: hasta
      },
      success: function (res) {
        $("#cabeceraTablaControlDocumentos").empty();
        $("#cuerpoTablaControlDocumentos").empty();
        if (!res.success || !res.filas || res.filas.length === 0) {
          $("#cuerpoTablaControlDocumentos").append('<tr><td class="text-center">No hay datos para este período.</td></tr>');
          return;
        }

        var estiloPrimeraCol = "position: sticky; left: 0; background-color: #fff; z-index: 1; text-align: left; min-width: 220px;";

        var trHead = $("<tr>").append(
          $("<th>").attr("style", estiloPrimeraCol + " z-index: 2;").text("DOCUMENTO")
        );
        res.meses.forEach(function (mes) {
          trHead.append($("<th>").addClass("text-center").attr("style", "min-width: 110px;").text(mes.label));
        });
        $("#cabeceraTablaControlDocumentos").append(trHead);

        function iconoControlDoc(c) {
          if (c.estado === "valido") {
            return '<i class="fa fa-check icono-validado" data-toggle="tooltip" title="Válido (' + c.validos + '/' + c.total + ')"></i>';
          } else if (c.estado === "no_valido") {
            return '<i class="fa fa-times icono-no-validado" data-toggle="tooltip" title="No válido (' + c.validos + '/' + c.total + ' validados)"></i>';
          }
          return '<i class="fa fa-minus" style="color: #999;" data-toggle="tooltip" title="No subido"></i>';
        }

        function iconosDetalleControl(items) {
          // un ícono por registro (ej. cada anticipo del mes): tilde si validado, cruz si no.
          return items.map(function (it) {
            return it.valido == 1
              ? '<i class="fa fa-check icono-validado" style="margin:0 3px;" data-toggle="tooltip" title="Anticipo ' + it.nro + ': validado"></i>'
              : '<i class="fa fa-times icono-no-validado" style="margin:0 3px;" data-toggle="tooltip" title="Anticipo ' + it.nro + ': no validado"></i>';
          }).join('');
        }

        res.filas.forEach(function (fila) {
          var tr = $("<tr>").append(
            $("<td>").attr("style", estiloPrimeraCol).text(fila.documento)
          );
          res.meses.forEach(function (mes) {
            var celda = fila.celdas[mes.key];
            var td = $("<td>").addClass("text-center");
            if (fila.detalle && celda.items && celda.items.length) {
              td.html(iconosDetalleControl(celda.items));
            } else {
              td.html(iconoControlDoc(celda));
            }
            tr.append(td);
          });
          $("#cuerpoTablaControlDocumentos").append(tr);
        });

        $("#tablaControlDocumentos [data-toggle='tooltip']")// .tooltip();

        filtrosControlDocumentos = { id_casino: casino, desde: desde, hasta: hasta };
        $("#btn-descargarControlDocumentos").prop("disabled", false);
      },
      error: function () {
        $("#cabeceraTablaControlDocumentos").empty();
        $("#cuerpoTablaControlDocumentos").empty().append('<tr><td class="text-center text-danger">Error al cargar el control de documentos.</td></tr>');
      }
    });
  });

  $("#btn-descargarControlDocumentos").on("click", function (e) {
    e.preventDefault();
    if (!filtrosControlDocumentos) return;
    window.location.href = "/documentosContables/descargarControlDocumentosXlsx?" + $.param(filtrosControlDocumentos);
  });
});

function filtrosGlobalDescarga() {
  var casino = $("#filtro_global_casino").val() || "";
  var desde = $("#filtro_global_desde_input").val() || "";
  var hasta = $("#filtro_global_hasta_input").val() || "";
  return { casino: casino || "4", desde: desde, hasta: hasta };
}

function buscarGlobal() {
  // Ver cuál es la pestaña activa
  var activeTab = $("[data-js-tabs] a.active").attr("data-js-tab");
  if (!activeTab) {
    activeTab = $("[data-js-tabs] a").first().attr("data-js-tab");
  }
  buscarPestana(activeTab);
}

function buscarPestana(tabId, preservePage) {
  preservePage = preservePage || false;
  if (!tabId) return;

  // Obtener valores globales (convertir vacíos a undefined para que el backend use defaults)
  var casino = $("#filtro_global_casino").val() || undefined;
  var desde = $("#filtro_global_desde_input").val() || undefined;
  var hasta = $("#filtro_global_hasta_input").val() || undefined;

  // Mapeo de Tab ID -> Función de búsqueda
  // Nota: Las funciones cargarX generalmente aceptan un objeto con parametros.
  // Debemos asegurar que todas las funciones cargarX (cargarIva, cargarDrei, etc)
  // existan y acepten los parametros.

  // Quitamos 'pant_' para obtener el nombre clave

  function getPg(funcName) {
    if (!preservePage) return 1;
    // We can infer paginator ID from the function name (e.g. cargarIva -> herramientasPaginacionIVA)
    // Actually, it is easier to find the active paginator inside the tab
    var $pag = $(tabId).find(".zonaPaginacion");
    if ($pag.length > 0 && typeof $pag.getCurrentPage === "function") {
        return $pag.getCurrentPage() || 1;
    }
    return 1;
  }
  
  function getSz(funcName) {
    var $pag = $(tabId).find(".zonaPaginacion");
    if ($pag.length > 0 && typeof $pag.getPageSize === "function") {
        return $pag.getPageSize() || 10;
    }
    return 10;
  }

  var key = tabId.replace("#pant_", "");

  // Casos especiales de nombres
  // Si el ID del tab no coincide exactamente con el nombre de la función, mapeamos aquí

  switch (key) {
    case "publico_casino":
      cargarPublicoCasino({
        page: getPg("cargarPublicoCasino"),
        perPage: getSz("cargarPublicoCasino"),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "iva":
      cargarIva({
        page: getPg(cargarIva),
        perPage: getSz(cargarIva),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "iibb":
      cargariibb({
        page: getPg(cargariibb),
        perPage: getSz(cargariibb),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "drei":
      cargarDREI({
        page: getPg(cargarDREI),
        perPage: getSz(cargarDREI),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "tgi":
      cargarTGI({
        page: getPg(cargarTGI),
        perPage: getSz(cargarTGI),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "imp_ap_mtm":
      cargarIMP_AP_MTM({
        page: getPg(cargarIMP_AP_MTM),
        perPage: getSz(cargarIMP_AP_MTM),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "imp_ap_ol":
      cargarIMP_AP_OL({
        page: getPg(cargarIMP_AP_OL),
        perPage: getSz(cargarIMP_AP_OL),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "ganancias":
      cargarGanancias({
        page: getPg(cargarGanancias),
        perPage: getSz(cargarGanancias),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      cargarGanancias_periodo({
        page: getPg(cargarGanancias_periodo),
        perPage: getSz(cargarGanancias_periodo),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "patentes":
      cargarPatentes({
        page: getPg(cargarPatentes),
        perPage: getSz(cargarPatentes),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "inmobiliario":
      cargarImpInmobiliario({
        page: getPg(cargarImpInmobiliario),
        perPage: getSz(cargarImpInmobiliario),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "deuda":
      cargarDeudaEstado({
        page: getPg(cargarDeudaEstado),
        perPage: getSz(cargarDeudaEstado),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "direct":
      cargarAutDirectores({
        page: getPg(cargarAutDirectores),
        perPage: getSz(cargarAutDirectores),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "premios_mtm":
      cargarPremiosMTM({
        page: getPg(cargarPremiosMTM),
        perPage: getSz(cargarPremiosMTM),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "promoticket":
      cargarPromoTickets({
        page: getPg(cargarPromoTickets),
        perPage: getSz(cargarPromoTickets),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "pozos_acumulados":
      cargarPozosAcumuladosLinkeados({
        page: getPg(cargarPozosAcumuladosLinkeados),
        perPage: getSz(cargarPozosAcumuladosLinkeados),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "jackpots_pagados":
      cargarJackpotsPagados({
        page: getPg(cargarJackpotsPagados),
        perPage: getSz(cargarJackpotsPagados),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "premios_pagados":
      cargarPremiosPagados({
        page: getPg(cargarPremiosPagados),
        perPage: getSz(cargarPremiosPagados),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "pagos_mesas":
      cargarPagosMayoresMesas({
        page: getPg(cargarPagosMayoresMesas),
        perPage: getSz(cargarPagosMayoresMesas),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "registros":
      cargarRegistrosContables({
        page: getPg(cargarRegistrosContables),
        perPage: getSz(cargarRegistrosContables),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "aportes":
      cargarAportesPatronales({
        page: getPg(cargarAportesPatronales),
        perPage: getSz(cargarAportesPatronales),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "rrhh":
      cargarRRHH({
        page: getPg(cargarRRHH),
        perPage: getSz(cargarRRHH),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "oper":
      cargarReporteYLavado({
        page: getPg(cargarReporteYLavado),
        perPage: getSz(cargarReporteYLavado),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "seguros":
      cargarSeguros({
        page: getPg(cargarSeguros),
        perPage: getSz(cargarSeguros),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "contrib_ente":
      cargarContribEnteTuristico({
        page: getPg(cargarContribEnteTuristico),
        perPage: getSz(cargarContribEnteTuristico),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "derecho":
      cargarDerechoAcceso({
        page: getPg(cargarDerechoAcceso),
        perPage: getSz(cargarDerechoAcceso),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    case "estado_contable":
      cargarEstadoContable({
        page: getPg(cargarEstadoContable),
        perPage: getSz(cargarEstadoContable),
        casino: casino,
        desde: desde,
        hasta: hasta,
      });
      break;
    default:
      console.warn("Tab no implementada en búsqueda global:", key);
      break;
  }
}

// -----------------------------------------------------------------------------

function clickIndiceReporteYLavado(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarReporteYLavado({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarReporteYLavado({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasReporteYLavado",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaReporteYLavado").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaReporteYLavado").append(generarFilaReporteYLavado(item));
      });

      $("#herramientasPaginacionReporteYLavado").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceReporteYLavado
      );
      $("#herramientasPaginacionReporteYLavado").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceReporteYLavado
      );
    },
    error(err) {
      console.error("Error cargando ReporteYLavado:", err);
    },
  });
}

function generarFilaReporteYLavado(ReporteYLavado, controlador) {
  const fila = $("<tr>").attr("id", ReporteYLavado.id_registroReporteYLavado);
  const fecha = convertirMesAno(ReporteYLavado.fecha_ReporteYLavado) || "-";
  const casino = ReporteYLavado.casino || "-";
  const sistematico = ReporteYLavado.reporte_sistematico || "-";
  const operaciones = ReporteYLavado.reporte_operaciones || "-";

  fila
    .append($("<td>").addClass("col-xs-2").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-3").text(sistematico))
    .append($("<td>").addClass("col-xs-3").text(operaciones));
  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-ReporteYLavado")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", ReporteYLavado.id_registroReporteYLavado)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteReporteYLavado")
    .attr("id", ReporteYLavado.id_registroReporteYLavado)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      ReporteYLavado.valido,
      "reporte_y_lavado",
      ReporteYLavado.id_registroReporteYLavado
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteReporteYLavado", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarReporteYLavado").attr("data-id", id);
  $("#modalEliminarReporteYLavado").modal("show");
});

$("#btn-eliminarReporteYLavado").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarReporteYLavado/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarReporteYLavado").modal("hide");
        cargarReporteYLavado({
          page: $("#herramientasPaginacionReporteYLavado").getCurrentPage(),
          perPage: $("#herramientasPaginacionReporteYLavado").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarReporteYLavadoCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarReporteYLavado .has-error").removeClass("has-error");
  $("#collapseDescargarReporteYLavado .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarReporteYLavadoCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarReporteYLavadoExcel").on("click", function (e) {
  $("#collapseDescargarReporteYLavado .has-error").removeClass("has-error");
  $("#collapseDescargarReporteYLavado .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarReporteYLavadoXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarReporteYLavadoXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

//REGISTROS CONTABLES

$(function () {
  instalarNumeroFlexibleAR(
    "#mtm_pesos_RegistrosContables, #mtm_usd_RegistrosContables, #mp_pesos_RegistrosContables",
    { decimales: 2, permitirNegativos: true }
  );
  instalarNumeroFlexibleAR(
    "#mp_usd_RegistrosContables, #bingo_RegistrosContables, #jol_RegistrosContables",
    { decimales: 2, permitirNegativos: true }
  );
  instalarNumeroFlexibleAR(
    "#total_RegistrosContables, #total_usd_RegistrosContables",
    { decimales: 2, permitirNegativos: true }
  );

  instalarAutoSumaAR({
    sources: [
      "#mtm_pesos_RegistrosContables",
      "#mp_pesos_RegistrosContables",
      "#bingo_RegistrosContables",
      "#jol_RegistrosContables",
    ],
    target: "#total_RegistrosContables",
    decimales: 2,
  });
  instalarAutoSumaAR({
    sources: ["#mtm_usd_RegistrosContables", "#mp_usd_RegistrosContables"],
    target: "#total_usd_RegistrosContables",
    decimales: 2,
  });
});

function cargarArchivosRegistrosContablesLista(id, target) {
  var $list = $(targetArchivosDoc("RegistrosContables", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosRegistrosContables/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/RegistrosContables/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="RegistrosContables"  class="btn btn-sm btn-danger btn-del-archivo-RegistrosContables" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-RegistrosContables", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalRegistrosContablesEditar(id) {
  resetFormRegistrosContables();
  $("#RegistrosContables_modo").val("edit");
  $("#id_registroRegistrosContables").val(id);
  $("#modalCargarRegistrosContables .modal-title").text(
    "| EDITAR REGISTRO DE REGISTROS CONTABLES"
  );
  $("#guardarRegistroRegistrosContables").text("ACTUALIZAR");
  $("#modalCargarRegistrosContables").modal("show");

  $.getJSON(
    "/documentosContables/llenarRegistrosContablesEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      $('#fechaRegistrosContables input[name="fecha_RegistrosContables"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoRegistrosContables").val(d.casino).trigger("change");

      $('input[name="mtm_pesos_RegistrosContables"]').val(
        formatoAR(d.mtm ?? "")
      );
      $('input[name="mtm_usd_RegistrosContables"]').val(
        formatoAR(d.mtm_usd ?? "")
      );
      $('input[name="mp_pesos_RegistrosContables"]').val(formatoAR(d.mp ?? ""));
      $('input[name="mp_usd_RegistrosContables"]').val(
        formatoAR(d.mp_usd ?? "")
      );
      $('input[name="bingo_RegistrosContables"]').val(formatoAR(d.bingo ?? ""));
      $('input[name="jol_RegistrosContables"]').val(formatoAR(d.jol ?? ""));
      $('input[name="total_RegistrosContables"]').val(formatoAR(d.total ?? ""));
      $('input[name="total_usd_RegistrosContables"]').val(
        formatoAR(d.total_usd ?? "")
      );
    }
  ).fail(function (xhr) {
    console.error(
      "[RegistrosContables] GET FAIL",
      xhr.status,
      xhr.responseText
    );
    alert("No se pudo cargar el registro.");
  });
}

function resetFormRegistrosContables() {
  var $f = $("#formNuevoRegistroRegistrosContables");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameRegistrosContables").val("");
    $("#uploadRegistrosContables").val("");
  $("#uploadsRegistrosContablesContainer").empty();
  $("#uploadsRegistrosContablesTable tbody").empty();
  $("#uploadsRegistrosContablesWrap").hide();
  $("#fileNameRegistrosContables").val("");
}

function abrirModalRegistrosContablesCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormRegistrosContables();
  $("#RegistrosContables_modo").val("create");
  $("#id_registroRegistrosContables").val("");
  $("#modalCargarRegistrosContables .modal-title").text(
    "| NUEVO REGISTRO DE REGISTROS CONTABLES"
  );
  $("#guardarRegistroRegistrosContables").text("GENERAR");
  $("#modalCargarRegistrosContables").modal("show");
}

$(document).on("click", "#RegistrosContables_nuevo", function () {
  abrirModalRegistrosContablesCrear();
});

$(document).on("click", ".btn-edit-RegistrosContables", function () {
  var id = $(this).data("id");
  abrirModalRegistrosContablesEditar(id);
});
$(document).on("click", "#guardarRegistroRegistrosContables", function (e) {
  var $form = $("#formNuevoRegistroRegistrosContables");
  let valid = true;
  var id = $("#id_registroRegistrosContables").val() || "";
  var modo = $("#RegistrosContables_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoRegistrosContables']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_RegistrosContables']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='mp_pesos_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='mp_usd_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='mtm_pesos_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='mtm_usd_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='bingo_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='jol_RegistrosContables']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='total_usd_RegistrosContables']"),
    ".col-md-5",
    "El total es necesario",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='total_RegistrosContables']"),
    ".col-md-5",
    "El total es necesario",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsRegistrosContablesContainer input[type="file"][name="uploadRegistrosContables[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadRegistrosContables[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadRegistrosContables");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadRegistrosContables[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarRegistrosContables/" + id
      : "/documentosContables/guardarRegistrosContables";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarRegistrosContables({
        page: $("#herramientasPaginacionRegistrosContables").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionRegistrosContables").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarRegistrosContables").modal("hide"), 1000);

      resetFormRegistrosContables();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceRegistrosContables(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarRegistrosContables({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarRegistrosContables({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasRegistrosContables",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaRegistrosContables").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaRegistrosContables").append(
          generarFilaRegistrosContables(item)
        );
      });

      $("#herramientasPaginacionRegistrosContables").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRegistrosContables
      );
      $("#herramientasPaginacionRegistrosContables").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRegistrosContables
      );
    },
    error(err) {
      console.error("Error cargando RegistrosContables:", err);
    },
  });
}

function generarFilaRegistrosContables(RegistrosContables, controlador) {
  const fila = $("<tr>").attr(
    "id",
    RegistrosContables.id_registroRegistrosContables
  );
  const fecha =
    convertirMesAno(RegistrosContables.fecha_RegistrosContables) || "-";
  const casino = RegistrosContables.casino || "-";
  const total = "$ " + formatoAR(RegistrosContables.total) || "-";
  const total_usd = "USD " + formatoAR(RegistrosContables.total_usd) || "-";

  fila
    .append($("<td>").addClass("col-xs-2").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-3").text(total))
    .append($("<td>").addClass("col-xs-3").text(total_usd));
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-RegistrosContables")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", RegistrosContables.id_registroRegistrosContables)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteRegistrosContables")
    .attr("id", RegistrosContables.id_registroRegistrosContables)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO CONTABLE")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      RegistrosContables.valido,
      "registros_contables",
      RegistrosContables.id_registroRegistrosContables
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteRegistrosContables", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarRegistrosContables").attr("data-id", id);
  $("#modalEliminarRegistrosContables").modal("show");
});

$("#btn-eliminarRegistrosContables").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarRegistrosContables/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarRegistrosContables").modal("hide");
        cargarRegistrosContables({
          page: $("#herramientasPaginacionRegistrosContables").getCurrentPage(),
          perPage: $("#herramientasPaginacionRegistrosContables").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarRegistrosContablesExcel").on("click", function (e) {
  $("#collapseDescargarRegistrosContables .has-error").removeClass("has-error");
  $("#collapseDescargarRegistrosContables .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarRegistrosContablesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarRegistrosContablesXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarRegistrosContablesCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarRegistrosContables .has-error").removeClass("has-error");
  $("#collapseDescargarRegistrosContables .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarRegistrosContablesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// APORTES PATRONALES

instalarNumeroFlexibleAR("#monto_pagado_AportesPatronales", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosAportesPatronalesLista(id, target) {
  var $list = $(targetArchivosDoc("AportesPatronales", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosAportesPatronales/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/AportesPatronales/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="AportesPatronales"  class="btn btn-sm btn-danger btn-del-archivo-AportesPatronales" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-AportesPatronales", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalAportesPatronalesEditar(id) {
  resetFormAportesPatronales();
  $("#AportesPatronales_modo").val("edit");
  $("#id_registroAportesPatronales").val(id);
  $("#modalCargarAportesPatronales .modal-title").text(
    "| EDITAR REGISTRO DE APORTES PATRONALES"
  );
  $("#guardarRegistroAportesPatronales").text("ACTUALIZAR");
  $("#modalCargarAportesPatronales").modal("show");

  $.getJSON(
    "/documentosContables/llenarAportesPatronalesEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      var ymd = String(d.fecha_pres || "").slice(0, 10);
      var ymdp = String(d.fecha_pago || "").slice(0, 10);

      $('#fechaAportesPatronales input[name="fecha_AportesPatronales"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $('#fechaAportesPatronalesPres input[name="fecha_AportesPatronalesPres"]')
        .val(ymd)
        .trigger("input")
        .trigger("change");
      $(
        '#fecha_pagoAportesPatronales input[name="fecha_pago_AportesPatronales"]'
      )
        .val(ymdp)
        .trigger("input")
        .trigger("change");

      $("#casinoAportesPatronales").val(d.casino).trigger("change");
      $('input[name="cant_empleados_AportesPatronales"]').val(
        d.cant_empleados ?? ""
      );
      $('input[name="monto_pagado_AportesPatronales"]').val(
        formatoAR(d.monto_pagado ?? "")
      );
      $('[name="obs_AportesPatronales"]').val(d.obs || "");
    }
  ).fail(function (xhr) {
    console.error("[AportesPatronales] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormAportesPatronales() {
  var $f = $("#formNuevoRegistroAportesPatronales");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameAportesPatronales").val("");
    $("#uploadAportesPatronales").val("");
  $("#uploadsAportesPatronalesContainer").empty();
  $("#uploadsAportesPatronalesTable tbody").empty();
  $("#uploadsAportesPatronalesWrap").hide();
  $("#fileNameAportesPatronales").val("");
}

function abrirModalAportesPatronalesCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormAportesPatronales();
  $("#AportesPatronales_modo").val("create");
  $("#id_registroAportesPatronales").val("");
  $("#modalCargarAportesPatronales .modal-title").text(
    "| NUEVO REGISTRO DE APORTES PATRONALES"
  );
  $("#guardarRegistroAportesPatronales").text("GENERAR");
  $("#modalCargarAportesPatronales").modal("show");
}

$(document).on("click", "#AportesPatronales_nuevo", function () {
  abrirModalAportesPatronalesCrear();
});

$(document).on("click", ".btn-edit-AportesPatronales", function () {
  var id = $(this).data("id");
  abrirModalAportesPatronalesEditar(id);
});

$(document).on("click", "#guardarRegistroAportesPatronales", function (e) {
  var $form = $("#formNuevoRegistroAportesPatronales");
  let valid = true;
  var id = $("#id_registroAportesPatronales").val() || "";
  var modo = $("#AportesPatronales_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoAportesPatronales']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_AportesPatronales']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_AportesPatronalesPres']",
    ".col-md-5",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_pago_AportesPatronales']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='cant_empleados_AportesPatronales']",
    ".col-md-4",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='monto_pagado_AportesPatronales']",
    ".col-md-4",
    "La cantidad es requerida.",
    valid
  );
  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsAportesPatronalesContainer input[type="file"][name="uploadAportesPatronales[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadAportesPatronales[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadAportesPatronales");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadAportesPatronales[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarAportesPatronales/" + id
      : "/documentosContables/guardarAportesPatronales";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarAportesPatronales({
        page: $("#herramientasPaginacionAportesPatronales").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionAportesPatronales").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarAportesPatronales").modal("hide"), 1000);
      resetFormAportesPatronales();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceAportesPatronales(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarAportesPatronales({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarAportesPatronales({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasAportesPatronales",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaAportesPatronales").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaAportesPatronales").append(
          generarFilaAportesPatronales(item)
        );
      });

      $("#herramientasPaginacionAportesPatronales").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAportesPatronales
      );
      $("#herramientasPaginacionAportesPatronales").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAportesPatronales
      );
    },
    error(err) {
      console.error("Error cargando AportesPatronales:", err);
    },
  });
}

function generarFilaAportesPatronales(AportesPatronales, controlador) {
  const fila = $("<tr>").attr(
    "id",
    AportesPatronales.id_registroAportesPatronales
  );
  const fecha =
    convertirMesAno(AportesPatronales.fecha_AportesPatronales) || "-";
  const casino = AportesPatronales.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-5").html(fecha))
    .append($("<td>").addClass("col-xs-5").text(casino));
  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-AportesPatronales")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", AportesPatronales.id_registroAportesPatronales)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteAportesPatronales")
    .attr("id", AportesPatronales.id_registroAportesPatronales)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR APORTE PATRONAL")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      AportesPatronales.valido,
      "aportes_patronales",
      AportesPatronales.id_registroAportesPatronales
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteAportesPatronales", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarAportesPatronales").attr("data-id", id);
  $("#modalEliminarAportesPatronales").modal("show");
});

$("#btn-eliminarAportesPatronales").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarAportesPatronales/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarAportesPatronales").modal("hide");
        cargarAportesPatronales({
          page: $("#herramientasPaginacionAportesPatronales").getCurrentPage(),
          perPage: $("#herramientasPaginacionAportesPatronales").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarAportesPatronalesExcel").on("click", function (e) {
  $("#collapseDescargarAportesPatronales .has-error").removeClass("has-error");
  $("#collapseDescargarAportesPatronales .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarAportesPatronalesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarAportesPatronalesXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarAportesPatronalesCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarAportesPatronales .has-error").removeClass("has-error");
  $("#collapseDescargarAportesPatronales .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarAportesPatronalesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//PROMO TICKETS

instalarNumeroFlexibleAR("#importe_PromoTickets", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosPromoTicketsLista(id, target) {
  var $list = $(targetArchivosDoc("PromoTickets", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPromoTickets/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/PromoTickets/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="PromoTickets"  class="btn btn-sm btn-danger btn-del-archivo-PromoTickets" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-PromoTickets", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalPromoTicketsEditar(id) {
  resetFormPromoTickets();
  $("#PromoTickets_modo").val("edit");
  $("#id_registroPromoTickets").val(id);
  $("#modalCargarPromoTickets .modal-title").text(
    "| EDITAR REGISTRO DE PROMOTICKETS"
  );
  $("#guardarRegistroPromoTickets").text("ACTUALIZAR");
  $("#modalCargarPromoTickets").modal("show");

  $.getJSON("/documentosContables/llenarPromoTicketsEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);

    $('#fechaPromoTickets input[name="fecha_PromoTickets"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $("#casinoPromoTickets").val(d.casino).trigger("change");

    $('input[name="cant_PromoTickets"]').val(d.cantidad ?? "");
    $('input[name="importe_PromoTickets"]').val(formatoAR(d.importe ?? ""));
  }).fail(function (xhr) {
    console.error("[PromoTickets] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro PromoTickets.");
  });
}

function resetFormPromoTickets() {
  var $f = $("#formNuevoRegistroPromoTickets");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNamePromoTickets").val("");
    $("#uploadPromoTickets").val("");
  $("#uploadsPromoTicketsContainer").empty();
  $("#uploadsPromoTicketsTable tbody").empty();
  $("#uploadsPromoTicketsWrap").hide();
  $("#fileNamePromoTickets").val("");
}

function abrirModalPromoTicketsCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormPromoTickets();
  $("#PromoTickets_modo").val("create");
  $("#id_registroPromoTickets").val("");
  $("#modalCargarPromoTickets .modal-title").text(
    "| NUEVO REGISTRO DE PROMOTICKETS"
  );
  $("#guardarRegistroPromoTickets").text("GENERAR");
  $("#modalCargarPromoTickets").modal("show");
}

$(document).on("click", "#PromoTickets_nuevo", function () {
  abrirModalPromoTicketsCrear();
});

$(document).on("click", ".btn-edit-PromoTickets", function () {
  var id = $(this).data("id");
  abrirModalPromoTicketsEditar(id);
});

$(document).on("click", "#guardarRegistroPromoTickets", function (e) {
  var $form = $("#formNuevoRegistroPromoTickets");
  let valid = true;
  var id = $("#id_registroPromoTickets").val() || "";
  var modo = $("#PromoTickets_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoPromoTickets']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_PromoTickets']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='cant_PromoTickets']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='importe_PromoTickets']"),
    ".col-md-5",
    "El importe es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsPromoTicketsContainer input[type="file"][name="uploadPromoTickets[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadPromoTickets[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadPromoTickets");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadPromoTickets[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarPromoTickets/" + id
      : "/documentosContables/guardarPromoTickets";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarPromoTickets({
        page: $("#herramientasPaginacionPromoTickets").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionPromoTickets").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarPromoTickets").modal("hide"), 1000);
      resetFormPromoTickets();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndicePromoTickets(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPromoTickets({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPromoTickets({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasPromoTickets",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPromoTickets").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaPromoTickets").append(generarFilaPromoTickets(item));
      });

      $("#herramientasPaginacionPromoTickets").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePromoTickets
      );
      $("#herramientasPaginacionPromoTickets").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePromoTickets
      );
    },
    error(err) {
      console.error("Error cargando PromoTickets:", err);
    },
  });
}

function generarFilaPromoTickets(PromoTickets, controlador) {
  const fila = $("<tr>").attr("id", PromoTickets.id_registroPromoTickets);
  const fecha = convertirMesAno(PromoTickets.fecha_PromoTickets) || "-";
  const casino = PromoTickets.casino || "-";
  const importe = "$ " + formatoAR(PromoTickets.importe) || "-";
  const cantidad = PromoTickets.cantidad || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-2").text(cantidad))
    .append($("<td>").addClass("col-xs-2").text(importe));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-PromoTickets")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", PromoTickets.id_registroPromoTickets)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deletePromoTickets")
    .attr("id", PromoTickets.id_registroPromoTickets)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR PROMO TICKET")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      PromoTickets.valido,
      "promo_tickets",
      PromoTickets.id_registroPromoTickets
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deletePromoTickets", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarPromoTickets").attr("data-id", id);
  $("#modalEliminarPromoTickets").modal("show");
});

$("#btn-eliminarPromoTickets").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarPromoTickets/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarPromoTickets").modal("hide");
        cargarPromoTickets({
          page: $("#herramientasPaginacionPromoTickets").getCurrentPage(),
          perPage: $("#herramientasPaginacionPromoTickets").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarPromoTicketsExcel").on("click", function (e) {
  $("#collapseDescargarPromoTickets .has-error").removeClass("has-error");
  $("#collapseDescargarPromoTickets .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarPromoTicketsXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPromoTicketsXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPromoTicketsCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarPromoTickets .has-error").removeClass("has-error");
  $("#collapseDescargarPromoTickets .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPromoTicketsCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//POZOS ACUMULADOS LINKEADOS E INDIVIDUALES

instalarNumeroFlexibleAR("#importe_PozosAcumuladosLinkeados", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosPozosAcumuladosLinkeadosLista(id, target) {
  var $list = $(targetArchivosDoc("PozosAcumuladosLinkeados", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPozosAcumuladosLinkeados/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/PozosAcumuladosLinkeados/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="PozosAcumuladosLinkeados"  class="btn btn-sm btn-danger btn-del-archivo-PozosAcumuladosLinkeados" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on(
  "click",
  ".btn-del-archivo-PozosAcumuladosLinkeados",
  function () {
    const id = $(this).data("id");
    const regId = $(this).data("regId");
    const scope = $(this).data("scope");
    $("#btn-eliminarArchivo").attr("data-id", id);
    $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
  }
);
function abrirModalPozosAcumuladosLinkeadosEditar(id) {
  resetFormPozosAcumuladosLinkeados();
  $("#PozosAcumuladosLinkeados_modo").val("edit");
  $("#id_registroPozosAcumuladosLinkeados").val(id);
  $("#modalCargarPozosAcumuladosLinkeados .modal-title").text(
    "| EDITAR REGISTRO DE POZOS ACUMULADOS LINKEADOS E INDIVIDUALES"
  );
  $("#guardarRegistroPozosAcumuladosLinkeados").text("ACTUALIZAR");
  $("#modalCargarPozosAcumuladosLinkeados").modal("show");

  $.getJSON(
    "/documentosContables/llenarPozosAcumuladosLinkeadosEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);

      $(
        '#fechaPozosAcumuladosLinkeados input[name="fecha_PozosAcumuladosLinkeados"]'
      )
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoPozosAcumuladosLinkeados").val(d.casino).trigger("change");
      $('input[name="importe_PozosAcumuladosLinkeados"]').val(
        formatoAR(d.importe ?? "")
      );
    }
  ).fail(function (xhr) {
    console.error(
      "[PozosAcumuladosLinkeados] GET FAIL",
      xhr.status,
      xhr.responseText
    );
    alert("No se pudo cargar el registro.");
  });
}

function resetFormPozosAcumuladosLinkeados() {
  var $f = $("#formNuevoRegistroPozosAcumuladosLinkeados");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNamePozosAcumuladosLinkeados").val(
    ""
  );
    $("#uploadPozosAcumuladosLinkeados").val("");
  $("#uploadsPozosAcumuladosLinkeadosContainer").empty();
  $("#uploadsPozosAcumuladosLinkeadosTable tbody").empty();
  $("#uploadsPozosAcumuladosLinkeadosWrap").hide();
  $("#fileNamePozosAcumuladosLinkeados").val(
    ""
  );
}

function abrirModalPozosAcumuladosLinkeadosCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormPozosAcumuladosLinkeados();
  $("#PozosAcumuladosLinkeados_modo").val("create");
  $("#id_registroPozosAcumuladosLinkeados").val("");
  $("#modalCargarPozosAcumuladosLinkeados .modal-title").text(
    "| NUEVO REGISTRO DE POZOS ACUMULADOS LINKEADOS E INDIVIDUALES"
  );
  $("#guardarRegistroPozosAcumuladosLinkeados").text("GENERAR");
  $("#modalCargarPozosAcumuladosLinkeados").modal("show");
}

$(document).on("click", "#PozosAcumuladosLinkeados_nuevo", function () {
  abrirModalPozosAcumuladosLinkeadosCrear();
});

$(document).on("click", ".btn-edit-PozosAcumuladosLinkeados", function () {
  var id = $(this).data("id");
  abrirModalPozosAcumuladosLinkeadosEditar(id);
});

$(document).on(
  "click",
  "#guardarRegistroPozosAcumuladosLinkeados",
  function (e) {
    var $form = $("#formNuevoRegistroPozosAcumuladosLinkeados");
    let valid = true;
    var id = $("#id_registroPozosAcumuladosLinkeados").val() || "";
    var modo = $("#PozosAcumuladosLinkeados_modo").val() || "create";
    $form.find(".has-error").removeClass("has-error");
    $form.find(".help-block.js-error").remove();

    valid = validarCampo(
      "select[name='casinoPozosAcumuladosLinkeados']",
      ".col-md-3",
      "El casino es requerido.",
      valid
    );

    valid = validarCampo(
      "input[name='fecha_PozosAcumuladosLinkeados']",
      ".col-md-4",
      "La fecha es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='importe_PozosAcumuladosLinkeados']",
      ".col-md-6",
      "El importe es requerido.",
      valid
    );
    if (!valid) return 0;

    let formElem = $form[0];
    let formData = new FormData(formElem);
    var fd = new FormData();

    $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

    $(
      '#uploadsPozosAcumuladosLinkeadosContainer input[type="file"][name="uploadPozosAcumuladosLinkeados[]"]'
    ).each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadPozosAcumuladosLinkeados[]", files[i]);
      }
    });

    var cur = document.getElementById("uploadPozosAcumuladosLinkeados");
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append("uploadPozosAcumuladosLinkeados[]", cur.files[j]);
      }
    }

    var url =
      modo === "edit"
        ? "/documentosContables/actualizarPozosAcumuladosLinkeados/" + id
        : "/documentosContables/guardarPozosAcumuladosLinkeados";

    $.ajax({
      url: url,
      method: "POST",
      data: fd,
      processData: false,
      contentType: false,
      success: function (res) {
        cargarPozosAcumuladosLinkeados({
          page: 1,
          perPage: $(
            "#herramientasPaginacionPozosAcumuladosLinkeados"
          ).getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
        setTimeout(
          () => $("#modalCargarPozosAcumuladosLinkeados").modal("hide"),
          1000
        );
        resetFormPozosAcumuladosLinkeados();
      },
      error: function (xhr) {
        $("#salir").next(".help-block.js-error").remove();
        $("#salir").after(
          '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
        );
        console.error("Error al guardar:", xhr);
      },
    });
  }
);

function clickIndicePozosAcumuladosLinkeados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPozosAcumuladosLinkeados({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPozosAcumuladosLinkeados({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasPozosAcumuladosLinkeados",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPozosAcumuladosLinkeados").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaPozosAcumuladosLinkeados").append(
          generarFilaPozosAcumuladosLinkeados(item)
        );
      });

      $("#herramientasPaginacionPozosAcumuladosLinkeados").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePozosAcumuladosLinkeados
      );
      $("#herramientasPaginacionPozosAcumuladosLinkeados").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePozosAcumuladosLinkeados
      );
    },
    error(err) {
      console.error("Error cargando PozosAcumuladosLinkeados:", err);
    },
  });
}

function generarFilaPozosAcumuladosLinkeados(
  PozosAcumuladosLinkeados,
  controlador
) {
  const fila = $("<tr>").attr(
    "id",
    PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados
  );
  const fecha =
    convertirMesAno(PozosAcumuladosLinkeados.fecha_PozosAcumuladosLinkeados) ||
    "-";
  const casino = PozosAcumuladosLinkeados.casino || "-";
  const importe = "$ " + formatoAR(PozosAcumuladosLinkeados.importe) || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").text(casino))
    .append($("<td>").addClass("col-xs-3").text(importe));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-PozosAcumuladosLinkeados")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr(
      "data-id",
      PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados
    )
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deletePozosAcumuladosLinkeados")
    .attr("id", PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR POZO ACUMULADO LINKEADO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      PozosAcumuladosLinkeados.valido,
      "pozos_acumulados",
      PozosAcumuladosLinkeados.id_registroPozosAcumuladosLinkeados
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deletePozosAcumuladosLinkeados", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarPozosAcumuladosLinkeados").attr("data-id", id);
  $("#modalEliminarPozosAcumuladosLinkeados").modal("show");
});

$("#btn-eliminarPozosAcumuladosLinkeados").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarPozosAcumuladosLinkeados/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarPozosAcumuladosLinkeados").modal("hide");
        cargarPozosAcumuladosLinkeados({
          page: $(
            "#herramientasPaginacionPozosAcumuladosLinkeados"
          ).getCurrentPage(),
          perPage: $(
            "#herramientasPaginacionPozosAcumuladosLinkeados"
          ).getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarPozosAcumuladosLinkeadosExcel").on("click", function (e) {
  $("#collapseDescargarPozosAcumuladosLinkeados .has-error").removeClass(
    "has-error"
  );
  $("#collapseDescargarPozosAcumuladosLinkeados .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarPozosAcumuladosLinkeadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPozosAcumuladosLinkeadosXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPozosAcumuladosLinkeadosCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarPozosAcumuladosLinkeados .has-error").removeClass(
    "has-error"
  );
  $("#collapseDescargarPozosAcumuladosLinkeados .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPozosAcumuladosLinkeadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//CONTRIBUCION ENTE TURISTICO

$(function () {
  instalarNumeroFlexibleAR(
    [
      "#base_imponible_ContribEnteTuristico",
      "#alicuota_ContribEnteTuristico",
      "#impuesto_determinado_ContribEnteTuristico",
      "#monto_pagado_ContribEnteTuristico",
    ].join(", "),
    { decimales: 5, permitirNegativos: true }
  );

  instalarAutoImpuestoAR({
    base: "#base_imponible_ContribEnteTuristico",
    alicuota: "#alicuota_ContribEnteTuristico",
    impuesto: "#impuesto_determinado_ContribEnteTuristico",
    decImp: 2,
    aliEsPorcentaje: true,
  });
});

function cargarArchivosContribEnteTuristicoLista(id, target) {
  var $list = $(targetArchivosDoc("ContribEnteTuristico", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosContribEnteTuristico/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/ContribEnteTuristico/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="ContribEnteTuristico"  class="btn btn-sm btn-danger btn-del-archivo-ContribEnteTuristico" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

function abrirModalContribEnteTuristicoEditar(id) {
  resetFormContribEnteTuristico();
  $("#ContribEnteTuristico_modo").val("edit");
  $("#id_registroContribEnteTuristico").val(id);
  $("#modalCargarContribEnteTuristico .modal-title").text(
    "| EDITAR REGISTRO DE CONTRIB. ENTE TURÍSTICO"
  );
  $("#guardarRegistroContribEnteTuristico").text("ACTUALIZAR");
  $("#modalCargarContribEnteTuristico").modal("show");

  $.getJSON(
    "/documentosContables/llenarContribEnteTuristicoEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      var ymd = String(d.fecha_pres || "").slice(0, 10);
      var ymdV = String(d.fecha_venc || "").slice(0, 10);

      $('#fechaContribEnteTuristico input[name="fecha_ContribEnteTuristico"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $(
        '#fechaContribEnteTuristicoPres input[name="fecha_ContribEnteTuristicoPres"]'
      )
        .val(ymd)
        .trigger("input")
        .trigger("change");
      $('input[name="fecha_venc_ContribEnteTuristico"]')
        .val(ymdV)
        .trigger("input")
        .trigger("change");

      $('input[name="base_imponible_ContribEnteTuristico"]').val(
        formatoAR(d.base_imponible ?? "")
      );
      $('input[name="alicuota_ContribEnteTuristico"]').val(
        formatoAR(d.alicuota ?? "")
      );
      $('input[name="impuesto_determinado_ContribEnteTuristico"]').val(
        formatoAR(d.impuesto_determinado ?? "")
      );
      $('input[name="monto_pagado_ContribEnteTuristico"]').val(
        formatoAR(d.monto_pagado ?? "")
      );
      $('[name="obs_ContribEnteTuristico"]').val(d.obs || "");

      var $cas = $("#casinoContribEnteTuristico");
      $cas.val(3).trigger("change");
      $cas.closest(".col-md-4, .col-md-6, .col-md-12").show();
    }
  ).fail(function (xhr) {
    console.error(
      "[ContribEnteTuristico] GET FAIL",
      xhr.status,
      xhr.responseText
    );
    alert("No se pudo cargar el registro.");
  });
}

function resetFormContribEnteTuristico() {
  var $f = $("#formNuevoRegistroContribEnteTuristico");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameContribEnteTuristico").val(
    ""
  );
    $("#uploadContribEnteTuristico").val("");
  $("#uploadsContribEnteTuristicoContainer").empty();
  $("#uploadsContribEnteTuristicoTable tbody").empty();
  $("#uploadsContribEnteTuristicoWrap").hide();
  $("#fileNameContribEnteTuristico").val(
    ""
  );
}

function abrirModalContribEnteTuristicoCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormContribEnteTuristico();
  $("#ContribEnteTuristico_modo").val("create");
  $("#id_registroContribEnteTuristico").val("");
  $("#modalCargarContribEnteTuristico .modal-title").text(
    "| NUEVO REGISTRO DE CONTRIB. ENTE TURISTICO"
  );
  $("#guardarRegistroContribEnteTuristico").text("GENERAR");
  $("#modalCargarContribEnteTuristico").modal("show");
}

$(document).on("click", "#ContribEnteTuristico_nuevo", function () {
  abrirModalContribEnteTuristicoCrear();
});

$(document).on("click", ".btn-edit-ContribEnteTuristico", function () {
  var id = $(this).data("id");
  abrirModalContribEnteTuristicoEditar(id);
});

$(document).on("click", "#guardarRegistroContribEnteTuristico", function (e) {
  var $form = $("#formNuevoRegistroContribEnteTuristico");
  let valid = true;
  var id = $("#id_registroContribEnteTuristico").val() || "";
  var modo = $("#ContribEnteTuristico_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "input[name='fecha_ContribEnteTuristico']",
    ".col-md-3",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_ContribEnteTuristicoPres']",
    ".col-md-6 ",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_venc_ContribEnteTuristico']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='alicuota_ContribEnteTuristico']",
    ".col-md-4",
    "El importe es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='impuesto_determinado_ContribEnteTuristico']",
    ".col-md-4",
    "El importe es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='base_imponible_ContribEnteTuristico']",
    ".col-md-4",
    "El importe es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='monto_pagado_ContribEnteTuristico']",
    ".col-md-6",
    "El importe es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsContribEnteTuristicoContainer input[type="file"][name="uploadContribEnteTuristico[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadContribEnteTuristico[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadContribEnteTuristico");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadContribEnteTuristico[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarContribEnteTuristico/" + id
      : "/documentosContables/guardarContribEnteTuristico";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarContribEnteTuristico({
        page: $("#herramientasPaginacionContribEnteTuristico").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionContribEnteTuristico").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(
        () => $("#modalCargarContribEnteTuristico").modal("hide"),
        1000
      );
      resetFormContribEnteTuristico();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceContribEnteTuristico(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarContribEnteTuristico({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarContribEnteTuristico({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasContribEnteTuristico",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaContribEnteTuristico").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaContribEnteTuristico").append(
          generarFilaContribEnteTuristico(item)
        );
      });

      $("#herramientasPaginacionContribEnteTuristico").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceContribEnteTuristico
      );
      $("#herramientasPaginacionContribEnteTuristico").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceContribEnteTuristico
      );
    },
    error(err) {
      console.error("Error cargando ContribEnteTuristico:", err);
    },
  });
}

function generarFilaContribEnteTuristico(ContribEnteTuristico, controlador) {
  const fila = $("<tr>").attr(
    "id",
    ContribEnteTuristico.id_registroContribEnteTuristico
  );
  const fecha =
    convertirMesAno(ContribEnteTuristico.fecha_ContribEnteTuristico) || "-";
  const fecha_pres = ContribEnteTuristico.fecha_pres || "-";
  const casino = ContribEnteTuristico.casino || "-";
  const monto_pagado = "$ " + ContribEnteTuristico.monto_pagado || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").text(fecha_pres))
    .append($("<td>").addClass("col-xs-3").text(monto_pagado));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-ContribEnteTuristico")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", ContribEnteTuristico.id_registroContribEnteTuristico)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteContribEnteTuristico")
    .attr("id", ContribEnteTuristico.id_registroContribEnteTuristico)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR CONTRIBUCIÓN ENTE TURISTICO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      ContribEnteTuristico.valido,
      "contrib_ente_turistico",
      ContribEnteTuristico.id_registroContribEnteTuristico
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteContribEnteTuristico", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarContribEnteTuristico").attr("data-id", id);
  $("#modalEliminarContribEnteTuristico").modal("show");
});

$("#btn-eliminarContribEnteTuristico").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarContribEnteTuristico/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarContribEnteTuristico").modal("hide");
        cargarContribEnteTuristico({
          page: $(
            "#herramientasPaginacionContribEnteTuristico"
          ).getCurrentPage(),
          perPage: $(
            "#herramientasPaginacionContribEnteTuristico"
          ).getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarContribEnteTuristicoExcel").on("click", function (e) {
  $("#collapseDescargarContribEnteTuristico .has-error").removeClass(
    "has-error"
  );
  $("#collapseDescargarContribEnteTuristico .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;
  window.location.href = `/documentosContables/descargarContribEnteTuristicoXlsx?casino=3&desde=${desde}&hasta=${hasta}`;
});

$("#btn-descargarContribEnteTuristicoCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarContribEnteTuristico .has-error").removeClass(
    "has-error"
  );
  $("#collapseDescargarContribEnteTuristico .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarContribEnteTuristicoCsv?casino=3`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//RR HH

instalarAutoSumaAR({
  sources: ["#personal_inicio_RRHH", "#altas_RRHH", "-#bajas_RRHH"],
  target: "#personal_final_RRHH",
  decimales: 0,
});

instalarAutoSumaAR({
  sources: ["#personal_final_RRHH", "-#personal_nomina_RRHH"],
  target: "#diferencia_RRHH",
  decimales: 0,
});

instalarAutoSumaAR({
  sources: ["#tercerizados_RRHH", "#personal_nomina_RRHH"],
  target: "#total_personal_RRHH",
  decimales: 0,
});

instalarAutoPorcentajeAR({
  base: "#total_ludicos_RRHH",
  parte: "#no_ludicos_RRHH",
  target: "#porcentaje_no_ludicos_RRHH",
  decimales: 2,
  blankIfZeroBase: true,
});

instalarAutoPorcentajeAR({
  base: "#total_ludicos_RRHH",
  parte: "#ludicos_RRHH",
  target: "#porcentaje_ludicos_RRHH",
  decimales: 2,
  blankIfZeroBase: true,
});
instalarAutoSumaAR({
  sources: ["#porcentaje_ludicos_RRHH", "#porcentaje_no_ludicos_RRHH"],
  target: "#total_porcentaje_ludicos_RRHH",
  decimales: 2,
});

instalarAutoSumaAR({
  sources: ["#personal_nomina_RRHH", "-#total_ludicos_RRHH"],
  target: "#dif_nomina_RRHH",
  decimales: 0,
});
instalarAutoSumaAR({
  sources: ["#ludicos_vivivendo_RRHH", "#no_ludicos_viviendo_RRHH"],
  target: "#total_ludicos_viviendo_RRHH",
  decimales: 0,
});
instalarAutoSumaAR({
  sources: ["#ludicos_RRHH", "#no_ludicos_RRHH"],
  target: "#total_ludicos_RRHH",
  decimales: 0,
});
instalarAutoPorcentajeAR({
  base: "#total_ludicos_RRHH",
  parte: "#ludicos_vivivendo_RRHH",
  target: "#porcentaje_ludicos_sf_RRHH",
  decimales: 2,
  blankIfZeroBase: true,
});
instalarAutoPorcentajeAR({
  base: "#total_ludicos_RRHH",
  parte: "#no_ludicos_viviendo_RRHH",
  target: "#porcentaje_no_ludicos_sf_RRHH",
  decimales: 2,
  blankIfZeroBase: true,
});
instalarAutoPorcentajeAR({
  base: "#total_ludicos_RRHH",
  parte: "#total_ludicos_viviendo_RRHH",
  target: "#porcentaje_total_sf_RRHH",
  decimales: 2,
  blankIfZeroBase: true,
});

function cargarArchivosRRHHLista(id, target) {
  var $list = $(targetArchivosDoc("RRHH", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosRRHH/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/RRHH/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="RRHH"  class="btn btn-sm btn-danger btn-del-archivo-RRHH" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-RRHH", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalRRHHEditar(id) {
  resetFormRRHH();
  $("#RRHH_modo").val("edit");
  $("#id_registroRRHH").val(id);
  $("#modalCargarRRHH .modal-title").text("| EDITAR REGISTRO DE RRHH");
  $("#guardarRegistroRRHH").text("ACTUALIZAR");
  $("#modalCargarRRHH").modal("show");

  $.getJSON("/documentosContables/llenarRRHHEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);
    $('#fechaRRHH input[name="fecha_RRHH"]')
      .val(ym)
      .trigger("input")
      .trigger("change");
    $("#casinoRRHH").val(d.casino).trigger("change");

    $('input[name="personal_inicio_RRHH"]').val(d.personal_inicio ?? "");
    $('input[name="personal_final_RRHH"]').val(d.personal_final ?? "");
    $('input[name="altas_RRHH"]').val(d.altas_mes ?? "");
    $('input[name="bajas_RRHH"]').val(d.bajas ?? "");
    $('input[name="personal_nomina_RRHH"]').val(d.personal_nomina ?? "");
    $('input[name="diferencia_RRHH"]').val(d.diferencia ?? "");
    $('input[name="tercerizados_RRHH"]').val(d.tercerizados ?? "");
    $('input[name="total_personal_RRHH"]').val(d.total_personal ?? "");
    $('input[name="ofertado_adjudicado_RRHH"]').val(
      d.ofertado_adjudicado ?? ""
    );
    $('input[name="ludicos_RRHH"]').val(d.ludico ?? "");
    $('input[name="no_ludicos_RRHH"]').val(d.no_ludico ?? "");
    $('input[name="total_ludicos_RRHH"]').val(d.total_tipo ?? "");
    $('input[name="porcentaje_ludicos_RRHH"]').val(
      formatoAR(d.porcentaje_ludico ?? "")
    );
    $('input[name="porcentaje_no_ludicos_RRHH"]').val(
      formatoAR(d.porcentaje_no_ludico ?? "")
    );
    $('input[name="total_porcentaje_ludicos_RRHH"]').val(
      formatoAR(d.porcentaje_total ?? "")
    );
    $('input[name="porcentaje_ludicos_sf_RRHH"]').val(
      formatoAR(d.porcentaje_ludico_viviendo ?? "")
    );
    $('input[name="porcentaje_no_ludicos_sf_RRHH"]').val(
      formatoAR(d.porcentaje_no_ludico_viviendo ?? "")
    );
    $('input[name="porcentaje_total_sf_RRHH"]').val(
      formatoAR(d.porcentaje_total_viviendo ?? "")
    );
    $('input[name="dif_nomina_RRHH"]').val(d.diferencia_nomina_ddjj ?? "");
    $('input[name="ludicos_vivivendo_RRHH"]').val(d.ludico_viviendo ?? "");
    $('input[name="no_ludicos_viviendo_RRHH"]').val(d.no_ludico_viviendo ?? "");
    $('textarea[name="obs_RRHH"]').val(d.observaciones ?? "");
    $('input[name="total_ludicos_viviendo_RRHH"]').val(
      d.total_ludico_viviendo ?? ""
    );
  }).fail(function (xhr) {
    console.error("[RRHH] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro RRHH.");
  });
}

$(document).on("hidden.bs.modal", "#modalCargarRRHH", function () {
  var $m = $("#modalCargarRRHH");
  $m.find(".has-error").removeClass("has-error");
  $m.find("input")
    .removeClass("is-invalid text-danger")
    .removeAttr("title data-original-title");
  $m.data("warn-shown", false);
});

function resetFormRRHH() {
  var $f = $("#formNuevoRegistroRRHH");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameRRHH").val("");
    $("#uploadRRHH").val("");
  $("#uploadsRRHHContainer").empty();
  $("#uploadsRRHHTable tbody").empty();
  $("#uploadsRRHHWrap").hide();
  $("#fileNameRRHH").val("");
}

function abrirModalRRHHCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormRRHH();
  $("#RRHH_modo").val("create");
  $("#id_registroRRHH").val("");
  $("#modalCargarRRHH .modal-title").text("| NUEVO REGISTRO DE RRHH");
  $("#guardarRegistroRRHH").text("GENERAR");
  $("#modalCargarRRHH").modal("show");
}

$(document).on("click", "#RRHH_nuevo", function () {
  abrirModalRRHHCrear();
});

$(document).on("click", ".btn-edit-RRHH", function () {
  var id = $(this).data("id");
  abrirModalRRHHEditar(id);
});
$(document)
  .off("click.rrhh", "#guardarRegistroRRHH")
  .on("click.rrhh", "#guardarRegistroRRHH", function () {
    var $form = $("#formNuevoRegistroRRHH");
    var id = $("#id_registroRRHH").val() || "";
    var modo = $("#RRHH_modo").val() || "create";

    $form.find(".has-error").removeClass("has-error");
    $form.find(".help-block.js-error").remove();

    let valid = true;

    valid = validarCampo(
      "select[name='casinoRRHH']",
      ".col-md-3",
      "El casino es requerido.",
      valid
    );
    valid = validarCampo(
      "input[name='fecha_RRHH']",
      ".col-md-4",
      "La fecha es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='personal_inicio_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='personal_final_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='altas_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='bajas_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='personal_nomina_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='diferencia_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='tercerizados_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='total_personal_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='ofertado_adjudicado_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='no_ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='total_ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='porcentaje_ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='porcentaje_no_ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='total_porcentaje_ludicos_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='ludicos_vivivendo_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='no_ludicos_viviendo_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='total_ludicos_viviendo_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='porcentaje_ludicos_sf_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='porcentaje_no_ludicos_sf_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );
    valid = validarCampoNum(
      "input[name='porcentaje_total_sf_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    valid = validarCampoNum(
      "input[name='dif_nomina_RRHH']",
      ".col-md-4",
      "La cantidad es requerida.",
      valid
    );

    if (!valid) {
      return;
    }
    var total = $("#total_personal_RRHH").val();
    var adjudicado = $("#ofertado_adjudicado_RRHH").val();
    var pctSF = $("#porcentaje_total_sf_RRHH").val();
    var umbralSF = 80;

    var warnPersonal = total < adjudicado;
    var warnSF = pctSF < umbralSF;

    var yaAdvirtio = $form.data("warn-shown") === true;

    if ((warnPersonal || warnSF) && !yaAdvirtio) {
      $("#personal-adjudicados-RRHH")
        .toggle(warnPersonal)
        .text(
          "El total de personal (" +
            total +
            ") es menor al adjudicado (" +
            adjudicado +
            ")."
        );
      $("#viviendo-adjudicados-RRHH")
        .toggle(warnSF)
        .text(
          "El % de personal viviendo en Santa Fe (" +
            pctSF +
            "%) es menor al umbral (" +
            umbralSF +
            "%)."
        );

      $form.data("warn-shown", true);
      $("#modalAdjudicadosRRHH").modal({
        backdrop: "static",
        keyboard: false,
        show: true,
      });

      return;
    }

    var formData = new FormData($form[0]);
    $form.serializeArray().forEach(function(p) {
      if (
        p.name.includes("RRHH") &&
        !["casinoRRHH", "fecha_RRHH", "RRHH_modo", "id_registroRRHH"].includes(p.name)
      ) {
        let val = parseNumeroFlexible(p.value)?.num ?? p.value;
        formData.set(p.name, val);
      }
    });
    var url =
      $("#RRHH_modo").val() === "edit"
        ? "/documentosContables/actualizarRRHH/" +
          ($("#id_registroRRHH").val() || "")
        : "/documentosContables/guardarRRHH";

    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function () {
        cargarRRHH({
          page: $("#herramientasPaginacionRRHH").getCurrentPage() || 1,
          perPage: $("#herramientasPaginacionRRHH").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
        setTimeout(() => $("#modalCargarRRHH").modal("hide"), 1000);
        resetFormRRHH();
        $form.removeData("warn-shown");
      },
      error: function (xhr) {
        $("#salir").next(".help-block.js-error").remove();
        $("#salir").after(
          '<span class="help-block js-error text-danger" style="color:red;">Ocurrió un error.</span>'
        );
        console.error("Error al guardar:", xhr);
      },
    });
  });

$(document).on("shown.bs.modal", "#modalCargarRRHH", function () {
  $("#formNuevoRegistroRRHH").removeData("warn-shown");
});

function clickIndiceRRHH(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarRRHH({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarRRHH({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasRRHH",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaRRHH").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaRRHH").append(generarFilaRRHH(item));
      });

      $("#herramientasPaginacionRRHH").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRRHH
      );
      $("#herramientasPaginacionRRHH").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceRRHH
      );
    },
    error(err) {
      console.error("Error cargando RRHH:", err);
    },
  });
}

function generarFilaRRHH(RRHH, controlador) {
  const fila = $("<tr>").attr("id", RRHH.id_registroRRHH);
  const fecha = convertirMesAno(RRHH.fecha_RRHH) || "-";
  const total = formatoAR(RRHH.total || "-");
  const casino = RRHH.casino || "-";
  const porcentaje_viviendo = formatoAR(RRHH.porcentaje || "-");

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-2").text(total))
    .append(
      $("<td>")
        .addClass("col-xs-2")
        .text(porcentaje_viviendo + " %")
    );

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-RRHH")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", RRHH.id_registroRRHH)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteRRHH")
    .attr("id", RRHH.id_registroRRHH)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO RRHH")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(RRHH.valido, "rrhh", RRHH.id_registroRRHH)
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteRRHH", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarRRHH").attr("data-id", id);
  $("#modalEliminarRRHH").modal("show");
});

$("#btn-eliminarRRHH").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarRRHH/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarRRHH").modal("hide");
        cargarRRHH({
          page: $("#herramientasPaginacionRRHH").getCurrentPage(),
          perPage: $("#herramientasPaginacionRRHH").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarRRHHExcel").on("click", function (e) {
  $("#collapseDescargarRRHH .has-error").removeClass("has-error");
  $("#collapseDescargarRRHH .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarRRHHXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarRRHHXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarRRHHCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarRRHH .has-error").removeClass("has-error");
  $("#collapseDescargarRRHH .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarRRHHCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//GANANCIAS

$(function () {
  instalarNumeroFlexibleAR(
    "#computa_Ganancias, #abonado_Ganancias, #anticipo_Ganancias",
    { decimales: 2, permitirNegativos: true }
  );

  instalarAutoSumaAR({
    sources: [
      "#anticipo_Ganancias",
      "-#abonado_Ganancias",
      "-#computa_Ganancias",
    ],
    target: "#diferencia_Ganancias",
    decimales: 2,
  });
});

function cargarArchivosGananciasLista(id, target) {
  var $list = $(targetArchivosDoc("Ganancias", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosGanancias/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/Ganancias/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="Ganancias"  class="btn btn-sm btn-danger btn-del-archivo-Ganancias" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-Ganancias", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalGananciasEditar(id) {
  resetFormGanancias();
  $("#Ganancias_modo").val("edit");
  $("#id_registroGanancias").val(id);
  $("#modalCargarGanancias .modal-title").text(
    "| EDITAR REGISTRO DE ANTICIPO DE GANANCIAS"
  );
  $("#guardarRegistroGanancias").text("ACTUALIZAR");
  $("#modalCargarGanancias").modal("show");

  $.getJSON("/documentosContables/llenarGananciasEdit/" + id, function (d) {
    var y = String(d.fecha || "").slice(0, 4);
    var ymdp = String(d.fecha_pago || "").slice(0, 10);

    $("#casinoGanancias").val(d.casino).trigger("change");

    $('input[name="fecha_GananciasPres"]').val(y);
    $('input[name="fecha_pago_Ganancias"]')
      .val(ymdp)
      .trigger("input")
      .trigger("change");

    $('input[name="nro_anticipo_Ganancias"]').val(
      formatoAR(d.nro_anticipo ?? "")
    );
    $('input[name="anticipo_Ganancias"]').val(formatoAR(d.anticipo ?? ""));
    $('input[name="diferencia_Ganancias"]').val(formatoAR(d.diferencia));
    $('input[name="abonado_Ganancias"]').val(formatoAR(d.abonado ?? ""));

    var qv = formatoAR(d.computa == null ? "" : String(d.computa));
    $('[name="computa_Ganancias"]').val(qv).trigger("change");

    $('[name="obs_Ganancias"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[Ganancias] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro de Ganancias.");
  });
}

function resetFormGanancias() {
  var $f = $("#formNuevoRegistroGanancias");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameGanancias").val("");
    $("#uploadGanancias").val("");
  $("#uploadsGananciasContainer").empty();
  $("#uploadsGananciasTable tbody").empty();
  $("#uploadsGananciasWrap").hide();
  $("#fileNameGanancias").val("");
}

function abrirModalGananciasCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormGanancias();
  $("#Ganancias_modo").val("create");
  $("#id_registroGanancias").val("");
  $("#modalCargarGanancias .modal-title").text("| NUEVO REGISTRO DE GANANCIAS");
  $("#guardarRegistroGanancias").text("GENERAR");
  $("#modalCargarGanancias").modal("show");
}

$(document).on("click", "#Ganancias_nuevo", function () {
  abrirModalGananciasCrear();
});

$(document).on("click", ".btn-edit-Ganancias", function () {
  var id = $(this).data("id");
  abrirModalGananciasEditar(id);
});

$(document).on("click", "#Ganancias_periodo_nuevo", function (e) {
  $("#modalCargarGanancias_periodo").modal("show");
});

$(document).on("click", "#guardarRegistroGanancias", function (e) {
  var $form = $("#formNuevoRegistroGanancias");
  let valid = true;
  var id = $("#id_registroGanancias").val() || "";
  var modo = $("#Ganancias_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoGanancias']",
    ".col-md-4",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='fecha_GananciasPres']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );
  valid = validarCampoNum(
    "input[name='nro_anticipo_Ganancias']",
    ".col-md-4",
    "El número es requerido.",
    valid
  );

  valid = validarCampoNum(
    "input[name='anticipo_Ganancias']",
    ".col-md-4",
    "El número es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='computa_Ganancias']",
    ".col-md-4",
    "El número es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='abonado_Ganancias']",
    ".col-md-4",
    "El número es requerido.",
    valid
  );
  valid = validarCampoNum(
    "input[name='diferencia_Ganancias']",
    ".col-md-4",
    "El numero es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_pago_Ganancias']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsGananciasContainer input[type="file"][name="uploadGanancias[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadGanancias[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadGanancias");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadGanancias[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarGanancias/" + id
      : "/documentosContables/guardarGanancias";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarGanancias({
        page: $("#herramientasPaginacionGanancias").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionGanancias").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarGanancias").modal("hide"), 1000);
      resetFormGanancias();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function cargarArchivosGanancias_periodoLista(id, target) {
  var $list = $(targetArchivosDoc("Ganancias_periodo", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosGanancias_periodo/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/Ganancias/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="Ganancias_periodo"  class="btn btn-sm btn-danger btn-del-archivo-Ganancias_periodo" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-Ganancias_periodo", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalGanancias_periodoEditar(id) {
  resetFormGanancias_periodo();
  $("#Ganancias_periodo_modo").val("edit");
  $("#id_registroGanancias_periodo").val(id);
  $("#modalCargarGanancias_periodo .modal-title").text(
    "| EDITAR REGISTRO DE PERÍODO FISCAL - GANANCIAS"
  );
  $("#guardarRegistroGanancias_periodo").text("ACTUALIZAR");
  $("#modalCargarGanancias_periodo").modal("show");

  $.getJSON(
    "/documentosContables/llenarGanancias_periodoEdit/" + id,
    function (d) {
      $("#casinoGanancias_periodo").val(d.casino).trigger("change");

      var year = String(d.periodo || "").slice(0, 4);
      $(
        '#fechaGanancias_periodoPres input[name="fecha_Ganancias_periodoPres"]'
      ).val(year);

      var ymd = String(d.fecha_pres || "").slice(0, 10);
      $(
        '#fecha_pres_Ganancias_periodo input[name="fecha_pres_Ganancias_periodo"]'
      )
        .val(ymd)
        .trigger("input")
        .trigger("change");

      $('input[name="saldo_Ganancias_periodo"]').val(d.saldo ?? "");
      $('[name="forma_pago_Ganancias_periodo"]').val(d.forma_pago ?? "");
      $('[name="obs_Ganancias_periodo"]').val(d.obs || "");
    }
  ).fail(function (xhr) {
    console.error("[Ganancias_periodo] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el período de Ganancias.");
  });
}

function resetFormGanancias_periodo() {
  var $f = $("#formNuevoRegistroGanancias_periodo");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameGanancias_periodo").val("");
    $("#uploadGanancias_periodo").val("");
  $("#uploadsGanancias_periodoContainer").empty();
  $("#uploadsGanancias_periodoTable tbody").empty();
  $("#uploadsGanancias_periodoWrap").hide();
  $("#fileNameGanancias_periodo").val("");
}

function abrirModalGanancias_periodoCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormGanancias_periodo();
  $("#Ganancias_periodo_modo").val("create");
  $("#id_registroGanancias_periodo").val("");
  $("#modalCargarGanancias_periodo .modal-title").text(
    "| NUEVO REGISTRO DE PERÍODO DE GANANCIAS"
  );
  $("#guardarRegistroGanancias_periodo").text("GENERAR");
  $("#modalCargarGanancias_periodo").modal("show");
}

$(document).on("click", "#Ganancias_periodo_nuevo", function () {
  abrirModalGanancias_periodoCrear();
});

$(document).on("click", ".btn-edit-Ganancias_periodo", function () {
  var id = $(this).data("id");
  abrirModalGanancias_periodoEditar(id);
});

$(document).on("click", "#guardarRegistroGanancias_periodo", function (e) {
  var $form = $("#formNuevoRegistroGanancias_periodo");
  let valid = true;
  var id = $("#id_registroGanancias_periodo").val() || "";
  var modo = $("#Ganancias_periodo_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoGanancias_periodo']",
    ".col-md-6",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_Ganancias_periodoPres']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='saldo_Ganancias_periodo']",
    ".col-md-6",
    "El saldo es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_pres_Ganancias_periodo']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsGanancias_periodoContainer input[type="file"][name="uploadGanancias_periodo[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadGanancias_periodo[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadGanancias_periodo");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadGanancias_periodo[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarGanancias_periodo/" + id
      : "/documentosContables/guardarGanancias_periodo";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarGanancias_periodo({
        page: $("#herramientasPaginacionGanancias_periodo").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionGanancias_periodo").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarGanancias_periodo").modal("hide"), 1000);
      resetFormGanancias_periodo();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function cargarGanancias({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasGanancias",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaGanancias").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaGanancias").append(generarFilaGanancias(item));
      });

      $("#herramientasPaginacionGanancias").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias
      );
      $("#herramientasPaginacionGanancias").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias
      );
    },
    error(err) {
      console.error("Error cargando Ganancias:", err);
    },
  });
}
function cargarGanancias_periodo({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasGanancias_periodo",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaGanancias_periodo").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaGanancias_periodo").append(
          generarFilaGanancias_periodo(item)
        );
      });

      $("#herramientasPaginacionGanancias_periodo").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias_periodo
      );
      $("#herramientasPaginacionGanancias_periodo").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceGanancias_periodo
      );
    },
    error(err) {
      console.error("Error cargando Ganancias_periodo:", err);
    },
  });
}

function clickIndiceGanancias(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarGanancias({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function clickIndiceGanancias_periodo(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarGanancias_periodo({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}
function generarFilaGanancias(Ganancias, controlador) {
  const fila = $("<tr>").attr("id", Ganancias.id_registroGanancias);

  const pres = Ganancias.periodo || "-";
  const casino = Ganancias.casino || "-";
  const anticipo = Ganancias.anticipo || "-";

  fila
    .append($("<td>").addClass("col-xs-3").text(pres))
    .append($("<td>").addClass("col-xs-3").text(anticipo))

    .append($("<td>").addClass("col-xs-3").text(casino));
  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");


  // Check if this removal is correct pattern
  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-Ganancias")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", Ganancias.id_registroGanancias)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteGanancias")
    .attr("id", Ganancias.id_registroGanancias)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO GANANCIAS")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      Ganancias.valido,
      "ganancias",
      Ganancias.id_registroGanancias
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteGanancias", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarGanancias").attr("data-id", id);
  $("#modalEliminarGanancias").modal("show");
});

$("#btn-eliminarGanancias").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarGanancias/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarGanancias").modal("hide");
        cargarGanancias({
          page: $("#herramientasPaginacionGanancias").getCurrentPage(),
          perPage: $("#herramientasPaginacionGanancias").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

function generarFilaGanancias_periodo(Ganancias_periodo, controlador) {
  const fila = $("<tr>").attr(
    "id",
    Ganancias_periodo.id_registroGanancias_periodo
  );

  const pres = Ganancias_periodo.periodo || "-";
  const casino = Ganancias_periodo.casino || "-";
  const file = Ganancias_periodo.archivo;

  fila
    .append($("<td>").addClass("col-xs-4").text(pres))

    .append($("<td>").addClass("col-xs-4").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-4 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-Ganancias_periodo")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", Ganancias_periodo.id_registroGanancias_periodo)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteGanancias_periodo")
    .attr("id", Ganancias_periodo.id_registroGanancias_periodo)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO PERIODO FISCAL")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(obtenerIconoValidacion(Ganancias_periodo.valido, 'ganancias_periodo', Ganancias_periodo.id_registroGanancias_periodo));

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteGanancias_periodo", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarGanancias_periodo").attr("data-id", id);
  $("#modalEliminarGanancias_periodo").modal("show");
});

$("#btn-eliminarGanancias_periodo").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarGanancias_periodo/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarGanancias_periodo").modal("hide");
        cargarGanancias_periodo({
          page: $("#herramientasPaginacionGanancias_periodo").getCurrentPage(),
          perPage: $("#herramientasPaginacionGanancias_periodo").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarGananciasCsvAnticipos").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarGanancias .has-error").removeClass("has-error");
  $("#collapseDescargarGanancias .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarGananciasCsvAnticipos?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarGananciasCsvPeriodos").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarGanancias .has-error").removeClass("has-error");
  $("#collapseDescargarGanancias .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarGananciasCsvPeriodos?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

$("#btn-descargarGananciasExcel").on("click", function (e) {
  $("#collapseDescargarGanancias .has-error").removeClass("has-error");
  $("#collapseDescargarGanancias .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarGananciasXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarGananciasXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

//Jackpots PAGADOS

instalarNumeroFlexibleAR("#importe_JackpotsPagados", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosJackpotsPagadosLista(id, target) {
  var $list = $(targetArchivosDoc("JackpotsPagados", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosJackpotsPagados/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/JackpotsPagados/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="JackpotsPagados"  class="btn btn-sm btn-danger btn-del-archivo-JackpotsPagados" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-JackpotsPagados", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalJackpotsPagadosEditar(id) {
  resetFormJackpotsPagados();
  $("#JackpotsPagados_modo").val("edit");
  $("#id_registroJackpotsPagados").val(id);
  $("#modalCargarJackpotsPagados .modal-title").text(
    "| EDITAR REGISTRO DE JACKPOTS PAGADOS"
  );
  $("#guardarRegistroJackpotsPagados").text("ACTUALIZAR");
  $("#modalCargarJackpotsPagados").modal("show");

  $.getJSON(
    "/documentosContables/llenarJackpotsPagadosEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      $('#fechaJackpotsPagados input[name="fecha_JackpotsPagados"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoJackpotsPagados").val(d.casino).trigger("change");
      $('input[name="importe_JackpotsPagados"]').val(
        formatoAR(d.importe ?? "")
      );
    }
  ).fail(function (xhr) {
    console.error("[JackpotsPagados] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormJackpotsPagados() {
  var $f = $("#formNuevoRegistroJackpotsPagados");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameJackpotsPagados").val("");
    $("#uploadJackpotsPagados").val("");
  $("#uploadsJackpotsPagadosContainer").empty();
  $("#uploadsJackpotsPagadosTable tbody").empty();
  $("#uploadsJackpotsPagadosWrap").hide();
  $("#fileNameJackpotsPagados").val("");
}

function abrirModalJackpotsPagadosCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormJackpotsPagados();
  $("#JackpotsPagados_modo").val("create");
  $("#id_registroJackpotsPagados").val("");
  $("#modalCargarJackpotsPagados .modal-title").text(
    "| NUEVO REGISTRO DE JACKPOTS PAGADOS"
  );
  $("#guardarRegistroJackpotsPagados").text("GENERAR");
  $("#modalCargarJackpotsPagados").modal("show");
}

$(document).on("click", "#JackpotsPagados_nuevo", function () {
  abrirModalJackpotsPagadosCrear();
});

$(document).on("click", ".btn-edit-JackpotsPagados", function () {
  var id = $(this).data("id");
  abrirModalJackpotsPagadosEditar(id);
});

$(document).on("click", "#guardarRegistroJackpotsPagados", function (e) {
  var $form = $("#formNuevoRegistroJackpotsPagados");
  let valid = true;
  var id = $("#id_registroJackpotsPagados").val() || "";
  var modo = $("#JackpotsPagados_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoJackpotsPagados']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_JackpotsPagados']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='importe_JackpotsPagados']",
    ".col-md-5",
    "El importe es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsJackpotsPagadosContainer input[type="file"][name="uploadJackpotsPagados[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadJackpotsPagados[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadJackpotsPagados");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadJackpotsPagados[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarJackpotsPagados/" + id
      : "/documentosContables/guardarJackpotsPagados";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarJackpotsPagados({
        page: $("#herramientasPaginacionJackpotsPagados").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionJackpotsPagados").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarJackpotsPagados").modal("hide"), 1000);
      resetFormJackpotsPagados();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceJackpotsPagados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarJackpotsPagados({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarJackpotsPagados({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasJackpotsPagados",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaJackpotsPagados").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaJackpotsPagados").append(
          generarFilaJackpotsPagados(item)
        );
      });

      $("#herramientasPaginacionJackpotsPagados").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceJackpotsPagados
      );
      $("#herramientasPaginacionJackpotsPagados").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceJackpotsPagados
      );
    },
    error(err) {
      console.error("Error cargando JackpotsPagados:", err);
    },
  });
}

function generarFilaJackpotsPagados(JackpotsPagados, controlador) {
  const fila = $("<tr>").attr("id", JackpotsPagados.id_registroJackpotsPagados);
  const fecha = convertirMesAno(JackpotsPagados.fecha_JackpotsPagados) || "-";
  const casino = JackpotsPagados.casino || "-";
  const importe = "$ " + formatoAR(JackpotsPagados.importe) || "-";

  fila
    .append($("<td>").addClass("col-xs-3").html(fecha))
    .append($("<td>").addClass("col-xs-3").text(casino))
    .append($("<td>").addClass("col-xs-3").text(importe));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-JackpotsPagados")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", JackpotsPagados.id_registroJackpotsPagados)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteJackpotsPagados")
    .attr("id", JackpotsPagados.id_registroJackpotsPagados)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR JACKPOT PAGADO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      JackpotsPagados.valido,
      "jackpots_pagados",
      JackpotsPagados.id_registroJackpotsPagados
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteJackpotsPagados", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarJackpotsPagados").attr("data-id", id);
  $("#modalEliminarJackpotsPagados").modal("show");
});

$("#btn-eliminarJackpotsPagados").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarJackpotsPagados/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarJackpotsPagados").modal("hide");
        cargarJackpotsPagados({
          page: $("#herramientasPaginacionJackpotsPagados").getCurrentPage(),
          perPage: $("#herramientasPaginacionJackpotsPagados").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarJackpotsPagadosExcel").on("click", function (e) {
  $("#collapseDescargarJackpotsPagados .has-error").removeClass("has-error");
  $("#collapseDescargarJackpotsPagados .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarJackpotsPagadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarJackpotsPagadosXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarJackpotsPagadosCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarJackpotsPagados .has-error").removeClass("has-error");
  $("#collapseDescargarJackpotsPagados .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarJackpotsPagadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//PREMIOS PAGADOS

instalarNumeroFlexibleAR("#importe_PremiosPagados", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosPremiosPagadosLista(id, target) {
  var $list = $(targetArchivosDoc("PremiosPagados", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPremiosPagados/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/PremiosPagados/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="PremiosPagados"  class="btn btn-sm btn-danger btn-del-archivo-PremiosPagados" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-PremiosPagados", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalPremiosPagadosEditar(id) {
  resetFormPremiosPagados();
  $("#PremiosPagados_modo").val("edit");
  $("#id_registroPremiosPagados").val(id);
  $("#modalCargarPremiosPagados .modal-title").text(
    "| EDITAR REGISTRO DE PREMIOS PAGADOS"
  );
  $("#guardarRegistroPremiosPagados").text("ACTUALIZAR");
  $("#modalCargarPremiosPagados").modal("show");

  $.getJSON(
    "/documentosContables/llenarPremiosPagadosEdit/" + id,
    function (d) {
      var ym = String(d.fecha || "").slice(0, 7);
      $('#fechaPremiosPagados input[name="fecha_PremiosPagados"]')
        .val(ym)
        .trigger("input")
        .trigger("change");
      $("#casinoPremiosPagados").val(d.casino).trigger("change");
      $('input[name="cant_PremiosPagados"]').val(d.cantidad ?? "");
      $('input[name="importe_PremiosPagados"]').val(formatoAR(d.importe ?? ""));
    }
  ).fail(function (xhr) {
    console.error("[PremiosPagados] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormPremiosPagados() {
  var $f = $("#formNuevoRegistroPremiosPagados");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNamePremiosPagados").val("");
    $("#uploadPremiosPagados").val("");
  $("#uploadsPremiosPagadosContainer").empty();
  $("#uploadsPremiosPagadosTable tbody").empty();
  $("#uploadsPremiosPagadosWrap").hide();
  $("#fileNamePremiosPagados").val("");
}

function abrirModalPremiosPagadosCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormPremiosPagados();
  $("#PremiosPagados_modo").val("create");
  $("#id_registroPremiosPagados").val("");
  $("#modalCargarPremiosPagados .modal-title").text(
    "| NUEVO REGISTRO DE PREMIOS PAGADOS"
  );
  $("#guardarRegistroPremiosPagados").text("GENERAR");
  $("#modalCargarPremiosPagados").modal("show");
}

$(document).on("click", "#PremiosPagados_nuevo", function () {
  abrirModalPremiosPagadosCrear();
});

$(document).on("click", ".btn-edit-PremiosPagados", function () {
  var id = $(this).data("id");
  abrirModalPremiosPagadosEditar(id);
});

$(document).on("click", "#guardarRegistroPremiosPagados", function (e) {
  var $form = $("#formNuevoRegistroPremiosPagados");
  let valid = true;
  var id = $("#id_registroPremiosPagados").val() || "";
  var modo = $("#PremiosPagados_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoPremiosPagados']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_PremiosPagados']",
    ".col-md-4",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='cant_PremiosPagados']"),
    ".col-md-5",
    "La cantidad es requerida.",
    valid
  );

  valid = validarCampoNumSiHayValor(
    $("input[name='importe_PremiosPagados']"),
    ".col-md-5",
    "El importe es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let val = p.value;
    if (["cant_PremiosPagados", "importe_PremiosPagados"].includes(p.name)) {
      val = parseNumeroFlexible(val)?.num ?? val;
    }
    fd.append(p.name, val);
  });

  $(
    '#uploadsPremiosPagadosContainer input[type="file"][name="uploadPremiosPagados[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadPremiosPagados[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadPremiosPagados");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadPremiosPagados[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarPremiosPagados/" + id
      : "/documentosContables/guardarPremiosPagados";
  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarPremiosPagados({
        page: $("#herramientasPaginacionPremiosPagados").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionPremiosPagados").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarPremiosPagados").modal("hide"), 1000);
      resetFormPremiosPagados();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndicePremiosPagados(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPremiosPagados({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPremiosPagados({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasPremiosPagados",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPremiosPagados").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaPremiosPagados").append(generarFilaPremiosPagados(item));
      });

      $("#herramientasPaginacionPremiosPagados").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosPagados
      );
      $("#herramientasPaginacionPremiosPagados").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosPagados
      );
    },
    error(err) {
      console.error("Error cargando PremiosPagados:", err);
    },
  });
}

function generarFilaPremiosPagados(PremiosPagados, controlador) {
  const fila = $("<tr>").attr("id", PremiosPagados.id_registroPremiosPagados);
  const fecha = convertirMesAno(PremiosPagados.fecha_PremiosPagados) || "-";
  const casino = PremiosPagados.casino || "-";
  const importe = "$ " + formatoAR(PremiosPagados.importe) || "-";
  const cantidad = PremiosPagados.cantidad || "-";

  fila
    .append($("<td>").addClass("col-xs-2").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($("<td>").addClass("col-xs-3").text(cantidad))
    .append($("<td>").addClass("col-xs-3").text(importe));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-PremiosPagados")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", PremiosPagados.id_registroPremiosPagados)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deletePremiosPagados")
    .attr("id", PremiosPagados.id_registroPremiosPagados)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR PREMIO PAGADO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      PremiosPagados.valido,
      "premios_pagados",
      PremiosPagados.id_registroPremiosPagados
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deletePremiosPagados", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarPremiosPagados").attr("data-id", id);
  $("#modalEliminarPremiosPagados").modal("show");
});

$("#btn-eliminarPremiosPagados").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarPremiosPagados/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarPremiosPagados").modal("hide");
        cargarPremiosPagados({
          page: $("#herramientasPaginacionPremiosPagados").getCurrentPage(),
          perPage: $("#herramientasPaginacionPremiosPagados").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

//no se usa
$("#btn-descargarPremiosPagadosExcel").on("click", function (e) {
  $("#collapseDescargarPremiosPagados .has-error").removeClass("has-error");
  $("#collapseDescargarPremiosPagados .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarPremiosPagadosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPremiosPagadosXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPremiosPagadosCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarPremiosPagados .has-error").removeClass("has-error");
  $("#collapseDescargarPremiosPagados .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPremiosPagadosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//PREMIOS MTM

$(function () {
  instalarNumeroFlexibleAR(
    "#cancel_PremiosMTM, #jack_PremiosMTM, #progre_PremiosMTM",
    { decimales: 2, permitirNegativos: true }
  );
  instalarNumeroFlexibleAR(
    "#cancel_usd_PremiosMTM, #jack_usd_PremiosMTM, #progre_usd_PremiosMTM",
    { decimales: 2, permitirNegativos: true }
  );
  instalarNumeroFlexibleAR("#total_PremiosMTM, #total_usd_PremiosMTM", {
    decimales: 2,
    permitirNegativos: true,
  });

  instalarAutoSumaAR({
    sources: ["#cancel_PremiosMTM", "#jack_PremiosMTM", "#progre_PremiosMTM"],
    target: "#total_PremiosMTM",
    decimales: 2,
  });
  instalarAutoSumaAR({
    sources: [
      "#cancel_usd_PremiosMTM",
      "#jack_usd_PremiosMTM",
      "#progre_usd_PremiosMTM",
    ],
    target: "#total_usd_PremiosMTM",
    decimales: 2,
  });
});

// Redundant shown.bs.modal handler REMOVED to avoid confusion (merged into unified logic)
/*
$(document).on('shown.bs.modal', '#modalCargarPremiosMTM', function(){
  $('#cancel_PremiosMTM, #jack_PremiosMTM, #progre_PremiosMTM, ' +
    '#cancel_usd_PremiosMTM, #jack_usd_PremiosMTM, #progre_usd_PremiosMTM'
  ).trigger('input');
});
*/

function cargarArchivosPremiosMTMLista(id, target) {
  var $list = $(targetArchivosDoc("PremiosMTM", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPremiosMTM/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/PremiosMTM/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="PremiosMTM"  class="btn btn-sm btn-danger btn-del-archivo-PremiosMTM" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-PremiosMTM", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function clickIndicePremiosMTM(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPremiosMTM({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPremiosMTM({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasPremiosMTM_Unificado",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPremiosMTM").empty();
      res.registros.forEach((item) => {
        $("#cuerpoTablaPremiosMTM").append(generarFilaPremiosMTM(item));
      });

      $("#herramientasPaginacionPremiosMTM").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosMTM
      );
      $("#herramientasPaginacionPremiosMTM").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePremiosMTM
      );
    },
    error(err) {
      console.error("Error cargando PremiosMTM:", err);
    },
  });
}

function generarFilaPremiosMTM(reg) {
  const fila = $("<tr>").data("fecha", reg.fecha).data("casino", reg.casino);
  const fecha = convertirMesAno(reg.fecha) || "-";
  const casino = reg.casino_nombre || "-";

  fila
    .append($("<td>").addClass("col-xs-5").html(fecha))
    .append($("<td>").addClass("col-xs-5").text(casino));

  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  const btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-PremiosMTM_Unificado")
    .attr("data-fecha", reg.fecha)
    .attr("data-casino", reg.casino)
    .attr("data-toggle", "tooltip")
    .attr("title", "EDITAR PREMIOS")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);
  
  // La fila agrupa los 7 documentos del mes+casino, así que el borrado se identifica por mes+casino
  // y no por el id de Premios MTM: ese id viene vacío si el mes no tiene Premios MTM cargado.
  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm mr-1 btn-deletePremiosMTM")
    .attr("data-fecha", reg.fecha)
    .attr("data-casino", reg.casino)
    .attr("data-casino-nombre", casino)
    .attr("data-mes", fecha)
    .attr("data-toggle", "tooltip")
    .attr("title", "ELIMINAR REGISTROS DEL MES")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);
  tdAcc.append(
    obtenerIconoValidacion(reg.valido, "premios_mtm", reg.id_registroPremiosMTM)
  );
  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

// UNIFIED PRIZE LOGIC
const prizeSectionsByCasino = {
  1: [
    "sec_PromoTickets",
    "sec_Pozos",
    "sec_Jackpots",
    "sec_PremiosPagados",
    "sec_PagosMesas",
    "sec_RegistrosContables",
  ],
  2: [
    "sec_PromoTickets",
    "sec_Pozos",
    "sec_PremiosPagados",
    "sec_PagosMesas",
    "sec_RegistrosContables",
  ],
  3: [
    "sec_PromoTickets",
    "sec_Pozos",
    "sec_PremiosMTM",
    "sec_PagosMesas",
    "sec_RegistrosContables",
  ],
};

// CSV de cada pseudo-documento del unificado, con la MISMA clave de sección que usa
// prizeSectionsByCasino: así se bajan solo los documentos que ese casino carga, y no archivos
// vacíos de documentos que no le corresponden (ej. Jackpots en Santa Fe).
// (El Excel no está acá: va en un único archivo que arma el backend.)
const csvUnificadoPorSeccion = {
  sec_PremiosMTM: "descargarPremiosMTMCsv",
  sec_PromoTickets: "descargarPromoTicketsCsv",
  sec_Pozos: "descargarPozosAcumuladosLinkeadosCsv",
  sec_Jackpots: "descargarJackpotsPagadosCsv",
  sec_PremiosPagados: "descargarPremiosPagadosCsv",
  sec_PagosMesas: "descargarPagosMayoresMesasCsv",
  sec_RegistrosContables: "descargarRegistrosContablesCsv",
};

// CSV que corresponden al casino elegido. Con "Todos" (4) van los 7, porque entre los tres casinos
// se cargan todos los documentos.
function endpointsCsvUnificado(id_casino) {
  var secciones =
    String(id_casino) === "4" || !prizeSectionsByCasino[id_casino]
      ? Object.keys(csvUnificadoPorSeccion)
      : prizeSectionsByCasino[id_casino];

  return secciones
    .map(function (sec) {
      return csvUnificadoPorSeccion[sec];
    })
    .filter(Boolean);
}

// Apuestas deportivas: solo las llevan Santa Fe (2) y Rosario (3). A diferencia de
// prizeSectionsByCasino, esto no es una seccion entera sino dos campos adentro de
// Registros Contables. El espejo en el backend es casinoLlevaApuestasDeportivas().
const casinosConApuestasDeportivas = [2, 3];

function casinoLlevaApuestasDeportivas(id_casino) {
  return casinosConApuestasDeportivas.indexOf(parseInt(id_casino, 10)) !== -1;
}

function updateUnifiedSections(id_casino) {
  $(".prize-section").hide();
  if (id_casino && prizeSectionsByCasino[id_casino]) {
    prizeSectionsByCasino[id_casino].forEach((secId) => {
      $("#" + secId).show();
    });
  }

  // Campos que dependen del casino pero no son una seccion completa.
  if (casinoLlevaApuestasDeportivas(id_casino)) {
    $(".campo-apuestas-deportivas").show();
  } else {
    // Se vacian al ocultarlos para que no viaje el valor de un casino en otro.
    // El change dispara el recalculo de Total y Total USD (son sources de la auto-suma).
    $(".campo-apuestas-deportivas").hide().find("input").val("").trigger("change");
  }
}

$(document).on("change", "#casino_Unificado", function () {
  updateUnifiedSections($(this).val());
});

$(document).on("click", "#PremiosMTM_nuevo", function () {
  $("#formNuevoRegistroPremiosMTM_Unificado")[0].reset();
  $(
    "#formNuevoRegistroPremiosMTM_Unificado input, #formNuevoRegistroPremiosMTM_Unificado select, #formNuevoRegistroPremiosMTM_Unificado textarea"
  ).attr("disabled", false);
  $("#obs_Unificado").val("");
  // Clear file containers and displays
  $(".containerUnified").empty();
  $(".wrapUnified").hide();
  $(".displayUnified").val("");

  $("#PremiosMTM_Unificado_modo").val("create");
  $("#modalCargarPremiosMTM_titulo").text("| NUEVO REGISTRO UNIFICADO");
  $("#guardarRegistroPremiosMTM_Unificado").text("GENERAR");
  $(".prize-section").hide();
  $("#modalCargarPremiosMTM").modal("show");
});

$(document).on("click", ".btn-deletePremiosMTM", function () {
  const $b = $(this);
  $("#btn-eliminarPremiosMTM")
    .attr("data-fecha", $b.attr("data-fecha"))
    .attr("data-casino", $b.attr("data-casino"));
  // Se aclara qué se va a borrar: son todos los documentos de ese mes y casino, no uno solo.
  $("#titulo-modal-eliminarPremiosMTM").text(
    "¿Seguro desea eliminar TODOS los registros contables y premios de " +
      ($b.attr("data-mes") || "") +
      " - " +
      ($b.attr("data-casino-nombre") || "") +
      "? Se borran también sus archivos adjuntos."
  );
  $("#modalEliminarPremiosMTM").modal("show");
});

// Auto-sumas para el modal unificado y formateo
$(function () {
  const fields = [
    "#cancel_PremiosMTM_Unified",
    "#jack_PremiosMTM_Unified",
    "#progre_PremiosMTM_Unified",
    "#cancel_usd_PremiosMTM_Unified",
    "#jack_usd_PremiosMTM_Unified",
    "#progre_usd_PremiosMTM_Unified",
    "#mtm_pesos_RegistrosContables_Unified",
    "#mp_pesos_RegistrosContables_Unified",
    "#bingo_RegistrosContables_Unified",
    "#jol_RegistrosContables_Unified",
    "#mtm_usd_RegistrosContables_Unified",
    "#mp_usd_RegistrosContables_Unified",
    "#apuestas_RegistrosContables_Unified",
    "#apuestas_usd_RegistrosContables_Unified",
    // Totales (Enabled, but auto-calculated)
    "#total_PremiosMTM_Unified",
    "#total_usd_PremiosMTM_Unified",
    "#total_RegistrosContables_Unified",
    "#total_usd_RegistrosContables_Unified",
    // Otros campos numericos
    "#importe_PromoTickets_Unified",
    "#importe_usd_PromoTickets_Unified",
    "#importe_Pozos_Unified",
    "#importe_Jackpots_Unified",
    "#importe_usd_Jackpots_Unified",
    "#importe_PremiosPagados_Unified",
    "#importe_usd_PremiosPagados_Unified",
    "#importe_PagosMesas_Unified",
    "#importe_usd_PagosMesas_Unified",
  ];
  fields.forEach((f) =>
    instalarNumeroFlexibleAR(f, { decimales: 2, permitirNegativos: true })
  );

  // Premios MTM
  instalarAutoSumaAR({
    sources: [
      "#cancel_PremiosMTM_Unified",
      "#jack_PremiosMTM_Unified",
      "#progre_PremiosMTM_Unified",
    ],
    target: "#total_PremiosMTM_Unified",
    decimales: 2,
  });
  instalarAutoSumaAR({
    sources: [
      "#cancel_usd_PremiosMTM_Unified",
      "#jack_usd_PremiosMTM_Unified",
      "#progre_usd_PremiosMTM_Unified",
    ],
    target: "#total_usd_PremiosMTM_Unified",
    decimales: 2,
  });
  // Registros Contables
  instalarAutoSumaAR({
    sources: [
      "#mtm_pesos_RegistrosContables_Unified",
      "#mp_pesos_RegistrosContables_Unified",
      "#bingo_RegistrosContables_Unified",
      "#jol_RegistrosContables_Unified",
      "#apuestas_RegistrosContables_Unified",
    ],
    target: "#total_RegistrosContables_Unified",
    decimales: 2,
  });
  instalarAutoSumaAR({
    sources: [
      "#mtm_usd_RegistrosContables_Unified",
      "#mp_usd_RegistrosContables_Unified",
      "#apuestas_usd_RegistrosContables_Unified",
    ],
    target: "#total_usd_RegistrosContables_Unified",
    decimales: 2,
  });
});

// Manejo de Archivos Unificado (Pattern: PromoTickets/Aportes)
$(document).on("click", ".btnPickUnified", function () {
  const mod = $(this).data("module");
  $("#upload_" + mod).click();
});

$(document).on("change", ".inputUnified", function () {
  if (!this.files || !this.files.length) return;
  const mod = $(this).closest(".prize-section").attr("id").replace("sec_", "");
  const $inputGroup = $(this).closest(".input-group");
  const gid =
    "lote_" +
    (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));

  $(this)
    .attr("data-group", gid)
    .removeAttr("id")
    .addClass("archived")
    .appendTo("#container_" + mod);
  renderUnifiedUploads(mod);

  const $new = $(
    '<input type="file" class="inputUnified" id="upload_' +
      mod +
      '" name="upload' +
      mod +
      '[]" multiple style="display:none;">'
  );
  $inputGroup.append($new);
});

function renderUnifiedUploads(mod) {
  const $tbody = $("#table_" + mod + " tbody").empty();
  let totalFiles = 0,
    rowIdx = 1;

  $("#container_" + mod + " input[type=file]").each(function () {
    const $inp = $(this);
    const gid = $inp.attr("data-group");
    const files = this.files || [];
    for (let i = 0; i < files.length; i++) {
      totalFiles++;
      const f = files[i];
      const $tr = $("<tr></tr>");
      $tr.append(`<td>${rowIdx++}</td>`);
      $tr.append(`<td>${f.name}</td>`);
      $tr.append(`<td>${(f.size / 1024).toFixed(1)} KB</td>`);
      const $btn = $(
        `<button type="button" class="btn btn-xs btn-danger" title="Quitar"><i class="fa fa-trash"></i></button>`
      )
        .attr("data-module", mod)
        .attr("data-group", gid)
        .attr("data-idx", i);
      $tr.append($("<td></td>").append($btn));
      $tbody.append($tr);
    }
  });

  $("#wrap_" + mod).toggle(totalFiles > 0);
  $("#display_" + mod).val(
    totalFiles
      ? totalFiles + " archivo(s) seleccionado(s)"
      : ""
  );
}

$(document).on("click", ".wrapUnified .btn-danger", function () {
  const mod = $(this).attr("data-module");
  const gid = $(this).attr("data-group");
  const idx = parseInt($(this).attr("data-idx"), 10);
  const $inp = $(`#container_${mod} input[data-group="${gid}"]`);

  if (!$inp.length) return;
  const dt = new DataTransfer();
  const files = $inp[0].files;
  for (let i = 0; i < files.length; i++) {
    if (i !== idx) dt.items.add(files[i]);
  }
  $inp[0].files = dt.files;
  if ($inp[0].files.length === 0) $inp.remove();
  renderUnifiedUploads(mod);
});

$(document).on("shown.bs.modal", "#modalCargarPremiosMTM", function () {
  // Trigger sums explicitly on open for all unified fields
  /*
  $(
    "#cancel_PremiosMTM_Unified, #jack_PremiosMTM_Unified, #progre_PremiosMTM_Unified, " +
      "#cancel_usd_PremiosMTM_Unified, #jack_usd_PremiosMTM_Unified, #progre_usd_PremiosMTM_Unified, " +
      "#mtm_pesos_RegistrosContables_Unified, #mp_pesos_RegistrosContables_Unified, #bingo_RegistrosContables_Unified, #jol_RegistrosContables_Unified, " +
      "#mtm_usd_RegistrosContables_Unified, #mp_usd_RegistrosContables_Unified, " +
      "#cant_PromoTickets_Unified, #importe_PromoTickets_Unified, " +
      "#importe_Pozos_Unified, " +
      "#importe_Jackpots_Unified, #importe_usd_Jackpots_Unified, " +
      "#cant_PremiosPagados_Unified, #importe_PremiosPagados_Unified, #importe_usd_PremiosPagados_Unified, " +
      "#cant_PagosMesas_Unified, #importe_PagosMesas_Unified, #importe_usd_PagosMesas_Unified"
  ).trigger("input");
  */
});

// Helper to clear all inputs/files in sections (triggered on casino change or new)
function clearUnifiedModalInputs() {
  $("#formNuevoRegistroPremiosMTM_Unificado")
    .find("input[type=text], input[type=hidden], select")
    .not("#PremiosMTM_Unificado_modo")
    .val("");
  // Clear file containers
  $(".containerUnified").empty();
  $(".wrapUnified").hide();
  $(".displayUnified").val("");
  // Clear validation errors
  $("#formNuevoRegistroPremiosMTM_Unificado")
    .find(".has-error")
    .removeClass("has-error");
  $("#formNuevoRegistroPremiosMTM_Unificado")
    .find(".help-block.js-error")
    .remove();
}

// Clear on Casino Change
$(document).on("click", "#guardarRegistroPremiosMTM_Unificado", function () {
  $("#fecha_Unificado, #casino_Unificado").attr("disabled", false); // Re-enable primary keys BEFORE FormData
  const formData = new FormData($("#formNuevoRegistroPremiosMTM_Unificado")[0]);

  $("#PremiosMTM_Unificado_modo").val("create");
  $("#modalCargarPremiosMTM_titulo").text("| CARGAR PREMIOS MTM");
  $("#guardarRegistroPremiosMTM_Unificado").show();

  // Reset file/view buttons
  $(".file-group-unified").show();
  $("#fechaPremiosMTM_Unificado").show();

  // Clean errors
  $(".has-error").removeClass("has-error");
  $(".help-block.js-error").remove();

  let valid = true;
  // Validacion legacy strict: validarCampo / validarCampoNum
  valid = validarCampo(
    "#fecha_Unificado",
    ".col-md-4",
    "Campo requerido",
    valid
  ); // Assuming .col-md-4 is the parent for error display
  valid = validarCampo(
    "#casino_Unificado",
    ".col-md-4",
    "Campo requerido",
    valid
  ); // Assuming .col-md-4 is the parent for error display

  if (!valid) return;

  // Manual Parsing for all numeric fields
  // Using _smartParseNum logic implied by instalarNumeroFlexibleAR but we do it explicitly before sending if needed,
  // or rely on backend. But user asked "parsear los numeros para poder usarlos en el back".
  // So we manually replace the values in formData with parsed versions.

  const numericFields = [
    "PremiosMTM[cancel]",
    "PremiosMTM[cancel_usd]",
    "PremiosMTM[progresivos]",
    "PremiosMTM[progresivos_usd]",
    "PremiosMTM[jackpots]",
    "PremiosMTM[jackpots_usd]",
    "PremiosMTM[total]",
    "PremiosMTM[total_usd]",
    "PromoTickets[cantidad]",
    "PromoTickets[importe]",
    "PromoTickets[importe_usd]",
    "Pozos[importe]",
    "Jackpots[importe]",
    "Jackpots[importe_usd]",
    "PremiosPagados[cantidad]",
    "PremiosPagados[importe]",
    "PremiosPagados[importe_usd]",
    "PagosMesas[cantidad]",
    "PagosMesas[importe]",
    "PagosMesas[importe_usd]",
    "RegistrosContables[mtm_pesos]",
    "RegistrosContables[mtm_dolares]",
    "RegistrosContables[mp_pesos]",
    "RegistrosContables[mp_dolares]",
    "RegistrosContables[bingo]",
    "RegistrosContables[juego_online]",
    "RegistrosContables[apuestas_deportivas]",
    "RegistrosContables[apuestas_deportivas_usd]",
    "RegistrosContables[total]",
    "RegistrosContables[total_usd]",
  ];

  // Validate numeric fields (Optional but must be numeric if present)
  numericFields.forEach(function (name) {
    const $input = $(`[name="${name}"]`);
    if ($input.length && $input.is(":visible")) {
      valid = validarCampoNumSiHayValor(
        $input,
        $input.closest(".col-xs-6, .col-md-3, .col-md-2, .col-md-4"),
        "Debe ser numérico",
        valid
      );
    }
  });

  if (!valid) return;

  // We need to iterate over form elements to find these names and parse them
  // Because FormData is immutable-ish in some browsers/contexts or we want to ensure we send parsed data.
  // actually we can append normalized data.

  $("#formNuevoRegistroPremiosMTM_Unificado")
    .find("input")
    .each(function () {
      const name = $(this).attr("name");
      if (numericFields.includes(name)) {
        const val = $(this).val();
        if (val) {
          // Parse ARG -> Float
          let parsed = val;
          if (typeof parsed === "string") {
            parsed = parsed.replace(/\./g, "").replace(",", ".");
          }
          formData.set(name, parsed);
        }
      }
    });

  $.ajax({
    type: "POST",
    url: "/documentosContables/guardarPremiosMTM_Unificado",
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      $("#modalCargarPremiosMTM").modal("hide");
      $("#mensajeExito h3").text("ÉXITO");
      $("#mensajeExito p").text("Los datos han sido guardados correctamente.");
      $("#mensajeExito").show();
      // Refresh current table logic
          },
    error: function (data) {
      const response = data.responseJSON;
      if (response.errors) {
        for (const key in response.errors) {
          // Simplified error mapping if keys match input names or similar
          // This part might need specific mapping if backend keys differ from input names
          const $input = $(`[name="${key}"]`);
          if ($input.length > 0) {
            mostrarErrorValidacion($input, response.errors[key][0], true);
          }
        }
      }
    },
  });
});

$(document).on(
  "click",
  ".btn-edit-PremiosMTM_Unificado",
  function () {
    // El botón "ver" se quitó de la tabla: ahora se entra siempre por el de editar. En registros
    // contables y premios se validan todos juntos, así que el estado de la fila gobierna todo el
    // modal: si está validado se abre en el modo de solo lectura que ya tenía el unificado.
    const isView =
      docContableValidado($(this));
    $("#modalCargarPremiosMTM").toggleClass("doc-solo-lectura", isView);
    const fecha = $(this).data("fecha");
    const casino = $(this).data("casino");

    $("#formNuevoRegistroPremiosMTM_Unificado")[0].reset();
    $("#guardarRegistroPremiosMTM_Unificado").text("ACTUALIZAR");
    $("#fecha_Unificado").val(fecha.substring(0, 7)).trigger("change");
    $("#casino_Unificado").val(casino).trigger("change");

    $("#PremiosMTM_Unificado_modo").val(isView ? "view" : "edit");
    $("#modalCargarPremiosMTM_titulo").text(
      isView ? "| VER REGISTROS UNIFICADOS" : "| EDITAR REGISTRO UNIFICADO"
    );
    $("#guardarRegistroPremiosMTM_Unificado").toggle(!isView);

    // Toggle File Inputs / Buttons
    if (isView) {
      $(".file-group-unified").hide();
      $("#fechaPremiosMTM_Unificado").show(); // Ensure date is visible
    } else {
      $(".file-group-unified").show();
      $("#fechaPremiosMTM_Unificado").show();
    }


    $.getJSON(
      "/documentosContables/llenarPremiosMTM_Unificado",
      { fecha: fecha.substring(0, 7), casino: casino },
      function (res) {

        // Fill all forms
        if (res.PremiosMTM) {
          const d = res.PremiosMTM;
          $('[name="PremiosMTM[cancel]"]').val(formatoAR(d.cancel));
          $('[name="PremiosMTM[cancel_usd]"]').val(formatoAR(d.cancel_usd));
          $('[name="PremiosMTM[progresivos]"]').val(formatoAR(d.progresivos));
          $('[name="PremiosMTM[progresivos_usd]"]').val(
            formatoAR(d.progresivos_usd)
          );
          $('[name="PremiosMTM[jackpots]"]').val(formatoAR(d.jackpots));
          $('[name="PremiosMTM[jackpots_usd]"]').val(formatoAR(d.jackpots_usd));
          $('[name="PremiosMTM[total]"]').val(formatoAR(d.total));
          $('[name="PremiosMTM[total_usd]"]').val(formatoAR(d.total_usd));
          if (d.id_registroPremiosMTM)
            $("#id_registroPremiosMTM_Unified").val(d.id_registroPremiosMTM);
        }
        if (res.PromoTickets) {
          const d = res.PromoTickets;
          $('[name="PromoTickets[cantidad]"]').val(d.cantidad);
          $('[name="PromoTickets[importe]"]').val(formatoAR(d.importe));
          $('[name="PromoTickets[importe_usd]"]').val(formatoAR(d.importe_usd));
          if (d.id_registroPromoTickets)
            $("#id_registroPromoTickets_Unified").val(
              d.id_registroPromoTickets
            );
        }
        if (res.Pozos) {
          const d = res.Pozos;
          $('[name="Pozos[importe]"]').val(formatoAR(d.importe));
          if (d.id_registroPozosAcumuladosLinkeados)
            $("#id_registroPozos_Unified").val(
              d.id_registroPozosAcumuladosLinkeados
            );
        }
        if (res.Jackpots) {
          const d = res.Jackpots;
          $('[name="Jackpots[importe]"]').val(formatoAR(d.importe));
          $('[name="Jackpots[importe_usd]"]').val(formatoAR(d.importe_usd));
          if (d.id_registroJackpotsPagados)
            $("#id_registroJackpots_Unified").val(d.id_registroJackpotsPagados);
        }
        if (res.PremiosPagados) {
          const d = res.PremiosPagados;
          $('[name="PremiosPagados[cantidad]"]').val(d.cantidad);
          $('[name="PremiosPagados[importe]"]').val(formatoAR(d.importe));
          $('[name="PremiosPagados[importe_usd]"]').val(
            formatoAR(d.importe_usd)
          );
          if (d.id_registroPremiosPagados)
            $("#id_registroPremiosPagados_Unified").val(
              d.id_registroPremiosPagados
            );
        }
        if (res.PagosMesas) {
          const d = res.PagosMesas;
          $('[name="PagosMesas[cantidad]"]').val(d.cant_pagos); // DB column alias
          $('[name="PagosMesas[importe]"]').val(formatoAR(d.importe_pesos)); // DB column alias
          $('[name="PagosMesas[importe_usd]"]').val(formatoAR(d.importe_usd));
          if (d.id_registroPagosMayoresMesas)
            $("#id_registroPagosMesas_Unified").val(
              d.id_registroPagosMayoresMesas
            );
        }
        if (res.RegistrosContables) {
          const d = res.RegistrosContables;
          $('[name="RegistrosContables[mtm_pesos]"]').val(formatoAR(d.mtm));
          $('[name="RegistrosContables[mtm_dolares]"]').val(
            formatoAR(d.mtm_usd)
          );
          $('[name="RegistrosContables[mp_pesos]"]').val(formatoAR(d.mp));
          $('[name="RegistrosContables[mp_dolares]"]').val(formatoAR(d.mp_usd));
          $('[name="RegistrosContables[bingo]"]').val(formatoAR(d.bingo));
          $('[name="RegistrosContables[juego_online]"]').val(formatoAR(d.jol));
          $('[name="RegistrosContables[apuestas_deportivas]"]').val(
            formatoAR(d.apuestas_deportivas)
          );
          $('[name="RegistrosContables[apuestas_deportivas_usd]"]').val(
            formatoAR(d.apuestas_deportivas_usd)
          );
          $('[name="RegistrosContables[total]"]').val(formatoAR(d.total));
          $('[name="RegistrosContables[total_usd]"]').val(
            formatoAR(d.total_usd)
          );
          if (d.id_registroRegistrosContables)
            $("#id_registroRegistrosContables_Unified").val(
              d.id_registroRegistrosContables
            );
        }
        // Observación de todo el registro (mes + casino), no de una sección.
        $("#obs_Unificado").val(
          res.Observaciones ? res.Observaciones.observaciones || "" : ""
        );

        // Trigger calculation updates (change event for auto-sum)
        $(
          "#cancel_PremiosMTM_Unified, #jack_PremiosMTM_Unified, #progre_PremiosMTM_Unified, " +
            "#cancel_usd_PremiosMTM_Unified, #jack_usd_PremiosMTM_Unified, #progre_usd_PremiosMTM_Unified, " +
            "#mtm_pesos_RegistrosContables_Unified, #mp_pesos_RegistrosContables_Unified, #bingo_RegistrosContables_Unified, #jol_RegistrosContables_Unified, " +
            "#mtm_usd_RegistrosContables_Unified, #mp_usd_RegistrosContables_Unified, " +
            "#apuestas_RegistrosContables_Unified, #apuestas_usd_RegistrosContables_Unified, " +
            "#cant_PromoTickets_Unified, #importe_PromoTickets_Unified, #importe_usd_PromoTickets_Unified, " +
            "#importe_Pozos_Unified, " +
            "#importe_Jackpots_Unified, #importe_usd_Jackpots_Unified, " +
            "#cant_PremiosPagados_Unified, #importe_PremiosPagados_Unified, #importe_usd_PremiosPagados_Unified, " +
            "#cant_PagosMesas_Unified, #importe_PagosMesas_Unified, #importe_usd_PagosMesas_Unified"
        ).trigger("change");
        if (isView) {
          $(
            "#formNuevoRegistroPremiosMTM_Unificado input, #formNuevoRegistroPremiosMTM_Unificado select, #formNuevoRegistroPremiosMTM_Unificado textarea"
          ).attr("disabled", true);
          // Hide file pickers and containers in View mode
          $(".file-group-unified").hide();
          $(".wrapUnified").hide();
          // Show "Ver Archivos" buttons
          $("#guardarRegistroPremiosMTM_Unificado").hide();
          // Ensure Date is visible
          $("#fechaPremiosMTM_Unificado").show();
          $("#fecha_Unificado").show().attr("disabled", true);
        } else {
          $(
            "#formNuevoRegistroPremiosMTM_Unificado input, #formNuevoRegistroPremiosMTM_Unificado select, #formNuevoRegistroPremiosMTM_Unificado textarea"
          ).attr("disabled", false);
          $("#fecha_Unificado, #casino_Unificado").attr("disabled", true); // Freeze primary keys on edit
          // Show file inputs
          $(".file-group-unified").show();
          $("#guardarRegistroPremiosMTM_Unificado").show();
          $("#fechaPremiosMTM_Unificado").show();
          $("#fecha_Unificado").show().attr("disabled", true);
        }

        // Archivos ya cargados de cada sub-documento, inline debajo de cada botón de subir.
        cargarArchivosUnificadoInline();

        $("#modalCargarPremiosMTM").modal("show");
      }
    );
  }
);

// Modal unificado: la lista de archivos de cada sub-documento se muestra inline, debajo de su
// botón de subir archivos (antes había un botón "VER ARCHIVOS" que abría un modal aparte).
// OJO: las claves son los nombres cortos (Pozos/Jackpots/PagosMesas), igual que la respuesta
// del backend y los contenedores del blade.
var MAP_ARCHIVOS_UNIFICADO = {
  PremiosMTM: { id: "#id_registroPremiosMTM_Unified", fn: "cargarArchivosPremiosMTMLista" },
  PromoTickets: { id: "#id_registroPromoTickets_Unified", fn: "cargarArchivosPromoTicketsLista" },
  Pozos: { id: "#id_registroPozos_Unified", fn: "cargarArchivosPozosAcumuladosLinkeadosLista" },
  Jackpots: { id: "#id_registroJackpots_Unified", fn: "cargarArchivosJackpotsPagadosLista" },
  PremiosPagados: { id: "#id_registroPremiosPagados_Unified", fn: "cargarArchivosPremiosPagadosLista" },
  PagosMesas: { id: "#id_registroPagosMesas_Unified", fn: "cargarArchivosPagosMayoresMesasLista" },
  RegistrosContables: { id: "#id_registroRegistrosContables_Unified", fn: "cargarArchivosRegistrosContablesLista" },
};

function cargarArchivosUnificadoInline() {
  Object.keys(MAP_ARCHIVOS_UNIFICADO).forEach(function (type) {
    var cfg = MAP_ARCHIVOS_UNIFICADO[type];
    var id = $(cfg.id).val();
    var fn = window[cfg.fn];
    var $wrap = $("#listaArchivosWrap_U_" + type);
    if (id && typeof fn === "function") {
      $wrap.show();
      fn(id, "#listaArchivos_U_" + type); // target explícito: contenedor del unificado
    } else {
      $("#listaArchivos_U_" + type).empty();
      $wrap.hide();
    }
  });
}

$("#btn-eliminarPremiosMTM").on("click", function () {
  const fecha = $(this).attr("data-fecha");
  const casino = $(this).attr("data-casino");
  if (!fecha || !casino) return;

  $.ajax({
    url: "/documentosContables/eliminarPremiosMTM_Unificado",
    method: "GET",
    data: { fecha: fecha, casino: casino },
  })
    .done((res) => {
      $("#modalEliminarPremiosMTM").modal("hide");
      if (res && res.success) {
        clickIndicePremiosMTM(null, $("#herramientasPaginacionPremiosMTM").getCurrentPage() || 1, 15);
      } else {
        $("#texto-aviso-validacion").text(
          (res && res.msg) || "No se pudieron eliminar los registros."
        );
        $("#modalAvisoValidacion").modal("show");
      }
    })
    .fail((xhr) => {
      $("#modalEliminarPremiosMTM").modal("hide");
      $("#texto-aviso-validacion").text(
        (xhr.responseJSON && xhr.responseJSON.msg) ||
          "No se pudieron eliminar los registros."
      );
      $("#modalAvisoValidacion").modal("show");
    });
});

$("#btn-descargarPremiosMTMExcel").on("click", function (e) {
  $("#collapseDescargarPremiosMTM .has-error").removeClass("has-error");
  $("#collapseDescargarPremiosMTM .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }
  if (!valid) return;

  // Un solo archivo, también para "Todos" (casino 4). Antes "Todos" disparaba 7 descargas sueltas
  // con iframes y el navegador bloqueaba todas menos la primera, así que parecía que bajaba un solo
  // documento. Ahora el backend arma un único Excel con una solapa por documento y casino.
  let url = `/documentosContables/descargarPremiosMTMUnificadoXlsx?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;
  window.location.href = url;
});

$("#btn-descargarPremiosMTMCsv").on("click", function () {
  $("#collapseDescargarPremiosMTM .has-error").removeClass("has-error");
  $("#collapseDescargarPremiosMTM .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }
  if (!valid) return;

  // Solo los documentos que le corresponden al casino: antes bajaba los 7 siempre, así que traía
  // archivos vacíos de documentos que ese casino ni carga.
  let endpoints = endpointsCsvUnificado(casino);

  endpoints.forEach((endpoint, index) => {
    setTimeout(() => {
      let url = `/documentosContables/${endpoint}?casino=${casino}`;
      if (desde) url += `&desde=${desde}`;
      if (hasta) url += `&hasta=${hasta}`;

      let iframe = document.createElement("iframe");
      iframe.style.display = "none";
      iframe.src = url;
      document.body.appendChild(iframe);
    }, index * 1000); // 1 second stagger
  });
});

//AUT. DIRECTORES
$(document).on("click", "#AutDirectores_nuevo_director", function (e) {
  $("#modalCargarAutDirectores_director").modal("show");
});

function cargarArchivosAutDirectoresLista(id, target) {
  var $list = $(targetArchivosDoc("AutDirectores", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosAutDirectores/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/AutDirectores/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="AutDirectores"  class="btn btn-sm btn-danger btn-del-archivo-AutDirectores" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-AutDirectores", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalAutDirectoresEditar(id) {
  resetFormAutDirectores_autorizacion();
  $("#AutDirectores_modo").val("edit");
  $("#id_registroAutDirectores").val(id);
  $("#modalCargarAutDirectores_autorizacion .modal-title").text(
    "| EDITAR REGISTRO DE AUT. DIRECTORES"
  );
  $("#guardarRegistroAutDirectores_autorizacion").text("ACTUALIZAR");
  $("#modalCargarAutDirectores_autorizacion").modal("show");

  $.getJSON("/documentosContables/llenarAutDirectoresEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);
    $('input[name="fecha_AutDirectores_autorizacion"]')
      .val(ym)
      .trigger("input");
    $("#casinoAutDirectores_autorizacion").val(d.casino);

    var html = (d.directores || []).map(filaDirectorAutDir).join("");
    $("#zona-directores").html(
      html ||
        '<div class="col-md-12 text-muted">Sin directores asociados.</div>'
    );
    // En edición el combo no se refresca solo (el handler del casino corta si el modo es edit).
    refrescarComboAgregarDirector();
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
    alert("No se pudo cargar el autónomo.");
  });
}

function resetFormAutDirectores_autorizacion() {
  var $f = $("#formNuevoRegistroAutDirectores_autorizacion");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameAutDirectores").val("");
    $("#zona-directores")
    .empty()
    .html(
      '<div class="col-md-12 text-muted">Elegí un casino para ver los directores.</div>'
    );
  $("#uploadAutDirectores").val("");
  $("#uploadsAutDirectoresContainer").empty();
  $("#uploadsAutDirectoresTable tbody").empty();
  $("#uploadsAutDirectoresWrap").hide();
  $("#fileNameAutDirectores").val("");
}

function abrirModalAutDirectoresCrear() {
  resetFormAutDirectores_autorizacion();
  $("#AutDirectores_modo").val("create");
  $("#id_registroAutDirectores").val("");
  $("#modalCargarAutDirectores .modal-title").text(
    "| NUEVO REGISTRO DE AUT. DIRECTORES"
  );
  $("#guardarRegistroAutDirectores_autorizacion").text("GENERAR");
  $("#modalCargarAutDirectores_autorizacion").modal("show");
}

$(document).on("click", "#AutDirectores_nuevo_autorizacion", function () {
  abrirModalAutDirectoresCrear();
});

$(document).on("click", ".btn-edit-AutDirectores", function () {
  var id = $(this).data("id");
  abrirModalAutDirectoresEditar(id);
});

$(document).on("click", "#AutDirectores_gestionar_directores", function () {
  $("#modalAutDirectores_gestionar_directores").modal("show");
  cargarAutDirectoresGestion();
});

function escapeHtml(s) {
  return (s || "").toString().replace(/[&<>"']/g, function (m) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[m];
  });
}

function renderFilaDirector(d) {
  var estado = d.habilitado
    ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
    : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';

  return (
    "" +
    '<tr data-nombre="' +
    d.nombre +
    '" data-cuit="' +
    d.cuit +
    '" data-estado="' +
    d.habilitado +
    '" data-id="' +
    d.id +
    '">' +
    '<td class="col-md-4">' +
    escapeHtml(d.nombre || "") +
    "</td>" +
    '<td class="col-md-4">' +
    escapeHtml(d.cuit || "-") +
    "</td>" +
    '<td class="col-md-1">' +
    escapeHtml(d.casino || "") +
    "</td>" +
    '<td class="text-center col-md-2 estado">' +
    estado +
    "</td>" +
    '<td class="col-md-1">' +
    '<button type="button" class="btn btn-sm btn-primary btn-editAutDirectores_director" title="MODIFICAR DIRECTOR">' +
    '<i class="fa fa-pencil-alt"></i>' +
    "</button> " +
    '<button type="button" class="btn btn-sm btn-danger btn-elimAutDirectores_director" title="ELIMINAR DIRECTOR">' +
    '<i class="fa fa-trash"></i>' +
    "</button>" +
    "</td>" +
    "</tr>"
  );
}

$(document).on(
  "click",
  "#guardarModifRegistroAutDirectores_director",
  function () {
    var $btn = $(this).prop("disabled", true);
    var $form = $("#formModificarRegistroAutDirectores_director");
    var id = $("#ModifId_AutDirectores_director").val();

    var valid = true;
    valid = validarCampo(
      "input[name='ModifAutDirectores_director_nombre']",
      ".col-md-4",
      "El nombre es requerido.",
      valid
    );
    valid = validarCampo(
      "input[name='ModifAutDirectores_director_cuit']",
      ".col-md-4",
      "El CUIT es requerido.",
      valid
    );

    if (!valid) {
      $btn.prop("disabled", false);
      return 0;
    }

    $.ajax({
      url: "/documentosContables/actualizarAutDirectores_director/" + id,
      method: "POST",
      data: $form.serialize(),
      success: function (res) {
        if (res && res.success) {
          cargarAutDirectoresGestion();

          $("#modalModificarAutDirectores_director").modal("hide");
        } else {
          alert("No se pudo guardar la modificación.");
        }
      },
      error: function (xhr) {
        console.error(xhr.responseText || xhr);
      },
      complete: function () {
        $btn.prop("disabled", false);
      },
    });
  }
);

$(document).on("click", ".btn-editAutDirectores_director", function () {
  const $tr = $(this).closest("tr");
  const id = $(this).closest("tr").data("id");
  const nombre = $(this).closest("tr").data("nombre");
  const cuit = $(this).closest("tr").data("cuit");

  const estado = $(this).closest("tr").data("estado");
  $("#ModifId_AutDirectores_director").val(id);
  $("#ModifAutDirectores_director_nombre").val(nombre);
  $("#ModifAutDirectores_director_cuit").val(cuit);
  $("#ModifAutDirectores_director_estado").val(estado);

  $("#modalModificarAutDirectores_director").modal("show");
});

function cargarAutDirectoresGestion(casinoId) {
  $("#dir-list-loading").show();
  $("#tabla-directores-AutDirectores").closest(".table-responsive").hide();
  $("#tabla-directores-AutDirectores tbody").empty();

  var url = "/documentosContables/getAutDirectores";

  $.getJSON(url, function (data) {
    var rows = "";
    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      rows += renderFilaDirector(d);
    }
    $("#tabla-directores-AutDirectores tbody").html(rows);
    $("#dir-list-loading").hide();
    $("#tabla-directores-AutDirectores").closest(".table-responsive").show();
  }).fail(function (xhr) {
    $("#dir-list-loading").text("Error cargando directores.");
    console.error(xhr.responseText);
  });
}

$(document).on("click", "#AutDirectoresHabilitar", function () {
  var $btn = $(this);
  var $tr = $btn.closest("tr");
  var id = $tr.data("id");

  $.get(
    "/documentosContables/AutDirectoresHabilitarDirector/" + id,
    function (res) {
      var habilitado = Number(res.habilitado) === 1;

      var estadoHtml = habilitado
        ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
        : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';

      $tr.find(".estado").html(estadoHtml);
      $btn
        .attr("title", habilitado ? "DESHABILITAR" : "HABILITAR")
        .attr("target", "_blank")
        .attr("data-toggle", "tooltip")
        .toggleClass("btn-success btn-danger")
        .html(
          habilitado
            ? '<i class="fa fa-times"></i>'
            : '<i class="fa fa-check"></i>'
        );
    }
  );
});

$(document).on("click", ".btn-elimAutDirectores_director", function () {
  const id = $(this).closest("tr").data("id");
  $("#btn-eliminarAutDirectores_director").attr("data-id", id);
  $("#modalEliminarAutDirectores_director").modal("show");
});

$(document).on("click", "#btn-eliminarAutDirectores_director", function () {
  const id = $(this).attr("data-id");
  const $tr = $('#tabla-directores-AutDirectores tr[data-id="' + id + '"]');

  $.get(
    "/documentosContables/AutDirectoresEliminarDirector/" + id,
    function (res) {
      if (res.ok) {
        $tr.remove();
        $("#modalEliminarAutDirectores_director").modal("hide");
      } else {
      }
    }
  ).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on("click", "#guardarRegistroAutDirectores_director", function (e) {
  var $form = $("#formNuevoRegistroAutDirectores_director");
  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoAutDirectores_director']",
    ".col-md-5",
    "El casino es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='nombre_AutDirectores_director']",
    ".col-md-5",
    "El nombre es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='cuit_AutDirectores_director']",
    ".col-md-5",
    "El C.U.I.T. es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: "/documentosContables/guardarAutDirectores_director",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarAutDirectores({
        page: $("#herramientasPaginacionAutDirectores").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionAutDirectores").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(
        () => $("#modalCargarAutDirectores_director").modal("hide"),
        1000
      );
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

$(document).on(
  "click",
  "#guardarRegistroAutDirectores_autorizacion",
  function (e) {
    var $form = $("#formNuevoRegistroAutDirectores_autorizacion");
    let valid = true;
    var id = $("#id_registroAutDirectores").val() || "";
    var modo = $("#AutDirectores_modo").val() || "create";
    $form.find(".has-error").removeClass("has-error");
    $form.find(".help-block.js-error").remove();

    valid = validarCampo(
      "select[name='casinoAutDirectores_autorizacion']",
      ".col-md-6",
      "El casino es requerido.",
      valid
    );
    valid = validarCampo(
      "input[name='fecha_AutDirectores_autorizacion']",
      ".col-md-6",
      "La fecha es requerida.",
      valid
    );

    if (!valid) return 0;

    let formElem = $form[0];
    let formData = new FormData(formElem);
    var fd = new FormData();

    $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

    $(
      '#uploadsAutDirectoresContainer input[type="file"][name="uploadAutDirectores[]"]'
    ).each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadAutDirectores[]", files[i]);
      }
    });

    var cur = document.getElementById("uploadAutDirectores");
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        fd.append("uploadAutDirectores[]", cur.files[j]);
      }
    }

    var url =
      modo === "edit"
        ? "/documentosContables/actualizarAutDirectores/" + id
        : "/documentosContables/guardarAutDirectores_autorizacion";

    $.ajax({
      url: url,
      method: "POST",
      data: fd,
      processData: false,
      contentType: false,
      success: function (res) {
        cargarAutDirectores({
          page: $("#herramientasPaginacionAutDirectores").getCurrentPage() || 1,
          perPage: $("#herramientasPaginacionAutDirectores").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
        setTimeout(
          () => $("#modalCargarAutDirectores_autorizacion").modal("hide"),
          1000
        );
        resetFormAutDirectores_autorizacion();
      },
      error: function (xhr) {
        $("#salir").next(".help-block.js-error").remove();
        $("#salir").after(
          '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
        );
        console.error("Error al guardar:", xhr);
      },
    });
  }
);

function clickIndiceAutDirectores(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarAutDirectores({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarAutDirectores({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasAutDirectores",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaAutDirectores").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaAutDirectores").append(generarFilaAutDirectores(item));
      });

      $("#herramientasPaginacionAutDirectores").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAutDirectores
      );
      $("#herramientasPaginacionAutDirectores").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceAutDirectores
      );
    },
    error(err) {
      console.error("Error cargando AutDirectores:", err);
    },
  });
}

function generarFilaAutDirectores(AutDirectores, controlador) {
  const fila = $("<tr>").attr("id", AutDirectores.id_registroAutDirectores);
  const fecha = convertirMesAno(AutDirectores.fecha_AutDirectores) || "-";
  const casino = AutDirectores.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-4").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-4 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-AutDirectores")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", AutDirectores.id_registroAutDirectores)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteAutDirectores")
    .attr("id", AutDirectores.id_registroAutDirectores)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR AUTÓNOMOS DE DIRECTORES")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      AutDirectores.valido,
      "aut_directores",
      AutDirectores.id_registroAutDirectores
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteAutDirectores", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarAutDirectores_autorizacion").attr("data-id", id);
  $("#modalEliminarAutDirectores_autorizacion").modal("show");
});

$("#btn-eliminarAutDirectores_autorizacion").on("click", function () {
  const id = $(this).attr("data-id");

  $.ajax({
    url: `/documentosContables/eliminarAutDirectores/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarAutDirectores_autorizacion").modal("hide");
        cargarAutDirectores({
          page: $("#herramientasPaginacionAutDirectores").getCurrentPage(),
          perPage: $("#herramientasPaginacionAutDirectores").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarAutDirectoresExcel").on("click", function (e) {
  $("#collapseDescargarAutDirectores .has-error").removeClass("has-error");
  $("#collapseDescargarAutDirectores .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarAutDirectoresXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarAutDirectoresXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarAutDirectoresCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarAutDirectores .has-error").removeClass("has-error");
  $("#collapseDescargarAutDirectores .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarAutDirectoresCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// SEGUROS

instalarNumeroFlexibleAR("#monto_Seguros", {
  decimales: 2,
  permitirNegativos: true,
});

$(document).on("click", "#Seguros_nuevo_tipo", function (e) {
  $("#modalCargarSeguros_tipo").modal("show");
});

function cargarArchivosSegurosLista(id, target) {
  var $list = $(targetArchivosDoc("Seguros", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosSeguros/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/Seguros/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="Seguros"  class="btn btn-sm btn-danger btn-del-archivo-Seguros" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-Seguros", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function abrirModalSegurosEditar(id) {
  resetFormSeguros();
  $("#Seguros_modo").val("edit");
  $("#id_registroSeguros").val(id);
  $("#modalCargarSeguros .modal-title").text("| EDITAR PÓLIZA DE SEGURO");
  $("#guardarRegistroSeguros").text("ACTUALIZAR");
  $("#modalCargarSeguros").modal("show");

  $.getJSON("/documentosContables/llenarSegurosEdit/" + id, function (d) {
    $("#fecha_SegurosDes")
      .val(String(d.desde || "").slice(0, 10))
      .trigger("input")
      .trigger("change");
    $("#fecha_SegurosHas")
      .val(String(d.hasta || "").slice(0, 10))
      .trigger("input")
      .trigger("change");
    $("#casinoSeguros").val(d.casino).trigger("change");
    $("#tipo_Seguros").val(d.tipo_id).trigger("change");
    $("#comp_Seguros").val(d.compania || "");
    $("#poliza_Seguros").val(d.poliza || "");
    $("#monto_Seguros").val(formatoAR(d.monto ?? ""));
    $("#estado_Seguros").val(d.estado ?? "");
    $('[name="obs_Seguros"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[SEGUROS] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro de Seguros.");
  });
}

function resetFormSeguros() {
  var $f = $("#formNuevoRegistroSeguros");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameSeguros").val("");
    $("#uploadSeguros").val("");
  $("#uploadsSegurosContainer").empty();
  $("#uploadsSegurosTable tbody").empty();
  $("#uploadsSegurosWrap").hide();
  $("#fileNameSeguros").val("");
}

function abrirModalSegurosCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormSeguros();
  $("#Seguros_modo").val("create");
  $("#id_registroSeguros").val("");
  $("#modalCargarSeguros .modal-title").text("| NUEVA PÓLIZA DE SEGURO");
  $("#guardarRegistroSeguros").text("GENERAR");
  $("#modalCargarSeguros").modal("show");
}

$(document).on("click", "#Seguros_nuevo", function () {
  abrirModalSegurosCrear();
});

$(document).on("click", ".btn-edit-Seguros", function () {
  var id = $(this).data("id");
  abrirModalSegurosEditar(id);
});

function cargarTiposSeguro(selectedId) {
  var $sel = $("#tipo_Seguros");

  $sel.empty();

  $.getJSON("/documentosContables/getSeguros_tipo", function (data) {
    $.each(data, function (_, t) {
      var id = t.id;
      var txt = t.tipo || "";
      $sel.append('<option value="' + id + '">' + txt + "</option>");
    });

    if (selectedId) $sel.val(String(selectedId));
  })
    .fail(function (xhr) {
      console.error(xhr.responseText || xhr);
      $sel.empty().append('<option value="">Error al cargar</option>');
    })
    .always(function () {
      $sel.prop("disabled", false);
    });
}

$(document).on("click", "#Seguros_tipo_gestionar", function () {
  $("#modalSeguros_tipo_gestionar").modal("show");
  cargarSeguros_tipoGestion();
});

function escapeAttr(s) {
  return (s || "")
    .toString()
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function renderFilaSeguros_tipo(d) {
  return (
    "" +
    '<tr data-id="' +
    d.id_registroSeguros_tipo +
    '" data-tipo="' +
    escapeAttr(d.tipo || "") +
    '">' +
    '<td class="col-md-9">' +
    escapeHtml(d.tipo || "") +
    "</td>" +
    '<td class="text-left col-md-3">' +
    '<button type="button" class="btn btn-sm btn-primary btn-editSeguros_tipo" title="MODIFICAR TIPO DE SEGURO">' +
    '<i class="fa fa-pencil-alt"></i>' +
    "</button> " +
    '<button type="button" class="btn btn-sm btn-danger btn-elimSeguros_tipo" title="ELIMINAR TIPO DE SEGURO">' +
    '<i class="fa fa-trash"></i>' +
    "</button>" +
    "</td>" +
    "</tr>"
  );
}

function cargarSeguros_tipoGestion(casinoId) {
  $("#dir-list-loading_seguros").show();
  $("#tabla-Seguros_tipo").closest(".table-responsive").hide();
  $("#tabla-Seguros_tipo tbody").empty();

  var url = "/documentosContables/getSeguros_tipo";

  $.getJSON(url, function (data) {
    var rows = "";
    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      rows += renderFilaSeguros_tipo(d);
    }
    $("#tabla-Seguros_tipo tbody").html(rows);
    $("#dir-list-loading_seguros").hide();
    $("#tabla-Seguros_tipo").closest(".table-responsive").show();
  }).fail(function (xhr) {
    $("#dir-list-loading_seguros").text("Error cargando tipos de seguro.");
    console.error(xhr.responseText);
  });
}

$(document).on("click", ".btn-elimSeguros_tipo", function () {
  const id = $(this).closest("tr").data("id");
  $("#btn-eliminarSeguros_tipo").attr("data-id", id);
  $("#modalEliminarSeguros_tipo").modal("show");
});

$(document).on("click", ".btn-editSeguros_tipo", function () {
  const id = $(this).closest("tr").data("id");
  const tipo = $(this).closest("tr").data("tipo");

  $("#guardarModifRegistroSeguros_tipo").attr("data-id", id);
  $("#ModifTipo_Seguros_tipo").val(tipo);
  $("#modalModificarSeguros_tipo").modal("show");
});

$(document).on("click", ".btn-editSeguros_tipo", function () {
  const $tr = $(this).closest("tr");
  const id = $tr.data("id");
  const tipo = $tr.find("td:first").text().trim();

  $("#ModifId_Seguros_tipo").val(id);
  $("#ModifTipo_Seguros_tipo").val(tipo);

  $("#modalModificarSeguros_tipo").modal("show");
});

$(document).on("click", "#guardarModifRegistroSeguros_tipo", function () {
  const $btn = $(this).prop("disabled", true);
  const $form = $("#formModificarRegistroSeguros_tipo");

  valid = true;
  valid = validarCampo(
    "input[name='ModifTipo_Seguros_tipo']",
    ".col-md-12",
    "El tipo es requerido.",
    valid
  );

  if (!valid) return 0;

  $.ajax({
    url: "/documentosContables/modificarSeguros_tipo",
    method: "POST",
    data: $form.serialize(),
    success: function (res) {
      cargarSeguros_tipoGestion();
      cargarTiposSeguro();
      $("#modalModificarSeguros_tipo").modal("hide");
    },
    error: function (xhr) {
      console.error(xhr.responseText || xhr);
    },
    complete: function () {
      $btn.prop("disabled", false);
    },
  });
});

$(document).on("click", "#btn-eliminarSeguros_tipo", function () {
  const id = $(this).attr("data-id");
  const $tr = $('#tabla-Seguros_tipo tr[data-id="' + id + '"]');

  $.get("/documentosContables/SegurosEliminarTipo/" + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $("#modalEliminarSeguros_tipo").modal("hide");
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on("click", "#guardarRegistroSeguros_tipo", function (e) {
  var $form = $("#formNuevoRegistroSeguros_tipo");
  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "input[name='tipo_Seguros_tipo']",
    ".col-md-12",
    "El tipo es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: "/documentosContables/guardarSeguros_tipo",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      setTimeout(() => $("#modalCargarSeguros_tipo").modal("hide"), 1000);
      cargarTiposSeguro();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

$(document).on("click", "#guardarRegistroSeguros", function (e) {
  var $form = $("#formNuevoRegistroSeguros");
  let valid = true;
  var id = $("#id_registroSeguros").val() || "";
  var modo = $("#Seguros_modo").val() || "create";
  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoSeguros']",
    ".col-md-6",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    "select[name='tipo_Seguros']",
    ".col-md-6",
    "El tipo es requerido.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_SegurosDes']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );
  valid = validarCampo(
    "input[name='fecha_SegurosHas']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='poliza_Seguros']",
    ".col-md-4",
    "La Póliza es requerida",
    valid
  );
  valid = validarCampoNum(
    "input[name='monto_Seguros']",
    ".col-md-4",
    "El monto es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='comp_Seguros']",
    ".col-md-4",
    "La compañía es requerida.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $('#uploadsSegurosContainer input[type="file"][name="uploadSeguros[]"]').each(
    function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        fd.append("uploadSeguros[]", files[i]);
      }
    }
  );

  var cur = document.getElementById("uploadSeguros");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadSeguros[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarSeguros/" + id
      : "/documentosContables/guardarSeguros";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarSeguros({
        page: $("#herramientasPaginacionSeguros").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionSeguros").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarSeguros").modal("hide"), 1000);
      resetFormSeguros();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceSeguros(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarSeguros({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarSeguros({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasSeguros",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaSeguros").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaSeguros").append(generarFilaSeguros(item));
      });

      $("#herramientasPaginacionSeguros").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceSeguros
      );
      $("#herramientasPaginacionSeguros").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceSeguros
      );
    },
    error(err) {
      console.error("Error cargando Seguros:", err);
    },
  });
}

function generarFilaSeguros(Seguros, controlador) {
  const fila = $("<tr>").attr("id", Seguros.id_registroSeguros);
  const periodoIn = Seguros.periodoIn || "-";
  const periodoFin = Seguros.periodoFin || "-";
  const tipo = Seguros.tipo || "-";
  const casino = Seguros.casino || "-";
  const vencido = Seguros.estado != 1;
  const estado = vencido ? "VENCIDO" : "VIGENTE";

  // El backend ya marca como vencido lo que pasó de su período hasta; acá solo se resalta en
  // amarillo la celda de estado para que se vea de un vistazo en la tabla.
  const $tdEstado = $("<td>").addClass("col-xs-2").text(estado);
  if (vencido) $tdEstado.addClass("seguro-vencido");

  fila
    .append($("<td>").addClass("col-xs-2").text(periodoIn))
    .append($("<td>").addClass("col-xs-2").text(periodoFin))
    .append($("<td>").addClass("col-xs-2").text(tipo))
    .append($("<td>").addClass("col-xs-2").text(casino))
    .append($tdEstado);

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");


  if (Seguros.estado == 1) {
    var btnCheck = $("<a>")
      .addClass("btn btn-success btn-sm btn-estadoRegSeguros")
      .attr("id", Seguros.id_registroSeguros)
      .attr("data-toggle", "tooltip")
      .attr("data-placement", "bottom")
      .attr("title", "MARCAR COMO VENCIDO")
      .append($("<i>").addClass("fa fa-fw fa-check"));
    tdAcc.append(btnCheck);
  }

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-Seguros")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", Seguros.id_registroSeguros)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteSeguros")
    .attr("id", Seguros.id_registroSeguros)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO DE SEGURO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      Seguros.valido,
      "seguros",
      Seguros.id_registroSeguros
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteSeguros", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarSeguros").attr("data-id", id);
  $("#modalEliminarSeguros").modal("show");
});

$("#btn-eliminarSeguros").on("click", function () {
  const id = $(this).attr("data-id");

  $.ajax({
    url: `/documentosContables/eliminarSeguros/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarSeguros").modal("hide");
        cargarSeguros({
          page: $("#herramientasPaginacionSeguros").getCurrentPage(),
          perPage: $("#herramientasPaginacionSeguros").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$(document).on("click", ".btn-estadoRegSeguros", function (e) {
  e.preventDefault();
  const id = $(this).attr("id");

  $.ajax({
    url: `/documentosContables/estadoSeguros/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        cargarSeguros({
          page: $("#herramientasPaginacionSeguros").getCurrentPage(),
          perPage: $("#herramientasPaginacionSeguros").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarSegurosExcel").on("click", function (e) {
  $("#collapseDescargarSeguros .has-error").removeClass("has-error");
  $("#collapseDescargarSeguros .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarSegurosXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarSegurosXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarSegurosCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarSeguros .has-error").removeClass("has-error");
  $("#collapseDescargarSeguros .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarSegurosCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//DERECHO DE ACCESO

instalarNumeroFlexibleAR("#monto_DerechoAcceso", {
  decimales: 2,
  permitirNegativos: true,
});

function cargarArchivosDerechoAccesoLista(id, target) {
  var $list = $(targetArchivosDoc("DerechoAcceso", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosDerechoAcceso/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/DerechoAcceso/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="DerechoAcceso"  class="btn btn-sm btn-danger btn-del-archivo-DerechoAcceso" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-DerechoAcceso", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});
function abrirModalDerechoAccesoEditar(id) {
  resetFormDerechoAcceso();
  $("#DerechoAcceso_modo").val("edit");
  $("#id_registroDerechoAcceso").val(id);
  $("#modalCargarDerechoAcceso .modal-title").text(
    "| EDITAR REGISTRO DE DERECHO DE ACCESO"
  );
  $("#guardarRegistroDerechoAcceso").text("ACTUALIZAR");
  $("#modalCargarDerechoAcceso").modal("show");

  $.getJSON("/documentosContables/llenarDerechoAccesoEdit/" + id, function (d) {
    var ym = String(d.fecha || "").slice(0, 7);

    $('#fechaDerechoAcceso input[name="fecha_DerechoAcceso"]')
      .val(ym)
      .trigger("input")
      .trigger("change");

    $("#casinoDerechoAcceso").val(d.casino).trigger("change");

    $('[name="semanaDerechoAcceso"]')
      .val(d.semana ?? "")
      .trigger("change");

    $('[name="fecha_venc_DerechoAcceso"]')
      .val(d.fecha_venc || "")
      .trigger("input")
      .trigger("change");

    $('[name="monto_DerechoAcceso"]').val(formatoAR(d.monto ?? ""));
    $('[name="obs_DerechoAcceso"]').val(d.obs || "");
  }).fail(function (xhr) {
    console.error("[DerechoAcceso] GET FAIL", xhr.status, xhr.responseText);
    alert("No se pudo cargar el registro.");
  });
}

function resetFormDerechoAcceso() {
  var $f = $("#formNuevoRegistroDerechoAcceso");
  // Si ese form no está en la pantalla, no se hace nada: un $f[0] undefined
  // tiraba TypeError y jQuery cortaba los demás handlers del mismo click.
  if (!$f.length) return;
  $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();
  $("#fileNameDerechoAcceso").val("");
    $("#uploadDerechoAcceso").val("");
  $("#uploadsDerechoAccesoContainer").empty();
  $("#uploadsDerechoAccesoTable tbody").empty();
  $("#uploadsDerechoAccesoWrap").hide();
  $("#fileNameDerechoAcceso").val("");
}

function abrirModalDerechoAccesoCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormDerechoAcceso();
  $("#DerechoAcceso_modo").val("create");
  $("#id_registroDerechoAcceso").val("");
  $("#modalCargarDerechoAcceso .modal-title").text(
    "| NUEVO REGISTRO DE DERECHO DE ACCESO"
  );
  $("#guardarRegistroDerechoAcceso").text("GENERAR");
  $("#modalCargarDerechoAcceso").modal("show");
}

$(document).on("click", "#DerechoAcceso_nuevo", function () {
  abrirModalDerechoAccesoCrear();
});

$(document).on("click", ".btn-edit-DerechoAcceso", function () {
  var id = $(this).data("id");
  abrirModalDerechoAccesoEditar(id);
});

$(document).on("click", "#guardarRegistroDerechoAcceso", function (e) {
  var $form = $("#formNuevoRegistroDerechoAcceso");
  let valid = true;
  var id = $("#id_registroDerechoAcceso").val() || "";
  var modo = $("#DerechoAcceso_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "input[name='fecha_DerechoAcceso']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampo(
    "input[name='fecha_venc_DerechoAcceso']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  valid = validarCampoNum(
    "input[name='monto_DerechoAcceso']",
    ".col-md-4",
    "El importe es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);
  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsDerechoAccesoContainer input[type="file"][name="uploadDerechoAcceso[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadDerechoAcceso[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadDerechoAcceso");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadDerechoAcceso[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarDerechoAcceso/" + id
      : "/documentosContables/guardarDerechoAcceso";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarDerechoAcceso({
        page: $("#herramientasPaginacionDerechoAcceso").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionDerechoAcceso").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarDerechoAcceso").modal("hide"), 1000);
      resetFormDerechoAcceso();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceDerechoAcceso(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarDerechoAcceso({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarDerechoAcceso({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasDerechoAcceso",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaDerechoAcceso").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaDerechoAcceso").append(generarFilaDerechoAcceso(item));
      });

      $("#herramientasPaginacionDerechoAcceso").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDerechoAcceso
      );
      $("#herramientasPaginacionDerechoAcceso").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceDerechoAcceso
      );
    },
    error(err) {
      console.error("Error cargando DerechoAcceso:", err);
    },
  });
}

function generarFilaDerechoAcceso(DerechoAcceso, controlador) {
  const fila = $("<tr>").attr("id", DerechoAcceso.id_registroDerechoAcceso);
  const fecha = convertirMesAno(DerechoAcceso.fecha_DerechoAcceso) || "-";
  const fecha_venc = DerechoAcceso.fecha_venc || "-";
  const casino = DerechoAcceso.casino || "-";
  const monto = "$ " + formatoAR(DerechoAcceso.monto) || "-";
  const obs = DerechoAcceso.obs || "-";
  const semana = DerechoAcceso.semana || "-";

  fila
    .append($("<td>").addClass("col-xs-2").html(fecha))
    .append($("<td>").addClass("col-xs-2").text(semana))
    .append($("<td>").addClass("col-xs-2").text(fecha_venc))
    .append($("<td>").addClass("col-xs-2").text(monto))
    .append($("<td>").addClass("col-xs-2").text(obs));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");

  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-DerechoAcceso")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", DerechoAcceso.id_registroDerechoAcceso)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteDerechoAcceso")
    .attr("id", DerechoAcceso.id_registroDerechoAcceso)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR DERECHO DE ACCESO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      DerechoAcceso.valido,
      "derecho_acceso",
      DerechoAcceso.id_registroDerechoAcceso
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteDerechoAcceso", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarDerechoAcceso").attr("data-id", id);
  $("#modalEliminarDerechoAcceso").modal("show");
});

$("#btn-eliminarDerechoAcceso").on("click", function () {
  const id = $(this).attr("data-id");
  $.ajax({
    url: `/documentosContables/eliminarDerechoAcceso/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarDerechoAcceso").modal("hide");
        cargarDerechoAcceso({
          page: $("#herramientasPaginacionDerechoAcceso").getCurrentPage(),
          perPage: $("#herramientasPaginacionDerechoAcceso").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarDerechoAccesoExcel").on("click", function (e) {
  $("#collapseDescargarDerechoAcceso .has-error").removeClass("has-error");
  $("#collapseDescargarDerechoAcceso .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;
  window.location.href = `/documentosContables/descargarDerechoAccesoXlsx?casino=3&desde=${desde}&hasta=${hasta}`;
});

$("#btn-descargarDerechoAccesoCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarDerechoAcceso .has-error").removeClass("has-error");
  $("#collapseDescargarDerechoAcceso .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarDerechoAccesoCsv?casino=3`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

//PATENTES

var PAT_PAGOS_IDX = 0;

function renderFilaPagoPatentes(p) {
  PAT_PAGOS_IDX++;
  var idx = PAT_PAGOS_IDX;

  var $row = $(
    '<div class="row pago-row" data-idx="' +
      idx +
      '" style="margin-bottom:10px;">' +
      '<input type="hidden" name="pago_patenteDe[]" value="' +
      (p.id || "") +
      '">' +
      '<div class="col-md-2">' +
      "<h5>Patente</h5>" +
      '<div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">' +
      (p.patenteDe || p.nombre || "") +
      "</div>" +
      "</div>" +
      '<div class="col-md-1">' +
      "<h5>Cuota</h5>" +
      '<input type="text" class="form-control pago-cuota" id="pat_pago_cuota_' +
      idx +
      '" name="pago_cuota[]">' +
      "</div>" +
      '<div class="col-md-2">' +
      "<h5>Importe</h5>" +
      '<input type="text" class="form-control pago-importe" id="pat_pago_importe_' +
      idx +
      '" name="pago_importe[]" placeholder="$">' +
      "</div>" +
      '<div class="col-md-2">' +
      "<h5>Fecha</h5>" +
      '<div class="input-group date" id="pat_pago_fecha_' +
      idx +
      '">' +
      '<input type="text" class="form-control pago-fecha" name="pago_fecha_pres[]" placeholder="yyyy-mm-dd" autocomplete="off">' +
      '<span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>' +
      '<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>' +
      "</div>" +
      "</div>" +
      '<div class="col-md-5">' +
      "<h5>Observación</h5>" +
      '<textarea class="form-control pago-observacion" id="pat_pago_obs_' +
      idx +
      '" name="pago_observacion[]" rows="1"></textarea>' +
      "</div>" +
      "</div>"
  );

  $row.find(".pago-cuota").val(p.cuota || "");
  if (p.importe != null && p.importe !== "") {
    var valImp = typeof formatoAR === "function" ? formatoAR(p.importe) : p.importe;
    $row.find(".pago-importe").val(valImp);
  }
  $row.find(".pago-fecha").val(p.fecha_pres || p.fecha || "");
  $row.find(".pago-observacion").val(p.observacion || p.obs || "");

  $("#pagosPatentesContainer").append($row);
  initPagoRowPatentes(idx);
  return $row;
}

function initPagoRowPatentes(idx) {
  var $modal = $("#modalCargarPatentes");
  var $fecha = $("#pat_pago_fecha_" + idx);
  
  if (typeof aplicarNumerico === "function") {
    aplicarNumerico("#pat_pago_importe_" + idx);
  }

  if (typeof instalarNumeroFlexibleAR === "function") {
    instalarNumeroFlexibleAR("#pat_pago_importe_" + idx, {
      decimales: 2,
      permitirNegativos: true,
    });
  }
  if (typeof attachYYYYMMDDFormatter === "function") {
    attachYYYYMMDDFormatter("#pat_pago_fecha_" + idx + " input");
  }

  if (!$.fn.datetimepicker) {
    console.warn("[Patentes] Falta $.fn.datetimepicker");
    return;
  }
  try {
    $fecha.datetimepicker("remove");
  } catch (e) {}

  var opts = {
    language: "es",
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: "yyyy-mm-dd",
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false,
  };

  $fecha.datetimepicker(opts);

  $fecha.off("show.dp.reloc").on("show.dp.reloc", function () {
    var inst = $fecha.data("datetimepicker");
    var $widget =
      inst && inst.picker ? inst.picker : $(".datetimepicker:visible").last();
    if ($widget.length && !$modal.has($widget).length) {
      $modal.append($widget);
      try {
        inst.place();
      } catch (e) {}
    }
    $widget
      .find(".timepicker, .datetimepicker-hours, .datetimepicker-minutes")
      .hide();
    $widget.find(".datepicker, .datetimepicker-days").show();
  });

  $("#pat_pago_fecha_" + idx + " .input-group-addon:last-child")
    .off("click")
    .on("click", function () {
      $(this).closest(".input-group.date").datetimepicker("show");
    });
  $("#pat_pago_fecha_" + idx + " .input-group-addon:first-child")
    .off("click")
    .on("click", function () {
      $(this)
        .closest(".input-group.date")
        .find("input")
        .val("")
        .trigger("input")
        .trigger("change");
      try {
        $(this).closest(".input-group.date").datetimepicker("hide");
      } catch (e) {}
    });

  $fecha.off("changeDate.dpHide").on("changeDate.dpHide", function () {
    try {
      $fecha.datetimepicker("hide");
    } catch (e) {}
  });
}

(function injectPickerFixesPat() {
  if (document.getElementById("dtp-fixes-pat-css")) return;
  var css =
    "#modalCargarPatentes .datetimepicker{ z-index: 20000 !important; }" +
    "#modalCargarPatentes .datetimepicker .timepicker," +
    "#modalCargarPatentes .datetimepicker .datetimepicker-hours," +
    "#modalCargarPatentes .datetimepicker .datetimepicker-minutes{ display:none !important; }" +
    "#modalCargarPatentes .datetimepicker .datepicker," +
    "#modalCargarPatentes .datetimepicker .datetimepicker-days{ display:block !important; }";
  var style = document.createElement("style");
  style.id = "dtp-fixes-pat-css";
  style.type = "text/css";
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();

function cargarPagosPorCasinoPatentes(casinoId) {
  var $cont = $("#pagosPatentesContainer").empty();
  PAT_PAGOS_IDX = 0;
  if (!casinoId) return;

  $.getJSON(
    "/documentosContables/getPatentes_patenteDeHabilitadosPorCasino/" + casinoId
  )
    .done(function (data) {
      (data || []).forEach(function (p) {
        renderFilaPagoPatentes({
          id: p.id || p.id_registroPatentes_patenteDe || "",
          patenteDe: p.nombre || "",
        });
      });
    })
    .fail(function () {
      $cont.append(
        '<div class="alert alert-danger">Error al cargar elementos patentables.</div>'
      );
    });
}

$(document)
  .off("change.patPagos", "#casinoPatentes")
  .on("change.patPagos", "#casinoPatentes", function () {
    if (window.PAT_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasinoPatentes($(this).val());
  });

var VER_PAT_PAGOS_IDX = 0;


$(document).on("click", "#Patentes_nuevo_patenteDe", function (e) {
  $("#modalCargarPatentes_patenteDe").modal("show");
});

function cargarArchivosPatentesLista(id, target) {
  var $list = $(targetArchivosDoc("Patentes", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosPatentes/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/Patentes/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="Patentes"  class="btn btn-sm btn-danger btn-del-archivo-Patentes" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-Patentes", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function resetFormPatentes() {
  var $f = $("#formNuevoRegistroPatentes");
  if ($f[0]) $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();

  $("#uploadsPatentesContainer").empty();
  $("#uploadsPatentesTable tbody").empty();
  $("#uploadsPatentesWrap").hide();
  $("#fileNamePatentes").val("");
  $("#uploadPatentes").val("");

  $("#pagosPatentesContainer").empty();
  PAT_PAGOS_IDX = 0;
}

function abrirModalPatentesCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormPatentes();
  $("#Patentes_modo").val("create");
  $("#id_registroPatentes").val("");
  $("#modalCargarPatentes .modal-title").text("| NUEVO REGISTRO DE PATENTES");
  $("#guardarRegistroPatentes").text("GENERAR");
  $("#modalCargarPatentes").modal("show");
}

$(document).on("click", "#Patentes_nuevo", function () {
  abrirModalPatentesCrear();
});

$(document).on("click", ".btn-edit-Patentes", function () {
  var id = $(this).data("id");
  abrirModalPatentesEditar(id);
});

function abrirModalPatentesEditar(id) {
  resetFormPatentes();
  $("#Patentes_modo").val("edit");
  $("#id_registroPatentes").val(id);
  $("#modalCargarPatentes .modal-title").text("| EDITAR REGISTRO DE PATENTES");
  $("#guardarRegistroPatentes").text("ACTUALIZAR");
  $("#modalCargarPatentes").modal("show");

  window.PAT_SUPPRESS_CASINO_CHANGE = true;

  $.getJSON("/documentosContables/llenarPatentesEdit/" + id, function (d) {
    var ym = String(d.fecha || d.fecha_Patentes || "").slice(0, 7);
    $('#fechaPatentes input[name="fecha_Patentes"]').val(ym);

    $("#casinoPatentes").val(d.casino || "");

    var $cont = $("#pagosPatentesContainer").empty();
    PAT_PAGOS_IDX = 0;

    if (Array.isArray(d.pagos) && d.pagos.length) {
      var seenIds = new Set();
      d.pagos.forEach(function (p) {
        var pid = p.id != null && p.id !== "" ? String(p.id) : null;
        if (pid && seenIds.has(pid)) return;
        if (pid) seenIds.add(pid);

        renderFilaPagoPatentes({
          id: p.patenteDe_id || p.patenteDe || "",
          patenteDe: p.patenteDe_nombre || p.patenteDe || p.nombre || "",
          cuota: p.cuota ?? "",
          importe: p.importe ?? "",
          fecha_pres: p.fecha_pres ?? "",
          observacion: p.observacion ?? p.obs ?? "",
        });
      });
    } else {
      if (d.casino) cargarPagosPorCasinoPatentes(d.casino);
    }
  })
    .fail(function (xhr) {
      console.error("[Patentes editar] GET FAIL", xhr.status, xhr.responseText);
      alert("No se pudo cargar el registro de Patentes.");
    })
    .always(function () {
      window.PAT_SUPPRESS_CASINO_CHANGE = false;
    });
}

$(document).on("click", "#Patentes_patenteDe_gestionar", function () {
  $("#modalPatentes_patenteDe_gestionar").modal("show");
  cargarPatentes_patenteDeGestion();
});

function renderFilaPatentes_patenteDe(d) {
  var estado = d.estado
    ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
    : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return (
    "" +
    '<tr data-id="' +
    d.id +
    '" data-estado="' +
    Number(d.estado) +
    '" data-nombre="' +
    escapeAttr(d.nombre || "") +
    '">' +
    '<td class="col-md-6">' +
    escapeHtml(d.nombre || "") +
    "</td>" +
    '<td class="col-md-1">' +
    escapeHtml(d.casino || "") +
    "</td>" +
    '<td class="col-md-2">' +
    estado +
    "</td>" +
    '<td class="text-left col-md-3">' +
    '<button type="button" class="btn btn-sm btn-primary btn-editPatentes_patenteDe" title="MODIFICAR PATENTE">' +
    '<i class="fa fa-pencil-alt"></i>' +
    "</button> " +
    '<button type="button" class="btn btn-sm btn-danger btn-elimPatentes_patenteDe" title="ELIMINAR PATENTE">' +
    '<i class="fa fa-trash"></i>' +
    "</button>" +
    "</td>" +
    "</tr>"
  );
}

function cargarPatentes_patenteDeGestion(casinoId) {
  $("#dir-list-loading_Patentes_patenteDe").show();
  $("#tabla-Patentes_patenteDe").closest(".table-responsive").hide();
  $("#tabla-Patentes_patenteDe tbody").empty();

  var url = "/documentosContables/getPatentes_patenteDe";

  $.getJSON(url, function (data) {
    var rows = "";
    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      rows += renderFilaPatentes_patenteDe(d);
    }
    $("#tabla-Patentes_patenteDe tbody").html(rows);
    $("#dir-list-loading_Patentes_patenteDe").hide();
    $("#tabla-Patentes_patenteDe").closest(".table-responsive").show();
  }).fail(function (xhr) {
    $("#dir-list-loading_Patentes_patenteDe").text(
      "Error cargando elementos patentables."
    );
    console.error(xhr.responseText);
  });
}

$(document).on("click", ".btn-elimPatentes_patenteDe", function () {
  const id = $(this).closest("tr").data("id");

  $("#btn-eliminarPatentes_patenteDe").attr("data-id", id);
  $("#modalEliminarPatentes_patenteDe").modal("show");
});

$(document).on("click", ".btn-editPatentes_patenteDe", function () {
  const $tr = $(this).closest("tr");
  const id = $(this).closest("tr").data("id");
  const nombre = $(this).closest("tr").data("nombre");
  const estado = $(this).closest("tr").data("estado");
  var $sel = $("#ModifPatentes_patenteDe_estado");
  $("#ModifId_Patentes_patenteDe").val(id);
  $("#ModifPatentes_patenteDe_nombre").val(nombre);
  if (Number(estado) === 1) {
    $sel
      .empty()
      .append(
        '<option value="1">Habilitado</option><option value="0">Deshabilitado</option>'
      );
  } else {
    $sel
      .empty()
      .append(
        '<option value="0">Deshabilitado</option><option value="1">Habilitado</option>'
      );
  }

  $("#modalModificarPatentes_patentesDe").modal("show");
});

$(document).on("click", "#guardarModifRegistroPatentes_patenteDe", function () {
  const $btn = $(this).prop("disabled", true);
  const $form = $("#formModificarRegistroPatentes_patenteDe");

  valid = true;
  valid = validarCampo(
    "input[name='ModifPatentes_patenteDe_nombre']",
    ".col-md-6",
    "El tipo es requerido.",
    valid
  );

  if (!valid) return 0;

  $.ajax({
    url: "/documentosContables/modificarPatentes_patenteDe",
    method: "POST",
    data: $form.serialize(),
    success: function (res) {
      cargarPatentes_patenteDeGestion();
      $("#modalModificarPatentes_patentesDe").modal("hide");
    },
    error: function (xhr) {
      console.error(xhr.responseText || xhr);
    },
    complete: function () {
      $btn.prop("disabled", false);
    },
  });
});

$(document).on("click", "#btn-eliminarPatentes_patenteDe", function () {
  const id = $(this).attr("data-id");
  const $tr = $('#tabla-Patentes_patenteDe tr[data-id="' + id + '"]');

  $.get("/documentosContables/PatentesEliminarpatenteDe/" + id, function (res) {
    if (res.ok) {
      $tr.remove();
      $("#modalEliminarPatentes_patenteDe").modal("hide");
    } else {
    }
  }).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on("click", "#guardarRegistroPatentes_patenteDe", function (e) {
  var $form = $("#formNuevoRegistroPatentes_patenteDe");
  let valid = true;

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='CasinoPatentes_patenteDe']",
    ".col-md-3",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='nombre_Patentes_patenteDe']",
    ".col-md-9",
    "El tipo es requerido.",
    valid
  );

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  $.ajax({
    url: "/documentosContables/guardarRegistroPatentes_patenteDe",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    success: function (res) {
      setTimeout(() => $("#modalCargarPatentes_patenteDe").modal("hide"), 1000);
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

$(document)
  .off("click", "#guardarRegistroPatentes")
  .on("click", "#guardarRegistroPatentes", function (e) {
    var $form = $("#formNuevoRegistroPatentes");
    let valid = true;
    var id = $("#id_registroPatentes").val() || "";
    var modo = $("#Patentes_modo").val() || "create";

    $form.find(".has-error").removeClass("has-error");
    $form.find(".help-block.js-error").remove();

    valid = validarCampo(
      "select[name='casinoPatentes']",
      ".col-md-4",
      "El casino es requerido.",
      valid
    );
    valid = validarCampo(
      "input[name='fecha_Patentes']",
      ".col-md-4",
      "La fecha es requerida.",
      valid
    );

    $("#pagosPatentesContainer .pago-row").each(function () {
      var $row = $(this);
      var $cuo = $row.find("input.pago-cuota");
      var $imp = $row.find("input.pago-importe");
      var $fec = $row.find("input.pago-fecha");

      var vCuo = ($cuo.val() || "").trim();
      var vImp = ($imp.val() || "").trim();
      var vFec = ($fec.val() || "").trim();

      if (!vCuo && !vImp && !vFec) return;

      valid = validarCampoNum(
        $cuo,
        ".col-md-1",
        "La cuota es requerida.",
        valid
      );
      valid = validarCampoNum(
        $imp,
        ".col-md-2",
        "El importe es requerido.",
        valid
      );
      valid = validarCampo($fec, ".col-md-2", "La fecha es requerida.", valid);
    });

    if (!valid) return 0;

    let formElem = $form[0];
    let formData = new FormData();

    $form.serializeArray().forEach(function (p) {
      let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
      let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
      let val = p.value;
      if ($inp.length > 0) {
        let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
        let dataNum = $inp.data("num");
        let numflex = $inp.data("_numflex_dec");
        if (!isDate && dataNum !== undefined && dataNum !== null) {
          val = dataNum;
        } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        } else if (!isDate && typeof val === 'string') {
          let cleaned = val.replace(/[$\s%]/g, '');
          if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
            let parsed = parseNumeroFlexible(val);
            if (parsed) val = parsed.num !== null ? parsed.num : "";
          }
        }
      }
      formData.append(p.name, val === null ? "" : val);
    });

    $(
      '#uploadsPatentesContainer input[type="file"][name="uploadPatentes[]"]'
    ).each(function () {
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        formData.append("uploadPatentes[]", files[i]);
      }
    });
    var cur = document.getElementById("uploadPatentes");
    if (cur && cur.files && cur.files.length) {
      for (var j = 0; j < cur.files.length; j++) {
        formData.append("uploadPatentes[]", cur.files[j]);
      }
    }

    var url =
      modo === "edit"
        ? "/documentosContables/actualizarPatentes/" + id
        : "/documentosContables/guardarPatentes";

    $.ajax({
      url: url,
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        cargarPatentes({
          page: $("#herramientasPaginacionPatentes").getCurrentPage() || 1,
          perPage: $("#herramientasPaginacionPatentes").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
        setTimeout(() => $("#modalCargarPatentes").modal("hide"), 1000);
        resetFormPatentes();
      },
      error: function (xhr) {
        $("#salir").next(".help-block.js-error").remove();
        $("#salir").after(
          '<span class="help-block js-error text-danger" style="color:red;">Ocurrió un error.</span>'
        );
        console.error("Error al guardar:", xhr);
      },
    });
  });

function clickIndicePatentes(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarPatentes({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarPatentes({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    url: "/documentosContables/ultimasPatentes",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaPatentes").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaPatentes").append(generarFilaPatentes(item));
      });

      $("#herramientasPaginacionPatentes").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePatentes
      );
      $("#herramientasPaginacionPatentes").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndicePatentes
      );
    },
    error(err) {
      console.error("Error cargando Patentes:", err);
    },
  });
}
function generarFilaPatentes(Patentes) {
  const fila = $("<tr>").attr("id", Patentes.id_registroPatentes);
  const fecha = convertirMesAno(Patentes.fecha_Patentes) || "-";
  const casino = Patentes.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-6").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  // Acciones
  const tdAcc = $("<td>").addClass("col-xs-2 d-flex flex-wrap");



  const btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-Patentes")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", Patentes.id_registroPatentes)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deletePatentes")
    .attr("id", Patentes.id_registroPatentes)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO DE PATENTES")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      Patentes.valido,
      "patentes",
      Patentes.id_registroPatentes
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deletePatentes", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarPatentes").attr("data-id", id);
  $("#modalEliminarPatentes").modal("show");
});

$("#btn-eliminarPatentes").on("click", function () {
  const id = $(this).attr("data-id");

  $.ajax({
    url: `/documentosContables/eliminarPatentes/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarPatentes").modal("hide");
        cargarPatentes({
          page: $("#herramientasPaginacionPatentes").getCurrentPage(),
          perPage: $("#herramientasPaginacionPatentes").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarPatentesExcel").on("click", function (e) {
  $("#collapseDescargarPatentes .has-error").removeClass("has-error");
  $("#collapseDescargarPatentes .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarPatentesXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPatentesXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPatentesCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarPatentes .has-error").removeClass("has-error");
  $("#collapseDescargarPatentes .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarPatentesCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// IMPUESTO INMOBILIARIO

var IMP_PAGOS_IDX = 0;

function renderFilaPagoImpInmobiliario(p) {
  IMP_PAGOS_IDX++;
  var idx = IMP_PAGOS_IDX;

  var $row = $(
    '<div class="row pago-row" data-idx="' +
      idx +
      '" style="margin-bottom:10px;">' +
      '<input type="hidden" name="pago_partida[]" value="' +
      (p.id || "") +
      '">' +
      '<div class="col-md-2">' +
      "<h5>Partida</h5>" +
      '<div class="form-control" style="height:auto; min-height:34px; padding-top:6px; font-weight:600;">' +
      (p.partida || "") +
      "</div>" +
      "</div>" +
      '<div class="col-md-1">' +
      "<h5>Cuota</h5>" +
      '<input type="text" class="form-control pago-cuota" id="imp_pago_cuota_' +
      idx +
      '" name="pago_cuota[]">' +
      "</div>" +
      '<div class="col-md-2">' +
      "<h5>Importe</h5>" +
      '<input type="text" class="form-control pago-importe" id="imp_pago_importe_' +
      idx +
      '" name="pago_importe[]" placeholder="$">' +
      "</div>" +
      '<div class="col-md-2">' +
      "<h5>Fecha</h5>" +
      '<div class="input-group date" id="imp_pago_fecha_' +
      idx +
      '">' +
      '<input type="text" class="form-control pago-fecha" name="pago_fecha_pres[]" placeholder="yyyy-mm-dd" autocomplete="off">' +
      '<span class="input-group-addon" style="border-left:none;cursor:pointer;"><i class="fa fa-times"></i></span>' +
      '<span class="input-group-addon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>' +
      "</div>" +
      "</div>" +
      '<div class="col-md-5">' +
      "<h5>Observación</h5>" +
      '<textarea class="form-control pago-observacion" id="imp_pago_obs_' +
      idx +
      '" name="pago_observacion[]" rows="1"></textarea>' +
      "</div>" +
      "</div>"
  );

  $row.find(".pago-cuota").val(p.cuota || "");
  if (p.importe != null && p.importe !== "")
    $row.find(".pago-importe").val(p.importe);
  $row.find(".pago-fecha").val(p.fecha_pres || p.fecha || "");
  $row.find(".pago-observacion").val(p.observacion || p.obs || "");

  $("#pagosImpInmobiliarioContainer").append($row);
  initPagoRowImpInmobiliario(idx);
  return $row;
}

function initPagoRowImpInmobiliario(idx) {
  var $modal = $("#modalCargarImpInmobiliario");
  var $fecha = $("#imp_pago_fecha_" + idx);

  if (typeof instalarNumeroFlexibleAR === "function") {
    instalarNumeroFlexibleAR("#imp_pago_importe_" + idx, {
      decimales: 2,
      permitirNegativos: true,
    });
  }
  if (typeof attachYYYYMMDDFormatter === "function") {
    attachYYYYMMDDFormatter("#imp_pago_fecha_" + idx + " input");
  }

  if (!$.fn.datetimepicker) {
    console.warn("[ImpInmobiliario] Falta $.fn.datetimepicker");
    return;
  }
  try {
    $fecha.datetimepicker("remove");
  } catch (e) {}

  var opts = {
    language: "es",
    todayBtn: true,
    autoclose: true,
    todayHighlight: true,
    format: "yyyy-mm-dd",
    startView: 2,
    minView: 2,
    maxView: 4,
    forceParse: false,
  };

  $fecha.datetimepicker(opts);

  $fecha.off("show.dp.reloc").on("show.dp.reloc", function () {
    var inst = $fecha.data("datetimepicker");
    var $widget =
      inst && inst.picker ? inst.picker : $(".datetimepicker:visible").last();
    if ($widget.length && !$modal.has($widget).length) {
      $modal.append($widget);
      try {
        inst.place();
      } catch (e) {}
    }
    $widget
      .find(".timepicker, .datetimepicker-hours, .datetimepicker-minutes")
      .hide();
    $widget.find(".datepicker, .datetimepicker-days").show();
  });

  $("#imp_pago_fecha_" + idx + " .input-group-addon:last-child")
    .off("click")
    .on("click", function () {
      $(this).closest(".input-group.date").datetimepicker("show");
    });
  $("#imp_pago_fecha_" + idx + " .input-group-addon:first-child")
    .off("click")
    .on("click", function () {
      $(this)
        .closest(".input-group.date")
        .find("input")
        .val("")
        .trigger("input")
        .trigger("change");
      try {
        $(this).closest(".input-group.date").datetimepicker("hide");
      } catch (e) {}
    });

  $fecha.off("changeDate.dpHide").on("changeDate.dpHide", function () {
    try {
      $fecha.datetimepicker("hide");
    } catch (e) {}
  });
}

(function injectPickerFixesImp() {
  if (document.getElementById("dtp-fixes-imp-css")) return;
  // aplicarNumerico('#modalCargarSeguros input[type="text"]'); // quitado: solo el Monto lleva parser y ya lo instala instalarNumeroFlexibleAR("#monto_Seguros"); compañía/póliza son texto libre
  aplicarNumerico('#modalCargarDerechoAcceso input[type="text"]');
  aplicarNumerico('#modalCargarPatentes input[type="text"]');
  aplicarNumerico('#modalCargarImpInmobiliario input[type="text"]');
  aplicarNumerico('#modalCargarPremiosMTM .prize-section input[type="text"]');
  var css =
    "#modalCargarImpInmobiliario .datetimepicker{ z-index: 20000 !important; }" +
    "#modalCargarImpInmobiliario .datetimepicker .timepicker," +
    "#modalCargarImpInmobiliario .datetimepicker .datetimepicker-hours," +
    "#modalCargarImpInmobiliario .datetimepicker .datetimepicker-minutes{ display:none !important; }" +
    "#modalCargarImpInmobiliario .datetimepicker .datepicker," +
    "#modalCargarImpInmobiliario .datetimepicker .datetimepicker-days{ display:block !important; }";
  var style = document.createElement("style");
  style.id = "dtp-fixes-imp-css";
  style.type = "text/css";
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
})();

function cargarPagosPorCasinoImpInmobiliario(casinoId) {
  var $cont = $("#pagosImpInmobiliarioContainer").empty();
  IMP_PAGOS_IDX = 0;
  if (!casinoId) return;

  $.getJSON("/documentosContables/getImpInmobiliario_partidaPorCasino", {
    casino: casinoId,
  })
    .done(function (data) {
      (data || []).forEach(function (p) {
        renderFilaPagoImpInmobiliario(p);
      });
    })
    .fail(function () {
      $cont.append(
        '<div class="alert alert-danger">Error al cargar partidas.</div>'
      );
    });
}

$(document)
  .off("change.impPagos", "#casinoImpInmobiliario")
  .on("change.impPagos", "#casinoImpInmobiliario", function () {
    if (IMP_SUPPRESS_CASINO_CHANGE) return;
    cargarPagosPorCasinoImpInmobiliario($(this).val());
  });

var VER_IMP_PAGOS_IDX = 0;

let IMP_SUPPRESS_CASINO_CHANGE = false;


$(document).on("click", "#ImpInmobiliario_nuevo", function (e) {
  $("#modalCargarImpInmobiliario").modal("show");
});

$(document).on("click", "#ImpInmobiliario_nueva_partida", function (e) {
  $("#modalCargarImpInmobiliario_partida").modal("show");
});

function abrirModalImpInmobiliarioEditar(id) {
  resetFormImpInmobiliario();
  $("#ImpInmobiliario_modo").val("edit");
  $("#id_registroImpInmobiliario").val(id);
  $("#modalCargarImpInmobiliario .modal-title").text(
    "| EDITAR REGISTRO DE IMPUESTO INMOBILIARIO"
  );
  $("#guardarRegistroImpInmobiliario").text("ACTUALIZAR");
  $("#modalCargarImpInmobiliario").modal("show");

  IMP_SUPPRESS_CASINO_CHANGE = true;

  $.getJSON(
    "/documentosContables/llenarImpInmobiliarioEdit/" + id,
    function (d) {
      var ym = String(d.fecha || d.fecha_ImpInmobiliario || "").slice(0, 7);
      $('#fechaImpInmobiliario input[name="fecha_ImpInmobiliario"]').val(ym);

      $("#casinoImpInmobiliario").val(d.casino); // NO dispares change acá

      var $cont = $("#pagosImpInmobiliarioContainer").empty();
      IMP_PAGOS_IDX = 0;

      if (Array.isArray(d.pagos) && d.pagos.length) {
        var seen = {};
        d.pagos.forEach(function (p) {
          var key =
            String(p.partida_id || "") +
            "|" +
            String(p.cuota || "") +
            "|" +
            String(p.importe || "") +
            "|" +
            String(p.fecha_pres || "");
          if (seen[key]) return;
          seen[key] = 1;

          var $row = renderFilaPagoImpInmobiliario({
            id: p.partida_id || "",
            partida: p.partida || "",
            cuota: p.cuota || "",
            importe: p.importe || "",
            fecha_pres: p.fecha_pres || "",
            observacion: p.observacion || "",
          });

          $row.find("input.pago-cuota").val(p.cuota || "");
          if (p.importe != null && p.importe !== "") {
            $row.find("input.pago-importe").val(p.importe);
          }
          $row.find("input.pago-fecha").val(p.fecha_pres || "");
          $row.find("textarea.pago-observacion").val(p.observacion || "");
        });
      }
    }
  )
    .fail(function (xhr) {
      console.error(
        "[ImpInmobiliario editar] GET FAIL",
        xhr.status,
        xhr.responseText
      );
      alert("No se pudo cargar el registro de Impuesto Inmobiliario.");
    })
    .always(function () {
      IMP_SUPPRESS_CASINO_CHANGE = false;
    });
}

$(document).on("click", "#ImpInmobiliario_partida_gestionar", function () {
  $("#modalImpInmobiliario_partida_gestionar").modal("show");
  cargarImpInmobiliario_partidaGestion();
});

function renderFilaImpInmobiliario_partida(d) {
  var estado = d.estado
    ? '<span class="text-success"><i class="fa fa-check"></i> Habilitado</span>'
    : '<span class="text-danger"><i class="fa fa-times"></i> Deshabilitado</span>';
  return (
    "" +
    '<tr data-estado="' +
    d.estado +
    '" data-id="' +
    d.id +
    '" data-casino"' +
    escapeAttr(d.casino_id || "") +
    '"  data-partida="' +
    escapeAttr(d.partida || "") +
    '">' +
    '<td class="col-md-8">' +
    escapeHtml(d.partida || "") +
    "</td>" +
    '<td class="col-md-1">' +
    escapeHtml(d.casino_nombre || "") +
    "</td>" +
    '<td class="col-md-2">' +
    estado +
    "</td>" +
    '<td class="col-md-1">' +
    '<button type="button" class="btn btn-sm btn-primary btn-editImpInmobiliario_partida" title="MODIFICAR PARTIDA">' +
    '<i class="fa fa-pencil-alt"></i>' +
    "</button> " +
    '<button type="button" class="btn btn-sm btn-danger btn-elimImpInmobiliario_partida" title="ELIMINAR PARTIDA">' +
    '<i class="fa fa-trash"></i>' +
    "</button>" +
    "</td>" +
    "</tr>"
  );
}

function cargarImpInmobiliario_partidaGestion(casinoId) {
  $("#dir-list-loading_ImpInmobiliario_partida").show();
  $("#tabla-ImpInmobiliario_partida").closest(".table-responsive").hide();
  $("#tabla-ImpInmobiliario_partida tbody").empty();

  var url = "/documentosContables/getImpInmobiliario_partida";

  $.getJSON(url, function (data) {
    var rows = "";
    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      rows += renderFilaImpInmobiliario_partida(d);
    }
    $("#tabla-ImpInmobiliario_partida tbody").html(rows);
    $("#dir-list-loading_ImpInmobiliario_partida").hide();
    $("#tabla-ImpInmobiliario_partida").closest(".table-responsive").show();
  }).fail(function (xhr) {
    $("#dir-list-loading_ImpInmobiliario_partida").text(
      "Error cargando elementos patentables."
    );
    console.error(xhr.responseText);
  });
}

$(document).on("click", ".btn-elimImpInmobiliario_partida", function () {
  const id = $(this).closest("tr").data("id");

  $("#btn-eliminarImpInmobiliario_partida").attr("data-id", id);
  $("#modalEliminarImpInmobiliario_partida").modal("show");
});

$(document).on("click", ".btn-editImpInmobiliario_partida", function () {
  const $tr = $(this).closest("tr");
  const id = $(this).closest("tr").data("id");
  const partida = $(this).closest("tr").data("partida");
  var $sel = $("#ModifImpInmobiliario_partida_estado");

  $("#ModifId_ImpInmobiliario_partida").val(id);
  $("#ModifImpInmobiliario_partida_partida").val(partida);

  $("#modalModificarImpInmobiliario_partida").modal("show");
});

$(document).on(
  "click",
  "#guardarModifRegistroImpInmobiliario_partida",
  function () {
    const $btn = $(this).prop("disabled", true);
    const $form = $("#formModificarRegistroImpInmobiliario_partida");

    valid = true;
    valid = validarCampo(
      "input[name='ModifImpInmobiliario_partida_partida']",
      ".col-md-12",
      "El tipo es requerido.",
      valid
    );

    if (!valid) return 0;

    $.ajax({
      url: "/documentosContables/modificarImpInmobiliario_partida",
      method: "POST",
      data: $form.serialize(),
      success: function (res) {
        cargarImpInmobiliario_partidaGestion();
        $("#modalModificarImpInmobiliario_partida").modal("hide");
      },
      error: function (xhr) {
        console.error(xhr.responseText || xhr);
      },
      complete: function () {
        $btn.prop("disabled", false);
      },
    });
  }
);

$(document).on("click", "#btn-eliminarImpInmobiliario_partida", function () {
  const id = $(this).attr("data-id");
  const $tr = $('#tabla-ImpInmobiliario_partida tr[data-id="' + id + '"]');

  $.get(
    "/documentosContables/ImpInmobiliarioEliminarPartida/" + id,
    function (res) {
      if (res.ok) {
        $tr.remove();
        $("#modalEliminarImpInmobiliario_partida").modal("hide");
      } else {
      }
    }
  ).fail(function (xhr) {
    console.error(xhr.responseText || xhr);
  });
});

$(document).on(
  "click",
  "#guardarRegistroImpInmobiliario_partida",
  function (e) {
    var $form = $("#formNuevoRegistroImpInmobiliario_partida");
    let valid = true;

    $form.find(".has-error").removeClass("has-error");
    $form.find(".help-block.js-error").remove();

    valid = validarCampo(
      "select[name='CasinoImpInmobiliario_partida']",
      ".col-md-3",
      "El casino es requerido.",
      valid
    );
    valid = validarCampo(
      "input[name='nombre_ImpInmobiliario_partida']",
      ".col-md-9",
      "El nombre de la partida es requerido.",
      valid
    );

    if (!valid) return 0;

    let formElem = $form[0];
    let formData = new FormData(formElem);

    $.ajax({
      url: "/documentosContables/guardarRegistroImpInmobiliario_partida",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        setTimeout(
          () => $("#modalCargarImpInmobiliario_partida").modal("hide"),
          1000
        );
      },
      error: function (xhr) {
        $("#salir").next(".help-block.js-error").remove();
        $("#salir").after(
          '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
        );
        console.error("Error al guardar:", xhr);
      },
    });
  }
);

function cargarArchivosImpInmobiliarioLista(id, target) {
  var $list = $(targetArchivosDoc("ImpInmobiliario", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosImpInmobiliario/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/ImpInmobiliario/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="ImpInmobiliario"  class="btn btn-sm btn-danger btn-del-archivo-ImpInmobiliario" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).on("click", ".btn-del-archivo-ImpInmobiliario", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

function resetFormImpInmobiliario() {
  const $f = $("#formNuevoRegistroImpInmobiliario");
  if ($f[0]) $f[0].reset();
  $f.find(".has-error").removeClass("has-error");
  $f.find(".help-block.js-error").remove();

    $("#uploadsImpInmobiliarioContainer").empty();
  $("#uploadsImpInmobiliarioTable tbody").empty();
  $("#uploadsImpInmobiliarioWrap").hide();
  $("#fileNameImpInmobiliario").val("");

}

function abrirModalImpInmobiliarioCrear() {
  // Limpia el formulario: si no, al venir de ver/editar otro registro quedaban
  // precargados sus datos y sus archivos.
  resetFormImpInmobiliario();
  $("#ImpInmobiliario_modo").val("create");
  $("#id_registroImpInmobiliario").val("");
  $("#modalCargarImpInmobiliario .modal-title").text(
    "| NUEVO REGISTRO DE IMPUESTO INMOBILIARIO"
  );
  $("#guardarRegistroImpInmobiliario").text("GENERAR");
  $("#modalCargarImpInmobiliario").modal("show");
}

$(document).on("click", "#ImpInmobiliario_nuevo", function () {
  abrirModalImpInmobiliarioCrear();
});

$(document).on("click", ".btn-edit-ImpInmobiliario", function () {
  var id = $(this).data("id");
  abrirModalImpInmobiliarioEditar(id);
});

$(document).on("click", "#guardarRegistroImpInmobiliario", function (e) {
  var $form = $("#formNuevoRegistroImpInmobiliario");
  let valid = true;
  var id = $("#id_registroImpInmobiliario").val() || "";
  var modo = $("#ImpInmobiliario_modo").val() || "create";

  $form.find(".has-error").removeClass("has-error");
  $form.find(".help-block.js-error").remove();

  valid = validarCampo(
    "select[name='casinoImpInmobiliario']",
    ".col-md-6",
    "El casino es requerido.",
    valid
  );
  valid = validarCampo(
    "input[name='fecha_ImpInmobiliario']",
    ".col-md-6",
    "La fecha es requerida.",
    valid
  );

  $("#pagosImpInmobiliarioContainer .pago-row").each(function () {
    var $row = $(this);
    var $cuo = $row.find("input.pago-cuota");
    var $imp = $row.find("input.pago-importe");
    var $fec = $row.find("input.pago-fecha");

    var vCuo = ($cuo.val() || "").trim();
    var vImp = ($imp.val() || "").trim();
    var vFec = ($fec.val() || "").trim();

    if (!vCuo && !vImp && !vFec) return;

    valid = validarCampoNum($cuo, ".col-md-1", "La cuota es requerida.", valid);
    valid = validarCampoNum(
      $imp,
      ".col-md-2",
      "El importe es requerido.",
      valid
    );
    valid = validarCampo($fec, ".col-md-2", "La fecha es requerida.", valid);
  });

  if (!valid) return 0;

  let formElem = $form[0];
  let formData = new FormData(formElem);

  var fd = new FormData();

  $form.serializeArray().forEach(function (p) {
    let escapeSelector = function(str) { return str.replace(/\[/g, "\\[").replace(/\]/g, "\\]"); };
    let $inp = $form.find(`[name="${escapeSelector(p.name)}"]`).filter(function() { return $(this).val() === p.value; }).first();
    let val = p.value;
    if ($inp.length > 0) {
      let isDate = $inp.hasClass("datepicker") || $inp.hasClass("pago-fecha") || p.name.toLowerCase().includes("fecha") || p.name === "periodo" || $inp.attr("placeholder") === "yyyy-mm-dd";
      let dataNum = $inp.data("num");
      let numflex = $inp.data("_numflex_dec");
      if (!isDate && dataNum !== undefined && dataNum !== null) {
        val = dataNum;
      } else if (!isDate && ($inp.hasClass("formato-ar") || numflex !== undefined)) {
        let parsed = parseNumeroFlexible(val);
        if (parsed) val = parsed.num !== null ? parsed.num : "";
      } else if (!isDate && typeof val === 'string') {
        let cleaned = val.replace(/[$\s%]/g, '');
        if (/^-?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,\d+)?$/.test(cleaned) || /^-?\d+(?:\.\d+)?$/.test(cleaned)) {
          let parsed = parseNumeroFlexible(val);
          if (parsed) val = parsed.num !== null ? parsed.num : "";
        }
      }
    }
    fd.append(p.name, val === null ? "" : val);
  });

  $(
    '#uploadsImpInmobiliarioContainer input[type="file"][name="uploadImpInmobiliario[]"]'
  ).each(function () {
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      fd.append("uploadImpInmobiliario[]", files[i]);
    }
  });

  var cur = document.getElementById("uploadImpInmobiliario");
  if (cur && cur.files && cur.files.length) {
    for (var j = 0; j < cur.files.length; j++) {
      fd.append("uploadImpInmobiliario[]", cur.files[j]);
    }
  }

  var url =
    modo === "edit"
      ? "/documentosContables/actualizarImpInmobiliario/" + id
      : "/documentosContables/guardarImpInmobiliario";

  $.ajax({
    url: url,
    method: "POST",
    data: fd,
    processData: false,
    contentType: false,
    success: function (res) {
      cargarImpInmobiliario({
        page: $("#herramientasPaginacionImpInmobiliario").getCurrentPage() || 1,
        perPage: $("#herramientasPaginacionImpInmobiliario").getPageSize(),
        casino: $("#filtro_global_casino").val(),
        desde: $("#filtro_global_desde_input").val(),
        hasta: $("#filtro_global_hasta_input").val(),
      });
      setTimeout(() => $("#modalCargarImpInmobiliario").modal("hide"), 1000);
      resetFormImpInmobiliario();
    },
    error: function (xhr) {
      $("#salir").next(".help-block.js-error").remove();
      $("#salir").after(
        '<span class="help-block js-error text-danger" style="color:red;" >Ocurrió un error.</span>'
      );
      console.error("Error al guardar:", xhr);
    },
  });
});

function clickIndiceImpInmobiliario(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarImpInmobiliario({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val(),
    desde: $("#filtro_global_desde_input").val(),
    hasta: $("#filtro_global_hasta_input").val(),
  });
}

function cargarImpInmobiliario({
  page = 1,
  perPage = 10,
  casino,
  desde,
  hasta,
}) {
  $.ajax({
    url: "/documentosContables/ultimasImpInmobiliario",
    data: {
      page,
      page_size: perPage,
      id_casino: casino,
      desde,
      hasta,
    },
    dataType: "json",
    success(res) {
      $("#cuerpoTablaImpInmobiliario").empty();

      res.registros.forEach((item) => {
        $("#cuerpoTablaImpInmobiliario").append(
          generarFilaImpInmobiliario(item)
        );
      });

      $("#herramientasPaginacionImpInmobiliario").generarTitulo(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceImpInmobiliario
      );
      $("#herramientasPaginacionImpInmobiliario").generarIndices(
        res.pagination.current_page,
        res.pagination.per_page,
        res.pagination.total,
        clickIndiceImpInmobiliario
      );
    },
    error(err) {
      console.error("Error cargando ImpInmobiliario:", err);
    },
  });
}

function generarFilaImpInmobiliario(ImpInmobiliario, controlador) {
  const fila = $("<tr>").attr("id", ImpInmobiliario.id_registroImpInmobiliario);
  const fecha = convertirMesAno(ImpInmobiliario.fecha_ImpInmobiliario) || "-";
  const casino = ImpInmobiliario.casino || "-";

  fila
    .append($("<td>").addClass("col-xs-4").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  // Columna de ACCIONES
  const tdAcc = $("<td>").addClass("col-xs-4 d-flex flex-wrap");


  var btnEdit = $("<button>")
    .addClass("btn btn-info btn-sm mr-1 btn-edit-ImpInmobiliario")
    .attr("type", "button")
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("data-id", ImpInmobiliario.id_registroImpInmobiliario)
    .attr("title", "EDITAR")
    .append($("<i>").addClass("fa fa-pencil-alt"));
  tdAcc.append(btnEdit);

  const btnDelete = $("<button>")
    .addClass("btn btn-danger btn-sm btn-deleteImpInmobiliario")
    .attr("id", ImpInmobiliario.id_registroImpInmobiliario)
    .attr("data-toggle", "tooltip")
    .attr("data-placement", "bottom")
    .attr("title", "ELIMINAR REGISTRO DE IMPUESTO INMOBILIARIO")
    .append($("<i>").addClass("fa fa-trash"));
  tdAcc.append(btnDelete);

  tdAcc.append(
    obtenerIconoValidacion(
      ImpInmobiliario.valido,
      "imp_inmobiliario",
      ImpInmobiliario.id_registroImpInmobiliario
    )
  );

  fila.append(tdAcc);
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  return fila;
}

$(document).on("click", ".btn-deleteImpInmobiliario", function () {
  const id = $(this).attr("id");
  $("#btn-eliminarImpInmobiliario").attr("data-id", id);
  $("#modalEliminarImpInmobiliario").modal("show");
});

$("#btn-eliminarImpInmobiliario").on("click", function () {
  const id = $(this).attr("data-id");

  $.ajax({
    url: `/documentosContables/eliminarImpInmobiliario/${id}`,
    method: "GET",
  })
    .done((res) => {
      if (res == 1) {
        $("#modalEliminarImpInmobiliario").modal("hide");
        cargarImpInmobiliario({
          page: $("#herramientasPaginacionImpInmobiliario").getCurrentPage(),
          perPage: $("#herramientasPaginacionImpInmobiliario").getPageSize(),
          casino: $("#filtro_global_casino").val(),
          desde: $("#filtro_global_desde_input").val(),
          hasta: $("#filtro_global_hasta_input").val(),
        });
      } else {
      }
    })
    .fail(() => {});
});

$("#btn-descargarImpInmobiliarioExcel").on("click", function (e) {
  $("#collapseDescargarImpInmobiliario .has-error").removeClass("has-error");
  $("#collapseDescargarImpInmobiliario .js-error").remove();

  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  if (casino != 4) {
    window.location.href = `/documentosContables/descargarImpInmobiliarioXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarImpInmobiliarioXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarImpInmobiliarioCsv").on("click", function () {
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;
  let valid = true;
  $("#collapseDescargarImpInmobiliario .has-error").removeClass("has-error");
  $("#collapseDescargarImpInmobiliario .js-error").remove();

  if (desde > hasta && hasta) {
    avisoDescargaDoc("El intérvalo de inicio no puede ser posterior al de fin.");
    valid = false;
  }

  if (!valid) return;

  let url = `/documentosContables/descargarImpInmobiliarioCsv?casino=${casino}`;
  if (desde) url += `&desde=${desde}`;
  if (hasta) url += `&hasta=${hasta}`;

  window.location.href = url;
});

// GENERALESSSSSS

$(function () {
  $("[data-js-tabs] a").on("click", function (e) {
    e.preventDefault();

    const selector = $(this).data("js-tab");

    $("[data-js-tabs] a").removeClass("active");
    $(this).addClass("active");

    // Use hidden attribute instead of .hide()/.show()
    $('[id^="pant_"]').attr("hidden", true);
    $(selector).removeAttr("hidden");

    // Call buscarPestana to load data for the selected tab
    buscarPestana(selector);

    if (selector === "#pant_iva") {
      $(document).on("click", "#btnPickIva", function () {
        $("#uploadIva").click();
      });

      $(document).on("change", "#uploadIva", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsIvaContainer");
        renderUploadsIva();
        var $new = $(
          '<input type="file" id="uploadIva" name="uploadIva[]" multiple style="display:none;">'
        );
        $("#btnPickIva").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsIva() {
        var $tbody = $("#uploadsIvaTable tbody").empty();
        var total = 0,
          row = 1;

        $('#uploadsIvaContainer input[type=file][name="uploadIva[]"]').each(
          function (ix) {
            var $inp = $(this);
            var gid = $inp.attr("data-group");
            var files = this.files || [];
            for (var i = 0; i < files.length; i++) {
              var f = files[i];
              total++;
              var $tr = $("<tr></tr>");
              $tr.append("<td>" + row++ + "</td>");
              $tr.append("<td>" + f.name + "</td>");
              $tr.append("<td>" + humanSize(f.size) + "</td>");
              var $btn = $(
                '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                  '<i class="fa fa-trash"></i>' +
                  "</button>"
              )
                .attr("data-group", gid)
                .attr("data-idx", i);
              $tr.append($("<td></td>").append($btn));
              $tbody.append($tr);
            }
          }
        );

        $("#uploadsIvaWrap").css("display", total ? "" : "none");
        $("#fileNameIva").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsIvaTable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsIvaContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsIva();
      });

      attachYYYYMMFormatter("#fecha_iva");
      attachYYYYMMDDFormatter("#fecha_ivaPres");

      $("#fechaIva").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
      $("#fechaIvaPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_iibb") {
      // cargariibb({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickiibb", function () {
        $("#uploadiibb").click();
      });

      $(document).on("change", "#uploadiibb", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsiibbContainer");
        renderUploadsiibb();
        var $new = $(
          '<input type="file" id="uploadiibb" name="uploadiibb[]" multiple style="display:none;">'
        );
        $("#btnPickiibb").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsiibb() {
        var $tbody = $("#uploadsiibbTable tbody").empty();
        var total = 0,
          row = 1;

        $('#uploadsiibbContainer input[type=file][name="uploadiibb[]"]').each(
          function (ix) {
            var $inp = $(this);
            var gid = $inp.attr("data-group");
            var files = this.files || [];
            for (var i = 0; i < files.length; i++) {
              var f = files[i];
              total++;
              var $tr = $("<tr></tr>");
              $tr.append("<td>" + row++ + "</td>");
              $tr.append("<td>" + f.name + "</td>");
              $tr.append("<td>" + humanSize(f.size) + "</td>");
              var $btn = $(
                '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                  '<i class="fa fa-trash"></i>' +
                  "</button>"
              )
                .attr("data-group", gid)
                .attr("data-idx", i);
              $tr.append($("<td></td>").append($btn));
              $tbody.append($tr);
            }
          }
        );

        $("#uploadsiibbWrap").css("display", total ? "" : "none");
        $("#fileNameiibb").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsiibbTable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsiibbContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsiibb();
      });

      attachYYYYMMFormatter("#fecha_iibb");
      attachYYYYMMDDFormatter("#fecha_iibbPres");

                              $("#fechaiibb").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaiibbPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      (function () {
        let contador = 0;
        $(document).on("iibb:set-contador", function (e, n) {
          contador = Number(n) || 0;
        });
        $(document)
          .off("click.iibb", "#agregar-bloque-iibb-cargar")
          .on("click.iibb", "#agregar-bloque-iibb-cargar", function (e) {
            e.preventDefault();
            contador++;

            const montoId = `monto_iibb_${contador}`;
            const aliId = `alicuota_iibb_${contador}`;
            const impId = `imp_iibb_${contador}`;

            const bloque = `
              <div class="bases-iibb-cargar" data-id="${contador}">
                <div class="row">
                  <div class="col-md-3"><h5>Base imponible...</h5></div>
                  <div class="col-md-3"><h5>Monto</h5></div>
                  <div class="col-md-2"><h5>Alicuota (%)</h5></div>
                  <div class="col-md-3"><h5>Impuesto Determinado</h5></div>
                  <div class="col-md-1"></div>
                </div>
                <div class="row">
                  <div class="col-md-3">
                    <textarea name="base[]" class="form-control" rows="2" placeholder="Completar con el concepto" maxlength="4000"></textarea>
                  </div>
                  <div class="col-md-3">
                    <input type="text" id="${montoId}" name="monto[]" class="form-control js-num-ar iibb-monto" placeholder="$" inputmode="decimal">
                  </div>
                  <div class="col-md-2">
                    <input type="text" id="${aliId}" name="alicuota[]" class="form-control js-num-ar iibb-ali" placeholder="%" inputmode="decimal">
                  </div>
                  <div class="col-md-3">
                    <input type="text" id="${impId}" name="impuesto[]" class="form-control js-num-ar iibb-imp" inputmode="decimal" readonly>
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm eliminar-bloque-iibb-cargar">X</button>
                  </div>
                </div>
              </div>`;

            $("#contenedor-inputs-iibb-cargar").append(bloque);

            instalarNumeroFlexibleAR(`#${montoId}, #${aliId}, #${impId}`, {
              decimales: 5,
              permitirNegativos: true,
            });

            instalarAutoImpuestoAR({
              base: `#${montoId}`,
              alicuota: `#${aliId}`,
              impuesto: `#${impId}`,
              decImp: 2,
              aliEsPorcentaje: true,
            });

            $(`#${montoId}`).trigger("input");
          });

        $(document)
          .off("click.iibb", ".eliminar-bloque-iibb-cargar")
          .on("click.iibb", ".eliminar-bloque-iibb-cargar", function () {
            $(this).closest(".bases-iibb-cargar").remove();
            const $firstImp = $(
              "#contenedor-inputs-iibb-cargar .iibb-imp"
            ).first();
            if ($firstImp.length) $firstImp.trigger("input");
            else
              $("#total_impuesto_iibb")
                .val(formatoAR(0, 2))
                .data("num", 0)
                .trigger("num:changed", [0]);
          });

        // Agregar deducción
        $(document).off("click.iibb", "#agregar-deduccion-iibb")
          .on("click.iibb", "#agregar-deduccion-iibb", function(e) {
            e.preventDefault();
            contadorDeduccionIibb++;
            appendDeduccionIibbFila({}, contadorDeduccionIibb);
          });

        // Eliminar deducción
        $(document).off("click.iibb", ".eliminar-deduccion-iibb")
          .on("click.iibb", ".eliminar-deduccion-iibb", function() {
            $(this).closest(".deduccion-iibb-fila").remove();
            if ($("#contenedor-deducciones-iibb .deduccion-iibb-fila").length === 0) {
              $("#contenedor-deducciones-iibb .deduccion-iibb-headers").remove();
            }
            recalcularSumaDeduccionesIibb();
          });

        // Recalcular suma deducciones cuando cambia un monto
        $(document).off("num:changed.iibb-ded", ".deduccion-monto-iibb")
          .on("num:changed.iibb-ded", ".deduccion-monto-iibb", function() {
            recalcularSumaDeduccionesIibb();
          });

        // Auto-fill bases del mes anterior al cambiar casino (solo en modo create)
        $(document).off("change.iibb-casino", "#casinoiibb")
          .on("change.iibb-casino", "#casinoiibb", function() {
            var casinoId = $(this).val();
            if (!casinoId || $("#iibb_modo").val() !== "create") return;
            $.getJSON("/documentosContables/ultimasAlicuotasiibb", { casino_id: casinoId }, function(data) {
              var bases = Array.isArray(data.bases) ? data.bases : [];
              if (!bases.length) return;
              $("#contenedor-inputs-iibb-cargar").empty();
              contador = 0;
              bases.forEach(function(b) {
                contador++;
                appendIibbFilaEditar({ obs: b.obs, alicuota: b.alicuota }, contador, "#contenedor-inputs-iibb-cargar");
              });
              $(document).trigger("iibb:set-contador", [contador]);
            });
          });

        $("#modalCargariibb").on("hidden.bs.modal", function () {
          $("#contenedor-inputs-iibb-cargar").empty();
          $("#contenedor-deducciones-iibb").empty();
          contadorDeduccionIibb = 0;
          contador = 0;
          const f = document.getElementById("formNuevoRegistroiibb");
          if (f) f.reset();
          $("#total_impuesto_iibb").val(formatoAR(0, 2)).data("num", 0);
          $("#deduccionesiibb").val(0).data("num", 0);
        });
      })();
    } else if (selector === "#pant_drei") {
      // cargarDREI({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickDREI", function () {
        $("#uploadDREI").click();
      });

      $(document).on("change", "#uploadDREI", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsDREIContainer");
        renderUploadsDREI();
        var $new = $(
          '<input type="file" id="uploadDREI" name="uploadDREI[]" multiple style="display:none;">'
        );
        $("#btnPickDREI").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsDREI() {
        var $tbody = $("#uploadsDREITable tbody").empty();
        var total = 0,
          row = 1;

        $('#uploadsDREIContainer input[type=file][name="uploadDREI[]"]').each(
          function (ix) {
            var $inp = $(this);
            var gid = $inp.attr("data-group");
            var files = this.files || [];
            for (var i = 0; i < files.length; i++) {
              var f = files[i];
              total++;
              var $tr = $("<tr></tr>");
              $tr.append("<td>" + row++ + "</td>");
              $tr.append("<td>" + f.name + "</td>");
              $tr.append("<td>" + humanSize(f.size) + "</td>");
              var $btn = $(
                '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                  '<i class="fa fa-trash"></i>' +
                  "</button>"
              )
                .attr("data-group", gid)
                .attr("data-idx", i);
              $tr.append($("<td></td>").append($btn));
              $tbody.append($tr);
            }
          }
        );

        $("#uploadsDREIWrap").css("display", total ? "" : "none");
        $("#fileNameDREI").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsDREITable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsDREIContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsDREI();
      });

      $(document).ready(function () {
        $("#casinoDREI").on("change", function () {
          var tipo = $(this).val();

          $("#formNuevoRegistroDREI .has-error").removeClass("has-error");
          $("#formNuevoRegistroDREI .help-block.js-error").remove();

          $(".formulario-DREI").hide();

          // Limpiar bases extra al cambiar casino
          $("#contenedor-inputs-drei-extra-csf, #contenedor-inputs-drei-extra-mel, #contenedor-inputs-drei-extra-ro").empty();
          contadorDreiExtra = 0;

          if (tipo == "1") {
            $("#formularioMEL").show();
          } else if (tipo == "2") {
            $("#formularioCSF").show();
          } else if (tipo == "3") {
            $("#formularioRO").show();
          }

          // Auto-completar alicuotas del mes anterior para el casino seleccionado
          if (tipo && $("#DREI_modo").val() === "create") {
            $.getJSON("/documentosContables/ultimasAlicuotasDREI", { casino_id: tipo }, function (data) {
              function setAlicuota(sel, val) {
                if (val == null) return;
                var n = parseFloat(val) || 0;
                var decs = $(sel).data("_numflex_dec") || 5;
                $(sel).val(formatoAR(n, decs)).data("num", n).trigger("num:changed", [n]);
              }
              if (tipo == "2") {
                setAlicuota("#alicuota_comDREI",   data.com_alicuota);
                setAlicuota("#alicuota_gasDREI",   data.gas_alicuota);
                setAlicuota("#alicuota_explDREI",  data.expl_alicuota);
                setAlicuota("#alicuota_apyjDREI",  data.apyju_alicuota);
              } else if (tipo == "1") {
                setAlicuota("#alicuota_melDREI",  data.com_alicuota);
                setAlicuota("#alicuotaO_melDREI", data.gas_alicuota);
              }
            });
          }
        });
      });

      attachYYYYMMDDFormatter("#fecha_DREIPres");
      attachYYYYMMFormatter("#fecha_DREI");
      attachYYYYMMDDFormatter("#fecha_DREIVenc");

      $("#fechaDREI").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaDREIPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaDREIVenc").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_tgi") {
      // cargarTGI({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickTGI", function () {
        $("#uploadTGI").click();
      });

      $(document).on("change", "#uploadTGI", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsTGIContainer");
        renderUploadsTGI();
        var $new = $(
          '<input type="file" id="uploadTGI" name="uploadTGI[]" multiple style="display:none;">'
        );
        $("#btnPickTGI").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsTGI() {
        var $tbody = $("#uploadsTGITable tbody").empty();
        var total = 0,
          row = 1;

        $('#uploadsTGIContainer input[type=file][name="uploadTGI[]"]').each(
          function (ix) {
            var $inp = $(this);
            var gid = $inp.attr("data-group");
            var files = this.files || [];
            for (var i = 0; i < files.length; i++) {
              var f = files[i];
              total++;
              var $tr = $("<tr></tr>");
              $tr.append("<td>" + row++ + "</td>");
              $tr.append("<td>" + f.name + "</td>");
              $tr.append("<td>" + humanSize(f.size) + "</td>");
              var $btn = $(
                '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                  '<i class="fa fa-trash"></i>' +
                  "</button>"
              )
                .attr("data-group", gid)
                .attr("data-idx", i);
              $tr.append($("<td></td>").append($btn));
              $tbody.append($tr);
            }
          }
        );

        $("#uploadsTGIWrap").css("display", total ? "" : "none");
        $("#fileNameTGI").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsTGITable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsTGIContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsTGI();
      });

      attachYYYYMMFormatter("#fecha_TGI");


      $("#fechaTGI").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                } else if (selector === "#pant_imp_ap_ol") {
      // cargarIMP_AP_OL({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickIMP_AP_OL", function () {
        $("#uploadIMP_AP_OL").click();
      });

      $(document).on("change", "#uploadIMP_AP_OL", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsIMP_AP_OLContainer");
        renderUploadsIMP_AP_OL();
        var $new = $(
          '<input type="file" id="uploadIMP_AP_OL" name="uploadIMP_AP_OL[]" multiple style="display:none;">'
        );
        $("#btnPickIMP_AP_OL").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsIMP_AP_OL() {
        var $tbody = $("#uploadsIMP_AP_OLTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsIMP_AP_OLContainer input[type=file][name="uploadIMP_AP_OL[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsIMP_AP_OLWrap").css("display", total ? "" : "none");
        $("#fileNameIMP_AP_OL").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsIMP_AP_OLTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsIMP_AP_OLContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsIMP_AP_OL();
        }
      );

      attachYYYYMMFormatter("#fecha_IMP_AP_OL");
      attachYYYYMMDDFormatter("#fecha_IMP_AP_OLPres");
      attachYYYYMMDDFormatter("#fecha_pago_IMP_AP_OL");

      $("#fechaIMP_AP_OL").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaIMP_AP_OLPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_pagoIMP_AP_OL").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_imp_ap_mtm") {
      // cargarIMP_AP_MTM({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickIMP_AP_MTM", function () {
        $("#uploadIMP_AP_MTM").click();
      });

      $(document).on("change", "#uploadIMP_AP_MTM", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsIMP_AP_MTMContainer");
        renderUploadsIMP_AP_MTM();
        var $new = $(
          '<input type="file" id="uploadIMP_AP_MTM" name="uploadIMP_AP_MTM[]" multiple style="display:none;">'
        );
        $("#btnPickIMP_AP_MTM").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsIMP_AP_MTM() {
        var $tbody = $("#uploadsIMP_AP_MTMTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsIMP_AP_MTMContainer input[type=file][name="uploadIMP_AP_MTM[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsIMP_AP_MTMWrap").css("display", total ? "" : "none");
        $("#fileNameIMP_AP_MTM").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsIMP_AP_MTMTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsIMP_AP_MTMContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsIMP_AP_MTM();
        }
      );

      attachYYYYMMFormatter("#fecha_IMP_AP_MTM");
      attachYYYYMMDDFormatter("#fecha_IMP_AP_MTMPres");
      attachYYYYMMDDFormatter("#fecha_pago_IMP_AP_MTM");

      $("#fechaIMP_AP_MTM").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaIMP_AP_MTMPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_pagoIMP_AP_MTM").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_deuda") {
      // cargarDeudaEstado({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickDeudaEstado", function () {
        $("#uploadDeudaEstado").click();
      });

      $(document).on("change", "#uploadDeudaEstado", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsDeudaEstadoContainer");
        renderUploadsDeudaEstado();
        var $new = $(
          '<input type="file" id="uploadDeudaEstado" name="uploadDeudaEstado[]" multiple style="display:none;">'
        );
        $("#btnPickDeudaEstado").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsDeudaEstado() {
        var $tbody = $("#uploadsDeudaEstadoTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsDeudaEstadoContainer input[type=file][name="uploadDeudaEstado[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsDeudaEstadoWrap").css("display", total ? "" : "none");
        $("#fileNameDeudaEstado").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsDeudaEstadoTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsDeudaEstadoContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsDeudaEstado();
        }
      );

      $(document).ready(function () {
        $("#regIncumDeudaEstado").on("change", function () {
          var tipo = $(this).val();

          $(".formulario-DeudaEstado").hide();

          if (tipo == "1") {
            $("#formularioIncumDeudaEstado").show();
          }
        });
      });

      attachYYYYMMFormatter("#fecha_DeudaEstado");
      attachYYYYMMDDFormatter("#fecha_DeudaEstadoPres");

      $("#fechaDeudaEstado").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaDeudaEstadoPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                  } else if (selector === "#pant_pagos_mesas") {
      // cargarPagosMayoresMesas({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickPagosMayoresMesas", function () {
        $("#uploadPagosMayoresMesas").click();
      });

      $(document).on("change", "#uploadPagosMayoresMesas", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsPagosMayoresMesasContainer");
        renderUploadsPagosMayoresMesas();
        var $new = $(
          '<input type="file" id="uploadPagosMayoresMesas" name="uploadPagosMayoresMesas[]" multiple style="display:none;">'
        );
        $("#btnPickPagosMayoresMesas").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsPagosMayoresMesas() {
        var $tbody = $("#uploadsPagosMayoresMesasTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsPagosMayoresMesasContainer input[type=file][name="uploadPagosMayoresMesas[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsPagosMayoresMesasWrap").css("display", total ? "" : "none");
        $("#fileNamePagosMayoresMesas").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsPagosMayoresMesasTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsPagosMayoresMesasContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsPagosMayoresMesas();
        }
      );
      attachYYYYMMFormatter("#fecha_PagosMayoresMesas");

      $("#fechaPagosMayoresMesas").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_oper") {
      // cargarReporteYLavado({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickReporteYLavado", function () {
        $("#uploadReporteYLavado").click();
      });

      $(document).on("change", "#uploadReporteYLavado", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsReporteYLavadoContainer");
        renderUploadsReporteYLavado();
        var $new = $(
          '<input type="file" id="uploadReporteYLavado" name="uploadReporteYLavado[]" multiple style="display:none;">'
        );
        $("#btnPickReporteYLavado").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsReporteYLavado() {
        var $tbody = $("#uploadsReporteYLavadoTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsReporteYLavadoContainer input[type=file][name="uploadReporteYLavado[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsReporteYLavadoWrap").css("display", total ? "" : "none");
        $("#fileNameReporteYLavado").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsReporteYLavadoTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsReporteYLavadoContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsReporteYLavado();
        }
      );

      attachYYYYMMFormatter("#fecha_ReporteYLavado");

      $("#fechaReporteYLavado").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_registros") {
      // cargarRegistrosContables({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickRegistrosContables", function () {
        $("#uploadRegistrosContables").click();
      });

      $(document).on("change", "#uploadRegistrosContables", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsRegistrosContablesContainer");
        renderUploadsRegistrosContables();
        var $new = $(
          '<input type="file" id="uploadRegistrosContables" name="uploadRegistrosContables[]" multiple style="display:none;">'
        );
        $("#btnPickRegistrosContables").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsRegistrosContables() {
        var $tbody = $("#uploadsRegistrosContablesTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsRegistrosContablesContainer input[type=file][name="uploadRegistrosContables[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsRegistrosContablesWrap").css("display", total ? "" : "none");
        $("#fileNameRegistrosContables").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsRegistrosContablesTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsRegistrosContablesContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsRegistrosContables();
        }
      );

      attachYYYYMMFormatter("#fecha_RegistrosContables");

      $("#fechaRegistrosContables").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_aportes") {
      // cargarAportesPatronales({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickAportesPatronales", function () {
        $("#uploadAportesPatronales").click();
      });

      $(document).on("change", "#uploadAportesPatronales", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsAportesPatronalesContainer");
        renderUploadsAportesPatronales();
        var $new = $(
          '<input type="file" id="uploadAportesPatronales" name="uploadAportesPatronales[]" multiple style="display:none;">'
        );
        $("#btnPickAportesPatronales").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsAportesPatronales() {
        var $tbody = $("#uploadsAportesPatronalesTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsAportesPatronalesContainer input[type=file][name="uploadAportesPatronales[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsAportesPatronalesWrap").css("display", total ? "" : "none");
        $("#fileNameAportesPatronales").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsAportesPatronalesTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsAportesPatronalesContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsAportesPatronales();
        }
      );

      attachYYYYMMFormatter("#fecha_AportesPatronales");
      attachYYYYMMDDFormatter("#fecha_AportesPatronalesPres");
      attachYYYYMMDDFormatter("#fecha_pago_AportesPatronales");

      $("#fechaAportesPatronales").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaAportesPatronalesPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_pagoAportesPatronales").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_promoticket") {
      // cargarPromoTickets({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickPromoTickets", function () {
        $("#uploadPromoTickets").click();
      });

      $(document).on("change", "#uploadPromoTickets", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsPromoTicketsContainer");
        renderUploadsPromoTickets();
        var $new = $(
          '<input type="file" id="uploadPromoTickets" name="uploadPromoTickets[]" multiple style="display:none;">'
        );
        $("#btnPickPromoTickets").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsPromoTickets() {
        var $tbody = $("#uploadsPromoTicketsTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsPromoTicketsContainer input[type=file][name="uploadPromoTickets[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsPromoTicketsWrap").css("display", total ? "" : "none");
        $("#fileNamePromoTickets").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsPromoTicketsTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsPromoTicketsContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsPromoTickets();
        }
      );

      attachYYYYMMFormatter("#fecha_PromoTickets");

      $("#fechaPromoTickets").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_pozos_acumulados") {
      // cargarPozosAcumuladosLinkeados({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickPozosAcumuladosLinkeados", function () {
        $("#uploadPozosAcumuladosLinkeados").click();
      });

      $(document).on("change", "#uploadPozosAcumuladosLinkeados", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsPozosAcumuladosLinkeadosContainer");
        renderUploadsPozosAcumuladosLinkeados();
        var $new = $(
          '<input type="file" id="uploadPozosAcumuladosLinkeados" name="uploadPozosAcumuladosLinkeados[]" multiple style="display:none;">'
        );
        $("#btnPickPozosAcumuladosLinkeados")
          .closest(".input-group")
          .append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsPozosAcumuladosLinkeados() {
        var $tbody = $("#uploadsPozosAcumuladosLinkeadosTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsPozosAcumuladosLinkeadosContainer input[type=file][name="uploadPozosAcumuladosLinkeados[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsPozosAcumuladosLinkeadosWrap").css(
          "display",
          total ? "" : "none"
        );
        $("#fileNamePozosAcumuladosLinkeados").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsPozosAcumuladosLinkeadosTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsPozosAcumuladosLinkeadosContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsPozosAcumuladosLinkeados();
        }
      );

      attachYYYYMMFormatter("#fecha_PozosAcumuladosLinkeados");

      $("#fechaPozosAcumuladosLinkeados").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_contrib_ente") {
      // cargarContribEnteTuristico({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickContribEnteTuristico", function () {
        $("#uploadContribEnteTuristico").click();
      });

      $(document).on("change", "#uploadContribEnteTuristico", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsContribEnteTuristicoContainer");
        renderUploadsContribEnteTuristico();
        var $new = $(
          '<input type="file" id="uploadContribEnteTuristico" name="uploadContribEnteTuristico[]" multiple style="display:none;">'
        );
        $("#btnPickContribEnteTuristico").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsContribEnteTuristico() {
        var $tbody = $("#uploadsContribEnteTuristicoTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsContribEnteTuristicoContainer input[type=file][name="uploadContribEnteTuristico[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsContribEnteTuristicoWrap").css(
          "display",
          total ? "" : "none"
        );
        $("#fileNameContribEnteTuristico").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsContribEnteTuristicoTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsContribEnteTuristicoContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsContribEnteTuristico();
        }
      );

      attachYYYYMMFormatter("#fecha_ContribEnteTuristico");

      attachYYYYMMDDFormatter("#fecha_venc_ContribEnteTuristico");
      attachYYYYMMDDFormatter("#fecha_ContribEnteTuristicoPres");

      $("#fechaContribEnteTuristico").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fechaContribEnteTuristicoPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_vencContribEnteTuristico").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_rrhh") {
      // cargarRRHH({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickRRHH", function () {
        $("#uploadRRHH").click();
      });

      $(document).on("change", "#uploadRRHH", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsRRHHContainer");
        renderUploadsRRHH();
        var $new = $(
          '<input type="file" id="uploadRRHH" name="uploadRRHH[]" multiple style="display:none;">'
        );
        $("#btnPickRRHH").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsRRHH() {
        var $tbody = $("#uploadsRRHHTable tbody").empty();
        var total = 0,
          row = 1;

        $('#uploadsRRHHContainer input[type=file][name="uploadRRHH[]"]').each(
          function (ix) {
            var $inp = $(this);
            var gid = $inp.attr("data-group");
            var files = this.files || [];
            for (var i = 0; i < files.length; i++) {
              var f = files[i];
              total++;
              var $tr = $("<tr></tr>");
              $tr.append("<td>" + row++ + "</td>");
              $tr.append("<td>" + f.name + "</td>");
              $tr.append("<td>" + humanSize(f.size) + "</td>");
              var $btn = $(
                '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                  '<i class="fa fa-trash"></i>' +
                  "</button>"
              )
                .attr("data-group", gid)
                .attr("data-idx", i);
              $tr.append($("<td></td>").append($btn));
              $tbody.append($tr);
            }
          }
        );

        $("#uploadsRRHHWrap").css("display", total ? "" : "none");
        $("#fileNameRRHH").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsRRHHTable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsRRHHContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsRRHH();
      });

      $(document).ready(function () {
        function setOfertadoPorCasino(tipo) {
          if (tipo == "1") {
            $("#ofertado_adjudicado_RRHH").val(194); // Melincué
          } else if (tipo == "2") {
            $("#ofertado_adjudicado_RRHH").val(585); // Santa Fe
          } else if (tipo == "3") {
            $("#ofertado_adjudicado_RRHH").val(1398); // Rosario
          } else {
            $("#ofertado_adjudicado_RRHH").val("");
          }
        }

        function mesRRHHSeleccionado() {
          return String($("#fecha_RRHH").val() || "")
            .trim()
            .slice(0, 7);
        }

        function cargarPersonalInicio(casinoId, mes) {
          // Autocompleta con el personal al final del mes anterior, pero SOLO si el campo está
          // vacío: si ya tiene un valor (lo escribió el usuario, o es el que trae el registro que
          // se está editando) no se toca. Aplica igual en crear y en editar.
          if ($.trim($("#personal_inicio_RRHH").val())) return;
          if (!casinoId || !mes) return;
          $.get(
            "/documentosContables/ultimosPersonalRRHH/" + casinoId,
            { mes: mes },
            function (data) {
              // Se vuelve a chequear al llegar la respuesta: puede haberse llenado mientras
              // esperábamos (p.ej. al editar, el propio registro asigna su personal_inicio justo
              // después de disparar este trigger). Así no se pisa el valor real del registro.
              if ($.trim($("#personal_inicio_RRHH").val())) return;

              var val = null;

              if (data == null) {
                val = "";
              } else if (typeof data === "object") {
                if ("personal_inicio" in data) val = data.personal_inicio;
                else if ("value" in data) val = data.value;
                else val = "";
              } else {
                val = String(data).trim();
              }

              $("#personal_inicio_RRHH").val(val);

              $("#personal_inicio_RRHH").trigger("input");
            }
          );
        }

        $("#casinoRRHH").on("change", function () {
          var tipo = $(this).val();

          $("#formNuevoRegistroRRHH .has-error").removeClass("has-error");
          $("#formNuevoRegistroRRHH .help-block.js-error").remove();

          setOfertadoPorCasino(tipo);

          cargarPersonalInicio(tipo, mesRRHHSeleccionado());
        });

        // El mes también decide de qué registro se toma el valor (el del mes anterior), así que
        // hay que recalcular cuando cambia, no solo cuando cambia el casino.
        $(document).on("change", "#fecha_RRHH", function () {
          cargarPersonalInicio($("#casinoRRHH").val(), mesRRHHSeleccionado());
        });
        $("#fechaRRHH").on("changeDate", function () {
          cargarPersonalInicio($("#casinoRRHH").val(), mesRRHHSeleccionado());
        });

        // Si el usuario deja el campo vacío (lo borró, o está editando un registro viejo sin
        // este dato), al salir del campo se autocompleta. Si tiene algo puesto, cargarPersonalInicio
        // no lo toca (ver el guard adentro).
        $(document).on("blur", "#personal_inicio_RRHH", function () {
          cargarPersonalInicio($("#casinoRRHH").val(), mesRRHHSeleccionado());
        });

        $(document).on("shown.bs.modal", "#modalCargarRRHH", function () {
          $("#casinoRRHH").trigger("change");
        });
      });
      $(document).on("shown.bs.modal", "#modalCargarRRHH", function () {
        $(this)
          .find('[data-toggle="popover"]')
          .popover({ container: "body", trigger: "hover focus" });
      });
      attachYYYYMMFormatter("#fecha_RRHH");

      $("#fechaRRHH").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_ganancias") {
      // cargarGanancias({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      // cargarGanancias_periodo({ page: 1, perPage: 10}); // COMMENTED: moved to buscarPestana

      $(document).on("click", "#btnPickGanancias", function () {
        $("#uploadGanancias").click();
      });

      $(document).on("change", "#uploadGanancias", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsGananciasContainer");
        renderUploadsGanancias();
        var $new = $(
          '<input type="file" id="uploadGanancias" name="uploadGanancias[]" multiple style="display:none;">'
        );
        $("#btnPickGanancias").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsGanancias() {
        var $tbody = $("#uploadsGananciasTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsGananciasContainer input[type=file][name="uploadGanancias[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsGananciasWrap").css("display", total ? "" : "none");
        $("#fileNameGanancias").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsGananciasTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsGananciasContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsGanancias();
        }
      );

      $(document).on("click", "#btnPickGanancias_periodo", function () {
        $("#uploadGanancias_periodo").click();
      });

      $(document).on("change", "#uploadGanancias_periodo", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsGanancias_periodoContainer");
        renderUploadsGanancias_periodo();
        var $new = $(
          '<input type="file" id="uploadGanancias_periodo" name="uploadGanancias_periodo[]" multiple style="display:none;">'
        );
        $("#btnPickGanancias_periodo").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsGanancias_periodo() {
        var $tbody = $("#uploadsGanancias_periodoTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsGanancias_periodoContainer input[type=file][name="uploadGanancias_periodo[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsGanancias_periodoWrap").css("display", total ? "" : "none");
        $("#fileNameGanancias_periodo").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsGanancias_periodoTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsGanancias_periodoContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsGanancias_periodo();
        }
      );

      attachYYYYMMDDFormatter("#fecha_pago_Ganancias");
      attachYYYYMMDDFormatter("#fecha_pago_Ganancias_periodo");

      $("#fechaGanancias_periodoPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy",
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
      $("#fechaGananciasPres").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy",
        pickerPosition: "bottom-left",
        startView: 4,
        minView: 4,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_pres_Ganancias_periodo").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
      $("#fecha_pagoGanancias").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_jackpots_pagados") {
      // cargarJackpotsPagados({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickJackpotsPagados", function () {
        $("#uploadJackpotsPagados").click();
      });

      $(document).on("change", "#uploadJackpotsPagados", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsJackpotsPagadosContainer");
        renderUploadsJackpotsPagados();
        var $new = $(
          '<input type="file" id="uploadJackpotsPagados" name="uploadJackpotsPagados[]" multiple style="display:none;">'
        );
        $("#btnPickJackpotsPagados").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsJackpotsPagados() {
        var $tbody = $("#uploadsJackpotsPagadosTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsJackpotsPagadosContainer input[type=file][name="uploadJackpotsPagados[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsJackpotsPagadosWrap").css("display", total ? "" : "none");
        $("#fileNameJackpotsPagados").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsJackpotsPagadosTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsJackpotsPagadosContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsJackpotsPagados();
        }
      );

      attachYYYYMMFormatter("#fecha_JackpotsPagados");

      $("#fechaJackpotsPagados").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_premios_pagados") {
      // cargarPremiosPagados({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickPremiosPagados", function () {
        $("#uploadPremiosPagados").click();
      });

      $(document).on("change", "#uploadPremiosPagados", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsPremiosPagadosContainer");
        renderUploadsPremiosPagados();
        var $new = $(
          '<input type="file" id="uploadPremiosPagados" name="uploadPremiosPagados[]" multiple style="display:none;">'
        );
        $("#btnPickPremiosPagados").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsPremiosPagados() {
        var $tbody = $("#uploadsPremiosPagadosTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsPremiosPagadosContainer input[type=file][name="uploadPremiosPagados[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsPremiosPagadosWrap").css("display", total ? "" : "none");
        $("#fileNamePremiosPagados").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsPremiosPagadosTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsPremiosPagadosContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsPremiosPagados();
        }
      );
      attachYYYYMMFormatter("#fecha_PremiosPagados");

      $("#fechaPremiosPagados").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_premios_mtm") {
      // cargarPremiosMTM({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

                                          $("#fechaPremiosMTM_Unificado").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        container: $("#modalCargarPremiosMTM"),
      });

          } else if (selector === "#pant_direct") {
      // cargarAutDirectores({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickAutDirectores", function () {
        $("#uploadAutDirectores").click();
      });

      $(document).on("change", "#uploadAutDirectores", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsAutDirectoresContainer");
        renderUploadsAutDirectores();
        var $new = $(
          '<input type="file" id="uploadAutDirectores" name="uploadAutDirectores[]" multiple style="display:none;">'
        );
        $("#btnPickAutDirectores").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsAutDirectores() {
        var $tbody = $("#uploadsAutDirectoresTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsAutDirectoresContainer input[type=file][name="uploadAutDirectores[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsAutDirectoresWrap").css("display", total ? "" : "none");
        $("#fileNameAutDirectores").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsAutDirectoresTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsAutDirectoresContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsAutDirectores();
        }
      );

      attachYYYYMMFormatter("#fecha_AutDirectores_autorizacion");

      $("#casinoAutDirectores_autorizacion")
        .off("change.autdir")
        .on("change.autdir", function () {
          if ($("#AutDirectores_modo").val() === "edit") return;
          var id = $(this).val();
          $("#zona-directores").html("");
          if (!id) {
            return;
          }
          $.getJSON(
            "/documentosContables/AutDirectoresHabilitadosPorCasino/" + id,
            function (data) {
              // Precarga: los habilitados del casino, sin tildar. Los demás (incluidos los
              // inhabilitados) se suman a mano desde el combo de "AGREGAR AL REGISTRO".
              $("#zona-directores").html(
                (data && data.length)
                  ? data.map(filaDirectorAutDir).join("")
                  : '<div class="col-md-12 text-muted">Sin directores habilitados para este casino. Podés agregarlos con el botón de abajo.</div>'
              );
              refrescarComboAgregarDirector();
            }
          );
        });

      $("#fechaAutDirectores_autorizacion").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_seguros") {
      // cargarSeguros({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters
      $(document).on("click", "#btnPickSeguros", function () {
        $("#uploadSeguros").click();
      });

      $(document).on("change", "#uploadSeguros", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsSegurosContainer");
        renderUploadsSeguros();
        var $new = $(
          '<input type="file" id="uploadSeguros" name="uploadSeguros[]" multiple style="display:none;">'
        );
        $("#btnPickSeguros").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsSeguros() {
        var $tbody = $("#uploadsSegurosTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsSegurosContainer input[type=file][name="uploadSeguros[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsSegurosWrap").css("display", total ? "" : "none");
        $("#fileNameSeguros").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsSegurosTable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsSegurosContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsSeguros();
      });

      attachYYYYMMDDFormatter("#fecha_SegurosDes");
      attachYYYYMMDDFormatter("#fecha_SegurosHas");
      cargarTiposSeguro();

      $("#fechaSegurosDes").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
      $("#fechaSegurosHas").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_derecho") {
      // cargarDerechoAcceso({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickDerechoAcceso", function () {
        $("#uploadDerechoAcceso").click();
      });

      $(document).on("change", "#uploadDerechoAcceso", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsDerechoAccesoContainer");
        renderUploadsDerechoAcceso();
        var $new = $(
          '<input type="file" id="uploadDerechoAcceso" name="uploadDerechoAcceso[]" multiple style="display:none;">'
        );
        $("#btnPickDerechoAcceso").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsDerechoAcceso() {
        var $tbody = $("#uploadsDerechoAccesoTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsDerechoAccesoContainer input[type=file][name="uploadDerechoAcceso[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsDerechoAccesoWrap").css("display", total ? "" : "none");
        $("#fileNameDerechoAcceso").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsDerechoAccesoTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsDerechoAccesoContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsDerechoAcceso();
        }
      );

      attachYYYYMMFormatter("#fecha_DerechoAcceso");
      attachYYYYMMDDFormatter("#fecha_venc_DerechoAcceso");

      $("#fechaDerechoAcceso").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
            $("#fecha_vencDerechoAcceso").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm-dd",
        pickerPosition: "bottom-left",
        startView: 2,
        minView: 2,
        ignoreReadonly: true,

        timePicker: false,
        container: $("main section"),
      });
                            } else if (selector === "#pant_patentes") {
      // cargarPatentes({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickPatentes", function () {
        $("#uploadPatentes").click();
      });

      $(document).on("change", "#uploadPatentes", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsPatentesContainer");
        renderUploadsPatentes();
        var $new = $(
          '<input type="file" id="uploadPatentes" name="uploadPatentes[]" multiple style="display:none;">'
        );
        $("#btnPickPatentes").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsPatentes() {
        var $tbody = $("#uploadsPatentesTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsPatentesContainer input[type=file][name="uploadPatentes[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsPatentesWrap").css("display", total ? "" : "none");
        $("#fileNamePatentes").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on("click", "#uploadsPatentesTable .btn-danger", function () {
        var gid = $(this).attr("data-group");
        var idx = parseInt($(this).attr("data-idx"), 10);
        var $inp = $(
          '#uploadsPatentesContainer input[type=file][data-group="' + gid + '"]'
        );
        if (!$inp.length) return;

        var dt = new DataTransfer();
        var files = $inp[0].files;
        for (var i = 0; i < files.length; i++) {
          if (i !== idx) dt.items.add(files[i]);
        }
        $inp[0].files = dt.files;
        if ($inp[0].files.length === 0) $inp.remove();
        renderUploadsPatentes();
      });

      attachYYYYMMFormatter("#fecha_Patentes");

      $("#fechaPatentes").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
                                        } else if (selector === "#pant_inmobiliario") {
      // cargarImpInmobiliario({ page: 1, perPage: 10}); // COMMENTED: buscarPestana() already loads with filters

      $(document).on("click", "#btnPickImpInmobiliario", function () {
        $("#uploadImpInmobiliario").click();
      });

      $(document).on("change", "#uploadImpInmobiliario", function () {
        if (!this.files || !this.files.length) return;
        var gid =
          "lote_" +
          (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
        $(this)
          .attr("data-group", gid)
          .removeAttr("id")
          .addClass("archived")
          .appendTo("#uploadsImpInmobiliarioContainer");
        renderUploadsImpInmobiliario();
        var $new = $(
          '<input type="file" id="uploadImpInmobiliario" name="uploadImpInmobiliario[]" multiple style="display:none;">'
        );
        $("#btnPickImpInmobiliario").closest(".input-group").append($new);
      });

      function humanSize(n) {
        if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
        if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
        return n + " B";
      }

      function renderUploadsImpInmobiliario() {
        var $tbody = $("#uploadsImpInmobiliarioTable tbody").empty();
        var total = 0,
          row = 1;

        $(
          '#uploadsImpInmobiliarioContainer input[type=file][name="uploadImpInmobiliario[]"]'
        ).each(function (ix) {
          var $inp = $(this);
          var gid = $inp.attr("data-group");
          var files = this.files || [];
          for (var i = 0; i < files.length; i++) {
            var f = files[i];
            total++;
            var $tr = $("<tr></tr>");
            $tr.append("<td>" + row++ + "</td>");
            $tr.append("<td>" + f.name + "</td>");
            $tr.append("<td>" + humanSize(f.size) + "</td>");
            var $btn = $(
              '<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar">' +
                '<i class="fa fa-trash"></i>' +
                "</button>"
            )
              .attr("data-group", gid)
              .attr("data-idx", i);
            $tr.append($("<td></td>").append($btn));
            $tbody.append($tr);
          }
        });

        $("#uploadsImpInmobiliarioWrap").css("display", total ? "" : "none");
        $("#fileNameImpInmobiliario").val(
          total
            ? total + " archivo(s) seleccionado(s)"
            : ""
        );
      }

      $(document).on(
        "click",
        "#uploadsImpInmobiliarioTable .btn-danger",
        function () {
          var gid = $(this).attr("data-group");
          var idx = parseInt($(this).attr("data-idx"), 10);
          var $inp = $(
            '#uploadsImpInmobiliarioContainer input[type=file][data-group="' +
              gid +
              '"]'
          );
          if (!$inp.length) return;

          var dt = new DataTransfer();
          var files = $inp[0].files;
          for (var i = 0; i < files.length; i++) {
            if (i !== idx) dt.items.add(files[i]);
          }
          $inp[0].files = dt.files;
          if ($inp[0].files.length === 0) $inp.remove();
          renderUploadsImpInmobiliario();
        }
      );

      attachYYYYMMFormatter("#fecha_ImpInmobiliario");

      $("#fechaImpInmobiliario").datetimepicker({
        language: "es",
        todayBtn: 1,
        autoclose: 1,
        todayHighlight: 1,
        format: "yyyy-mm",
        pickerPosition: "bottom-left",
        startView: 3,
        minView: 3,
        ignoreReadonly: true,
        timePicker: false,
        container: $("main section"),
      });
                                        }
  });
  $("[data-js-tabs] a").first().trigger("click");
});

$(document).on("click", ".validarDocumento", function () {
  var id = $(this).data("id");
  var tipo = $(this).data("tipo");
  var valido = $(this).data("valido");
  var nuevoValido = valido == 1 ? 0 : 1;

  $("#btn-confirmar-validacion")
    .data("id", id)
    .data("tipo", tipo)
    .data("valido", nuevoValido);
  $("#modalValidarDocumento").modal("show");
});

$(document).on("click", "#btn-confirmar-validacion", function () {
  var id = $(this).data("id");
  var tipo = $(this).data("tipo");
  var valido = $(this).data("valido");

  $.ajax({
    url: "/documentosContables/validarDocumento",
    type: "POST",
    data: {
      id: id,
      tipo: tipo,
      validar: valido,
    },
    success: function (data) {
      $("#modalValidarDocumento").modal("hide");
      // Refresh preserving page
      var activeTab = $("[data-js-tabs] a.active").attr("data-js-tab");
      if (!activeTab) activeTab = $("[data-js-tabs] a").first().attr("data-js-tab");
      buscarPestana(activeTab, true);
    },
    error: function (xhr) {
      var msg = (xhr.responseJSON && xhr.responseJSON.msg)
        ? xhr.responseJSON.msg
        : "Ocurrió un error al validar el documento.";
      // Cerramos el modal de confirmación y, una vez oculto, mostramos el aviso
      // (evita el solapamiento de backdrops de Bootstrap al encadenar dos modales).
      $("#modalValidarDocumento")
        .one("hidden.bs.modal", function () {
          $("#texto-aviso-validacion").text(msg);
          $("#modalAvisoValidacion").modal("show");
        })
        .modal("hide");
    },
  });
});

// --- ESTADO CONTABLE ---

// Estaba definida DENTRO del $(document).ready() de abajo, así que era una función local y no
// global: prepararModalDocContable la busca con window["cargarArchivos"+tipo+"Lista"], y como no
// existía en window, al editar un Estado Contable la sección de "archivos cargados" quedaba
// siempre vacía/oculta (aunque el registro sí tuviera archivos) — no había forma de llegar al
// botón de borrar. Se saca afuera para que sea global, igual que las otras 26 funciones
// cargarArchivos*Lista del módulo.
function cargarArchivosEstadoContableLista(id, target) {
  var $list = $(targetArchivosDoc("EstadoContable", target))
    .empty()
    .append('<div class="list-group-item">Cargando...</div>');

  $.getJSON("/documentosContables/archivosEstadoContable/" + id)
    .done(function (res) {
      var files = Array.isArray(res)
        ? res
        : res.data || res.archivos || res.items || [];
      $list.empty();
      if (!files.length) {
        $list.append(
          '<div class="list-group-item">Sin archivos asociados.</div>'
        );
        return;
      }
      files.forEach(function (f) {
        var fid = f.id || f.id_registro_archivo || f.id_archivo;
        var nombre =
          f.nombre ||
          f.archivo ||
          (f.path ? String(f.path).split("/").pop() : "archivo");
        var href =
          "/documentosContables/visualizarArchivo/EstadoContable/" +
          encodeURIComponent(nombre);

        var $row = $('<div class="list-group-item clearfix">');
        var $a = $('<a target="_blank">')
          .attr("href", href)
          .text(iconoExt(nombre) + " " + nombreVisibleArchivo(nombre));
        var $del = $(
          '<button type="button" data-id="' +
            fid +
            '" data-reg-id="' +
            id +
            '" data-scope="EstadoContable"  class="btn btn-sm btn-danger btn-del-archivo-EstadoContable" title="Quitar">'
        )
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "ELIMINAR ARCHIVO")
          .css("float", "right")
          .append($("<i>").addClass("fa fa-trash"));

        $row.append($a).append($del);
        $list.append($row);
      });
    })
    .fail(function () {
      $list
        .empty()
        .append(
          '<div class="list-group-item text-danger">Error al cargar archivos.</div>'
        );
    });
}

$(document).ready(function() {
  $('#dtpFechaEstadoContable').datetimepicker({
    language:  'es',
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    format: 'yyyy-mm-dd',
    pickerPosition: "bottom-left",
    startView: 4,
    minView: 2,
    ignoreReadonly: true,
  });

  aplicarNumerico('#modalCargarEstadoContable input:not([readonly])', { permitirNegativos: true });

  $("#modalCargarEstadoContable").on("show.bs.modal", function () {
    $(this).find('[data-toggle="popover"]').popover({ trigger: "hover focus", container: "body" });
  });

  $('[data-js-tab="#pant_estado_contable"]').click(function(e) {
    clickIndiceEstadoContable(e, 1, 20);
  });

  $(document).on("click", "#btnPickEstadoContable", function () {
    $("#uploadEstadoContable").click();
  });

  $(document).on("change", "#uploadEstadoContable", function () {
    if (!this.files || !this.files.length) return;
    var gid = "lote_" + (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
    $(this).attr("data-group", gid).removeAttr("id").addClass("archived").appendTo("#uploadsEstadoContableContainer");
    renderUploadsEstadoContable();
    var $new = $('<input type="file" id="uploadEstadoContable" name="uploadEstadoContable[]" multiple style="display:none;">');
    $("#btnPickEstadoContable").closest(".input-group").append($new);
  });

  function humanSize(n) {
    if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
    if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
    return n + " B";
  }

  function renderUploadsEstadoContable() {
    var $tbody = $("#uploadsEstadoContableTable tbody").empty();
    var total = 0, row = 1;

    $('#uploadsEstadoContableContainer input[type=file][name="uploadEstadoContable[]"]').each(function (ix) {
      var $inp = $(this);
      var gid = $inp.attr("data-group");
      var files = this.files || [];
      for (var i = 0; i < files.length; i++) {
        var f = files[i];
        total++;
        var $tr = $("<tr></tr>");
        $tr.append("<td>" + row++ + "</td>");
        $tr.append("<td>" + f.name + "</td>");
        $tr.append("<td>" + humanSize(f.size) + "</td>");
        var $btn = $('<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar"><i class="fa fa-trash"></i></button>')
          .attr("data-group", gid).attr("data-idx", i);
        $tr.append($("<td></td>").append($btn));
        $tbody.append($tr);
      }
    });

    $("#uploadsEstadoContableWrap").css("display", total ? "" : "none");
    $("#fileNameEstadoContable").val(total ? total + " archivo(s) seleccionado(s)" : "");
  }

  $(document).on("click", "#uploadsEstadoContableTable .btn-danger", function () {
    var gid = $(this).attr("data-group");
    var idx = parseInt($(this).attr("data-idx"));
    var $inp = $('#uploadsEstadoContableContainer input[data-group="' + gid + '"]');
    if (!$inp.length) return;
    
    const filedt = new DataTransfer();
    const files = $inp[0].files;
    for (let i = 0; i < files.length; i++) {
      if (i !== idx) filedt.items.add(files[i]);
    }
    $inp[0].files = filedt.files;
    if ($inp[0].files.length === 0) $inp.remove();
    renderUploadsEstadoContable();
  });

  $("#estado_contable_nuevo").click(function(e) {
    e.preventDefault();
    $("#modalCargarEstadoContable .modal-title").text("| NUEVO ESTADO CONTABLE");
    // Encabezado unificado con el resto de los modales de documentos contables.
    $("#modalCargarEstadoContable .modal-header").attr('style','background-color: #00695c;');
    $("#btn-guardarEstadoContable").removeClass("btn-warning").addClass("btn-success").val("nuevo");
    
    // Limpiar formulario manual
    var frm = $("#frmEstadoContable");
    frm[0].reset();
    frm.find('.has-error').removeClass('has-error');
    frm.find('.js-error').remove();
    $("#id_estado_contable").val("0");
    
    $("#uploadsEstadoContableContainer").empty();
    renderUploadsEstadoContable();

    $("#modalCargarEstadoContable").modal("show");
  });



  $("#btn-guardarEstadoContable").click(function(e) {
    e.preventDefault();
    var formData = new FormData();
    var id = $("#id_estado_contable").val();
    
    formData.append("casinoEstadoContable", $("#casinoEstadoContable").val());
    formData.append("fecha_EstadoContable", $("#inputFechaEstadoContable").val());
    
    formData.append("activo_corriente", _smartParseNum($("#activo_corriente").val()));
    formData.append("activo_corriente_reexpresado", _smartParseNum($("#activo_corriente_reexpresado").val()));
    formData.append("activo_nocorriente", _smartParseNum($("#activo_nocorriente").val()));
    formData.append("activo_nocorriente_reexpresado", _smartParseNum($("#activo_nocorriente_reexpresado").val()));
    
    formData.append("pasivo_corriente", _smartParseNum($("#pasivo_corriente").val()));
    formData.append("pasivo_corriente_reexpresado", _smartParseNum($("#pasivo_corriente_reexpresado").val()));
    formData.append("pasivo_nocorriente", _smartParseNum($("#pasivo_nocorriente").val()));
    formData.append("pasivo_nocorriente_reexpresado", _smartParseNum($("#pasivo_nocorriente_reexpresado").val()));
    
    formData.append("ingresos", _smartParseNum($("#ingresos").val()));
    formData.append("ingresos_reexpresado", _smartParseNum($("#ingresos_reexpresado").val()));
    formData.append("costos", _smartParseNum($("#costos").val()));
    formData.append("costos_reexpresado", _smartParseNum($("#costos_reexpresado").val()));
    
    formData.append("gastos_comercio", _smartParseNum($("#gastos_comercio").val()));
    formData.append("gastos_comercio_reexpresado", _smartParseNum($("#gastos_comercio_reexpresado").val()));
    formData.append("gastos_adm", _smartParseNum($("#gastos_adm").val()));
    formData.append("gastos_adm_reexpresado", _smartParseNum($("#gastos_adm_reexpresado").val()));
    formData.append("recpam", _smartParseNum($("#recpam").val()));
    formData.append("recpam_reexpresado", _smartParseNum($("#recpam_reexpresado").val()));
    formData.append("otros", _smartParseNum($("#otros").val()));
    formData.append("otros_reexpresado", _smartParseNum($("#otros_reexpresado").val()));
    formData.append("imp_ganancias", _smartParseNum($("#imp_ganancias").val()));
    formData.append("imp_ganancias_reexpresado", _smartParseNum($("#imp_ganancias_reexpresado").val()));

    $('#uploadsEstadoContableContainer input[type=file]').each(function(){
      var files = this.files;
      for(var i=0; i<files.length; i++) {
        formData.append('uploadEstadoContable[]', files[i]);
      }
    });

    let url = id == "0" ? "documentosContables/guardarEstadoContable" : "documentosContables/actualizarEstadoContable/"+id;

    $.ajax({
      url: url,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      success: function(res) {
        $("#modalCargarEstadoContable").modal("hide");
        $("#mensajeExito h3").text("ÉXITO");
        $("#mensajeExito p").text("El estado contable han sido guardados correctamente.");
        $("#mensajeExito").show();
        clickIndiceEstadoContable(null, $("#herramientasPaginacionEstadoContable").getCurrentPage() || 1, 20);
      },
      error: function(res) {
        $("#frmEstadoContable .js-error").remove();
        $("#frmEstadoContable .has-error").removeClass("has-error");
        var errors = res.responseJSON || res;
        Object.keys(errors).forEach(key => {
            mostrarErrorValidacion($("#"+key), errors[key][0], false);
        });
      }
    });
  });

  $(document).on("click", ".btn-deleteEstadoContable", function(e) {
    e.preventDefault();
    $("#btn-eliminarEstadoContable").val($(this).val());
    $("#modalEliminarEstadoContable").modal("show");
  });

  $("#btn-eliminarEstadoContable").click(function(e) {
    e.preventDefault();
    $.ajax({
      type: "GET",
      url: "documentosContables/eliminarEstadoContable/" + $(this).val(),
      success: function(res) {
        $("#modalEliminarEstadoContable").modal("hide");
        clickIndiceEstadoContable(null, $("#herramientasPaginacionEstadoContable").getCurrentPage() || 1, 20);
      },
      error: function() { console.log("Error consultando eliminaci\u00f3n estado contable"); }
    });
  });

  $(document).on("click", ".btn-edit-EstadoContable", function(e) {
    e.preventDefault();
    var id = $(this).val();
    $("#modalCargarEstadoContable .modal-title").text("| EDITAR ESTADO CONTABLE");
    // Encabezado unificado con el resto de los modales de documentos contables.
    $("#modalCargarEstadoContable .modal-header").attr('style','background-color: #00695c;');
    $("#btn-guardarEstadoContable").removeClass("btn-success").addClass("btn-warning").val("editar");
    
    var frm = $("#frmEstadoContable");
    frm[0].reset();
    frm.find('.has-error').removeClass('has-error');
    frm.find('.js-error').remove();
    $("#id_estado_contable").val(id);

    $.ajax({
      type: "GET",
      url: "documentosContables/llenarEstadoContableEdit/" + id,
      success: function(res) {
        $("#casinoEstadoContable").val(res.casino);
        $("#inputFechaEstadoContable").val(res.fecha_EstadoContable);
        
        const formatEditVal = (val) => {
          if(!val && val !== 0) return '';
          return formatoAR(val, 2);
        };
        $("#activo_corriente").val(formatEditVal(res.activo_corriente));
        $("#activo_corriente_reexpresado").val(formatEditVal(res.activo_corriente_reexpresado));
        $("#activo_nocorriente").val(formatEditVal(res.activo_nocorriente));
        $("#activo_nocorriente_reexpresado").val(formatEditVal(res.activo_nocorriente_reexpresado));
        
        $("#pasivo_corriente").val(formatEditVal(res.pasivo_corriente));
        $("#pasivo_corriente_reexpresado").val(formatEditVal(res.pasivo_corriente_reexpresado));
        $("#pasivo_nocorriente").val(formatEditVal(res.pasivo_nocorriente));
        $("#pasivo_nocorriente_reexpresado").val(formatEditVal(res.pasivo_nocorriente_reexpresado));
        
        $("#ingresos").val(formatEditVal(res.ingresos));
        $("#ingresos_reexpresado").val(formatEditVal(res.ingresos_reexpresado));
        $("#costos").val(formatEditVal(res.costos));
        $("#costos_reexpresado").val(formatEditVal(res.costos_reexpresado));

        $("#gastos_comercio").val(formatEditVal(res.gastos_comercio));
        $("#gastos_comercio_reexpresado").val(formatEditVal(res.gastos_comercio_reexpresado));
        $("#gastos_adm").val(formatEditVal(res.gastos_adm));
        $("#gastos_adm_reexpresado").val(formatEditVal(res.gastos_adm_reexpresado));
        $("#recpam").val(formatEditVal(res.recpam));
        $("#recpam_reexpresado").val(formatEditVal(res.recpam_reexpresado));
        $("#otros").val(formatEditVal(res.otros));
        $("#otros_reexpresado").val(formatEditVal(res.otros_reexpresado));
        $("#imp_ganancias").val(formatEditVal(res.imp_ganancias));
        $("#imp_ganancias_reexpresado").val(formatEditVal(res.imp_ganancias_reexpresado));

        $("#uploadsEstadoContableContainer").empty();
        renderUploadsEstadoContable();
        
        if (res.fecha_EstadoContable) {
          const year = parseInt(res.fecha_EstadoContable.split('-')[0]);
          $('#modalCargarEstadoContable .ec-anio-actual').text(year);
          $('#modalCargarEstadoContable .ec-anio-anterior').text((year - 1) + ' Reexpresado');
        }
        calcularTotalesEstadoContable('');

        $("#modalCargarEstadoContable").modal("show");
      }
    });
  });

      $(document).on("click", ".btn-del-archivo-EstadoContable", function () {
    const id = $(this).data("id");
    const regId = $(this).data("regId");
    const scope = $(this).data("scope");
    $("#btn-eliminarArchivo").attr("data-id", id);
    $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
  });
});

function cargarEstadoContable({ page = 1, perPage = 20, casino, desde, hasta }) {
  $.ajax({
    url: "documentosContables/ultimasEstadoContable",
    type: "GET",
    data: {
      page: page,
      page_size: perPage,
      id_casino: casino,
      desde: desde,
      hasta: hasta
    },
    dataType: "json",
    success: function(res) {
      $("#cuerpoTablaEstadoContable").empty();
      res.registros.forEach((item) => {
        $("#cuerpoTablaEstadoContable").append(generarFilaEstadoContable(item));
      });
      $("#herramientasPaginacionEstadoContable").generarTitulo(res.pagination.current_page, res.pagination.per_page, res.pagination.total, clickIndiceEstadoContable);
      $("#herramientasPaginacionEstadoContable").generarIndices(res.pagination.current_page, res.pagination.per_page, res.pagination.total, clickIndiceEstadoContable);
    },
    error: function(err) { console.error("Error cargando Estado Contable:", err); }
  });
}

function clickIndiceEstadoContable(e, pageNumber, pageSize) {
  if (e) e.preventDefault();
  cargarEstadoContable({
    page: pageNumber,
    perPage: pageSize,
    casino: $("#filtro_global_casino").val() || undefined,
    desde: $("#filtro_global_desde_input").val() || undefined,
    hasta: $("#filtro_global_hasta_input").val() || undefined
  });
}

function generarFilaEstadoContable(est) {
  const fila = $("<tr>").attr("id", est.id_registroEstadoContable);

  const fecha = est.fecha_EstadoContable ? convertirMesAno(est.fecha_EstadoContable) : "-";
  const casino = est.casino ? est.casino : "-";

  fila
    .append($("<td>").addClass("col-xs-4").html(fecha))
    .append($("<td>").addClass("col-xs-4").text(casino));

  const tdAcc = $("<td>").addClass("col-xs-4");

  const btnEdit = $("<button>").addClass("btn btn-warning btn-edit-EstadoContable").val(est.id_registroEstadoContable).append($("<i>").addClass("fa fa-fw fa-pencil-alt"))
  .attr("data-toggle", "tooltip")
  .attr("data-placement", "bottom")
  .attr("title", "MODIFICAR ESTADO CONTABLE");
  const btnDel  = $("<button>").addClass("btn btn-danger btn-deleteEstadoContable").val(est.id_registroEstadoContable).append($("<i>").addClass("fa fa-fw fa-trash"))
  .attr("data-toggle", "tooltip")
  .attr("data-placement", "bottom")
  .attr("title", "ELIMINAR ESTADO CONTABLE");

  let btnValidar = $("<span>");
  if (true) { // If user has permission, use `obtenerIconoValidacion`
      btnValidar = obtenerIconoValidacion(
        est.valido,
        "estado_contable",
        est.id_registroEstadoContable
      );
  }

  tdAcc.append(btnEdit).append(btnDel).append(btnValidar);


  fila.append(tdAcc);
  // Initialize tooltips immediately when generating row
  fila.find('[data-toggle="tooltip"]')// .tooltip();
  
  return fila;
}



function calcularTotalesEstadoContable(prefix = '') {
  const formatValor = (val, isPercentage = false) => {
    if (isNaN(val) || !isFinite(val)) val = 0;
    if (isPercentage) {
      return formatoAR(val * 100, 2) + '%';
    }
    return '$ ' + formatoAR(val, 2);
  };

  const formatRatio = (val) => {
    if (isNaN(val) || !isFinite(val)) val = 0;
    return formatoAR(val, 4);
  };

  let Totales = { actual: {}, anterior: {} };

  const calcColumn = (sufijo, obj) => {
    const ActivoC = _smartParseNum($(`#${prefix}activo_corriente${sufijo}`).val()) || 0;
    const ActivoNC = _smartParseNum($(`#${prefix}activo_nocorriente${sufijo}`).val()) || 0;
    obj.TotalActivo = ActivoC + ActivoNC;
    $(`#${prefix}total_activo${sufijo}`).val(formatValor(obj.TotalActivo));

    const PasivoC = _smartParseNum($(`#${prefix}pasivo_corriente${sufijo}`).val()) || 0;
    const PasivoNC = _smartParseNum($(`#${prefix}pasivo_nocorriente${sufijo}`).val()) || 0;
    obj.TotalPasivo = PasivoC + PasivoNC;
    $(`#${prefix}total_pasivo${sufijo}`).val(formatValor(obj.TotalPasivo));

    obj.PN = obj.TotalActivo - obj.TotalPasivo;
    $(`#${prefix}patrimonio_neto${sufijo}`).val(formatValor(obj.PN));

    const Ingresos = _smartParseNum($(`#${prefix}ingresos${sufijo}`).val()) || 0;
    const Costos = _smartParseNum($(`#${prefix}costos${sufijo}`).val()) || 0; 
    obj.GananciaBruta = Ingresos + Costos;
    $(`#${prefix}ganancia_bruta${sufijo}`).val(formatValor(obj.GananciaBruta));

    const GastosComercio = _smartParseNum($(`#${prefix}gastos_comercio${sufijo}`).val()) || 0;
    const GastosAdm = _smartParseNum($(`#${prefix}gastos_adm${sufijo}`).val()) || 0;
    const Recpam = _smartParseNum($(`#${prefix}recpam${sufijo}`).val()) || 0;
    const Otros = _smartParseNum($(`#${prefix}otros${sufijo}`).val()) || 0;
    const ImpGanancias = _smartParseNum($(`#${prefix}imp_ganancias${sufijo}`).val()) || 0;

    obj.GananciaEjercicio = obj.GananciaBruta + GastosComercio + GastosAdm + Recpam + Otros + ImpGanancias;
    $(`#${prefix}ganancia_ejercicio${sufijo}`).val(formatValor(obj.GananciaEjercicio));

    // Variaciones Porcentuales (Both colums)
    $(`#${prefix}vp_liquidez_corr${sufijo}`).val(formatRatio(ActivoC / PasivoC));
    $(`#${prefix}vp_solvencia${sufijo}`).val(formatRatio(obj.TotalActivo / obj.TotalPasivo));
    $(`#${prefix}vp_endeudamiento${sufijo}`).val(formatRatio(obj.TotalPasivo / obj.PN));
    $(`#${prefix}vp_activo_pn${sufijo}`).val(formatRatio(obj.TotalActivo / obj.PN));
    $(`#${prefix}vp_resultado_pn${sufijo}`).val(formatRatio(obj.GananciaEjercicio / obj.PN));
    $(`#${prefix}vp_resultado_pasivo${sufijo}`).val(formatRatio(obj.GananciaEjercicio / obj.TotalPasivo));
    
    $(`#${prefix}vp_margen_utilidad${sufijo}`).val(formatRatio(obj.GananciaBruta / Ingresos));
    $(`#${prefix}vp_roe${sufijo}`).val(formatRatio(obj.GananciaBruta / obj.PN));
    $(`#${prefix}vp_roa${sufijo}`).val(formatRatio(obj.GananciaBruta / obj.TotalActivo));
  };

  calcColumn('', Totales.actual);
  calcColumn('_reexpresado', Totales.anterior);

  // Variaciones Anuales (Only compares current vs anterior)
  const calcVarAnual = (id, valActual, valAnterior) => {
    let result = 0;
    if (valAnterior !== 0) {
      result = (valActual / valAnterior) - 1;
    }
    $(`#${prefix}va_${id}`).val(formatValor(result, true));
  };

  calcVarAnual('activo', Totales.actual.TotalActivo, Totales.anterior.TotalActivo);
  calcVarAnual('pasivo', Totales.actual.TotalPasivo, Totales.anterior.TotalPasivo);
  calcVarAnual('patrimonio', Totales.actual.PN, Totales.anterior.PN);
  calcVarAnual('resultado', Totales.actual.GananciaEjercicio, Totales.anterior.GananciaEjercicio);
}

$(document).on('input num:changed', '#modalCargarEstadoContable input:not([readonly])', function() {
  calcularTotalesEstadoContable('');
});

$('#dtpFechaEstadoContable').on('changeDate', function(e) {
  if (e.date) {
    const year = e.date.getFullYear();
    $('#modalCargarEstadoContable .ec-anio-actual').text(year);
    $('#modalCargarEstadoContable .ec-anio-anterior').text((year - 1) + ' Reexpresado');
  }
});


// ----------------- PÚBLICO CASINO -----------------

$(document).on("click", ".btn-editar-publico-casino", function () {
  const id = $(this).val();
  // El botón "ver" se quitó de la tabla: ahora se entra siempre por el de editar, y el modal queda
  // en solo lectura únicamente si el documento ya está validado.
  const readonly =
    docContableValidado($(this));
  
  $.get("/documentosContables/llenarPublicoCasino/" + id, function (data) {
    $("#id_registroPublicoCasino").val(id);
    $(".input-publico-dia").val("");
    
    $("#uploadsPublicoCasinoContainer").empty();
    $("#uploadsPublicoCasinoWrap").hide();
    $("#fileNamePublicoCasino").val("");
    var $new = $('<input type="file" id="uploadPublicoCasino" name="uploadPublicoCasino[]" multiple style="display:none;">');
    $("#uploadPublicoCasino").remove();
    $("#btnPickPublicoCasino").closest(".input-group").append($new);
    
    // Parse fecha_mes accurately avoiding timezone offsets changing the month
    const parts = data.fecha_mes.split("-");
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10);
    const daysInMonth = new Date(year, month, 0).getDate();
    
    for(let i = 1; i <= 31; i++) {
        let val = data["dia_"+i];
        if (val !== null) $("#pc_dia_"+i).val(formatoAR(val, 0));
        if (i > daysInMonth) {
            $("#pc_dia_"+i).prop("disabled", true).val("");
        } else {
            $("#pc_dia_"+i).prop("disabled", readonly);
        }
    }
    
    if (readonly) {
        $("#btn-guardarPublicoCasino").hide();
    } else {
        $("#btn-guardarPublicoCasino").show();
    }
    
    $(".input-publico-dia").first().trigger("change");
    $("#modalPublicoCasino").modal("show");
  });
});

$(document).on("input blur change", ".input-publico-dia", function(e) {
  if (e.type === "input") {
    let clean = $(this).val().replace(/[^0-9.]/g, "");
    if (clean !== $(this).val()) {
      $(this).val(clean);
    }
  }

  let total = 0;
  $(".input-publico-dia").each(function() {
    let raw = $(this).val();
    let num = parseInt(raw.replace(/\./g, ""), 10);
    if (!isNaN(num)) {
      total += num;
      if (e.type === "blur" && raw !== "") {
        $(this).val(formatoAR(num, 0));
      }
    } else if (e.type === "blur") {
      $(this).val("");
    }
  });
  $("#pc_total_mensual_input").val(formatoAR(total, 0));
});

$(document).on("click", "#btn-guardarPublicoCasino", function (e) {
  e.preventDefault();
  const id = $("#id_registroPublicoCasino").val();
  const formData = new FormData($("#frmPublicoCasino")[0]);
  
  // Añadir archivos que están en el input clonado (el real).
  // Si hubiera múltiples selecciones acumuladas, se manejan enviándolos aquí.
  // Pero ya usamos FormData con el input del form, así que los envía.

  $.ajax({
    type: "POST",
    url: "/documentosContables/actualizarPublicoCasino/" + id,
    data: formData,
    dataType: "json",
    processData: false,
    contentType: false,
    success: function (data) {
      $("#modalPublicoCasino").modal("hide");
      var activeTab = $("[data-js-tabs] a.active").attr("data-js-tab");
      if (!activeTab) activeTab = $("[data-js-tabs] a").first().attr("data-js-tab");
      buscarPestana(activeTab, true);
    },
    error: function (data) {
      console.error(data);
    }
  });
});

function cargarPublicoCasino({ page = 1, perPage = 10, casino, desde, hasta }) {
  $.ajax({
    type: "GET",
    url: "/documentosContables/ultimasPublicoCasino",
    data: { page: page, page_size: perPage, id_casino: casino, desde: desde, hasta: hasta },
    success: function (data) {
      $("#herramientasPaginacionPublicoCasino").generarTitulo(data.pagination.current_page, data.pagination.per_page, data.pagination.total, clickIndicePublicoCasino);
      $("#herramientasPaginacionPublicoCasino").generarIndices(data.pagination.current_page, data.pagination.per_page, data.pagination.total, clickIndicePublicoCasino);
      $("#cuerpoTablaPublicoCasino").empty();
      data.registros.forEach(function (r) {
        let fila = $("<tr>");
        let btnEditar = $("<button>").addClass("btn btn-warning btn-sm btn-editar-publico-casino").val(r.id_registroPublicoCasino).append($("<i>").addClass("fa fa-fw fa-pencil-alt"))
          .attr("data-toggle", "tooltip")
          .attr("data-placement", "bottom")
          .attr("title", "MODIFICAR PÚBLICO CASINO");
          
        let btnValidar = obtenerIconoValidacion(r.valido, 'publico_casino', r.id_registroPublicoCasino);
        
        let parts = r.fecha_mes.split("-");
        let year = parts[0];
        let month = parts[1];
        let daysInMonth = new Date(year, month, 0).getDate();
        let mesFormatText = convertirMesAno(r.fecha_mes);

        fila.append($("<td>").addClass("col-xs-3").text(mesFormatText));
        fila.append($("<td>").addClass("col-xs-3").text(r.casino));
        
        fila.append($("<td>").addClass("col-xs-3").text(r.dias_completados + " / " + daysInMonth));
        let tdAcc = $("<td>").addClass("col-xs-3 d-flex flex-wrap");
        tdAcc.append(btnEditar).append(btnValidar);
        fila.append(tdAcc);
        $("#cuerpoTablaPublicoCasino").append(fila);
      });
    },
    error: function (xhr, status, error) {
      console.error("Error en ultimasPublicoCasino: ", error, xhr.responseText);
      // alert("Ocurrió un error al cargar la tabla de Público Casino.");
    }
  });
}

function clickIndicePublicoCasino(e, pageNumber, tam) {
  if (e != null) e.preventDefault();
  var tam = tam != null ? tam : $("#herramientasPaginacionPublicoCasino").getPageSize();
  cargarPublicoCasino({
      page: pageNumber, 
      perPage: tam, 
      casino: $("#filtro_global_casino").val(), 
      desde: $("#filtro_global_desde_input").val(), 
      hasta: $("#filtro_global_hasta_input").val()
  });
}

$(document).on("click", "#btnPickPublicoCasino", function () {
  $("#uploadPublicoCasino").click();
});

$(document).on("change", "#uploadPublicoCasino", function () {
  if (!this.files || !this.files.length) return;
  var gid = "lote_" + (Date.now().toString(36) + Math.random().toString(36).slice(2, 6));
  $(this).attr("data-group", gid).removeAttr("id").addClass("archived").appendTo("#uploadsPublicoCasinoContainer");
  renderUploadsPublicoCasino();
  var $new = $('<input type="file" id="uploadPublicoCasino" name="uploadPublicoCasino[]" multiple style="display:none;">');
  $("#btnPickPublicoCasino").closest(".input-group").append($new);
});

function humanSize(n) {
  if (n >= 1024 * 1024) return (n / 1048576).toFixed(1) + " MB";
  if (n >= 1024) return (n / 1024).toFixed(0) + " KB";
  return n + " B";
}

function renderUploadsPublicoCasino() {
  var $tbody = $("#uploadsPublicoCasinoTable tbody").empty();
  var total = 0, row = 1;
  $('#uploadsPublicoCasinoContainer input[type=file][name="uploadPublicoCasino[]"]').each(function () {
    var gid = $(this).attr("data-group");
    var files = this.files || [];
    for (var i = 0; i < files.length; i++) {
      var f = files[i];
      total++;
      var $tr = $("<tr></tr>");
      $tr.append("<td>" + row++ + "</td>");
      $tr.append("<td>" + f.name + "</td>");
      $tr.append("<td>" + humanSize(f.size) + "</td>");
      var $btn = $('<button type="button" class="btn btn-xs btn-danger btn-remove-file" title="Quitar"><i class="fa fa-trash"></i></button>')
        .attr("data-group", gid).attr("data-idx", i);
      $tr.append($("<td></td>").append($btn));
      $tbody.append($tr);
    }
  });
  $("#uploadsPublicoCasinoWrap").css("display", total ? "" : "none");
  $("#fileNamePublicoCasino").val(total ? total + " archivo(s) seleccionado(s)" : "");
}

$(document).on("click", "#uploadsPublicoCasinoTable .btn-danger", function () {
  var gid = $(this).attr("data-group");
  var idx = parseInt($(this).attr("data-idx"));
  var $inp = $('#uploadsPublicoCasinoContainer input[data-group="' + gid + '"]');
  if (!$inp.length) return;
  const filedt = new DataTransfer();
  const files = $inp[0].files;
  for (let i = 0; i < files.length; i++) {
    if (i !== idx) filedt.items.add(files[i]);
  }
  $inp[0].files = filedt.files;
  if ($inp[0].files.length === 0) $inp.remove();
  renderUploadsPublicoCasino();
});

function cargarArchivosPublicoCasinoLista(id, target) {
  var $list = $(targetArchivosDoc("PublicoCasino", target)).empty().append('<div class="list-group-item">Cargando...</div>');
  $.getJSON("/documentosContables/archivosPublicoCasino/" + id).done(function (res) {
    var files = Array.isArray(res) ? res : res.data || res.archivos || res.items || [];
    $list.empty();
    if (!files.length) {
      $list.append('<div class="list-group-item">Sin archivos asociados.</div>');
      return;
    }
    files.forEach(function (f) {
      var fid = f.id || f.id_registro_archivo || f.id_archivo;
      var nombre = f.nombre || f.archivo || (f.path ? String(f.path).split("/").pop() : "archivo");
      var href = "/documentosContables/visualizarArchivo/PublicoCasino/" + encodeURIComponent(nombre);
      var html = '<div class="list-group-item d-flex justify-content-between align-items-center" style="display:flex;">' +
        '<a href="' + href + '" target="_blank" style="flex:1;">' + nombre + '</a>' +
        '<button class="btn btn-danger btn-xs btn-del-archivo-publico-casino" data-id="' + fid + '" data-reg-id="' + id + '" data-scope="publicoCasino" title="Eliminar"><i class="fa fa-trash"></i></button>' +
        '</div>';
      $list.append(html);
    });
  }).fail(function () {
    $list.empty().append('<div class="list-group-item text-danger">Error al cargar archivos.</div>');
  });
}

$(document).on("click", ".btn-del-archivo-publico-casino", function () {
  const id = $(this).data("id");
  const regId = $(this).data("regId");
  const scope = $(this).data("scope");
  $("#btn-eliminarArchivo").attr("data-id", id);
  $("#modalEliminarArchivo").data({ id, regId, scope }).modal("show");
});

$(document).on("click", "#btn-totalesAnualesPublicoCasino", function() {
    let currentYear = new Date().getFullYear();
    let selAnio = $("#ta_anio");
    selAnio.empty();
    for (let y = currentYear; y >= 2020; y--) {
        selAnio.append($("<option>").val(y).text(y));
    }
    
    $("#ta_tbody").empty();
    $("#ta_tfoot_row").empty();
    $("#modalTotalesAnualesPublicoCasino").modal("show");
    
    setTimeout(function() {
        $("#btn-buscarTotalesAnuales").click();
    }, 500);
});

$(document).on("click", "#btn-buscarTotalesAnuales", function() {
    let casino = $("#ta_casino").val();
    let anio = $("#ta_anio").val();
    
    if(!casino || !anio) return;
    
    $.get("/documentosContables/totalesAnualesPublicoCasino", { id_casino: casino, anio: anio }, function(data) {
        let matrix = data.datos;
        
        let tbody = $("#ta_tbody");
        let tfoot = $("#ta_tfoot_row");
        tbody.empty();
        tfoot.empty();
        
        let colTotals = {1:0, 2:0, 3:0, 4:0, 5:0, 6:0, 7:0, 8:0, 9:0, 10:0, 11:0, 12:0, 'anual':0};
        
        for (let d = 1; d <= 31; d++) {
            let tr = $("<tr>");
            tr.append($("<td>").text(d).css("font-weight", "bold").css("background-color", "#f5f5f5"));
            
            let rowTotal = 0;
            for (let m = 1; m <= 12; m++) {
                let yearInt = parseInt(anio, 10);
                let daysInMonth = new Date(yearInt, m, 0).getDate();
                let td = $("<td>");
                
                if (d > daysInMonth) {
                    td.css("background-color", "#ddd");
                } else {
                    let val = matrix[m] ? matrix[m][d] : null;
                    if (val !== null) {
                        td.text(formatoAR(val, 0));
                        rowTotal += val;
                        colTotals[m] += val;
                    } else {
                        td.text("");
                    }
                }
                tr.append(td);
            }
            
            colTotals['anual'] += rowTotal;
            tr.append($("<td>").text(rowTotal > 0 ? formatoAR(rowTotal, 0) : "").css("font-weight", "bold").css("background-color", "#fffde7"));
            
            tbody.append(tr);
        }
        
        tfoot.append($("<td>").text("TOTAL").css("text-align", "center"));
        for (let m = 1; m <= 12; m++) {
            tfoot.append($("<td>").text(colTotals[m] > 0 ? formatoAR(colTotals[m], 0) : "").css("text-align", "center").css("color", "red"));
        }
        tfoot.append($("<td>").text(colTotals['anual'] > 0 ? formatoAR(colTotals['anual'], 0) : "").css("text-align", "center").css("color", "red").css("background-color", "#fffde7"));
    });
});

$(document).on("change", "#ta_casino, #ta_anio", function() {
    $("#btn-buscarTotalesAnuales").click();
});

$("#btn-descargarPublicoCasinoExcel").on("click", function (e) {
  e.preventDefault();
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;

  if (casino != 4 && casino != 0) {
    window.location.href = `/documentosContables/descargarPublicoCasinoXlsx?casino=${casino}&desde=${desde}&hasta=${hasta}`;
  } else {
    window.location.href = `/documentosContables/descargarPublicoCasinoXlsxTodos?desde=${desde}&hasta=${hasta}`;
  }
});

$("#btn-descargarPublicoCasinoCsv").on("click", function (e) {
  e.preventDefault();
  var _fg = filtrosGlobalDescarga();
  const casino = _fg.casino;
  const desde = _fg.desde;
  const hasta = _fg.hasta;

  window.location.href = `/documentosContables/descargarPublicoCasinoCsv?casino=${casino}&desde=${desde}&hasta=${hasta}`;
});

/* ============ DOCUMENTOS CONTABLES: ARCHIVOS INLINE + MODO SOLO LECTURA ============
   Antes cada fila tenía un botón "ver documento" (abría un modal aparte de solo lectura) y otro
   "ver archivos" (abría #modalArchivosAsociados). Ahora todo se hace desde el botón de editar:
   el modal de carga muestra los datos y, debajo del botón de subir archivos, la lista de los
   archivos ya cargados. Si el documento está validado, el modal se abre en modo solo lectura.
   Los botones viejos, sus handlers y los modales que abrían (#modalArchivosAsociados y los
   modalVer*) ya se eliminaron; quedan en el historial de git.
   =================================================================================== */

// Contenedor donde renderizar la lista de archivos. Si viene target explícito gana (lo usa el modal
// unificado, que tiene sus propios contenedores); si no, el contenedor inline del tipo.
function targetArchivosDoc(tipo, target) {
  if (target) return target;
  var sel = "#listaArchivos_" + tipo;
  if ($(sel).length) return sel;
  // Algunos tipos solo viven dentro del modal unificado, que usa nombres cortos.
  var corto =
    {
      PozosAcumuladosLinkeados: "Pozos",
      JackpotsPagados: "Jackpots",
      PagosMayoresMesas: "PagosMesas",
    }[tipo] || tipo;
  return "#listaArchivos_U_" + corto;
}

// Modal de carga y botón de guardar de cada tipo (se derivan del nombre, con excepciones).
function cfgDocContable(tipo) {
  var esp =
    {
      AutDirectores: {
        modal: "#modalCargarAutDirectores_autorizacion",
        guardar: "#guardarRegistroAutDirectores_autorizacion",
      },
      // PremiosMTM se carga/edita desde el modal unificado.
      PremiosMTM: { modal: "#modalCargarPremiosMTM", guardar: "#guardarRegistroPremiosMTM_Unificado" },
      EstadoContable: { modal: "#modalCargarEstadoContable", guardar: "#btn-guardarEstadoContable" },
      PublicoCasino: { modal: "#modalPublicoCasino", guardar: "#btn-guardarPublicoCasino" },
    }[tipo] || {};
  return {
    modal: esp.modal || "#modalCargar" + tipo,
    guardar: esp.guardar || "#guardarRegistro" + tipo,
  };
}

// ¿El documento de esta fila está validado? Se lee del botón de validación, que ya trae data-valido.
function docContableValidado($btn) {
  return String($btn.closest("tr").find(".validarDocumento").attr("data-valido")) === "1";
}

// Bloquea/desbloquea el modal. Solo revierte lo que deshabilitó esta misma función (marca data-ro-doc),
// para no pisar campos que el flujo original deja deshabilitados o readonly a propósito.
function aplicarSoloLecturaDoc(cfg, soloLectura) {
  var $m = $(cfg.modal);
  var $guardar = $(cfg.guardar);
  // La clase marca el modal como solo lectura. Se usa además desde CSS para ocultar los botones de
  // eliminar archivo, que se renderizan después (la lista se carga por AJAX).
  $m.toggleClass("doc-solo-lectura", !!soloLectura);
  if (soloLectura) {
    $m.find("input, select, textarea")
      .not("[type=hidden]")
      .not(":disabled")
      .attr("data-ro-doc", "1")
      .prop("disabled", true);
    $m.find("[id^=btnPick], .btnPickUnified").attr("data-ro-doc-hide", "1").hide();
    $guardar.attr("data-ro-doc-hide", "1").hide();
  } else {
    $m.find("[data-ro-doc]").removeAttr("data-ro-doc").prop("disabled", false);
    $m.find("[data-ro-doc-hide]").removeAttr("data-ro-doc-hide").show();
    $guardar.removeAttr("data-ro-doc-hide").show();
  }
}

// Muestra los archivos ya cargados del registro dentro del modal y aplica el modo solo lectura.
function prepararModalDocContable(tipo, id, soloLectura, soloArchivos) {
  var fn = window["cargarArchivos" + tipo + "Lista"];
  var $wrap = $("#listaArchivosWrap_" + tipo);

  if (id && typeof fn === "function") {
    $wrap.show();
    fn(id);
  } else {
    $("#listaArchivos_" + tipo).empty();
    $wrap.hide();
  }

  var cfg = cfgDocContable(tipo);
  if (soloArchivos) {
    // El tipo ya maneja su propio bloqueo: solo se marca el modal (para el CSS de borrar archivos).
    $(cfg.modal).toggleClass("doc-solo-lectura", !!soloLectura);
  } else {
    aplicarSoloLecturaDoc(cfg, soloLectura);
  }
  ajustarTituloModalDoc($(cfg.modal));
}

// El handler de cada tipo escribe el título como "EDITAR ...". Cuando el documento está validado el
// modal se abre en solo lectura, así que el título tiene que decir "VER ...".
function ajustarTituloModalDoc($m) {
  if (!$m.hasClass("doc-solo-lectura")) return;
  $m.find(".modal-title").each(function () {
    var t = $(this).text();
    if (/editar/i.test(t)) $(this).text(t.replace(/editar/gi, "VER"));
  });
}

// Red de seguridad: algunos tipos escriben el título dentro del callback AJAX, o sea después de que
// corre el hook. shown.bs.modal dispara más tarde, así que acá se corrige igual.
$(document).on("shown.bs.modal", ".modal", function () {
  ajustarTituloModalDoc($(this));
});

// Hook único para todos los botones de editar. Va al final del archivo a propósito: los handlers
// delegados corren en orden de registro, así este se ejecuta DESPUÉS del handler propio de cada
// tipo (que es el que abre y llena el modal).
$(document).on("click", "[class*='btn-edit-'], .btn-editar-publico-casino", function () {
  var $btn = $(this);
  var clases = $btn.attr("class") || "";
  var tipo;

  if (clases.indexOf("btn-editar-publico-casino") >= 0) {
    tipo = "PublicoCasino";
  } else {
    var m = clases.match(/btn-edit-([A-Za-z0-9_]+)/);
    if (!m) return;
    tipo = m[1];
    if (tipo === "PremiosMTM_Unificado") return; // el unificado tiene su propio flujo
    if (tipo === "iva") tipo = "Iva"; // la clase va en minúscula, la función no
  }

  var id = $btn.data("id") || $btn.val();
  var validado = docContableValidado($btn);
  // Público Casino ya tiene su propio bloqueo (deshabilita los días y esconde ACEPTAR), así que
  // para ese tipo solo cargamos los archivos y marcamos el modal.
  var soloArchivos = tipo === "PublicoCasino";

  // En setTimeout para no depender del orden en que se registraron los handlers: así corre siempre
  // después del handler propio del tipo, que es el que abre y llena el modal.
  setTimeout(function () {
    prepararModalDocContable(tipo, id, validado, soloArchivos);
  }, 0);
});

// Vacía el "buffer" de archivos del modal: los inputs de archivos ya elegidos que quedan
// archivados esperando el submit, la tabla de archivos a subir y el input visible. Si no se limpia,
// al abrir un documento nuevo aparecen precargados los archivos del documento anterior.
function limpiarBufferArchivosDoc($m) {
  $m.find("[id^=uploads][id$=Container]").empty();
  $m.find("[id^=uploads][id$=Table]").find("tbody").empty();
  $m.find("[id^=uploads][id$=Wrap]").hide();
  $m.find("input[type=file]").val("");
  // modal unificado
  $m.find(".wrapUnified").hide();
  $m.find(".tableUnified").find("tbody").empty();
  $m.find(".displayUnified").val("");
}

// Al cerrar un modal se limpia todo el estado: archivos ya cargados, buffer de subida y
// solo-lectura. Así el próximo "nuevo registro" abre en blanco y editable, sin arrastrar nada
// del registro anterior (ni datos ni archivos).
$(document).on("hidden.bs.modal", ".modal", function () {
  var $m = $(this);
  var id = $m.attr("id") || "";
  var esModalDeCarga = id.indexOf("modalCargar") === 0 || id === "modalPublicoCasino";
  var esDocContable =
    esModalDeCarga ||
    $m.find(".lista-archivos-doc").length ||
    $m.find("[id^=uploads][id$=Container]").length ||
    $m.hasClass("doc-solo-lectura");
  if (!esDocContable) return;

  $m.removeClass("doc-solo-lectura");
  $m.find(".lista-archivos-doc").hide().find(".list-group").empty();
  $m.find("[data-ro-doc]").removeAttr("data-ro-doc").prop("disabled", false);
  $m.find("[data-ro-doc-hide]").removeAttr("data-ro-doc-hide").show();
  limpiarBufferArchivosDoc($m);

  // Descarta el número cacheado de los campos numéricos. data("num") solo se refresca cuando el
  // usuario tipea (al llenar el modal se usa .val()), y el guardado lo usa con prioridad sobre lo
  // que se ve: si quedaba el del registro anterior, abrir otro y guardar sin tocar ese campo
  // guardaba el valor viejo. También hacía que los campos calculados no se actualizaran.
  $m.find("input").removeData("num").removeData("normalized");

  // Limpieza del formulario al cerrar. Es la red de seguridad del problema "abro NUEVO y me
  // aparecen los datos del registro que estaba viendo": varios flujos de "nuevo" abren el modal
  // directo, sin pasar por abrirModal*Crear (Ganancias_periodo, ImpInmobiliario, las sub-pantallas
  // de partidas/tipos/directores...), así que limpiar acá cubre todos los caminos.
  if (esModalDeCarga) resetFormularioModalDoc($m, id);
});

// Limpia el formulario del modal usando la función propia del tipo si existe; si no, hace un reset
// nativo de los formularios que tenga adentro.
function resetFormularioModalDoc($m, id) {
  var tipo = id.indexOf("modalCargar") === 0 ? id.slice("modalCargar".length) : "PublicoCasino";
  var propia =
    tipo === "PremiosMTM" && typeof window.clearUnifiedModalInputs === "function"
      ? window.clearUnifiedModalInputs
      : window["resetForm" + tipo];

  try {
    if (typeof propia === "function") {
      propia();
      return;
    }
  } catch (e) {
    // si la función propia falla, se sigue con el reset nativo
  }

  $m.find("form").each(function () {
    if (typeof this.reset === "function") this.reset();
  });
  $m.find(".has-error").removeClass("has-error");
  $m.find(".help-block.js-error, .js-error").remove();
}

// El botón de acción de cada fila es EDITAR (lápiz) si el documento no está validado, y VER (ojo)
// si ya lo está — en ese caso el modal se abre en solo lectura. Se resuelve en una sola pasada
// genérica leyendo el data-valido que ya trae el botón de validación de la fila, así no hay que
// tocar los 27 armadores de fila.
function ajustarBotonesVerEditarDoc() {
  $("tbody tr").each(function () {
    var $tr = $(this);
    var $val = $tr.find(".validarDocumento");
    if (!$val.length) return;
    var $btn = $tr.find("[class*='btn-edit-'], .btn-editar-publico-casino").first();
    if (!$btn.length) return;

    var validado = String($val.attr("data-valido")) === "1";
    var $i = $btn.find("i");
    if (validado) {
      $i.removeClass("fa-pencil-alt fa-pencil").addClass("fa-eye");
      $btn.removeClass("btn-info").addClass("btn-success").attr("title", "VER DOCUMENTO");
    } else {
      $i.removeClass("fa-eye").addClass("fa-pencil-alt");
      $btn.removeClass("btn-success").addClass("btn-info").attr("title", "EDITAR");
    }
  });
}

// Las tablas se dibujan por AJAX, así que se reajusta cada vez que termina una llamada.
var _tmrBotonesDoc = null;
$(document).ajaxComplete(function () {
  clearTimeout(_tmrBotonesDoc);
  _tmrBotonesDoc = setTimeout(ajustarBotonesVerEditarDoc, 60);
});

/* ====== REGISTRO DE AUTÓNOMOS: directores del registro ======
   El registro precarga los directores HABILITADOS del casino, pero se puede sumar cualquier otro
   del mismo casino (incluidos los inhabilitados) y sacar los que no correspondan.
   Los names de los inputs son los mismos de siempre (directores[id] / observacion[id]).
   ============================================================ */

function escAutDir(s) {
  return String(s == null ? "" : s)
    .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;").replace(/'/g, "&#39;");
}

// Arma la fila de un director. Acepta tanto el formato del listado por casino
// (id_registroAutDirectores_director) como el del registro guardado (id).
function filaDirectorAutDir(d) {
  var id = d.id != null ? d.id : d.id_registroAutDirectores_director;
  var nombre = escAutDir(d.nombre);
  var cuit = escAutDir(d.cuit);
  var obs = escAutDir(d.observacion);
  var tildado = parseInt(d.autoriza) ? " checked" : "";
  // habilitado solo viene en los listados por casino; si no viene, no se muestra la marca.
  var marca =
    d.habilitado !== undefined && !parseInt(d.habilitado)
      ? ' <span class="label label-default" title="Este director no está habilitado">inhabilitado</span>'
      : "";

  return (
    '<div class="col-md-7 d-flex align-items-center mb-2 fila-director" data-dir="' + id + '">' +
    '  <span class="me-2" style="white-space:nowrap;">' + nombre + " C.U.I.T.: " + cuit + marca + "</span>" +
    '  <input type="hidden" name="directores[' + id + ']" value="0">' +
    '  <input type="checkbox" name="directores[' + id + ']" value="1" style="margin-left:6px;"' + tildado + ">" +
    "</div>" +
    '<div class="col-md-5 mb-2 fila-director" data-dir="' + id + '" style="display:flex; gap:6px; align-items:flex-start;">' +
    '  <textarea name="observacion[' + id + ']" class="form-control form-control-sm" rows="1" placeholder="observacion" style="flex:1;">' + obs + "</textarea>" +
    '  <button type="button" class="btn btn-danger btn-sm btn-quitar-director" data-dir="' + id + '" title="Quitar del registro"><i class="fa fa-trash"></i></button>' +
    "</div>"
  );
}

// Ids de los directores que ya están en el registro (cada uno aparece en 2 columnas).
function directoresEnRegistroAutDir() {
  var vistos = {};
  $("#zona-directores .fila-director[data-dir]").each(function () {
    vistos[String($(this).attr("data-dir"))] = true;
  });
  return vistos;
}

// Llena el combo con los directores del casino que todavía NO están en el registro.
function refrescarComboAgregarDirector() {
  var casino = $("#casinoAutDirectores_autorizacion").val();
  var $zona = $("#zona-agregar-director");
  var $sel = $("#selectAgregarDirector");

  if (!casino) {
    $zona.hide();
    return;
  }

  $.getJSON("/documentosContables/AutDirectoresTodosPorCasino/" + casino, function (data) {
    var yaEstan = directoresEnRegistroAutDir();
    var opciones = '<option value="">Elegí un director para agregar…</option>';
    var disponibles = 0;

    (data || []).forEach(function (d) {
      var id = String(d.id_registroAutDirectores_director);
      if (yaEstan[id]) return;
      disponibles++;
      opciones +=
        '<option value="' + id + '"' +
        ' data-nombre="' + escAutDir(d.nombre) + '"' +
        ' data-cuit="' + escAutDir(d.cuit) + '"' +
        ' data-habilitado="' + (parseInt(d.habilitado) ? 1 : 0) + '">' +
        escAutDir(d.nombre) + " — CUIT " + escAutDir(d.cuit) +
        (parseInt(d.habilitado) ? "" : " (inhabilitado)") +
        "</option>";
    });

    $sel.html(opciones);
    $("#avisoAgregarDirector").text(
      disponibles ? "" : "No quedan directores para agregar en este casino."
    );
    $zona.show();
  }).fail(function () {
    $("#avisoAgregarDirector").text("No se pudo cargar la lista de directores.");
    $zona.show();
  });
}

$(document).on("click", "#btnAgregarDirector", function () {
  var $opt = $("#selectAgregarDirector option:selected");
  var id = $opt.val();
  if (!id) return;

  // Si estaba el cartel de "sin directores", se limpia antes de agregar la primera fila.
  $("#zona-directores .text-muted").closest("div.col-md-12").remove();

  $("#zona-directores").append(
    filaDirectorAutDir({
      id: id,
      nombre: $opt.attr("data-nombre"),
      cuit: $opt.attr("data-cuit"),
      habilitado: $opt.attr("data-habilitado"),
      autoriza: 0,
      observacion: "",
    })
  );
  refrescarComboAgregarDirector();
});

$(document).on("click", ".btn-quitar-director", function () {
  var id = $(this).attr("data-dir");
  $('#zona-directores .fila-director[data-dir="' + id + '"]').remove();
  refrescarComboAgregarDirector();
});

// Al abrir el modal de confirmación de borrado, se guarda EL CONTENEDOR exacto donde estaba el
// archivo. Después de borrar hay que refrescar esa misma lista, y no se puede deducir solo del
// tipo: en el modal unificado cada sub-documento tiene su propia lista (#listaArchivos_U_*), pero
// varios de esos tipos TAMBIÉN tienen su modal suelto (#listaArchivos_*), así que el destino por
// tipo terminaba refrescando la lista equivocada y la del unificado quedaba desactualizada.
// Va al final del archivo a propósito: los handlers delegados corren en orden de registro, así que
// este se ejecuta después del handler propio de cada tipo (el que setea id/regId/scope).
$(document).on("click", "[class*='btn-del-archivo-']", function () {
  var id = $(this).closest(".list-group").attr("id");
  $("#modalEliminarArchivo").data("contenedor", id ? "#" + id : null);
});

/* ============ ORDEN POR COLUMNAS DE FECHA (documentos contables) ============
   Los <th data-orden="columna"> de cada tabla de listado alternan
   ascendente -> descendente -> sin orden. Cada pestaña recuerda su propio
   criterio, y el prefilter lo agrega a los listados /documentosContables/ultimas*.
   ------------------------------------------------------------------------- */
var ordenFechaDocs = {}; // { pant_iva: { col: "fecha_iva", dir: "asc" }, ... }

function pantallaVisibleDocs() {
  return $('[id^="pant_"]').not("[hidden]").first().attr("id") || null;
}

function limpiarOrdenDocs() {
  var tabs = Object.keys(ordenFechaDocs);
  ordenFechaDocs = {};
  tabs.forEach(pintarIconosOrdenDocs);
}

function pintarIconosOrdenDocs(tab) {
  var est = ordenFechaDocs[tab];
  $("#" + tab)
    .find("th[data-orden]")
    .each(function () {
      var $th = $(this);
      var $i = $th.find("i.fa");
      $th.removeClass("activa");
      $i.attr("class", "fa fa-sort");
      if (est && String($th.attr("data-orden")) === est.col) {
        $th.addClass("activa");
        $i.attr("class", est.dir === "asc" ? "fa fa-sort-up" : "fa fa-sort-down");
      }
    });
}

$(document).on("click", '[id^="pant_"] th[data-orden]', function () {
  var tab = $(this).closest('[id^="pant_"]').attr("id");
  if (!tab) return;
  var col = String($(this).attr("data-orden"));
  var est = ordenFechaDocs[tab];

  if (est && est.col === col) {
    if (est.dir === "asc") est.dir = "desc";
    else delete ordenFechaDocs[tab]; // tercer clic: vuelve al orden por defecto
  } else {
    ordenFechaDocs[tab] = { col: col, dir: "asc" };
  }

  pintarIconosOrdenDocs(tab);
  if (typeof buscarPestana === "function") buscarPestana("#" + tab);
});

$.ajaxPrefilter(function (options) {
  // Algunos listados usan la URL relativa (sin "/" inicial), por eso no se exige la barra.
  if (!options.url || options.url.indexOf("documentosContables/ultimas") === -1) return;
  var tab = pantallaVisibleDocs();
  var est = tab ? ordenFechaDocs[tab] : null;
  if (!est) return;
  options.url +=
    (options.url.indexOf("?") === -1 ? "?" : "&") +
    "orden_col=" + encodeURIComponent(est.col) +
    "&orden_dir=" + est.dir;
});

// Los avisos de las descargas apuntaban a los filtros por pestaña, que ya no existen: el mensaje
// no se veía en ningún lado y el botón parecía no hacer nada. Ahora van al modal de aviso, igual
// que el resto de las validaciones del módulo.
function avisoDescargaDoc(mensaje) {
  $("#texto-aviso-validacion").text(mensaje);
  $("#modalAvisoValidacion").modal("show");
}

/* ==================== ARRASTRAR ARCHIVOS (todos los documentos) ====================
   Los 33 selectores del módulo tienen la misma forma: un .input-group con el botón
   "Examinar…", un display readonly y un input[type=file] oculto. Al soltar archivos se
   cargan en ese input y se dispara su "change", así que sigue corriendo la lógica de
   acumulación / render / envío que ya existe para cada documento. Mismo truco que en
   public/js/Autoexclusion/index.js, pero genérico.
   ---------------------------------------------------------------------------------- */
function zonaArchivosDoc($el) {
  var $zona = $el.closest(".input-group");
  if (!$zona.length) return null;
  // Solo las que realmente tienen un selector de archivos (.input-group también la usan
  // los datepickers y otros campos del módulo).
  if (!$zona.find('input[type=file]').length) return null;
  // Documento validado: los botones de subir están ocultos, tampoco se acepta soltar.
  if ($zona.find("[data-ro-doc-hide]").length) return null;
  return $zona;
}

$(document).on("dragenter dragover", ".input-group", function (e) {
  var $zona = zonaArchivosDoc($(this));
  if (!$zona) return;
  e.preventDefault();
  e.stopPropagation();
  $zona.addClass("zona-drop-activa");
});

$(document).on("dragleave drop", ".input-group", function (e) {
  var $zona = zonaArchivosDoc($(this));
  if (!$zona) return;
  $zona.removeClass("zona-drop-activa");
});

$(document).on("drop", ".input-group", function (e) {
  var $zona = zonaArchivosDoc($(this));
  if (!$zona) return;
  e.preventDefault();
  e.stopPropagation();

  var dt = e.originalEvent && e.originalEvent.dataTransfer;
  if (!dt || !dt.files || !dt.files.length) return;

  // El input vivo es el último sin archivar: al elegir archivos el anterior se guarda en
  // el contenedor con la clase "archived" y se crea uno nuevo en su lugar.
  var $input = $zona.find('input[type=file]').not(".archived").last();
  if (!$input.length) return;

  var lote = new DataTransfer();
  for (var i = 0; i < dt.files.length; i++) lote.items.add(dt.files[i]);
  if (!lote.files.length) return;

  $input[0].files = lote.files;
  $input.trigger("change");
});

// Sin esto, soltar un archivo fuera de una zona hace que el navegador lo abra y se pierda
// todo lo que había cargado en el modal.
$(document).on("dragover drop", function (e) {
  if ($(e.target).closest(".input-group").find('input[type=file]').length) return;
  e.preventDefault();
});

/* ==================== HISTORIAL DEL DOCUMENTO ====================
   Quién tocó cada documento y cuándo (no qué cambió). El botón lo agrega
   obtenerIconoValidacion(), que es por donde pasan las 28 tablas del módulo.
   ----------------------------------------------------------------- */
$(document).on("click", ".btn-historial-doc", function () {
  var tipo = $(this).attr("data-tipo");
  var id = $(this).attr("data-id");
  var $cont = $("#contenidoHistorialDoc")
    .empty()
    .append('<div class="list-group-item">Cargando…</div>');

  $("#modalHistorialDoc").modal("show");

  $.getJSON("/documentosContables/historialDocumento/" + tipo + "/" + id)
    .done(function (res) {
      $cont.empty();
      var movs = (res && res.movimientos) || [];

      if (!movs.length) {
        // Los documentos cargados antes de que existiera el historial no tienen movimientos:
        // se muestra el último toque, que la propia tabla del documento ya guardaba.
        if (res && res.previo) {
          $cont.append(
            $('<div class="list-group-item">').html(
              "<b>Última modificación</b><br>" +
                $("<span>").text(res.previo.usuario).html() +
                " — " +
                res.previo.fecha +
                '<br><small class="text-muted">Los movimientos anteriores no quedaron registrados.</small>'
            )
          );
        } else {
          $cont.append(
            '<div class="list-group-item">Sin movimientos registrados.</div>'
          );
        }
        return;
      }

      movs.forEach(function (m) {
        var detalle = m.detalle
          ? '<br><small class="text-muted">' +
            $("<span>").text(m.detalle).html() +
            "</small>"
          : "";
        $cont.append(
          $('<div class="list-group-item">').html(
            "<b>" +
              $("<span>").text(m.accion).html() +
              "</b><br>" +
              $("<span>").text(m.usuario).html() +
              " — " +
              m.fecha +
              detalle
          )
        );
      });
    })
    .fail(function () {
      $cont
        .empty()
        .append(
          '<div class="list-group-item text-danger">No se pudo cargar el historial.</div>'
        );
    });
});
