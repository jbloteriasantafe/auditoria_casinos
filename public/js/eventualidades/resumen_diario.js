/* =====================================================================
   REPORTE DIARIO POR CASINO — COBERTURA DE PROCEDIMIENTOS
   ===================================================================== */
$(function () {
  // Título de la sección en la tarjeta del header (#btn-ayuda). Cada página lo setea por JS;
  // sin esto queda el placeholder "---".
  $('.tituloSeccionPantalla').text('Resumen Diario');

  function htmlEscapeR(s) { return $('<div>').text(s == null ? '' : s).html(); }

  // Los datetimepickers del filtro (#dtpFechaEv / #dtpFechaEvHasta) se inicializan
  // al final de este archivo (esta página tiene su propio filtro desde/hasta/casino).

  function pintarFila(r) {
    const colorBar = r.coverage_pct >= 100 ? '#5cb85c'
                   : r.coverage_pct >= 50  ? '#f0ad4e'
                   : '#d9534f';
    const widthBar = Math.min(r.coverage_pct, 100);
    const bar =
      '<div style="background:#eee; height:14px; border-radius:3px; overflow:hidden;">' +
        '<div style="width:' + widthBar + '%; height:14px; background:' + colorBar + ';"></div>' +
      '</div>' +
      '<small>' + r.cubiertos + '/' + r.esperados + ' (' + r.coverage_pct + '%)</small>';

    const permisos = window.PERMISOS_EVENTUALIDADES || {};
    const visado = r.estado === 'visado';
    const puedeVisar = !!permisos.visar_resumen_diario;
    // Círculo de estado (SOLO display; el visado/desvisado se hace con el botón de ACCIONES,
    // igual que el tilde de la tabla de eventualidades).
    const circuloClase = visado ? 'faVisado' : 'faNoVisado';
    const estadoLabel  = visado ? 'Visado' : 'No visado';
    const badgeEstado =
      '<span class="estado-resumen">' +
        '<i class="fas fa-fw fa-dot-circle iconoEstado ' + circuloClase + '"></i> ' + estadoLabel +
      '</span>';
    const obsCountHtml = r.observaciones_count > 0
      ? '<span class="obs-count">' + r.observaciones_count + '</span>' : '';
    const btnObs =
      '<button class="btn btn-warning btn-xs btn-obsResumen" ' +
              'data-casino="' + r.id_casino + '" data-fecha="' + r.fecha + '" ' +
              'data-casino-nombre="' + htmlEscapeR(r.casino) + '" ' +
              'data-toggle="tooltip" data-placement="bottom" title="OBSERVACIONES">' +
        '<i class="fa fa-comments"></i>' + obsCountHtml +
      '</button>';
    const btnDetalle =
      '<button class="btn btn-info btn-xs btn-verDetalleCobertura" ' +
              'data-casino="' + r.id_casino + '" data-fecha="' + r.fecha + '" ' +
              'data-casino-nombre="' + htmlEscapeR(r.casino) + '" ' +
              'data-toggle="tooltip" data-placement="bottom" title="DETALLE DE COBERTURA">' +
        '<i class="fa fa-search-plus"></i>' +
      '</button>';
    // Botón de visado (el "tilde", igual que en la tabla de eventualidades). Sólo con permiso.
    var btnVisar = '';
    if (puedeVisar) {
      var datosVisar =
        'data-casino="' + r.id_casino + '" data-fecha="' + r.fecha + '" ' +
        'data-casino-nombre="' + htmlEscapeR(r.casino) + '" ' +
        'data-estado="' + (visado ? 'visado' : 'no_visado') + '" ';
      btnVisar = visado
        ? '<button class="btn btn-default btn-xs btn-visarResumen" ' + datosVisar +
                  'data-toggle="tooltip" data-placement="bottom" title="QUITAR VISADO"><i class="fa fa-undo"></i></button>'
        : '<button class="btn btn-success btn-xs btn-visarResumen" ' + datosVisar +
                  'data-toggle="tooltip" data-placement="bottom" title="VISAR RESUMEN"><i class="fa fa-check"></i></button>';
    }

    var cantBtn =
      '<button class="btn btn-default btn-xs btn-toggleEvs" ' +
              'data-casino="' + r.id_casino + '" data-fecha="' + r.fecha + '" ' +
              'data-toggle="tooltip" data-placement="bottom" title="VER EVENTUALIDADES DEL DÍA">' +
        r.eventualidades + ' <span class="caret" style="transition:transform .15s;"></span>' +
      '</button>';

    return (
      '<tr>' +
        '<td>' + r.fecha + '</td>' +
        '<td>' + htmlEscapeR(r.casino) + '</td>' +
        '<td class="text-center">' + cantBtn + '</td>' +
        '<td>' + bar + '</td>' +
        '<td class="text-center">' + badgeEstado + '</td>' +
        '<td class="text-center">' + btnVisar + ' ' + btnObs + ' ' + btnDetalle + '</td>' +
      '</tr>'
    );
  }

  // Paginación (mismo plugin paginacion.js que la tabla de eventualidades).
  function clickIndiceReporte(e, pageNumber) {
    if (e) e.preventDefault();
    cargarReporteDiario({ page: pageNumber });
  }
  function pintarPaginacionReporte(p) {
    var $pag = $('#herramientasPaginacionResumen');
    if (!$pag.length || !p) return;
    $pag.generarTitulo(p.current_page, p.per_page, p.total, clickIndiceReporte);
    $pag.generarIndices(p.current_page, p.per_page, p.total, clickIndiceReporte);
  }

  function cargarReporteDiario(opts) {
    opts = opts || {};
    const $tb = $('#cuerpoReporteDiario');
    if (!$tb.length) return;
    var $pag = $('#herramientasPaginacionResumen');
    // Sin page explícito: se mantiene la página actual (las acciones no cambian el set de filas).
    // El botón BUSCAR sí pasa {page:1} para arrancar una búsqueda nueva.
    var page = opts.page || ($pag.length ? $pag.getCurrentPage() : 1);
    var pageSize = $pag.length ? $pag.getPageSize() : 10;
    $tb.empty().append('<tr><td colspan="6" class="text-center text-muted">Cargando…</td></tr>');

    // Filtros: se toman del panel "Filtros de búsqueda" de arriba.
    $.getJSON('/eventualidades/reporteDiario', {
      desde:     $('#B_fecha_ev').val()     || undefined,
      hasta:     $('#B_fecha_evhasta').val() || undefined,
      id_casino: $('#B_CasinoEv').val()     || undefined,
      page:      page,
      page_size: pageSize,
    })
    .done(function (res) {
      $tb.empty();
      if (!res.rows || !res.rows.length) {
        $tb.append('<tr><td colspan="6" class="text-center text-muted">Sin datos en el rango.</td></tr>');
        pintarPaginacionReporte(res.pagination);
        return;
      }
      res.rows.forEach(function (r) { $tb.append(pintarFila(r)); });
      $tb.find('[data-toggle="tooltip"]').tooltip({ container: 'body' });
      pintarPaginacionReporte(res.pagination);
    })
    .fail(function () {
      $tb.empty().append('<tr><td colspan="6" class="text-center text-danger">Error al cargar el reporte.</td></tr>');
    });
  }

  // El refresh (alta de obs, etc.) recarga la página actual del reporte (default = página actual).
  $(document).on('reporteDiario:refresh', function () { cargarReporteDiario(); });

  // Fix Bootstrap 3 (modal anidado): al cerrar el modal de arriba, si queda otro modal
  // abierto, el body pierde .modal-open y el de atrás queda sin scroll. Lo restauramos.
  $(document).on('hidden.bs.modal', '.modal', function () {
    if ($('.modal.in').length) {
      $('body').addClass('modal-open');
    }
  });

  $(document).on('click', '.btn-verDetalleCobertura', function () {
    const idCasino     = $(this).data('casino');
    const fecha        = $(this).data('fecha');
    const casinoNombre = $(this).data('casinoNombre') || $(this).data('casino-nombre');
    $('#detalleCabecera').text((casinoNombre || '') + ' — ' + fecha);

    const $rea = $('#listaRealizados').empty().append('<li class="text-muted">Cargando…</li>');
    const $nor = $('#listaNoRealizados').empty();

    function pintarLista($ul, items, icono, color) {
      $ul.empty();
      if (!items.length) {
        $ul.append('<li><em class="text-muted">Ninguno</em></li>');
        return;
      }
      items.forEach(function (it) {
        var nombre = (typeof it === 'string') ? it : (it && it.nombre) || '';
        var entries = (it && it.entries) ? it.entries
                    : (it && it.usuarios) ? it.usuarios.map(function (u) { return { usuario: u, turno: null }; })
                    : [];
        var detalleHtml = '';
        if (entries.length) {
          var partes = entries.map(function (e) {
            var u = e.usuario || '—';
            var t = (e.turno !== null && e.turno !== undefined && e.turno !== '') ? ' (Turno ' + htmlEscapeR(e.turno) + ')' : '';
            return htmlEscapeR(u) + t;
          });
          detalleHtml = ' <small class="text-muted">— ' + partes.join(', ') + '</small>';
        }
        $ul.append(
          '<li><i class="fa ' + icono + '" style="color:' + color + ';"></i> ' +
          htmlEscapeR(nombre) + detalleHtml + '</li>'
        );
      });
    }

    $.getJSON('/eventualidades/reporteDiarioDetalle', { id_casino: idCasino, fecha: fecha })
      .done(function (res) {
        pintarLista($rea, res.realizados    || [], 'fa-check', '#3c763d');
        pintarLista($nor, res.no_realizados || [], 'fa-times', '#a94442');
      })
      .fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error al cargar el detalle.';
        $rea.empty().append('<li class="text-danger">' + msg + '</li>');
        $nor.empty();
      });

    var $evs = $('#cuerpoEventualidadesDia').html('<div class="text-muted" style="padding:6px;">Cargando…</div>');
    $.getJSON('/eventualidades/resumen-diario/' + idCasino + '/' + fecha + '/eventualidades')
      .done(function (res) { $evs.html(renderListaEvs(res.eventualidades || [])); $evs.find('[data-toggle="tooltip"]').tooltip({ container: 'body' }); })
      .fail(function () { $evs.html('<div class="text-danger">Error al cargar las eventualidades.</div>'); });

    $('#modalDetalleCobertura').modal('show');
  });

  /* -------- VISADO del resumen (botón "tilde" en ACCIONES + modal de confirmación, igual que eventualidades) -------- */
  var ctxVisarResumen = { idCasino: null, fecha: null, estado: null, casinoNombre: null };

  $(document).on('click', '.btn-visarResumen', function () {
    var $el = $(this);
    ctxVisarResumen = {
      idCasino:     $el.data('casino'),
      fecha:        $el.data('fecha'),
      estado:       $el.data('estado'),
      casinoNombre: $el.data('casinoNombre') || $el.data('casino-nombre') || ''
    };
    var visar = ctxVisarResumen.estado !== 'visado';
    $('#tituloModalVisarResumen').text(visar ? '| VISAR RESUMEN DIARIO' : '| QUITAR VISADO');
    $('#textoModalVisarResumen').text(
      (visar ? '¿Seguro desea VISAR el resumen diario de '
             : '¿Seguro desea QUITAR el visado del resumen diario de ') +
      ctxVisarResumen.casinoNombre + ' del ' + ctxVisarResumen.fecha + '?'
    );
    $('#detalleModalVisarResumen').text(
      visar ? 'Las eventualidades firmadas de ese día se marcarán como visadas. Las que aún no estén firmadas quedarán generadas y se visarán automáticamente al firmarse.'
            : 'Las eventualidades visadas de ese día volverán al estado firmado.'
    );
    $('#btn-confirmarVisarResumen').text(visar ? 'VISAR' : 'QUITAR VISADO');
    $('#modalVisarResumen').modal('show');
  });

  $(document).on('click', '#btn-confirmarVisarResumen', function () {
    var visar = ctxVisarResumen.estado !== 'visado';
    var ruta  = visar ? '/eventualidades/resumen-diario/visar'
                      : '/eventualidades/resumen-diario/desvisar';
    var $btn  = $(this).prop('disabled', true);
    $.post(ruta, {
      id_casino: ctxVisarResumen.idCasino,
      fecha:     ctxVisarResumen.fecha,
      _token:    $('meta[name="csrf-token"]').attr('content')
    })
    .done(function () {
      $('#modalVisarResumen').modal('hide');
      // La cascada cambió estados de eventualidades → recargo el reporte.
      cargarReporteDiario();
    })
    .fail(function (xhr) {
      var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo actualizar el estado.';
      alert(msg);
    })
    .always(function () { $btn.prop('disabled', false); });
  });

  /* -------- OBSERVACIONES del resumen -------- */
  var ctxObs = { idCasino: null, fecha: null, casinoNombre: null };

  function renderArchivosLista(files) {
    if (!files || !files.length) return '';
    var items = files.map(function (f) {
      return '<a href="' + f.url + '" target="_blank" class="text-primary">' +
               '<i class="fa fa-paperclip"></i> ' + htmlEscapeR(f.filename) +
             '</a>';
    });
    return '<div class="obs-archivos" style="margin-top:4px;">' + items.join('<br>') + '</div>';
  }

  function renderObs(lista, esControlador) {
    var $ul = $('#listaObsResumen').empty();
    if (!lista.length) {
      $ul.append('<li class="list-group-item text-muted"><em>Sin observaciones</em></li>');
      return;
    }
    lista.forEach(function (o) {
      var when  = (o.created_at || '').replace('T', ' ').replace(/\..+$/, '');
      var files = o.files || [];
      var del = esControlador
        ? '<button class="btn btn-danger btn-xs pull-right btn-elimObsResumen" data-id="' + o.id + '" data-toggle="tooltip" data-placement="bottom" title="ELIMINAR"><i class="fa fa-trash"></i></button>'
        : '';
      var txt = '';
      if (o.observacion) {
        txt = '<div style="margin-top:4px;">' + htmlEscapeR(o.observacion) + '</div>';
      }
      // avatarObsHtml está definido en observaciones_eventualidad.js (se carga antes en esta página).
      var avatar = (typeof avatarObsHtml === 'function') ? avatarObsHtml(o.user_imagen, 38) : '';
      var fechaHtml = when ? ' <small class="text-muted">' + htmlEscapeR(when) + '</small>' : '';
      $ul.append(
        '<li class="list-group-item" style="display:flex; gap:10px; align-items:flex-start;">' +
          avatar +
          '<div style="flex:1; min-width:0;">' +
            del +
            '<strong>' + htmlEscapeR(o.usuario || '—') + '</strong>' + fechaHtml +
            txt +
            renderArchivosLista(files) +
          '</div>' +
        '</li>'
      );
    });
    $ul.find('[data-toggle="tooltip"]').tooltip({ container: 'body' });
  }

  function cargarObservacionesResumen() {
    if (!ctxObs.idCasino) return;
    var $ul = $('#listaObsResumen')
        .empty()
        .append('<li class="list-group-item text-muted">Cargando…</li>');
    $.getJSON('/eventualidades/resumen-diario/' + ctxObs.idCasino + '/' + ctxObs.fecha + '/observaciones')
      .done(function (res) { renderObs(res.obs || [], !!res.controlador); })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error al cargar observaciones';
        $ul.empty().append('<li class="list-group-item text-danger">' + msg + '</li>');
      });
  }

  /* ---- Expand de fila del reporte para mostrar eventualidades del día ---- */
  function estadoTxtEv(e) {
    // Mismo círculo de estado que la tabla de eventualidades (colores: generado/firmado/visado).
    function circ(color, label) {
      return '<i class="fas fa-dot-circle" style="color:' + color + ';"></i> ' +
             '<span style="color:' + color + ';">' + label + '</span>';
    }
    switch (parseInt(e, 10)) {
      case 1: return circ('#9e9e9e', 'Generada'); // gris (generada, sin firmar)
      case 2: return circ('#5cb85c', 'Firmada');  // verde (firmada)
      case 3: return circ('#2962FF', 'Visada');   // azul (visada)
      default: return '';
    }
  }

  function renderListaEvs(lista) {
    if (!lista || !lista.length) {
      return '<div class="text-muted" style="padding:8px;"><em>No hay eventualidades cargadas este día.</em></div>';
    }
    var html = '<ul class="list-group" style="margin-bottom:0;">';
    lista.forEach(function (ev) {
      var turno = ev.turno ? 'Turno ' + ev.turno : 'Turno —';
      // Estilos inline (no via clase): el tema global resetea .btn { background:none } y, según
      // cómo se sirva el HTML, el override por clase puede no llegar. Inline garantiza el color.
      // flex + align-items:center centra verticalmente los botones en la fila (antes caían abajo).
      html +=
        '<li class="list-group-item" style="display:flex; align-items:center; justify-content:space-between; padding:6px 10px;">' +
          '<span class="info-ev">' +
            '<strong>#' + ev.id + '</strong> · ' +
            htmlEscapeR(turno) + ' · ' +
            htmlEscapeR(ev.creador || '—') + ' ' +
            estadoTxtEv(ev.estado) +
          '</span>' +
          '<span class="acciones-ev" style="white-space:nowrap; margin-left:10px;">' +
            // SIN clase .btn a propósito: el tema resetea .btn { background:none } y escala los iconos
            // (.btn i { scale 1.5 }). Sin esa clase, nada en las hojas de estilo toca estos elementos
            // y los estilos inline mandan al 100%.
            '<a href="/eventualidades/pdf/' + ev.id + '" target="_blank" ' +
               'style="display:inline-block; background:#0067b1; color:#fff; border:1px solid #005a99; ' +
                      'border-radius:3px; padding:3px 10px; font-size:12px; font-weight:bold; line-height:1.4; ' +
                      'text-decoration:none; vertical-align:middle;" ' +
               'data-toggle="tooltip" data-placement="bottom" title="ABRIR PDF">' +
              '<i class="fa fa-file-pdf-o" style="margin-right:3px;"></i>PDF' +
            '</a> ' +
            '<button type="button" class="btn-verObsDesdeResumen" ' +
               'style="display:inline-block; background:#f0ad4e; color:#fff; border:1px solid #eea236; ' +
                      'border-radius:3px; padding:3px 10px; font-size:12px; line-height:1.4; cursor:pointer; ' +
                      'vertical-align:middle;" ' +
               'data-id="' + ev.id + '" data-toggle="tooltip" data-placement="bottom" title="OBSERVACIONES">' +
              '<i class="fa fa-comments"></i>' +
            '</button>' +
          '</span>' +
        '</li>';
    });
    return html + '</ul>';
  }

  // Click en el botón de "cantidad" → toggle de fila expandida con la lista de eventualidades
  $(document).on('click', '.btn-toggleEvs', function () {
    var $btn  = $(this);
    var $tr   = $btn.closest('tr');
    var $next = $tr.next('.fila-evs-expand');
    if ($next.length) {
      $next.remove();
      $btn.find('.caret').css('transform', '');
      return;
    }
    $btn.find('.caret').css('transform', 'rotate(180deg)');
    var idCasino = $btn.data('casino');
    var fecha    = $btn.data('fecha');
    var colspan  = $tr.children('td').length;
    var $expand  = $('<tr class="fila-evs-expand"><td colspan="' + colspan + '" style="background:#fafafa; padding:8px 16px;">' +
                      '<div class="text-muted">Cargando…</div>' +
                    '</td></tr>');
    $tr.after($expand);
    $.getJSON('/eventualidades/resumen-diario/' + idCasino + '/' + fecha + '/eventualidades')
      .done(function (res) {
        $expand.find('td').html(renderListaEvs(res.eventualidades || []));
        $expand.find('[data-toggle="tooltip"]').tooltip({ container: 'body' });
      })
      .fail(function () {
        $expand.find('td').html('<div class="text-danger">Error al cargar las eventualidades del día.</div>');
      });
  });

  // Click en el botón comments del item expandido → abre el modal de obs de la eventualidad.
  $(document).on('click', '.btn-verObsDesdeResumen', function () {
    var evId = $(this).data('id');
    $('#listaObsEv').data('ev-id', evId);
    $('#obsEvCabecera').text('Cargando…');
    $('#obsEvTexto').val('');
    $('#obsEvFile').val('');
    $('#obsEvFileName').val('');
    cargarObservacionesEv();
    $('#modalVerObservaciones').modal('show');
  });

  $(document).on('click', '.btn-obsResumen', function () {
    ctxObs.idCasino     = $(this).data('casino');
    ctxObs.fecha        = $(this).data('fecha');
    ctxObs.casinoNombre = $(this).data('casinoNombre') || $(this).data('casino-nombre');
    $('#obsResumenCabecera').text((ctxObs.casinoNombre || '') + ' — ' + ctxObs.fecha);
    $('#obsResumenTexto').val('');
    $('#obsResumenFile').val('');
    $('#obsResumenFileName').val('');
    cargarObservacionesResumen();
    $('#modalObsResumen').modal('show');
  });

  // Mostrar nombres / cantidad de archivos elegidos
  $(document).on('change', '#obsResumenFile', function () {
    var fs = this.files || [];
    if (!fs.length) { $('#obsResumenFileName').val(''); return; }
    if (fs.length === 1) { $('#obsResumenFileName').val(fs[0].name); return; }
    $('#obsResumenFileName').val(fs.length + ' archivos seleccionados');
  });

  $(document).on('click', '#btnGuardarObsResumen', function () {
    var texto = ($('#obsResumenTexto').val() || '').trim();
    var files = $('#obsResumenFile')[0].files;
    if (!texto && (!files || !files.length)) {
      alert('Escribí una observación o adjuntá un archivo.');
      return;
    }

    var fd = new FormData();
    fd.append('id_casino',   ctxObs.idCasino);
    fd.append('fecha',       ctxObs.fecha);
    fd.append('observacion', texto);
    for (var i = 0; i < files.length; i++) fd.append('files[]', files[i]);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $.ajax({
      url: '/eventualidades/resumen-diario/observacion/agregar',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false
    })
    .done(function () {
      $('#obsResumenTexto').val('');
      $('#obsResumenFile').val('');
      $('#obsResumenFileName').val('');
      cargarObservacionesResumen();
      cargarReporteDiario();
    })
    .fail(function (xhr) {
      var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo guardar.';
      alert(msg);
    });
  });

  $(document).on('click', '.btn-elimObsResumen', function () {
    var id = $(this).data('id');
    // pedirEliminarObservacion está en observaciones_eventualidad.js (se carga antes en esta página).
    pedirEliminarObservacion('¿Seguro que querés eliminar esta observación?', function () {
      $.ajax({
        url: '/eventualidades/resumen-diario/observacion/' + id,
        type: 'DELETE',
        data: { _token: $('meta[name="csrf-token"]').attr('content') }
      })
      .done(function () {
        cargarObservacionesResumen();
        cargarReporteDiario();
      })
      .fail(function (xhr) {
        var msg = (xhr.responseJSON && xhr.responseJSON.error) || 'No se pudo eliminar.';
        alert(msg);
      });
    });
  });

  /* -------- Filtro propio de esta página (desde / hasta / casino) --------
     Guardado con if ($.fn.datetimepicker): si el plugin no estuviera cargado, NO debe
     romper el IIFE ni impedir la carga inicial del reporte (de ahí este chequeo). */
  if ($.fn.datetimepicker) {
    $('#dtpFechaEv').datetimepicker({
      language: 'es', format: 'yyyy-mm-dd', autoclose: true, todayBtn: true, minView: 2
    });
    $('#dtpFechaEvHasta').datetimepicker({
      language: 'es', format: 'yyyy-mm-dd', autoclose: true, todayBtn: true, minView: 2
    });
  }
  $('#B_fecha_ev').on('change', function () { $(this).trigger('focusin'); });
  // Botón "X" de los input-group: limpia la fecha.
  $('#dtpFechaEv, #dtpFechaEvHasta').on('click', '.fa-times', function () {
    $(this).closest('.input-group').find('input').val('');
  });
  $(document).on('click', '#btn-buscarResumen', function (e) {
    e.preventDefault();
    cargarReporteDiario({ page: 1 }); // búsqueda nueva → arranca en la página 1
  });

  // Carga inicial
  if ($('#cuerpoReporteDiario').length) {
    cargarReporteDiario();
  }
});
