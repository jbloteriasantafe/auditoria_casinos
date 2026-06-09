// ABM Procedimientos — modal embebido en la página de Resumen Diario.
// Requiere meta[name="csrf-token"] en el layout (ya presente).
//
// Reglas de negocio:
//  - El usuario sólo ve/toca SUS casinos (el backend filtra por usuario_tiene_casino).
//  - "Activo" es POR CASINO (no global): cada casino tiene su switch.
//  - El orden se elige como "antes/después de" otro procedimiento (no con números).

(function () {
  let _casinosCache = [];   // [{id_casino, nombre}] — sólo los del usuario
  let _procsCache   = [];   // [{id_procedimiento, nombre, orden}] ordenados por orden
  let _totalCasinos = 0;
  let _initialized  = false;

  function htmlEscape(s) {
    return $('<div>').text(s == null ? '' : s).html();
  }

  function renderTabla(procedimientos, asignaciones) {
    const $tb = $('#tbodyABMProcedimientos').empty();
    if (!procedimientos.length) {
      $tb.append('<tr><td colspan="3" class="text-center text-muted">Sin procedimientos.</td></tr>');
      return;
    }
    procedimientos.forEach(function (p) {
      const activos = (asignaciones && asignaciones[p.id_procedimiento]) || [];
      const n = activos.length;
      const badge = n > 0
        ? '<span class="label label-success">' + n + ' / ' + _totalCasinos + ' casinos</span>'
        : '<span class="label label-default">inactivo</span>';
      $tb.append(
        '<tr>' +
          '<td>' + htmlEscape(p.nombre) + '</td>' +
          '<td>' + badge + '</td>' +
          '<td>' +
            '<button class="btn btn-warning btn-sm btnModificarProc" data-id="' + p.id_procedimiento + '" data-toggle="tooltip" data-placement="bottom" title="MODIFICAR"><i class="fa fa-pencil-alt"></i></button> ' +
            '<button class="btn btn-danger btn-sm btnEliminarProc"  data-id="' + p.id_procedimiento + '" data-toggle="tooltip" data-placement="bottom" title="ELIMINAR"><i class="fa fa-trash-alt"></i></button>' +
          '</td>' +
        '</tr>'
      );
    });
    $tb.find('[data-toggle="tooltip"]').tooltip();
  }

  // Llena el select de "referencia" con los procedimientos (excluyendo el que se edita).
  function renderRefSelect(excludeId) {
    const $ref = $('#abmRef').empty();
    const otros = _procsCache.filter(function (p) { return p.id_procedimiento !== excludeId; });
    if (!otros.length) {
      $ref.append('<option value="">(será el primer procedimiento)</option>').prop('disabled', true);
      $('#abmPosicion').prop('disabled', true);
      return;
    }
    $ref.prop('disabled', false);
    $('#abmPosicion').prop('disabled', false);
    otros.forEach(function (p) {
      $ref.append($('<option>').val(p.id_procedimiento).text(p.nombre));
    });
  }

  function setUbicacion(posicion, refId) {
    $('#abmPosicion').val(posicion || 'despues');
    if (refId) $('#abmRef').val(refId);
  }

  // Render de los switches de activo por casino. casinos = [{id_casino, nombre, activo}].
  function renderCasinosForm(casinos) {
    const $box = $('#abmListaCasinos').empty();
    if (!casinos.length) {
      $box.append('<div class="text-muted">No tenés casinos asignados.</div>');
      return;
    }
    casinos.forEach(function (c) {
      const checked = c.activo ? 'checked' : '';
      $box.append(
        '<div class="abm-casino-row">' +
          '<span class="abm-casino-nombre">' + htmlEscape(c.nombre) + '</span>' +
          '<label class="switch" style="margin:0;">' +
            '<input type="checkbox" class="abmChkCasino" value="' + c.id_casino + '" ' + checked + '>' +
            '<span class="slider round"></span>' +
          '</label>' +
        '</div>'
      );
    });
  }

  function cargarData() {
    $('#tbodyABMProcedimientos').empty().append('<tr><td colspan="3" class="text-center text-muted">Cargando…</td></tr>');
    return $.getJSON('/eventualidades/procedimientos_abm/data')
      .done(function (res) {
        _casinosCache = res.casinos || [];
        _procsCache   = res.procedimientos || [];
        _totalCasinos = res.total_casinos || _casinosCache.length;
        renderTabla(_procsCache, res.asignaciones || {});
      })
      .fail(function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error cargando procedimientos.';
        $('#tbodyABMProcedimientos').empty().append(
          '<tr><td colspan="3" class="text-center text-danger">' + msg + '</td></tr>'
        );
      });
  }

  function limpiarFormProc() {
    $('#abmIdProcedimiento').val(0);
    $('#abmNombre').val('').removeClass('alerta');
    $('#abmAlertaNombre').text('').hide();
    $('#tituloModalProcedimiento').text('| NUEVO PROCEDIMIENTO');
    $('#btnGuardarProcedimiento').val('nuevo');
  }

  function abrirFormNuevo() {
    limpiarFormProc();
    renderRefSelect(null);
    // Por defecto: al final de la lista (después del último).
    if (_procsCache.length) setUbicacion('despues', _procsCache[_procsCache.length - 1].id_procedimiento);
    // Nuevo procedimiento: por defecto activo en todos los casinos del usuario.
    renderCasinosForm(_casinosCache.map(function (c) {
      return { id_casino: c.id_casino, nombre: c.nombre, activo: 1 };
    }));
    $('#modalProcedimiento').modal('show');
  }

  function abrirFormModificar(id) {
    limpiarFormProc();
    renderRefSelect(id);
    $('#abmListaCasinos').empty().append('<div class="text-muted">Cargando…</div>');
    $('#modalProcedimiento').modal('show');

    $.getJSON('/eventualidades/procedimientos_abm/get/' + id)
      .done(function (res) {
        $('#abmIdProcedimiento').val(res.id_procedimiento);
        $('#abmNombre').val(res.nombre);
        $('#tituloModalProcedimiento').text('| MODIFICAR PROCEDIMIENTO');
        $('#btnGuardarProcedimiento').val('modificar');

        // Ubicación por defecto = la posición actual (después del anterior; si es el primero, antes del siguiente).
        const idx = _procsCache.findIndex(function (p) { return p.id_procedimiento === res.id_procedimiento; });
        if (idx > 0)                     setUbicacion('despues', _procsCache[idx - 1].id_procedimiento);
        else if (_procsCache.length > 1) setUbicacion('antes',   _procsCache[1].id_procedimiento);

        renderCasinosForm(res.casinos || []);
      })
      .fail(function () {
        avisoEv('No se pudo cargar el procedimiento.');
        $('#modalProcedimiento').modal('hide');
      });
  }

  function guardarProcedimiento() {
    $('#abmNombre').removeClass('alerta');
    $('#abmAlertaNombre').text('').hide();

    const esNuevo = $('#btnGuardarProcedimiento').val() === 'nuevo';
    const nombre  = ($('#abmNombre').val() || '').trim();

    if (!nombre) {
      $('#abmNombre').addClass('alerta');
      $('#abmAlertaNombre').text('El nombre es obligatorio.').show();
      return;
    }

    // Mapa { id_casino: 0|1 } con TODOS los casinos del usuario (para saber cuáles apagar).
    const casinos = {};
    $('#abmListaCasinos .abmChkCasino').each(function () {
      casinos[this.value] = this.checked ? 1 : 0;
    });

    const data = {
      nombre:   nombre,
      posicion: $('#abmPosicion').val() || 'despues',
      casinos:  casinos,
      _token:   $('meta[name="csrf-token"]').attr('content'),
    };
    const ref = $('#abmRef').val();
    if (ref) data.ref_id = parseInt(ref, 10);
    if (!esNuevo) data.id_procedimiento = parseInt($('#abmIdProcedimiento').val(), 10);

    $.ajax({
      url: esNuevo
        ? '/eventualidades/procedimientos_abm/guardar'
        : '/eventualidades/procedimientos_abm/modificar',
      method: 'POST',
      data: data,
      success: function () {
        $('#modalProcedimiento').modal('hide');
        cargarData();
      },
      error: function (xhr) {
        const errs = xhr.responseJSON && xhr.responseJSON.errors;
        if (errs && errs.nombre) {
          $('#abmNombre').addClass('alerta');
          $('#abmAlertaNombre').text(errs.nombre[0]).show();
        } else {
          const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error al guardar.';
          avisoEv(msg);
        }
      }
    });
  }

  function eliminarProcedimiento(id) {
    $.ajax({
      url: '/eventualidades/procedimientos_abm/eliminar/' + id,
      method: 'DELETE',
      data: { _token: $('meta[name="csrf-token"]').attr('content') },
      success: function () {
        $('#modalEliminarProcedimiento').modal('hide');
        cargarData();
      },
      error: function (xhr) {
        const msg = (xhr.responseJSON && xhr.responseJSON.error) || 'Error al eliminar.';
        avisoEv(msg);
        $('#modalEliminarProcedimiento').modal('hide');
      }
    });
  }

  // Bindings
  $('#btnAbrirABMProcedimientos').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $('#modalGestionProcedimientos').modal('show');
    if (!_initialized) {
      cargarData().always(function () { _initialized = true; });
    } else {
      cargarData();
    }
  });

  $(document).on('click', '#btnNuevoProcedimiento', abrirFormNuevo);
  $(document).on('click', '.btnModificarProc', function () { abrirFormModificar($(this).data('id')); });
  $(document).on('click', '.btnEliminarProc', function () {
    $('#btnEliminarProcedimientoModal').val($(this).data('id'));
    $('#modalEliminarProcedimiento').modal('show');
  });
  $(document).on('click', '#btnGuardarProcedimiento', guardarProcedimiento);
  $(document).on('click', '#btnEliminarProcedimientoModal', function () {
    eliminarProcedimiento(parseInt($(this).val(), 10));
  });
  $(document).on('hidden.bs.modal', '#modalProcedimiento', limpiarFormProc);
})();
