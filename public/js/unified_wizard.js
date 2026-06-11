// Modal de confirmación personalizado (reemplaza confirm() nativo)
(function () {
    var modalHtml =
        '<div class="modal fade" id="modalConfirmar" tabindex="-1" role="dialog" style="z-index: 99999;">' +
        '  <div class="modal-dialog modal-sm" role="document">' +
        '    <div class="modal-content" style="border-radius: 10px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">' +
        '      <div class="modal-header" id="modalConfirmarHeader" style="background: #d9534f; color: white; border: none; padding: 15px 20px;">' +
        '        <h4 class="modal-title" style="margin: 0; font-size: 16px;"><i class="fa fa-exclamation-triangle"></i> <span id="modalConfirmarTitulo">Confirmar</span></h4>' +
        '      </div>' +
        '      <div class="modal-body" style="padding: 20px; font-size: 14px;">' +
        '        <p id="modalConfirmarMensaje" style="margin: 0;"></p>' +
        '      </div>' +
        '      <div class="modal-footer" style="border-top: 1px solid #eee; padding: 12px 20px;">' +
        '        <button type="button" class="btn btn-default" id="btnConfirmarCancelar" data-dismiss="modal">Cancelar</button>' +
        '        <button type="button" class="btn btn-danger" id="btnConfirmarAceptar">Aceptar</button>' +
        '      </div>' +
        '    </div>' +
        '  </div>' +
        '</div>';
    if ($('#modalConfirmar').length === 0) {
        $('body').append(modalHtml);
    }
})();

function confirmar(mensaje, callback, opciones) {
    opciones = opciones || {};
    var titulo = opciones.titulo || 'Confirmar';
    var color = opciones.color || '#d9534f';
    var textoBoton = opciones.textoBoton || 'Aceptar';

    $('#modalConfirmarTitulo').text(titulo);
    $('#modalConfirmarMensaje').html(mensaje);
    $('#modalConfirmarHeader').css('background', color);
    $('#btnConfirmarAceptar').text(textoBoton).removeClass('btn-danger btn-warning btn-primary').addClass(
        color === '#f0ad4e' ? 'btn-warning' : color === '#337ab7' ? 'btn-primary' : 'btn-danger'
    );

    // Limpiar handlers previos
    $('#btnConfirmarAceptar').off('click').on('click', function () {
        $('#modalConfirmar').modal('hide');
        if (callback) callback();
    });

    $('#modalConfirmar').modal('show');
}

$(document).ready(function () {
    $('.tituloSeccionPantalla').text('Notas Unificadas');
    let activos = [];

    // --- ESPACIADO DEL WIZARD STEP 2 ---
    (function fixStep2Spacing() {
        var $s = $('#step2Content');
        if (!$s.length) return;
        // Aplicar margin-bottom a cada hijo directo que sea un contenedor de campo
        $s.children('div').not('[style*="margin-top"]').each(function () {
            $(this).css('margin-bottom', '25px');
        });
        // Labels: más espacio abajo, bold
        $s.find('label').each(function () {
            $(this).css({
                'display': 'block',
                'margin-bottom': '8px',
                'margin-top': '0',
                'font-weight': '600',
                'font-size': '12px',
                'text-transform': 'uppercase',
                'color': '#374151',
                'letter-spacing': '0.3px'
            });
        });
    })();

    // --- INIT & HELPERS ---
    function esPlataformaSeleccionada(selector) {
        return $(selector).find('option:selected').data('es-plataforma') == '1';
    }

    // Plataforma de apuestas deportivas: opción de casino/plataforma que NO asocia juegos/MTM
    function esDeportivaSeleccionada() {
        return $('#selCasino').find('option:selected').data('es-deportiva') == '1';
    }

    function actualizarHiddenCasino() {
        var opt = $('#selCasino').find('option:selected');
        if (opt.data('es-plataforma') == '1') {
            $('#hidCasinoId').val('');
            $('#hidPlataformaId').val(opt.val());
        } else {
            $('#hidCasinoId').val(opt.val());
            $('#hidPlataformaId').val('');
        }
    }

    function actualizarTiposActivo() {
        let select = $('#selTipoActivo');
        select.empty();

        // Apuestas deportivas: sin juegos/MTM asociables
        if (esDeportivaSeleccionada()) {
            return;
        }

        if (esPlataformaSeleccionada('#selCasino')) {
            select.append('<option value="JUEGO_ONLINE">Juego Online</option>');
            $('#inpIdActivo').attr('placeholder', 'ID o Codigo de Juego');
        } else {
            select.append('<option value="ISLA">Isla (Físico)</option>');
            select.append('<option value="MTM">MTM Individual</option>');
            select.append('<option value="MESA">Mesa de Paño</option>');
            select.append('<option value="BINGO">Bingo</option>');
            $('#inpIdActivo').attr('placeholder', 'Nro Isla / Admin / Mesa');
        }
    }

    actualizarTiposActivo();
    actualizarHiddenCasino();
    aplicarRestriccionDeportiva();

    // --- EVENT HANDLERS STEP 1 ---

    $('#selCasino').change(function () {
        actualizarHiddenCasino();
        actualizarTiposActivo();
        filtrarTipoEventoFISC();
        aplicarRestriccionDeportiva();
        // Limpiar activos al cambiar de casino/plataforma
        activos = [];
        $('#tablaActivos tbody').empty();
    });

    // Apuestas deportivas: no asocian juegos/MTM — se oculta el tilde
    // "¿Involucra Juegos?" (MKT), el panel de activos asociados y el tilde
    // "¿Involucra a sala de Casino Físico?" (compartir con administrador).
    function aplicarRestriccionDeportiva() {
        if (esDeportivaSeleccionada()) {
            $('#chkInvolucraJuegos').prop('checked', false);
            $('#secInvolucraJuegos').hide();
            $('#chkCompartirAdmin').prop('checked', false);
            $('#secCompartirAdmin').hide();
            $('#secActivosAsociados').hide();
            activos = [];
            renderTablaActivos();
        } else {
            $('#secInvolucraJuegos').show();
            $('#secCompartirAdmin').show();
        }
    }

    function filtrarTipoEventoFISC() {
        var esPlataforma = esPlataformaSeleccionada('#selCasino');
        $('#selTipoEventoFISC option').each(function () {
            var ctx = $(this).data('contexto');
            if (!ctx || ctx === 'todos') {
                $(this).show();
            } else if (ctx === 'fisico') {
                $(this).toggle(!esPlataforma);
            } else if (ctx === 'plataforma') {
                $(this).toggle(esPlataforma);
            }
        });
        var $sel = $('#selTipoEventoFISC');
        if ($sel.find('option:selected').is(':hidden')) {
            $sel.val('');
        }
    }

    // Ocultar activos cuando el tipo evento FISC es un Alta (no existe el activo aún)
    $('#selTipoEventoFISC').change(function () {
        var texto = $(this).find('option:selected').text().trim().toUpperCase();
        if (texto.indexOf('ALTA') === 0) {
            $('#secActivosAsociados').slideUp();
            activos = [];
            $('#tablaActivos tbody').empty();
        } else if ($('#selTipoTarea').val() === 'FISCALIZACION' && !esDeportivaSeleccionada()) {
            $('#secActivosAsociados').slideDown();
        }
    });

    $('#selTipoActivo').change(function () {
        let tipo = $(this).val();
        if (tipo === 'BINGO') {
            $('#inpIdActivo').val('Actividad Bingo (General)');
            $('#inpIdActivo').attr('disabled', true);
            $('#hidIdActivo').val('0');
            $('#resultadosBusqueda').hide();
        } else {
            $('#inpIdActivo').val('');
            $('#inpIdActivo').attr('disabled', false);
            $('#hidIdActivo').val('');
            if (tipo.includes('JUEGO_ONLINE')) {
                $('#inpIdActivo').attr('placeholder', 'ID o Codigo de Juego');
            } else {
                $('#inpIdActivo').attr('placeholder', 'Nro Isla / Admin / Mesa');
            }
        }
    });

    // Toggle Fields based on Task Type & Request Type
    function updateSectionVisibility() {
        let tarea = $('#selTipoTarea').val(); // MARKETING or FISCALIZACION

        // Cambiar de tipo de tarea reinicia los activos (evita arrastrar juegos entre ramas)
        activos = [];
        renderTablaActivos();

        if (tarea === 'MARKETING') {
            $('.section-marketing').show();
            $('.section-fiscalizacion').hide();
            // Re-aplicar: el show() de .section-marketing vuelve a mostrar
            // el tilde "¿Involucra Juegos?" aunque sea apuestas deportivas
            aplicarRestriccionDeportiva();

            // Apply gradient for Marketing
            $('.wizard-sidebar').css('background', 'linear-gradient(160deg, #3b82f6 0%, #8b5cf6 100%)');

            // Required Logic
            $('#selTipoSolicitud').attr('required', true);
            $('#selTipoEventoLegacy').attr('required', false);
            $('#selCategoriaLegacy').attr('required', false);

            // Fecha fin obligatoria para MKT
            $('#inpFechaFin').attr('required', true);
            $('#lblFechaFin').html('Fecha Fin * <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Cuándo finaliza la vigencia."></i>');

            // Trigger sub-logic
            $('#selTipoSolicitud').trigger('change');

        } else {
            // FISCALIZACION
            $('.section-marketing').hide();
            $('.section-fiscalizacion').show();

            // Apply gradient for Fiscalizacion (Green/Teal?)
            $('.wizard-sidebar').css('background', 'linear-gradient(160deg, #10b981 0%, #0d9488 100%)');

            // Required Logic
            $('#selTipoSolicitud').attr('required', false);
            $('#selTipoEventoLegacy').attr('required', true);
            $('#selCategoriaLegacy').attr('required', true);

            // Fecha fin opcional para FISC
            $('#inpFechaFin').attr('required', false);
            $('#lblFechaFin').html('Fecha Fin <i class="fa fa-question-circle text-muted" data-toggle="tooltip" title="Cuándo finaliza la vigencia (opcional)."></i>');

            // Filtrar tipos evento según casino seleccionado
            filtrarTipoEventoFISC();

            // Mostrar activos (salvo que sea Alta o apuestas deportivas)
            if (!esDeportivaSeleccionada()) {
                $('#secActivosAsociados').slideDown();
            }
        }
        updateProgressBar();
    }

    $('#selTipoTarea').change(function () {
        updateSectionVisibility();
    });

    $('#selTipoSolicitud').on('change input', function () {
        if ($('#selTipoTarea').val() !== 'MARKETING') return;
        // MKT: el panel de activos depende del tilde "¿Involucra Juegos?"
        toggleActivosMkt();
    });

    // MKT: muestra/oculta el panel de activos según el tilde "¿Involucra Juegos?"
    function toggleActivosMkt() {
        if ($('#selTipoTarea').val() !== 'MARKETING') return;
        if ($('#chkInvolucraJuegos').is(':checked') && !esDeportivaSeleccionada()) {
            actualizarTiposActivo();
            $('#secActivosAsociados').slideDown();
        } else {
            $('#secActivosAsociados').slideUp();
            activos = [];
            renderTablaActivos();
        }
        updateProgressBar();
    }

    $('#chkInvolucraJuegos').change(toggleActivosMkt);

    // --- NON-LINEAR PROGRESS BAR & ANIMATIONS ---
    function updateProgressBar() {
        let totalFields = 0;
        let filledFields = 0;
        let fields = $('#frmNuevaNota').find('input, select').filter('[required]:visible');
        totalFields = fields.length;
        fields.each(function () { if ($(this).val()) filledFields++; });
        let percent = totalFields === 0 ? 0 : Math.round((filledFields / totalFields) * 100);

        if ($('#wizardProgressBar').length === 0) {
            $('.wizard-sidebar').append(`
                <div style="margin-top:auto; padding-top:20px;">
                    <small style="color:white; opacity:0.8; display:block; margin-bottom:5px;">Completado</small>
                    <div class="progress" style="height:6px; background:rgba(255,255,255,0.2); border-radius:3px; margin:0;">
                        <div id="wizardProgressBar" class="progress-bar" role="progressbar" style="width: 0%; background:white; transition:width 0.5s;"></div>
                    </div>
                    <small id="wizardProgressText" style="color:white; font-weight:bold; display:block; text-align:right; margin-top:5px;">0%</small>
                </div>
             `);
        }
        $('#wizardProgressBar').css('width', percent + '%');
        $('#wizardProgressText').text(percent + '%');
    }

    $('#frmNuevaNota').on('change input', 'input, select', function () {
        updateProgressBar();
    });

    // Trigger initial state
    $('#selTipoSolicitud').trigger('change');

    // --- DROPDOWN FILTERING LOGIC (MKT vs FISC) ---
    window.filterDropdowns = function (type) {
        // IDs of dropdowns to filter
        const targets = ['#selTipoEventoLegacy', '#selCategoriaLegacy'];

        targets.forEach(selector => {
            let select = $(selector);
            select.val(''); // Reset selection

            select.find('option').each(function () {
                let optType = $(this).data('tipo');
                // DEBUG
                // console.log("Opt: " + $(this).text() + " Type: " + optType + " Target: " + type);

                // Always show placeholder (empty value)
                if ($(this).val() === "") {
                    $(this).show();
                    return;
                }

                // Show only matching type
                if (optType === type) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    };

    // --- ASSET SEARCH LOGIC ---
    let timeout = null;

    $(document).click(function (e) {
        if (!$(e.target).closest('#inpIdActivo, #resultadosBusqueda').length) {
            $('#resultadosBusqueda').hide();
        }
    });

    $('#inpIdActivo').keyup(function (e) {
        clearTimeout(timeout);
        let val = $(this).val();
        let tipo = $('#selTipoActivo').val();
        let params = { q: val, tipo: tipo };
        if (esPlataformaSeleccionada('#selCasino')) {
            params.id_plataforma = $('#selCasino').val();
        } else {
            params.id_casino = $('#selCasino').val();
        }

        if (val.length < 1) {
            $('#resultadosBusqueda').hide();
            return;
        }

        timeout = setTimeout(function () {
            $.get('/notas-unificadas/buscar-activos', params, function (data) {
                $('#resultadosBusqueda').empty();

                if (data.length === 0) {
                    $('#resultadosBusqueda').append('<a href="#" class="list-group-item list-group-item-warning">No se encontraron resultados</a>');
                } else {
                    data.forEach(function (item) {
                        let safeData = item.data || {};
                        let dataJson = JSON.stringify(safeData).replace(/"/g, "&quot;");
                        let itemHtml = `
                            <a href="#" class="list-group-item list-group-item-action resultado-item" 
                               data-id="${item.id}" 
                               data-text="${item.text}"
                               data-extra="${dataJson}">
                                <strong>${item.text}</strong><br>
                                <small class="text-muted">${item.info}</small>
                            </a>
                        `;
                        $('#resultadosBusqueda').append(itemHtml);
                    });
                }
                $('#resultadosBusqueda').show();
            });
        }, 300);
    });

    $(document).on('click', '.resultado-item', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
        let text = $(this).data('text');
        let data = $(this).data('extra');

        $('#inpIdActivo').val(text);
        $('#inpIdActivo').data('selected-data', data);
        $('#hidIdActivo').val(id);
        $('#resultadosBusqueda').hide();
    });

    // --- TABLE & ASSET ADDITION ---
    var tipoLabels = {
        'MTM': 'Máquinas Tragamonedas',
        'MESA': 'Mesas de Paño',
        'ISLA': 'Islas',
        'BINGO': 'Bingo',
        'JUEGO_ONLINE': 'Juegos Online'
    };

    function renderTablaActivos() {
        var thead = $('#tablaActivos thead tr');
        var tbody = $('#tablaActivos tbody');
        thead.empty();
        tbody.empty();

        if (activos.length === 0) {
            thead.append('<th>Tipo</th><th>ID</th><th>Acción</th>');
            return;
        }

        // Agrupar por tipo manteniendo orden de aparición
        var tiposOrden = [];
        var grupos = {};
        activos.forEach(function(a) {
            if (!grupos[a.tipo]) {
                grupos[a.tipo] = [];
                tiposOrden.push(a.tipo);
            }
            grupos[a.tipo].push(a);
        });

        // Calcular max columnas entre todos los tipos
        var maxCols = 0;
        tiposOrden.forEach(function(tipo) {
            var keys = Object.keys(grupos[tipo][0].data || {});
            if (keys.length > maxCols) maxCols = keys.length;
        });
        maxCols += 1; // +1 por columna acción
        thead.append('<th colspan="' + maxCols + '" style="display:none;"></th>');

        tiposOrden.forEach(function(tipo) {
            var items = grupos[tipo];
            var sample = items[0].data || {};
            var keys = Object.keys(sample);
            var extraCols = maxCols - keys.length - 1; // celdas vacías para rellenar

            // Sub-header de grupo
            tbody.append(
                '<tr class="activo-group-header">' +
                    '<td colspan="' + maxCols + '" style="background:#f1f5f9; font-weight:700; font-size:12px; padding:8px 12px; color:#2c3e50; border-bottom:2px solid #cbd5e1;">' +
                        '<i class="fa fa-caret-down" style="color:#3498db;"></i> ' + (tipoLabels[tipo] || tipo) +
                        ' <span style="font-weight:400; color:#94a3b8;">(' + items.length + ')</span>' +
                    '</td>' +
                '</tr>'
            );

            // Header de columnas para este tipo
            var headerRow = '<tr style="background:#f8fafc;">';
            keys.forEach(function(k) {
                headerRow += '<th style="padding:5px 10px; font-size:11px; color:#64748b; font-weight:600;">' + k + '</th>';
            });
            for (var i = 0; i < extraCols; i++) { headerRow += '<th></th>'; }
            headerRow += '<th style="padding:5px 10px; font-size:11px; width:40px;"></th></tr>';
            tbody.append(headerRow);

            // Filas de datos
            items.forEach(function(a) {
                var row = '<tr data-tipo="' + a.tipo + '" data-id="' + a.id + '">';
                if (keys.length > 0) {
                    keys.forEach(function(k) {
                        row += '<td style="padding:4px 10px; font-size:12px;">' + (a.data[k] || '-') + '</td>';
                    });
                } else {
                    row += '<td style="padding:4px 10px; font-size:12px;">' + (a.texto || a.id) + '</td>';
                }
                for (var i = 0; i < extraCols; i++) { row += '<td></td>'; }
                row += '<td style="padding:4px 10px;"><button class="btn btn-xs btn-borrar-activo" style="background:#e2e8f0; color:#64748b; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold; transition:all 0.15s;" onmouseover="this.style.background=\'#d9534f\';this.style.color=\'#fff\';this.style.borderColor=\'#d43f3a\';" onmouseout="this.style.background=\'#e2e8f0\';this.style.color=\'#64748b\';this.style.borderColor=\'#cbd5e1\';">X</button></td></tr>';
                tbody.append(row);
            });
        });
    }

    function agregarFila(tipo, id, texto, data) {
        if (activos.some(a => a.id == id && a.tipo == tipo)) {
            return false;
        }

        activos.push({ tipo: tipo, id: id, texto: texto, data: data || {} });
        renderTablaActivos();
        return true;
    }

    $('#btnAgregarActivo').click(function () {
        let tipo = $('#selTipoActivo').val();
        let texto = $.trim($('#inpIdActivo').val());
        let idSel = $('#hidIdActivo').val();
        let data = $('#inpIdActivo').data('selected-data') || {};

        function limpiar() {
            $('#inpIdActivo').val('');
            $('#inpIdActivo').data('selected-data', null);
            $('#hidIdActivo').val('');
        }

        // Explota una isla (ya resuelta) en sus MTMs.
        function explotarIsla(idIsla) {
            mostrarCargando(true);
            $.get('/notas-unificadas/obtener-activos-isla/' + idIsla, function (mtms) {
                mostrarCargando(false);
                if (mtms.length > 0) {
                    let n = 0;
                    mtms.forEach(function (m) { if (agregarFila('MTM', m.id, m.text, m.data)) n++; });
                    notificacion('success', 'Se agregaron ' + n + ' máquinas.');
                } else {
                    notificacion('error', 'La isla no tiene máquinas activas.');
                }
            }).fail(function () { mostrarCargando(false); notificacion('error', 'Error al obtener isla'); });
        }

        // BINGO: actividad general, no requiere validar un id real.
        if (tipo === 'BINGO') {
            agregarFila(tipo, idSel || 'BINGO', texto || 'Bingo (general)', { 'Descripción': 'Aplica a todas las sesiones / actividad general de Bingo' });
            limpiar();
            return;
        }

        // Si eligió del desplegable, el id ya es canónico/válido.
        if (idSel) {
            if (tipo === 'ISLA') { explotarIsla(idSel); limpiar(); return; }
            if (!agregarFila(tipo, idSel, texto || idSel, data)) notificacion('warning', 'Este activo ya está en la lista.');
            else limpiar();
            return;
        }

        if (!texto) { notificacion('warning', 'Buscá y seleccioná un activo (o escribí su ID/código).'); return; }

        // No eligió: RESOLVER el valor tipeado contra los datos reales. No se agregan números inventados.
        var params = { _token: $('meta[name="csrf-token"]').attr('content'), tipo: tipo, valores: [texto] };
        if (esPlataformaSeleccionada('#selCasino')) params.id_plataforma = $('#selCasino').val();
        else params.id_casino = $('#selCasino').val();
        $.post('/notas-unificadas/resolver-activos', params, function (res) {
            if (res.resueltos && res.resueltos.length) {
                var r = res.resueltos[0];
                if (tipo === 'ISLA') { explotarIsla(r.id); limpiar(); return; }
                agregarFila(tipo, r.id, r.nombre, {});
                limpiar();
            } else if (res.ambiguos && res.ambiguos.length) {
                notificacion('warning', '"' + texto + '" coincide con varios; elegilo del buscador.');
            } else {
                notificacion('error', '"' + texto + '" no corresponde a ningún activo de este casino/plataforma.');
            }
        }).fail(function () { notificacion('error', 'Error al validar el activo.'); });
    });

    // Wizard — carga masiva: mostrar/ocultar textarea
    $(document).on('click', '#wizToggleMasiva', function () {
        $('#wizMasivaWrap').slideToggle(150);
    });

    // Wizard — carga masiva: resolver lista pegada y agregar los válidos
    $(document).on('click', '#wizBtnResolverMasiva', function () {
        var tipo = $('#selTipoActivo').val();
        var raw = $('#wizMasivaText').val() || '';
        var valores = raw.split(/[\r\n,;]+/).map(function (s) { return s.trim(); }).filter(function (s) { return s.length; });
        if (!valores.length) { notificacion('warning', 'Pegá al menos un ID o código.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Resolviendo...');
        var params = { _token: $('meta[name="csrf-token"]').attr('content'), tipo: tipo, valores: valores };
        if (esPlataformaSeleccionada('#selCasino')) params.id_plataforma = $('#selCasino').val();
        else params.id_casino = $('#selCasino').val();

        $.post('/notas-unificadas/resolver-activos', params, function (res) {
            $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> Resolver y agregar');
            var ag = 0, dup = 0;
            (res.resueltos || []).forEach(function (r) { if (agregarFila(tipo, r.id, r.nombre, {})) ag++; else dup++; });
            var rep = '<div style="color:#166534;"><b>' + ag + '</b> agregados' + (dup ? ' · ' + dup + ' ya estaban' : '') + '</div>';
            if (res.no_encontrados && res.no_encontrados.length) {
                rep += '<div style="color:#991b1b; margin-top:4px;"><b>No encontrados (' + res.no_encontrados.length + '):</b> ' + res.no_encontrados.map(escAdj).join(', ') + '</div>';
            }
            if (res.ambiguos && res.ambiguos.length) {
                rep += '<div style="color:#92400e; margin-top:4px;"><b>Ambiguos (' + res.ambiguos.length + ') — elegilos del buscador:</b> ' + res.ambiguos.map(function (a) { return escAdj(a.valor); }).join(', ') + '</div>';
            }
            $('#wizMasivaReporte').html(rep);
            // Dejar en el textarea solo los problemáticos
            var problem = (res.no_encontrados || []).concat((res.ambiguos || []).map(function (a) { return a.valor; }));
            $('#wizMasivaText').val(problem.join('\n'));
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> Resolver y agregar');
            notificacion('error', 'Error al resolver la lista.');
        });
    });

    $('#tablaActivos').on('click', '.btn-borrar-activo', function () {
        let tr = $(this).closest('tr');
        let tipo = tr.data('tipo');
        let id = tr.data('id');
        activos = activos.filter(function(a) {
            return !(a.tipo == tipo && a.id == id);
        });
        renderTablaActivos();
    });

    // --- FORM SUBMISSION & STEPPER ---

    $('#btnGuardarNota').click(function () {
        // Validation
        let reqFields = $('#modalNuevaNota [required]');
        let valid = true;
        reqFields.each(function () {
            if (!$(this).val()) {
                $(this).closest('.form-group, .col-md-6, .col-md-12').addClass('has-error');
                valid = false;
            } else {
                $(this).closest('.form-group, .col-md-6, .col-md-12').removeClass('has-error');
            }
        });

        if (!valid) {
            notificacion('error', 'Por favor complete todos los campos obligatorios.');
            return;
        }

        // Date Valid
        if ($('#selTipoSolicitud').val() === 'EVENTO') {
            let d1 = new Date($('#inpFechaInicioEvento').val());
            let d2 = new Date($('#inpFechaFinEvento').val());
            if (d2 < d1) {
                notificacion('error', 'La fecha de fin no puede ser menor a la de inicio.');
                return;
            }
        }

        let tipoTarea = $('#selTipoTarea').val();
        let isMkt = tipoTarea === 'MARKETING';
        let formData = {
            nro_nota: $('input[name="nro_nota"]').val(),
            anio: $('input[name="anio"]').val(),
            titulo: $('input[name="titulo"]').val(),
            id_casino: $('#hidCasinoId').val(),
            id_plataforma: $('#hidPlataformaId').val(),
            tipo_tarea: tipoTarea,
            tipo_solicitud: $('#selTipoSolicitud').val(),
            // MKT: Tipo Evento eliminado de la UI → siempre null. FISC mantiene su Tipo Evento Técnico.
            id_tipo_evento: isMkt ? null : $('#selTipoEventoFISC').val(),
            id_categoria: isMkt ? $('#selCategoriaMKT').val() : $('#selCategoriaFISC').val(),
            fecha_inicio_evento: $('input[name="fecha_inicio_evento"]').val(),
            fecha_fin_evento: $('input[name="fecha_fin_evento"]').val(),
            // fecha_referencia eliminada de la UI; el backend recibirá null.
            activos: activos,
            _token: $('input[name="_token"]').val()
        };

        $(this).attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Iniciando...');

        $.ajax({
            url: '/notas-unificadas/iniciar',
            type: 'POST',
            data: formData,
            success: function (res) {
                notificacion('success', 'Nota iniciada correctamente.');
                $('#btnGuardarNota').attr('disabled', false).text('Iniciar Trámite');

                if (document.startViewTransition) {
                    document.startViewTransition(() => {
                        $('#modalNuevaNota').modal('hide');
                        setTimeout(() => { setupStep2(res); }, 300); // Shorter wait
                    });
                } else {
                    // Fallback
                    $('#modalNuevaNota').modal('hide');
                    setTimeout(function () { setupStep2(res); }, 500);
                }
            },
            error: function (err) {
                let msg = 'Error al guardar.';
                if (err.responseJSON && err.responseJSON.mensaje) msg = err.responseJSON.mensaje;
                notificacion('error', msg);
                $('#btnGuardarNota').attr('disabled', false).text('Iniciar Trámite');
            }
        });
    });

    function setupStep2(dataRes) {
        // Populate Hidden IDs
        if (dataRes.ids_notas) {
            $('#hidIdNotaFisc').val(dataRes.ids_notas.fisc || '');
            $('#hidIdNotaMkt').val(dataRes.ids_notas.mkt || '');
        } else {
            $('#hidIdNotaMkt').val(dataRes.id_nota || '');
        }

        $('#lblNotaIniciada').text(dataRes.nro_nota + '-' + dataRes.anio);
        $('#lblTituloNota').text(dataRes.titulo);
        $('#lblTipoNota').text(dataRes.tipo_solicitud);

        // Reset Files
        $('#frmPaso2')[0].reset();
        $('.dropzone input').val('');
        $('.dropzone input[type=text]').val('');

        $('#modalPaso2').modal('show');
    }

    // --- 5-STEP WIZARD LOGIC ---

    let currentStep = 0; // 0=Task, 1=General, 2=Spec, 3=Files, 4=Summary

    // Init Tooltips
    // ============================================
    // TOOLTIPS DISABLED - Performance optimization
    // ============================================
    // $('[data-toggle="tooltip"]').tooltip();

    // EXPOSE TO WINDOW FOR ONCLICK IN BLADE
    window.selectTaskType = function (type) {
        console.log("Selected Type: " + type);
        $('#selTipoTarea').val(type);
        updateSectionVisibility();

        // Highlight Selected Card
        $('.card-type').css('border', '2px solid transparent').removeClass('selected-card');
        if (type === 'MARKETING') {
            $('.card-type:eq(0)').css('border', '2px solid #3b82f6').addClass('selected-card');
        } else {
            $('.card-type:eq(1)').css('border', '2px solid #10b981').addClass('selected-card');
        }

        // Auto Advance
        setTimeout(() => window.wizardNext(), 300);
    };

    window.wizardNext = function () {
        console.log("Next Step: " + currentStep);
        if (!validateStep(currentStep)) return;

        // ! INTERCEPT FOR STEP 3 (Files) -> 4
        if (currentStep === 3) {
            uploadAdjuntos(() => {
                let nextStep = currentStep + 1;
                goToStep(nextStep);
            });
            return;
        }

        // ! INTERCEPT FOR STEP 1 (Datos admin) -> 2
        // Verifica dos casos contra el backend:
        //   a) rama_ya_existe -> bloquea con modal de error.
        //   b) existe (mismo nro+año+casino) pero otra rama -> pide confirmación de acople.
        if (currentStep === 1 && !$('#idGrupoExistente').val()) {
            $.get('/notas-unificadas/verificar-grupo-existente', {
                nro_nota: $('#inpNroNota').val(),
                anio: $('#inpAnio').val(),
                id_casino: $('#hidCasinoId').val() || '',
                id_plataforma: $('#hidPlataformaId').val() || '',
                tipo_tarea: $('#selTipoTarea').val()
            }).done(function (res) {
                if (res && res.existe && res.rama_ya_existe) {
                    mostrarModalAcople('duplicada', res.grupo, $('#selTipoTarea').val());
                    return;
                }
                if (res && res.existe && !res.rama_ya_existe) {
                    mostrarModalAcople('acoplar', res.grupo, $('#selTipoTarea').val(), function () {
                        goToStep(currentStep + 1);
                    });
                    return;
                }
                goToStep(currentStep + 1);
            }).fail(function () {
                // si falla la verificación, no bloquear el flujo
                goToStep(currentStep + 1);
            });
            return;
        }

        let nextStep = currentStep + 1;
        goToStep(nextStep);
    };

    // Modal personalizado para aviso de acople / rama duplicada
    // modo: 'acoplar' (confirm con callback) | 'duplicada' (solo cerrar)
    function mostrarModalAcople(modo, grupo, tipoTarea, onConfirm) {
        grupo = grupo || {};
        var ramaNueva = tipoTarea === 'MARKETING' ? 'MKT' : (tipoTarea === 'FISCALIZACION' ? 'FISC' : '');
        var ramasBadges = (grupo.ramas || []).map(function (r) {
            var cls = r === 'MKT' ? 'label-primary' : 'label-success';
            return '<span class="label ' + cls + '" style="margin-right:4px;">' + r + '</span>';
        }).join('') || '<span class="text-muted">—</span>';

        var $header = $('#wizardAcopleHeader');
        var $title = $('#wizardAcopleTitle');
        var $body = $('#wizardAcopleBody');
        var $footer = $('#wizardAcopleFooter');

        if (modo === 'duplicada') {
            $header.css('background', 'linear-gradient(135deg, #ef4444, #b91c1c)');
            $title.html('<i class="fa fa-ban"></i> Ya existe una nota ' + ramaNueva);
            $body.html(
                '<p style="margin:0 0 12px; color:#334155;">' +
                'El trámite <strong>Nro ' + grupo.nro_nota + '-' + grupo.anio + '</strong> ' +
                'de <strong>' + (grupo.casino || '—') + '</strong> ya tiene una nota <strong>' + ramaNueva + '</strong>.' +
                '</p>' +
                '<div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; font-size:13px;">' +
                (grupo.titulo ? '<div style="margin-bottom:6px;"><strong>Título:</strong> ' + grupo.titulo + '</div>' : '') +
                '<div><strong>Ramas existentes:</strong> ' + ramasBadges + '</div>' +
                '</div>' +
                '<p style="margin:14px 0 0; color:#64748b; font-size:12.5px;">' +
                'No podés crear otra nota ' + ramaNueva + ' para el mismo trámite. Cambiá el Nro de Nota, el año o el casino, o complementá el trámite existente desde la grilla.' +
                '</p>'
            );
            $footer.html(
                '<button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:8px;">Entendido</button>'
            );
        } else {
            $header.css('background', 'linear-gradient(135deg, #f59e0b, #d97706)');
            $title.html('<i class="fa fa-link"></i> Se va a acoplar a un trámite existente');
            $body.html(
                '<p style="margin:0 0 12px; color:#334155;">' +
                'Ya existe el trámite <strong>Nro ' + grupo.nro_nota + '-' + grupo.anio + '</strong> ' +
                'para <strong>' + (grupo.casino || '—') + '</strong>.' +
                '</p>' +
                '<div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:12px; font-size:13px;">' +
                (grupo.titulo ? '<div style="margin-bottom:6px;"><strong>Título:</strong> ' + grupo.titulo + '</div>' : '') +
                '<div><strong>Ramas existentes:</strong> ' + ramasBadges + '</div>' +
                '</div>' +
                '<p style="margin:14px 0 0; color:#334155;">' +
                'Si continuás, esta nota <strong>' + ramaNueva + '</strong> se va a <strong>acoplar</strong> al trámite existente ' +
                '(no se crea un trámite nuevo).' +
                '</p>'
            );
            $footer.html(
                '<button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius:8px;">Cancelar</button> ' +
                '<button type="button" id="btnConfirmarAcople" class="btn btn-warning" style="border-radius:8px;"><i class="fa fa-link"></i> Acoplar y continuar</button>'
            );
            $('#btnConfirmarAcople').off('click').on('click', function () {
                $('#modalWizardAcople').modal('hide');
                if (typeof onConfirm === 'function') onConfirm();
            });
        }

        $('#modalWizardAcople').modal('show');
    }

    function uploadAdjuntos(callback) {
        let formData = new FormData();
        formData.append('_token', $('input[name="_token"]').val());

        // Retrieve IDs injected by crearNotaBorrador
        let id_nota_mkt = $('input[name="id_nota_mkt"]').val();
        let id_nota_fisc = $('input[name="id_nota_fisc"]').val();

        console.log('UPLOAD DEBUG: id_nota_mkt=', id_nota_mkt, ', id_nota_fisc=', id_nota_fisc);

        formData.append('id_nota_mkt', id_nota_mkt);
        formData.append('id_nota_fisc', id_nota_fisc);

        // Adjunta TODOS los archivos de cada input (soporta selección múltiple) como inputName[].
        function appendFiles(inputId, fieldName) {
            var el = document.getElementById(inputId);
            if (!el || !el.files) return;
            for (var i = 0; i < el.files.length; i++) {
                formData.append(fieldName + '[]', el.files[i]);
            }
        }
        // MKT
        appendFiles('adjuntoSolicitud', 'adjuntoSolicitud');
        appendFiles('adjuntoDisenio', 'adjuntoDisenio');
        appendFiles('adjuntoBases', 'adjuntoBases');
        appendFiles('adjuntoInformeMkt', 'adjuntoInformeMkt');
        appendFiles('adjuntoAnexosMkt', 'adjuntoAnexosMkt');
        // FISC
        appendFiles('adjuntoSolicitudFisc', 'adjuntoSolicitudFisc');
        appendFiles('adjuntoVarios', 'adjuntoVarios');
        appendFiles('adjuntoInformeFisc', 'adjuntoInformeFisc');
        appendFiles('adjuntoAnexosFisc', 'adjuntoAnexosFisc');

        // UX: Show loading on button
        let btn = $('.btn-wizard-next');

        // Guard anti doble-submit: si ya hay una subida en curso, no disparar otra.
        if (btn.data('uploading')) {
            console.warn('uploadAdjuntos: ya hay una subida en curso, se ignora el click.');
            return;
        }
        btn.data('uploading', true);
        btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');

        // Re-habilita SIEMPRE el botón pase lo que pase (éxito, error, timeout) → nunca queda clavado.
        function rehabilitar() {
            btn.data('uploading', false);
            btn.attr('disabled', false).text('Siguiente');
        }

        $.ajax({
            url: '/notas-unificadas/guardar-adjuntos',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 180000, // 3 min: si el server no responde, no deja el botón colgado para siempre
            success: function (res) {
                console.log("Upload success", res);
                // Si el backend reporta fallo lógico, no avanzar.
                if (res && res.success === false) {
                    rehabilitar();
                    notificacion('error', 'No se pudieron subir los adjuntos: ' + (res.msg || 'Error desconocido'));
                    return;
                }
                rehabilitar();
                callback();
            },
            error: function (xhr, textStatus) {
                console.error("Upload error", textStatus, xhr.status, xhr.responseText);
                rehabilitar();
                var msg;
                if (textStatus === 'timeout') {
                    msg = 'La subida tardó demasiado y se canceló. Revisá tu conexión o probá con archivos más livianos.';
                } else if (xhr.status === 413) {
                    msg = 'Los archivos son demasiado grandes para el servidor (límite de subida superado).';
                } else if (xhr.status === 419 || xhr.status === 401) {
                    msg = 'Tu sesión expiró. Recargá la página e iniciá sesión de nuevo.';
                } else {
                    msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : ('Error al subir los adjuntos (HTTP ' + xhr.status + ').');
                }
                notificacion('error', msg);
            }
        });
    }

    window.wizardPrev = function () {
        if (currentStep === 0) return;
        let prevStep = currentStep - 1;
        goToStep(prevStep);
    };

    function goToStep(step) {
        // Animation / Transition View
        if (document.startViewTransition) {
            document.startViewTransition(() => { showStepUI(step); });
        } else {
            showStepUI(step);
        }
    }

    function showStepUI(step) {
        // Hide All Steps
        $('#step0Content, #step1Content, #step2Content, #step3Content, #step4Content').hide();

        // Show Current Step
        if (step === 0) $('#step0Content').fadeIn();
        if (step === 1) $('#step1Content').fadeIn();
        if (step === 2) {
            $('#step2Content').fadeIn();
            // Aplicar espaciado a los campos del paso 2
            $('#step2Content').children('div, h4').each(function () {
                if (!$(this).is('[style*="display:none"], [style*="display: none"]')) {
                    $(this).css('margin-bottom', '24px');
                }
            });
            $('#step2Content').find('label').css({
                'display': 'block',
                'margin-bottom': '8px'
            });
        }

        if (step === 3) {
            // STEP 3: ATTACHMENTS
            // Función para mostrar las secciones de adjuntos correctas
            let _mostrarSeccionesAdjuntos = function () {
                let tipoTarea = $('#selTipoTarea').val();
                let tipoSolicitud = $('#selTipoSolicitud').val();
                let isComplementing = $('#idGrupoExistente').val() !== '';

                console.log('STEP 3: tipoTarea=', tipoTarea, ', tipoSolicitud=', tipoSolicitud, ', isComplementing=', isComplementing);

                $('.section-marketing').hide();
                $('.section-fiscalizacion').hide();
                $('.section-informe').hide();

                if (isComplementing) {
                    if (tipoTarea === 'MARKETING') $('.section-marketing').show();
                    else if (tipoTarea === 'FISCALIZACION') $('.section-fiscalizacion').show();
                } else {
                    if (tipoTarea === 'MARKETING') {
                        $('.section-marketing').show();
                    } else if (tipoTarea === 'FISCALIZACION') {
                        $('.section-fiscalizacion').show();
                    }
                }

                $('#step3Content').fadeIn();
                currentStep = 3;
                updateStepperUI(3);
                updateButtonsUI(3);
            };

            // Solo crear borrador si aún no existe
            if (!window._draftGrupoId) {
                crearNotaBorrador(_mostrarSeccionesAdjuntos);
                return; // Wait for callback
            } else {
                _mostrarSeccionesAdjuntos();
                return;
            }
        }

        if (step === 4) {
            // Validate Step 3 (Adjuntos) if needed, then show Summary
            if (window.generateSummary) window.generateSummary(); // CALL SUMMARY GENERATOR
            $('#step4Content').fadeIn();
        }

        currentStep = step;
        updateStepperUI(step);
        updateButtonsUI(step);
    }

    function resetWizard() {
        console.log('RESET WIZARD');
        currentStep = 0;
        window._draftGrupoId = null;
        window._wizardFinished = false;

        // Hide all steps, show Step 0
        $('#step1Content, #step2Content, #step3Content, #step4Content').hide();
        $('#step0Content').show();

        // Clear form
        $('#frmNuevaNota')[0].reset();

        // Clear hidden inputs
        $('#idGrupoExistente').val('');
        $('#hidIdActivo').val('');
        $('#hidIdGrupoPadre').val('');
        $('#notaPadreSeleccionada').hide();
        $('#buscadorNotaPadre').show();
        $('#inpBuscarNotaPadre').val('');
        $('#resultadosBusquedaPadre').hide().empty();
        activos = []; // Global array
        $('#tablaActivos tbody').empty();
        $('#secActivosAsociados').hide();

        // Reset validators/classes
        $('.form-group').removeClass('has-error');
        $('.text-danger').remove();

        // Reset UI
        updateStepperUI(0);
        updateButtonsUI(0);

        // Reset fields state (in case they were disabled by complementation)
        $('#inpNroNota').prop('readonly', false);
        $('#inpAnio').prop('disabled', false);
        $('#selCasino').prop('disabled', false);
        $('#inpTitulo').prop('readonly', false);
        $('#selTipoSolicitud').prop('disabled', false);
        $('#inpFechaInicio').prop('readonly', false);
        $('#inpFechaFin').prop('readonly', false);

        // Reset Title
        $('#modalNuevaNota .modal-title').text('Nuevo Trámite Unificado'); // Default title

        // Reset Cards
        $('.card-type').show();
        $('.section-marketing, .section-fiscalizacion').hide();
    }

    // Expose globally if needed (though we are inside document.ready, our click handler is also inside)
    // But since complementarGrupo is inside document.ready, it can access this function directly.

    function updateStepperUI(step) {
        $('.stepper-item').removeClass('active completed');
        for (let i = 0; i <= 4; i++) {
            if (i < step) {
                $('#stepIndicator' + i).addClass('completed');
                $('#stepIndicator' + i + ' .step-counter').html('<i class="fa fa-check"></i>');
            } else if (i === step) {
                $('#stepIndicator' + i).addClass('active');
                $('#stepIndicator' + i + ' .step-counter').text(i + 1);
            } else {
                $('#stepIndicator' + i + ' .step-counter').text(i + 1);
            }
        }
    }

    function updateButtonsUI(step) {
        if (step === 0) {
            $('.btn-wizard-prev').hide();
            $('.btn-wizard-next').hide(); // Hidden, card click advances
            $('.btn-wizard-finish').hide();
        } else {
            $('.btn-wizard-prev').show();
            $('.btn-wizard-next').show();
            $('.btn-wizard-finish').hide();
        }

        if (step === 2) {
            $('.btn-wizard-next').text("Siguiente (Crear Borrador)");
        } else {
            $('.btn-wizard-next').text("Siguiente");
        }

        // Final Step: Show Finish Button
        if (step === 4) {
            $('.btn-wizard-next').hide();
            $('.btn-wizard-finish').show();
        }
    }

    function validateStep(step) {
        if (step === 0) return $('#selTipoTarea').val() !== '';

        let container = (step === 1) ? '#step1Content' : '#step2Content';
        let valid = true;
        // Check required inside container
        $(container).find('[required]:visible').each(function () {
            if (!$(this).val()) {
                $(this).closest('.form-group, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-12').addClass('has-error');
                valid = false;
            } else {
                $(this).closest('.form-group, .col-md-3, .col-md-4, .col-md-5, .col-md-6, .col-md-12').removeClass('has-error');
            }
        });

        if (!valid) notificacion('error', 'Complete los campos obligatorios.');
        return valid;
    }

    function crearNotaBorrador(callback) {
        let formData = {
            nro_nota: $('input[name="nro_nota"]').val(),
            anio: $('input[name="anio"]').val(),
            titulo: $('input[name="titulo"]').val(),
            id_casino: $('#hidCasinoId').val(),
            id_plataforma: $('#hidPlataformaId').val(),
            tipo_tarea: $('#selTipoTarea').val(),
            tipo_solicitud: $('#selTipoSolicitud').val(),

            // Inputs MKT/FISC. 'id_tipo_evento_mkt' eliminado del UI (queda NULL en BD).
            id_categoria_mkt: $('select[name="id_categoria_mkt"]').val(),
            id_tipo_evento_fisc: $('select[name="id_tipo_evento_fisc"]').val(),

            fecha_pretendida_aprobacion: $('#inpFechaPretendida').val() || '',
            fecha_propuesta_realizacion: $('#inpFechaPropuestaReal').val() || '',
            compartir_administrador: $('#chkCompartirAdmin').is(':checked') ? 1 : 0,
            involucra_juegos: $('#chkInvolucraJuegos').is(':checked') ? 1 : 0,
            fecha_inicio_evento: $('#inpFechaInicio').val(),
            fecha_fin_evento: $('#inpFechaFin').val(),
            // fecha_referencia eliminada del UI; el backend recibirá null.
            id_grupo_padre: $('#hidIdGrupoPadre').val() || '',
            activos: activos,
            _token: $('input[name="_token"]').val()
        };

        $('.btn-wizard-next').attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creando...');

        $.post('/notas-unificadas/iniciar', formData, function (data) {
            console.log("Draft Created", data);
            // Setup Data for Step 3
            // We need to inject ID into the Step 3 form (which is frmPaso2)
            // We can do this by creating a hidden input in frmPaso2 or reusing 'setupStep2' function logic?

            // Reuse setupStep2 logic if available or replicate:
            if (data.success && data.ids_notas) {
                // Remove potential old inputs to prevent duplicates
                $('#frmNuevaNota input[name="id_nota"]').remove();
                $('#frmNuevaNota input[name="id_nota_mkt"]').remove();
                $('#frmNuevaNota input[name="id_nota_fisc"]').remove();

                // Track the created grupo for cancel/cleanup
                window._draftGrupoId = data.id_grupo || null;

                // If dual notes (MKT/FISC), store IDs
                if (data.ids_notas.mkt) {
                    console.log('INJECTING id_nota_mkt:', data.ids_notas.mkt);
                    $('<input>').attr({ type: 'hidden', name: 'id_nota_mkt', value: data.ids_notas.mkt }).appendTo('#frmNuevaNota');
                }
                // Store FISC id if present
                if (data.ids_notas.fisc) {
                    console.log('INJECTING id_nota_fisc:', data.ids_notas.fisc);
                    $('<input>').attr({ type: 'hidden', name: 'id_nota_fisc', value: data.ids_notas.fisc }).appendTo('#frmNuevaNota');
                }

                $('#lblNotaIniciada').text(data.nro_nota + '-' + data.anio);
                $('#lblTituloNota').text(data.titulo);
            }

            callback();

        }).fail(function (err) {
            console.error(err);
            if (err.status === 422) {
                notificacion('warning', err.responseJSON.msg); // Show duplicate warning
            } else {
                notificacion('error', 'Error al crear la nota.');
            }
            $('.btn-wizard-next').attr('disabled', false).text("Siguiente (Crear Borrador)");
            // Volver al step anterior para que no quede todo oculto
            goToStep(2);
        });
    }

    // --- UI HELPERS ---

    window.notificacion = notificacion;
    function notificacion(tipo, mensaje) {
        // Simple Custom Notification
        // Check if container exists
        let container = $('#toast-container');
        if (container.length === 0) {
            $('body').append('<div id="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
            container = $('#toast-container');
        }

        let color = '#333';
        let icon = 'fa-info-circle';
        if (tipo === 'success') { color = '#5cb85c'; icon = 'fa-check-circle'; }
        if (tipo === 'error') { color = '#d9534f'; icon = 'fa-exclamation-triangle'; }
        if (tipo === 'warning') { color = '#f0ad4e'; icon = 'fa-exclamation-circle'; }

        let toast = $(`
            <div class="alert" style="background-color: ${color}; color: white; display: none; min-width: 250px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <i class="fa ${icon}"></i> ${mensaje}
            </div>
        `);

        container.append(toast);
        toast.fadeIn();

        setTimeout(function () {
            toast.fadeOut(function () { $(this).remove(); });
        }, 4000);
    }

    // Botón "quitar archivo" para inputs file
    $(document).on('change', 'input[type="file"]', function () {
        var $input = $(this);
        // No aplicar dentro del editor de anotaciones
        if ($input.attr('id') === 'inputNuevaVersion') return;
        // Quitar botón previo si existe
        $input.next('.btn-quitar-archivo').remove();
        if ($input.val()) {
            var $btn = $('<a href="javascript:void(0)" class="btn-quitar-archivo" style="display:inline-block; margin-top:4px; font-size:12.5px; color:#d9534f; cursor:pointer;"><i class="fa fa-times"></i> quitar</a>');
            $btn.on('click', function () {
                $input.val('');
                $(this).remove();
            });
            $input.after($btn);
        }
    });

    function mostrarCargando(show) {
        // Implementar un overlay global si se desea, por ahora no bloqueante
    }
    // --- ADVANCED LIST LOGIC (Hyper-Modern) ---

    // State
    let gridState = {
        q: '',
        id_casino: [],        // múltiple
        id_plataforma: [],    // múltiple
        casinoKeys: [],       // helper UI: valores crudos del popup ("5", "p_3")
        rama: [],             // múltiple
        estado: [],           // múltiple
        fecha_desde: '',
        fecha_hasta: '',
        ver_todo: '',
        page: 1,
        page_size: 10,
        sort_by: 'id',
        order: 'desc'
    };

    // 1. Debounced Search
    let searchTimeout;
    $('#inpBusqueda').on('input', function () {
        clearTimeout(searchTimeout);
        gridState.q = $(this).val();
        gridState.page = 1;
        searchTimeout = setTimeout(ajaxLoadTable, 300);
    });

    // 2. Filters — now handled by header filter popups (see HEADER FILTER POPUPS section)

    // 3. Sorting (skip if clicking filter icon)
    $(document).on('click', '.sortable', function (e) {
        if ($(e.target).hasClass('th-filter-icon') || $(e.target).closest('.th-filter-icon').length) return;
        let field = $(this).data('sort');
        if (gridState.sort_by === field) {
            gridState.order = (gridState.order === 'desc') ? 'asc' : 'desc';
        } else {
            gridState.sort_by = field;
            gridState.order = 'desc'; // Default new sort
        }
        ajaxLoadTable();
    });

    // ========== HEADER FILTER POPUPS ==========

    // Build filter popup content based on filter type
    function buildFilterPopup(filterType) {
        var html = '<div class="header-filter-popup" data-filter-type="' + filterType + '" style="position:absolute; z-index:9999; background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 30px rgba(0,0,0,0.15); padding:14px; min-width:220px; font-size:12px;">';
        html += '<div style="font-weight:700; color:#334155; margin-bottom:10px; font-size:13px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;">';

        if (filterType === 'casino') {
            html += '<i class="fa fa-building"></i> Casino / Plataforma <span style="font-weight:400; color:#94a3b8; font-size:11px;">(podés elegir varios)</span></div>';
            html += '<div style="max-height:200px; overflow-y:auto; margin-bottom:10px;">';
            var keysCasino = gridState.casinoKeys || [];
            $('#selFiltroCasino option').each(function () {
                if ($(this).val() === '') return;
                var checked = (keysCasino.indexOf($(this).val()) >= 0) ? 'checked' : '';
                html += '<label style="display:block; padding:4px 0; cursor:pointer; font-weight:400;"><input type="checkbox" name="fp_casino" value="' + $(this).val() + '" ' + checked + '> ' + $(this).text() + '</label>';
            });
            html += '</div>';
        } else if (filterType === 'rama') {
            html += '<i class="fa fa-code-fork"></i> Rama <span style="font-weight:400; color:#94a3b8; font-size:11px;">(podés elegir varias)</span></div>';
            var ramasSel = gridState.rama || [];
            html += '<label style="display:block; padding:5px 0; cursor:pointer; font-weight:400;"><input type="checkbox" name="fp_rama" value="MKT" ' + (ramasSel.indexOf('MKT') >= 0 ? 'checked' : '') + '> <span class="label label-primary">MKT</span> Marketing</label>';
            html += '<label style="display:block; padding:5px 0; cursor:pointer; font-weight:400;"><input type="checkbox" name="fp_rama" value="FISC" ' + (ramasSel.indexOf('FISC') >= 0 ? 'checked' : '') + '> <span class="label label-success">FISC</span> Fiscalización</label>';
        } else if (filterType === 'estado') {
            html += '<i class="fa fa-flag"></i> Estado <span style="font-weight:400; color:#94a3b8; font-size:11px;">(podés elegir varios)</span></div>';
            html += '<div style="max-height:250px; overflow-y:auto; margin-bottom:10px;">';
            var estadosSel = gridState.estado || [];
            $('#selFiltroEstado option').each(function () {
                if ($(this).val() === '') return;
                var checked = (estadosSel.indexOf($(this).val()) >= 0) ? 'checked' : '';
                html += '<label style="display:block; padding:4px 0; cursor:pointer; font-weight:400;"><input type="checkbox" name="fp_estado" value="' + $(this).val() + '" ' + checked + '> <span class="label" style="' + getEstadoStyle($(this).val()) + '">' + $(this).text() + '</span></label>';
            });
            html += '</div>';
        } else if (filterType === 'fecha') {
            html += '<i class="fa fa-calendar"></i> Fecha Subida</div>';
            html += '<div style="margin-bottom:8px;"><label style="font-weight:600; font-size:11px; color:#64748b;">Desde</label><input type="date" class="form-control input-sm fp-fecha-desde" value="' + (gridState.fecha_desde || '') + '"></div>';
            html += '<div style="margin-bottom:8px;"><label style="font-weight:600; font-size:11px; color:#64748b;">Hasta</label><input type="date" class="form-control input-sm fp-fecha-hasta" value="' + (gridState.fecha_hasta || '') + '"></div>';
        }

        html += '<div style="text-align:right; border-top:1px solid #f1f5f9; padding-top:10px; margin-top:5px;">';
        html += '<button class="btn btn-xs btn-default fp-clear" style="margin-right:5px; border-radius:12px;">Limpiar</button>';
        html += '<button class="btn btn-xs fp-apply" style="border-radius:12px; background:linear-gradient(135deg, #667eea, #764ba2); color:#fff; border:none; padding:3px 14px;">Aplicar</button>';
        html += '</div></div>';
        return html;
    }

    // Show/hide popup on filter icon or non-sortable filterable header click
    function openFilterPopup(th) {
        var filterType = th.data('filter');
        if (!filterType) return;

        // Close any open popup
        $('.header-filter-popup').remove();

        var popup = $(buildFilterPopup(filterType));

        // Position below the header
        $('body').append(popup);
        var offset = th.offset();
        popup.css({
            top: offset.top + th.outerHeight() + 2,
            left: Math.min(offset.left, $(window).width() - popup.outerWidth() - 20)
        });

        th.find('.th-filter-icon').css('color', '#764ba2');
    }

    // Click filter icon on sortable+filterable headers
    $(document).on('click', '.th-filter-icon', function (e) {
        e.stopPropagation();
        openFilterPopup($(this).closest('.th-filterable'));
    });

    // Click anywhere on non-sortable filterable headers (Ramas, Estado)
    $(document).on('click', '.th-filterable:not(.sortable)', function (e) {
        e.stopPropagation();
        openFilterPopup($(this));
    });

    // Close popup on outside click
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.header-filter-popup, .th-filterable').length) {
            $('.header-filter-popup').remove();
        }
    });

    // Apply filter from popup
    $(document).on('click', '.fp-apply', function () {
        var popup = $(this).closest('.header-filter-popup');
        var type = popup.data('filter-type');

        if (type === 'casino') {
            var keys = popup.find('input[name="fp_casino"]:checked').map(function () { return $(this).val(); }).get();
            aplicarCasinoKeys(keys);
        } else if (type === 'rama') {
            gridState.rama = popup.find('input[name="fp_rama"]:checked').map(function () { return $(this).val(); }).get();
        } else if (type === 'estado') {
            gridState.estado = popup.find('input[name="fp_estado"]:checked').map(function () { return $(this).val(); }).get();
        } else if (type === 'fecha') {
            gridState.fecha_desde = popup.find('.fp-fecha-desde').val() || '';
            gridState.fecha_hasta = popup.find('.fp-fecha-hasta').val() || '';
            $('#inpFechaDesde').val(gridState.fecha_desde);
            $('#inpFechaHasta').val(gridState.fecha_hasta);
        }

        gridState.page = 1;
        ajaxLoadTable();
        updateActiveFilters();
        popup.remove();
    });

    // Clear single filter
    $(document).on('click', '.fp-clear', function () {
        var popup = $(this).closest('.header-filter-popup');
        var type = popup.data('filter-type');

        if (type === 'casino') { popup.find('input[name="fp_casino"]').prop('checked', false); }
        else if (type === 'rama') { popup.find('input[name="fp_rama"]').prop('checked', false); }
        else if (type === 'estado') { popup.find('input[name="fp_estado"]').prop('checked', false); }
        else if (type === 'fecha') { popup.find('.fp-fecha-desde, .fp-fecha-hasta').val(''); }
    });

    // Reparte las claves crudas del popup de casino ("5", "p_3") en id_casino[] / id_plataforma[]
    function aplicarCasinoKeys(keys) {
        keys = keys || [];
        gridState.casinoKeys = keys;
        gridState.id_casino = keys.filter(function (k) { return k.toString().indexOf('p_') !== 0; });
        gridState.id_plataforma = keys.filter(function (k) { return k.toString().indexOf('p_') === 0; })
            .map(function (k) { return k.replace('p_', ''); });
    }

    // Texto de una clave de casino/plataforma según el option correspondiente
    function casinoKeyText(key) {
        var t = '';
        $('#selFiltroCasino option').each(function () {
            if ($(this).val() === key) { t = $(this).text(); return false; }
        });
        return t || key;
    }

    // Show active filter tags bar
    function updateActiveFilters() {
        var tags = '';
        (gridState.casinoKeys || []).forEach(function (key) {
            tags += '<span class="active-filter-tag" data-clear="casino" data-val="' + key + '" style="display:inline-block; background:#ede9fe; color:#6d28d9; padding:3px 10px; border-radius:12px; font-size:11px; margin-right:5px; cursor:pointer;">' + casinoKeyText(key) + ' <i class="fa fa-times" style="margin-left:4px;"></i></span>';
        });
        (gridState.rama || []).forEach(function (r) {
            tags += '<span class="active-filter-tag" data-clear="rama" data-val="' + r + '" style="display:inline-block; background:#dbeafe; color:#1e40af; padding:3px 10px; border-radius:12px; font-size:11px; margin-right:5px; cursor:pointer;">' + (r === 'MKT' ? 'Marketing' : 'Fiscalización') + ' <i class="fa fa-times" style="margin-left:4px;"></i></span>';
        });
        (gridState.estado || []).forEach(function (est) {
            tags += '<span class="active-filter-tag" data-clear="estado" data-val="' + est + '" style="display:inline-block; background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:12px; font-size:11px; margin-right:5px; cursor:pointer;">' + est + ' <i class="fa fa-times" style="margin-left:4px;"></i></span>';
        });
        if (gridState.fecha_desde || gridState.fecha_hasta) {
            var fechaLabel = (gridState.fecha_desde || '...') + ' → ' + (gridState.fecha_hasta || '...');
            tags += '<span class="active-filter-tag" data-clear="fecha" style="display:inline-block; background:#d1fae5; color:#065f46; padding:3px 10px; border-radius:12px; font-size:11px; margin-right:5px; cursor:pointer;">' + fechaLabel + ' <i class="fa fa-times" style="margin-left:4px;"></i></span>';
        }

        if (tags) {
            $('#activeFilterTags').html(tags);
            $('#activeFiltersBar').slideDown(150);
        } else {
            $('#activeFiltersBar').slideUp(150);
        }

        // Update header filter icons color
        $('.th-filter-icon').css('color', '#cbd5e1');
        if ((gridState.casinoKeys || []).length) $('[data-filter="casino"] .th-filter-icon').css('color', '#764ba2');
        if ((gridState.rama || []).length) $('[data-filter="rama"] .th-filter-icon').css('color', '#764ba2');
        if ((gridState.estado || []).length) $('[data-filter="estado"] .th-filter-icon').css('color', '#764ba2');
        if (gridState.fecha_desde || gridState.fecha_hasta) $('[data-filter="fecha"] .th-filter-icon').css('color', '#764ba2');
    }

    // Click active filter tag to remove it (quita solo ese valor)
    $(document).on('click', '.active-filter-tag', function () {
        var clear = $(this).data('clear');
        var val = ($(this).data('val') !== undefined) ? $(this).data('val').toString() : '';
        if (clear === 'casino') {
            aplicarCasinoKeys((gridState.casinoKeys || []).filter(function (k) { return k !== val; }));
        } else if (clear === 'rama') {
            gridState.rama = (gridState.rama || []).filter(function (r) { return r !== val; });
        } else if (clear === 'estado') {
            gridState.estado = (gridState.estado || []).filter(function (e) { return e !== val; });
        } else if (clear === 'fecha') {
            $('#inpFechaDesde').val(''); $('#inpFechaHasta').val('');
            gridState.fecha_desde = ''; gridState.fecha_hasta = '';
        }
        gridState.page = 1;
        ajaxLoadTable();
        updateActiveFilters();
    });

    // 4. Pagination — callback para paginacion.js
    function clickIndice(e, page, size) {
        gridState.page = page;
        if (size) gridState.page_size = parseInt(size);
        ajaxLoadTable();
    }

    // Paginación inicial (primer render sin AJAX)
    if (typeof TOTAL_GRUPOS_INICIAL !== 'undefined' && $.fn.generarTitulo) {
        $('#herramientasPaginacion').generarTitulo(1, gridState.page_size, TOTAL_GRUPOS_INICIAL, clickIndice);
        $('#herramientasPaginacion2').generarIndices(1, gridState.page_size, TOTAL_GRUPOS_INICIAL, clickIndice);
    }

    // Expose for external use (avoid location.reload)
    window.refreshTable = function () { ajaxLoadTable(); };

    // Highlight de texto buscado solo en columnas: Nro Nota (4), Título (6), Nro Aprob (9)
    function highlightSearch(term) {
        var escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        var regex = new RegExp('(' + escaped + ')', 'gi');
        var reTest = new RegExp(escaped, 'i'); // no-global para .test() (evita el bug de lastIndex)
        var marca = '<mark style="background:#fde68a; padding:0 2px; border-radius:2px;">$1</mark>';
        var cols = [3, 5, 8]; // índices 0-based: col 4, col 6, col 9
        $('#divTablaNotas tbody tr').each(function () {
            var $tds = $(this).children('td');
            cols.forEach(function (colIdx) {
                var $td = $tds.eq(colIdx);
                if (!$td.length) return;
                $td.find('.label, b, strong').addBack().contents().filter(function () {
                    return this.nodeType === 3 && reTest.test(this.nodeValue);
                }).each(function () {
                    var $span = $('<span>').html(this.nodeValue.replace(regex, marca));
                    $(this).replaceWith($span);
                });
            });
        });
        // Resaltar también el "borrador" (anotación) de cada nota hija
        $('#divTablaNotas .texto-borrador').contents().filter(function () {
            return this.nodeType === 3 && reTest.test(this.nodeValue);
        }).each(function () {
            var $span = $('<span>').html(this.nodeValue.replace(regex, marca));
            $(this).replaceWith($span);
        });
    }

    // Si el match cae en una nota hija (p.ej. el borrador), expandir su nota padre
    // para que el campo resaltado quede a la vista.
    function expandirCoincidenciasEnHijas() {
        var grupos = {};
        $('#divTablaNotas tr.nota-hija').each(function () {
            if ($(this).find('mark').length) {
                grupos[$(this).data('parent-grupo')] = true;
            }
        });
        Object.keys(grupos).forEach(function (grupoId) {
            $('tr.grupo-row[data-grupo-id="' + grupoId + '"]').addClass('expanded');
            $('tr.nota-hija[data-parent-grupo="' + grupoId + '"]').show();
        });
    }

    // Core Load Function
    function ajaxLoadTable() {
        $('#divTablaNotas').css('opacity', '0.5');

        $.get('/notas-unificadas', gridState, function (res) {
            $('#divTablaNotas').html(res.html).css('opacity', '1');

            // Highlight búsqueda en la tabla + expandir notas padre con match en una hija (borrador)
            if (gridState.q && $.trim(gridState.q).length >= 2) {
                highlightSearch(gridState.q);
                expandirCoincidenciasEnHijas();
            }

            // Paginación estilo sistema
            $('#herramientasPaginacion').generarTitulo(gridState.page, gridState.page_size, res.total, clickIndice);
            $('#herramientasPaginacion2').generarIndices(gridState.page, gridState.page_size, res.total, clickIndice);

            // Re-render sort icons
            $('.sortable').find('i.fa-sort, i.fa-sort-asc, i.fa-sort-desc').removeClass('fa-sort-asc fa-sort-desc').addClass('fa-sort');
            let activeHeader = $(`.sortable[data-sort="${gridState.sort_by}"]`);
            let icon = (gridState.order === 'asc') ? 'fa-sort-asc' : 'fa-sort-desc';
            activeHeader.find('i.fa-sort').removeClass('fa-sort').addClass(icon);

            // Re-color filter icons for active filters
            updateActiveFilters();
        }).fail(function () {
            notificacion('error', 'Error al cargar listado.');
            $('#divTablaNotas').css('opacity', '1');
        });
    }

    // --- EXPORTAR PDF / EXCEL ---
    function buildExportUrl(formato) {
        var params = $.extend({}, gridState, { formato: formato, export: '1' });
        delete params.page;
        delete params.page_size;
        return '/notas-unificadas/exportar?' + $.param(params);
    }
    $('#btnExportPdf').on('click', function (e) {
        e.preventDefault();
        window.open(buildExportUrl('pdf'), '_blank');
    });
    $('#btnExportExcel').on('click', function (e) {
        e.preventDefault();
        window.open(buildExportUrl('excel'), '_blank');
    });

    // --- DRAWER LOGIC - DISABLED ---
    $(document).on('click', '.btn-ver-nota', function () {
        // Drawer disabled for performance
        // let id = $(this).data('id');
        // openDrawer(id);
    });

    $('#btnCloseDrawer, #drawer-backdrop').click(function () {
        closeDrawer();
    });

    function openDrawer(id) {
        // DISABLED - Performance optimization
        // $('#drawer-backdrop').fadeIn();
        // $('#drawer-right').css('right', '0');
        // $('#drawer-content').html('<h4 class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando detalles...</h4>');
        // $.get('/notas-unificadas/' + id, function (res) {
        //     $('#drawer-content').html(res);
        // }).fail(function () {
        //     $('#drawer-content').html('<p class="text-danger">Error al cargar detalle.</p>');
        // });
    }

    function closeDrawer() {
        $('#drawer-right').css('right', '-500px');
        $('#drawer-backdrop').fadeOut();
    }
    // --- INLINE EDITING ---
    // Recalcular badges de estado en la fila padre
    function actualizarEstadosPadre(grupoId) {
        var estados = [];
        $('tr.nota-hija[data-parent-grupo="' + grupoId + '"]').each(function () {
            var badge = $(this).find('.estado-badge');
            var txt = badge.length ? badge.text().trim() : '';
            if (!txt) {
                // Si no tiene badge, buscar label-warning (PENDIENTE)
                var lbl = $(this).find('.label-warning');
                txt = lbl.length ? lbl.text().trim() : '';
            }
            if (txt && estados.indexOf(txt) === -1) {
                estados.push(txt);
            }
        });
        var td = $('td.grupo-estados[data-grupo-id="' + grupoId + '"]');
        td.empty();
        for (var i = 0; i < estados.length; i++) {
            td.append('<span class="label" style="' + getEstadoStyle(estados[i]) + ' margin-right:2px;">' + estados[i] + '</span>');
        }
    }

    $(document).on('click', '.estado-badge', function (e) {
        e.stopPropagation();
        let span = $(this);
        let current = span.text().trim();
        let id = span.data('id');
        let notaRow = span.closest('tr.nota-hija');
        let grupoId = notaRow.length ? notaRow.data('parent-grupo') : null;

        if (span.hasClass('editing')) return;

        // Determinar opciones permitidas según rol
        var allEstados = (window.OPCIONES_ESTADO || []).map(function(e) { return e.descripcion; });
        var trans = window.TRANSICIONES_ESTADO || {};
        var permitidos = [];

        if (window.NIVEL_ESTADO === 'admin') {
            permitidos = allEstados;
        } else {
            var nivel = window.NIVEL_ESTADO || 'regular';
            var mapa = trans[nivel] || {};
            permitidos = mapa[current] || [];
        }

        // "APROBADO - NOTA/DISPOSICION" solo Superusuario o Despacho.
        if (!window.PUEDE_APROBAR_NOTA) {
            permitidos = permitidos.filter(function (e) { return e !== window.ESTADO_APROBADO_NOTA; });
        }

        span.addClass('editing');

        let wrapper = $('<span class="estado-edit-wrap" style="white-space:nowrap;"></span>');
        let select = $('<select class="form-control input-sm" style="width:auto; display:inline-block; padding: 2px; font-size: 11px;"></select>');
        // Mostrar todos los estados: los permitidos habilitados, los demás deshabilitados y grises
        allEstados.forEach(function(op) {
            var enabled = (permitidos.indexOf(op) !== -1) || (op === current);
            select.append('<option value="' + op + '"' + (!enabled ? ' disabled style="color:#ccc;"' : '') + '>' + op + '</option>');
        });
        select.val(current);
        let btnConfirm = $('<button class="btn btn-success btn-xs" style="margin-left:3px;" title="Confirmar"><i class="fa fa-check"></i></button>');
        let btnCancel = $('<button class="btn btn-danger btn-xs" style="margin-left:2px;" title="Cancelar"><i class="fa fa-times"></i></button>');
        wrapper.append(select).append(btnConfirm).append(btnCancel);
        span.replaceWith(wrapper);
        select.focus();

        function restoreBadge(text) {
            let newSpan = $('<span class="label estado-badge" data-id="' + id + '" data-toggle="popover" data-trigger="hover" style="' + getEstadoStyle(text) + '">' + text + '</span>');
            wrapper.replaceWith(newSpan);
        }

        btnConfirm.on('click', function (ev) {
            ev.stopPropagation();
            let newVal = select.val();
            restoreBadge(newVal);
            if (newVal !== current) {
                $.post('/notas-unificadas/quick-update', {
                    _token: $('input[name="_token"]').val(),
                    id: id,
                    field: 'estado',
                    value: newVal
                }, function () {
                    notificacion('success', 'Estado actualizado.');
                    if (grupoId) actualizarEstadosPadre(grupoId);
                }).fail(function () {
                    notificacion('error', 'Error al actualizar.');
                });
            }
        });

        btnCancel.on('click', function (ev) {
            ev.stopPropagation();
            restoreBadge(current);
        });

        select.on('keydown', function (ev) {
            if (ev.key === 'Enter') { btnConfirm.click(); }
            if (ev.key === 'Escape') { btnCancel.click(); }
        });
    });

    function getBalanceColor(status) {
        return getEstadoClass(status);
    }

    // --- KANBAN & CALENDAR VIEW TOGGLE ---
    $('#btnViewKanban, #btnViewTable, #btnViewCalendar').change(function () {
        let isKanban = $('#btnViewKanban').find('input').is(':checked');
        let isCalendar = $('#btnViewCalendar').find('input').is(':checked');

        $('#divTablaNotas').show();
        $('#divCalendarioNotas').hide();
        $('.kanban-board').remove(); // Cleanup previous kanban

        if (isKanban) {
            gridState.view_mode = 'kanban';
            gridState.page_size = 50;
            ajaxLoadTable();

            // Wait for partial to load then Init Sortable
            let checkExist = setInterval(function () {
                if ($('.kanban-column').length) {
                    clearInterval(checkExist);
                    initKanbanSortable();
                }
            }, 100);

        } else if (isCalendar) {
            $('#divTablaNotas').hide();
            $('#divCalendarioNotas').show();
            initCalendar();

        } else {
            // Table
            gridState.view_mode = 'table';
            gridState.page_size = 10;
            ajaxLoadTable();
        }
    });

    // --- BULK ACTIONS LOGIC ---
    let selectedIds = [];

    // 1. Select All
    $(document).on('change', '#checkAll', function () {
        let isChecked = $(this).is(':checked');
        $('.check-item').prop('checked', isChecked);
        updateBulkToolbar();
    });

    // 2. Individual Select
    $(document).on('change', '.check-item', function () {
        updateBulkToolbar();
    });

    function updateBulkToolbar() {
        let count = $('.check-item:checked').length;
        $('#bulkCount').text(count);

        if (count > 0) {
            $('#bulkToolbar').fadeIn();
        } else {
            $('#bulkToolbar').fadeOut();
            $('#checkAll').prop('checked', false);
        }
    }

    // 3. Cancel
    $('#btnBulkCancel').click(function () {
        $('.check-item').prop('checked', false);
        $('#checkAll').prop('checked', false);
        updateBulkToolbar();
    });

    // 4. Bulk Delete Confirm
    $('#btnBulkDelete').click(function () {
        let ids = [];
        $('.check-item:checked').each(function () { ids.push($(this).val()); });

        if (ids.length === 0) return;

        confirmar('¿Está seguro de eliminar las ' + ids.length + ' notas seleccionadas?', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar-masivo',
                type: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    ids: ids
                },
                success: function (res) {
                    notificacion('success', res.mensaje || 'Notas eliminadas');
                    ajaxLoadTable();
                    $('#bulkToolbar').fadeOut();
                    $('#checkAll').prop('checked', false);
                },
                error: function (err) {
                    notificacion('error', 'Error al eliminar notas.');
                }
            });
        }, { titulo: 'Eliminar notas' });
    });

    // 4. Calendar Logic (Rest of code...)
    let calendarInitialized = false;
    function initCalendar() {
        if (calendarInitialized) {
            $('#divCalendarioNotas').fullCalendar('refetchEvents');
            return;
        }

        $('#divCalendarioNotas').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,listMonth'
            },
            navLinks: true,
            editable: false,
            eventLimit: true,
            events: '/calendar-events', // Correct route
            eventClick: function (event) {
                if (event.url && event.url.indexOf('javascript:') === 0) {
                    eval(event.url.substring(11));
                    return false;
                }
            }
        });
        calendarInitialized = true;
    }

    function initKanbanSortable() {
        if (typeof Sortable === 'undefined') return;

        $('.kanban-dropzone').each(function () {
            new Sortable(this, {
                group: 'kanban', // Allow dragging between lists
                animation: 150,
                ghostClass: 'bg-info',
                onEnd: function (evt) {
                    let item = $(evt.item);
                    let id = item.data('id');
                    let newStatus = $(evt.to).data('status');
                    let oldStatus = $(evt.from).data('status');

                    if (newStatus !== oldStatus) {
                        // Optimistic UI Update is handled by Sortable
                        // We just notify and sync
                        notificacion('info', 'Actualizando estado...');

                        $.post('/notas-unificadas/quick-update', {
                            _token: $('input[name="_token"]').val(),
                            id: id,
                            field: 'estado',
                            value: newStatus
                        }, function (res) {
                            notificacion('success', 'Estado cambiado a: ' + newStatus);
                        }).fail(function () {
                            notificacion('error', 'Error al cambiar estado.');
                            gridState.view_mode = 'kanban';
                            ajaxLoadTable(); // Revert on error
                        });
                    }
                }
            });
        });
    }

    // --- CONTEXT MENU ---
    $(document).on('contextmenu', 'tr[data-id], .kanban-card', function (e) {
        e.preventDefault();
        let id = $(this).data('id');

        // Position menu
        $('#custom-context-menu')
            .data('id', id)
            .css({
                display: 'block',
                left: e.clientX,
                top: e.clientY
            });
    });

    $(document).on('click', function () {
        $('#custom-context-menu').hide();
    });

    $('.ctx-action').click(function (e) {
        e.preventDefault();
        let action = $(this).data('action');
        let id = $('#custom-context-menu').data('id');
        $('#custom-context-menu').hide();

        if (action === 'ver') {
            openDrawer(id);
        } else if (action === 'eliminar') {
            confirmar('¿Eliminar esta nota?', function () {
                $.ajax({
                    url: '/notas-unificadas/eliminar/' + id,
                    type: 'DELETE',
                    data: { _token: $('input[name="_token"]').val() },
                    success: function () { ajaxLoadTable(); notificacion('success', 'Eliminado'); }
                });
            }, { titulo: 'Eliminar nota' });
        } else if (action === 'descargar-todo') {
            // For MVP, maybe open all links? Or create a ZIP endpoint later. 
            // Currently just notify
            notificacion('info', 'Función de descarga masiva en desarrollo (requiere ZIP stream).');
        }
    });

    // --- SAVED FILTERS (QUICK VIEWS) ---
    $('.btn-quick-filter').click(function () {
        let filter = $(this).data('filter');
        $('.btn-quick-filter').removeClass('btn-primary').addClass('btn-default');
        // Restore "Limpiar" purple gradient (not btn-primary/btn-default)
        $('.btn-quick-filter[data-filter="reset"]').css({'background':'linear-gradient(135deg, #667eea 0%, #764ba2 100%)','color':'#fff'});

        if (filter !== 'reset') {
            $(this).removeClass('btn-default').addClass('btn-primary');
            // If it's "ver_todo", give it a distinct style
            if (filter === 'ver_todo') {
                $(this).css({'background':'#e74c3c','color':'#fff','border-color':'#c0392b'});
            }
        }

        // Limpiar quick filters primero
        gridState.q = '';
        gridState.quick_filter = '';
        gridState.page = 1;
        $('#inpBusqueda').val('');

        if (filter === 'hoy') {
            gridState.quick_filter = 'hoy';
            gridState.ver_todo = '';
        } else if (filter === 'proximos') {
            gridState.quick_filter = 'proximos';
            gridState.ver_todo = '';
        } else if (filter === 'por_vencer') {
            gridState.quick_filter = 'por_vencer';
            gridState.ver_todo = '';
        } else if (filter === 'ver_todo') {
            // "Ver todo" activa el flag, sin cambiar otros filtros
            gridState.ver_todo = '1';
        } else if (filter === 'reset') {
            // Reset completo: vuelve a filtros por defecto del rol (sin ver_todo)
            gridState.id_casino = [];
            gridState.id_plataforma = [];
            gridState.casinoKeys = [];
            gridState.rama = [];
            gridState.estado = [];
            gridState.fecha_desde = '';
            gridState.fecha_hasta = '';
            gridState.quick_filter = '';
            gridState.ver_todo = '';
            $('#inpFechaDesde').val('');
            $('#inpFechaHasta').val('');
        }

        ajaxLoadTable();
        updateActiveFilters();
    });

    // --- TIMELINE TOOLTIP ---
    // ============================================
    // ALL POPOVERS / TOOLTIPS DISABLED - Performance optimization
    // ============================================
    // $('[data-toggle="popover"]').popover();

    // ============================================
    // POPOVER DISABLED - Performance optimization
    // ============================================
    // Removed hover logic for 'estado-badge' that loads history via AJAX
    // This was causing performance issues and unnecessary memory usage
    /*
    $('body').popover({
        selector: '.estado-badge',
        trigger: 'hover',
        html: true,
        placement: 'bottom',
        content: function () {
            ...
        }
    });
    */


    function updateStepperUI(step) {
        // HYPER-MODERN CIRCULAR STEPPER LOGIC
        // 1. Reset all circles
        $('.stepper-circle').removeClass('active completed').css({
            'background': '#f0f4f8',
            'color': '#cbd5e1',
            'border': 'none',
            'transform': 'translate(-50%, -50%) scale(1)', // RESET WITH TRANSLATE
            'box-shadow': '5px 5px 10px #d1d9e6, -5px -5px 10px #ffffff'
        });

        // 2. Calculate Progress Width (0% to 100%)
        let progress = (step / 4) * 100;
        $('#progressFill').css('width', progress + '%');

        // 3. Update Circles based on current step
        for (let i = 0; i <= 4; i++) {
            let circle = $('#stepIndicator' + i);
            if (i < step) {
                // Completed
                circle.addClass('completed').css({
                    'background': '#e0f2fe',
                    'color': '#3b82f6',
                    'border': '2px solid #3b82f6',
                    'box-shadow': 'inset 2px 2px 5px rgba(0,0,0,0.05)'
                }).html('<i class="fa fa-check"></i>');
            } else if (i === step) {
                // Active
                circle.addClass('active').css({
                    'background': 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                    'color': 'white',
                    'transform': 'translate(-50%, -50%) scale(1.15)', // KEEP TRANSLATE
                    'box-shadow': '0 10px 20px rgba(118, 75, 162, 0.4)',
                    'border': 'none'
                }).html(i === 0 ? '<i class="fa fa-mouse-pointer"></i>' : (i === 3 ? '<i class="fa fa-paperclip"></i>' : (i === 4 ? '<i class="fa fa-flag-checkered"></i>' : (i + 1))));
            } else {
                // Future
                circle.html(i === 0 ? '<i class="fa fa-mouse-pointer"></i>' : (i === 3 ? '<i class="fa fa-paperclip"></i>' : (i === 4 ? '<i class="fa fa-flag-checkered"></i>' : (i + 1))));
            }
        }
    }

    // --- PHASE 4: AUTO-SAVE REMOVED AS REQUESTED ---
    /*
    const STORAGE_KEY = 'draft_nueva_nota';
    ... (Disabled)
    */

    // --- FINALIZAR ---
    window._wizardFinished = false;
    window._draftGrupoId = null;

    window.wizardFinish = function () {
        window._wizardFinished = true;

        // Animación de cierre: la barra de progreso "viaja" hasta la bandera y la bandera festeja, recién ahí se cierra.
        var $fill = $('#progressFill');
        var $flag = $('#stepIndicator4');

        // El relleno arranca lleno hasta el final de la línea (80% del contenedor);
        // 133.33% lo lleva hasta el círculo de la bandera (100% del contenedor).
        $fill.css('width', '100%');
        setTimeout(function () {
            $fill.css('width', '133.33%');
            // La bandera se pone verde y "salta"
            $flag.removeClass('active').css({
                'background': 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                'color': '#fff',
                'border': 'none',
                'transform': 'translate(-50%, -50%) scale(1.3)',
                'box-shadow': '0 12px 26px rgba(16,185,129,0.55)'
            });
        }, 60);
        // pequeño rebote final de la bandera
        setTimeout(function () {
            $flag.css('transform', 'translate(-50%, -50%) scale(1.15)');
        }, 620);

        // Cerrar recién cuando termina la animación
        setTimeout(function () {
            $('#modalNuevaNota').modal('hide');
            notificacion('success', 'Trámite finalizado exitosamente.');
            setTimeout(() => ajaxLoadTable(), 400);
        }, 950);
    };

    // --- CANCELAR: limpiar borrador al cerrar modal sin finalizar ---
    $('#modalNuevaNota').on('hidden.bs.modal', function () {
        if (window._wizardFinished) {
            // Finalizó correctamente, no borrar nada
            window._wizardFinished = false;
            window._draftGrupoId = null;
            resetWizard();
            return;
        }

        var grupoId = window._draftGrupoId;
        if (grupoId) {
            // Hay un borrador creado que no se finalizó → eliminarlo
            $.ajax({
                url: '/notas-unificadas/eliminar-grupo/' + grupoId,
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').val() },
                success: function () {
                    console.log('Borrador grupo ' + grupoId + ' eliminado por cancelación.');
                },
                error: function (err) {
                    console.error('Error al eliminar borrador:', err);
                }
            });
            window._draftGrupoId = null;
        }

        // Siempre resetear el wizard al cerrar
        resetWizard();
    });

    // --- SUMMARY GENERATOR ---
    window.generateSummary = function () {
        // Texto de la opción seleccionada de un <select> (vacío si no hay valor)
        function selText(sel) {
            var $s = $(sel);
            if (!$s.length || !$s.val()) return '';
            return $.trim($s.find('option:selected').text());
        }
        function esc(s) { return (s === undefined || s === null) ? '' : String(s).replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

        var tipoTareaRaw = $('#selTipoTarea').val();
        var tipoTarea = tipoTareaRaw === 'MARKETING' ? 'Marketing / Publicidad'
            : (tipoTareaRaw === 'FISCALIZACION' ? 'Aspectos Técnicos' : (tipoTareaRaw || '—'));

        var tipoSolicitud = selText('#selTipoSolicitud');
        var categoriaMkt = selText('#selCategoriaMKT');
        var eventoFisc = selText('#selTipoEventoFISC');
        var titulo = $.trim($('#inpTitulo').val());
        var anio = $('#inpAnio').val();
        var casino = $.trim($('#selCasino option:selected').text());
        var nroNota = $('#inpNroNota').val();
        var fechaPret = $('#inpFechaPretendida').val();
        var fechaPropReal = $('#inpFechaPropuestaReal').val();
        var fInicio = $('#inpFechaInicio').val();
        var fFin = $('#inpFechaFin').val();
        var compartir = $('#chkCompartirAdmin').is(':checked');
        var involucra = $('#chkInvolucraJuegos').is(':checked');
        var notaPadre = $('#hidIdGrupoPadre').val()
            ? ($.trim($('#inpBuscarNotaPadre').val()) || ('Trámite #' + $('#hidIdGrupoPadre').val()))
            : '';

        // Construcción de filas "etiqueta : valor"
        var rows = '';
        function addRow(icon, label, value) {
            if (value === undefined || value === null || value === '') return;
            rows +=
                '<div style="display:flex; align-items:flex-start; padding:9px 4px; border-bottom:1px solid #f1f5f9;">' +
                    '<div style="flex:0 0 195px; color:#94a3b8; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;"><i class="fa ' + icon + '" style="width:16px; color:#a5b4fc;"></i> ' + label + '</div>' +
                    '<div style="flex:1; color:#334155; font-size:14px; font-weight:600; word-break:break-word;">' + esc(value) + '</div>' +
                '</div>';
        }

        addRow('fa-folder-open', 'Tipo de trámite', tipoTarea);
        addRow('fa-list', 'Tipo de solicitud', tipoSolicitud);
        if (categoriaMkt) addRow('fa-tag', 'Categoría', categoriaMkt);
        if (eventoFisc) addRow('fa-cogs', 'Tipo de evento', eventoFisc);
        addRow('fa-bookmark', 'Nota referencia', (nroNota || 'S/N') + '-' + (anio || '—'));
        addRow('fa-building', 'Casino / Plataforma', casino);
        addRow('fa-pencil', 'Título', titulo || 'Sin título');
        addRow('fa-calendar', 'Fecha pretendida aprob.', fechaPret);
        addRow('fa-calendar', 'Fecha propuesta realiz.', fechaPropReal);
        if (fInicio || fFin) addRow('fa-calendar', 'Fechas del evento', 'Del ' + (fInicio || '—') + ' al ' + (fFin || '—'));
        addRow('fa-share-alt', 'Compartir c/ administrador', compartir ? 'Sí' : 'No');
        addRow('fa-gamepad', 'Involucra juegos', involucra ? 'Sí' : 'No');
        if (notaPadre) addRow('fa-link', 'Se acopla a', notaPadre);

        var html =
            '<div class="panel panel-default" style="border:none; box-shadow:0 4px 10px rgba(0,0,0,0.06); border-radius:12px;">' +
                '<div class="panel-body" style="padding:8px 18px;">' + rows + '</div>' +
            '</div>';

        // Activos asociados (máquinas / juegos / islas)
        if (typeof activos !== 'undefined' && activos && activos.length) {
            var actHtml = '';
            activos.forEach(function (a) {
                actHtml +=
                    '<span style="display:inline-block; background:#f3e8ff; color:#6d28d9; border:1px solid #e9d5ff; padding:3px 10px; border-radius:12px; font-size:12px; margin:0 6px 6px 0;">' +
                        '<b>' + esc(a.tipo) + '</b> ' + esc(a.texto || a.id) +
                    '</span>';
            });
            html +=
                '<div class="panel panel-default" style="border:none; box-shadow:0 4px 10px rgba(0,0,0,0.06); border-radius:12px; margin-top:14px;">' +
                    '<div class="panel-body" style="padding:14px 18px;">' +
                        '<h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px; margin:0 0 10px;"><i class="fa fa-desktop"></i> Máquinas / Juegos asociados (' + activos.length + ')</h5>' +
                        actHtml +
                    '</div>' +
                '</div>';
        }

        // Adjuntos seleccionados
        var adjuntosMap = [
            ['adjuntoSolicitud', 'Solicitud Concesionario (MKT)'],
            ['adjuntoDisenio', 'Diseño (MKT)'],
            ['adjuntoBases', 'Bases y Condiciones (MKT)'],
            ['adjuntoInformeMkt', 'Informe Técnico (MKT)'],
            ['adjuntoAnexosMkt', 'Anexos (MKT)'],
            ['adjuntoSolicitudFisc', 'Solicitud Concesionario (FISC)'],
            ['adjuntoVarios', 'Archivos Varios (FISC)'],
            ['adjuntoInformeFisc', 'Informe Técnico (FISC)'],
            ['adjuntoAnexosFisc', 'Anexos (FISC)']
        ];
        var adjHtml = '';
        var totalArchivos = 0;
        adjuntosMap.forEach(function (m) {
            var el = document.getElementById(m[0]);
            if (!el || !el.files || el.files.length === 0) return;
            // Listar TODOS los archivos seleccionados de cada campo (no solo el primero)
            for (var i = 0; i < el.files.length; i++) {
                totalArchivos++;
                adjHtml +=
                    '<div style="display:flex; align-items:center; padding:6px 4px; border-bottom:1px solid #f1f5f9; font-size:13px;">' +
                        '<i class="fa fa-paperclip" style="color:#10b981; width:18px;"></i>' +
                        '<span style="flex:0 0 230px; color:#475569; font-weight:600;">' + m[1] + '</span>' +
                        '<span style="flex:1; color:#64748b; word-break:break-all;">' + esc(el.files[i].name) + '</span>' +
                    '</div>';
            }
        });
        if (adjHtml) {
            html +=
                '<div class="panel panel-default" style="border:none; box-shadow:0 4px 10px rgba(0,0,0,0.06); border-radius:12px; margin-top:14px;">' +
                    '<div class="panel-body" style="padding:14px 18px;">' +
                        '<h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px; margin:0 0 8px;"><i class="fa fa-paperclip"></i> Archivos a subir (' + totalArchivos + ')</h5>' +
                        adjHtml +
                    '</div>' +
                '</div>';
        } else {
            html +=
                '<div class="alert alert-warning" style="margin-top:14px; padding:8px 14px; font-size:13px;">' +
                    '<i class="fa fa-exclamation-circle"></i> No se cargó ningún adjunto. Podés finalizar igual y agregarlos después.' +
                '</div>';
        }

        $('#step4Content').html(
            '<h4 class="text-center" style="margin-bottom:20px; font-weight:700; color:#475569;">Resumen de la Solicitud</h4>' +
            html +
            '<div class="alert alert-success text-center" style="margin-top:16px; margin-bottom:20px; padding:8px;"><i class="fa fa-info-circle"></i> Verifique que todos los datos sean correctos antes de confirmar.</div>'
        );
    }

    // =====================================================
    // DELETE HANDLERS
    // =====================================================

    // Single Delete Button (Action Column)
    $(document).on('click', '.btn-borrar-nota', function (e) {
        e.preventDefault();
        let id = $(this).data('id');

        confirmar('¿Está seguro de eliminar esta nota? Esta acción no se puede deshacer.', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                success: function (res) {
                    if (res.success) {
                        notificacion('success', 'Nota eliminada correctamente');
                        ajaxLoadTable();
                    } else {
                        notificacion('error', res.msg || 'Error al eliminar');
                    }
                },
                error: function (err) {
                    notificacion('error', 'Error al eliminar la nota');
                    console.error(err);
                }
            });
        }, { titulo: 'Eliminar nota' });
    });

    // Context Menu Delete Action
    $(document).on('click', '.ctx-action[data-action="eliminar"]', function (e) {
        e.preventDefault();
        let id = $(this).closest('#custom-context-menu').data('target-id');

        $('#custom-context-menu').hide();

        if (!id) return;

        confirmar('¿Está seguro de eliminar esta nota?', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                success: function (res) {
                    if (res.success) {
                        notificacion('success', 'Nota eliminada');
                        ajaxLoadTable();
                    } else {
                        notificacion('error', res.msg || 'Error al eliminar');
                    }
                },
                error: function (err) {
                    notificacion('error', 'Error al eliminar');
                }
            });
        }, { titulo: 'Eliminar nota' });
    });

    // Bulk Delete Button
    $(document).on('click', '#btnBulkDelete', function () {
        let ids = [];
        $('.check-item:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            notificacion('warning', 'Seleccione al menos una nota');
            return;
        }

        confirmar('¿Está seguro de eliminar ' + ids.length + ' nota(s)? Esta acción no se puede deshacer.', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar-masivo',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                data: { ids: ids },
                success: function (res) {
                    if (res.success) {
                    notificacion('success', res.deleted + ' nota(s) eliminada(s)');
                    ids.forEach(function (id) {
                        $('tr[data-id="' + id + '"]').fadeOut(300, function () { $(this).remove(); });
                    });
                    $('#bulkToolbar').hide();
                    $('#checkAll').prop('checked', false);
                } else {
                    notificacion('error', res.msg || 'Error al eliminar');
                }
            },
            error: function (err) {
                notificacion('error', 'Error al eliminar notas');
            }
        });
        }, { titulo: 'Eliminar notas' });
    });

    // Check All toggle
    $(document).on('change', '#checkAll', function () {
        $('.check-item').prop('checked', $(this).is(':checked'));
        updateBulkToolbar();
    });

    $(document).on('change', '.check-item', function () {
        updateBulkToolbar();
    });

    function updateBulkToolbar() {
        let count = $('.check-item:checked').length;
        $('#bulkCount').text(count);
        if (count > 0) {
            $('#bulkToolbar').css('display', 'flex');
        } else {
            $('#bulkToolbar').hide();
        }
    }

    // Close Bulk Toolbar
    $(document).on('click', '#btnBulkCancel', function () {
        $('.check-item').prop('checked', false);
        $('#checkAll').prop('checked', false);
        $('#bulkToolbar').hide();
    });

    // =====================================================
    // COLLAPSIBLE GRUPO HANDLERS
    // =====================================================

    // Toggle expand/collapse for grupo rows
    $(document).on('click', '.grupo-row', function (e) {
        // Ignore if clicking checkbox or buttons
        if ($(e.target).is('input, button, i, a') || $(e.target).closest('button, a').length) {
            return;
        }

        let grupoId = $(this).data('grupo-id');
        let childRows = $('tr.nota-hija[data-parent-grupo="' + grupoId + '"]');

        if ($(this).hasClass('expanded')) {
            // Collapse
            $(this).removeClass('expanded');
            childRows.slideUp(150);
        } else {
            // Expand
            $(this).addClass('expanded');
            childRows.slideDown(150);
        }
    });

    // Toggle on chevron click
    $(document).on('click', '.toggle-grupo', function (e) {
        e.stopPropagation();
        $(this).closest('.grupo-row').trigger('click');
    });

    // Ver Grupo button - expand and scroll
    $(document).on('click', '.btn-ver-grupo', function (e) {
        e.preventDefault();
        let grupoId = $(this).data('id');
        let grupoRow = $('tr.grupo-row[data-grupo-id="' + grupoId + '"]');

        // Expand if not already
        if (!grupoRow.hasClass('expanded')) {
            grupoRow.trigger('click');
        }

        // TODO: Could open a detail modal here
        notificacion('info', 'Grupo #' + grupoId + ' expandido');
    });

    // Borrar Grupo - elimina el grupo y todas sus notas
    $(document).on('click', '.btn-borrar-grupo', function (e) {
        e.preventDefault();
        let grupoId = $(this).data('id');

        confirmar('¿Está seguro de eliminar este grupo y <strong>TODAS</strong> sus notas asociadas? Esta acción no se puede deshacer.', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar-grupo/' + grupoId,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
                success: function (res) {
                    if (res.success) {
                        notificacion('success', 'Grupo eliminado correctamente');
                        ajaxLoadTable();
                    } else {
                        notificacion('error', res.msg || 'Error al eliminar');
                    }
                },
                error: function (err) {
                    notificacion('error', 'Error al eliminar el grupo');
                }
            });
        }, { titulo: 'Eliminar grupo' });
    });

    // =====================================================
    // BORRADOR INLINE (anotaciones rápidas por nota hija)
    // =====================================================
    // Doble click: el span con el texto se reemplaza por un input + Save/Cancel.
    // Save guarda contra POST /notas-unificadas/borrador/{id} y restaura el span con el nuevo valor.
    // Cancel restaura el valor previo (guardado en data-borrador).

    function _restaurarSpanBorrador(notaId, valor) {
        // Mismo estilo que el render server-side: word-wrap + sin nowrap para que el texto baje
        // a renglones siguientes en vez de expandir la celda y empujar las columnas vecinas.
        var styleWrap = {
            cursor: 'pointer',
            'font-size': '11px',
            display: 'block',
            'word-wrap': 'break-word',
            'overflow-wrap': 'anywhere',
            'word-break': 'break-word'
        };
        var html;
        if (valor && valor.length > 0) {
            html = $('<span class="texto-borrador">')
                .attr('data-nota-id', notaId)
                .attr('data-borrador', valor)
                .attr('title', valor)
                .css(styleWrap)
                .text(valor);
        } else {
            html = $('<span class="texto-borrador">')
                .attr('data-nota-id', notaId)
                .attr('data-borrador', '')
                .attr('title', 'Doble click para agregar')
                .css(styleWrap)
                .html('<small class="text-muted" style="font-style:italic; font-size:10px;">— doble click —</small>');
        }
        return html;
    }

    $(document).on('dblclick', '.texto-borrador', function (e) {
        e.stopPropagation();  // Evita que la grupo-row capture el click (toggling expand)
        var $span = $(this);
        var notaId = $span.data('nota-id');
        var valor = $span.attr('data-borrador') || '';

        var $wrap = $(
            '<div class="edit-borrador-wrap" style="display:flex; gap:2px; align-items:center;">' +
                '<input type="text" class="form-control input-borrador" maxlength="500" ' +
                       'style="font-size:11px; padding:1px 4px; height:22px; flex:1; min-width:0;">' +
                '<button class="btn btn-success btn-xs btn-save-borrador" title="Guardar (Enter)" ' +
                        'style="padding:1px 5px; font-size:11px; line-height:1.2;"><i class="fa fa-check"></i></button>' +
                '<button class="btn btn-default btn-xs btn-cancel-borrador" title="Cancelar (Esc)" ' +
                        'style="padding:1px 5px; font-size:11px; line-height:1.2;"><i class="fa fa-times"></i></button>' +
            '</div>'
        );
        $wrap.attr('data-nota-id', notaId).attr('data-original', valor);
        $wrap.find('.input-borrador').val(valor);
        $span.replaceWith($wrap);
        $wrap.find('.input-borrador').focus().select();
    });

    $(document).on('click', '.btn-save-borrador', function (e) {
        e.stopPropagation();
        var $btn = $(this);
        var $wrap = $btn.closest('.edit-borrador-wrap');
        var notaId = $wrap.data('nota-id');
        var valor = $wrap.find('.input-borrador').val();

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/notas-unificadas/borrador/' + notaId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').first().val(), borrador: valor },
            success: function (res) {
                if (res.success) {
                    var nuevo = res.borrador || '';
                    $wrap.replaceWith(_restaurarSpanBorrador(notaId, nuevo));
                    notificacion('success', 'Borrador guardado');
                } else {
                    notificacion('error', res.msg || 'Error al guardar');
                    $btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
                }
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) || 'Error al guardar';
                notificacion('error', msg);
                $btn.prop('disabled', false).html('<i class="fa fa-check"></i>');
            }
        });
    });

    $(document).on('click', '.btn-cancel-borrador', function (e) {
        e.stopPropagation();
        var $wrap = $(this).closest('.edit-borrador-wrap');
        var notaId = $wrap.data('nota-id');
        var original = $wrap.attr('data-original') || '';
        $wrap.replaceWith(_restaurarSpanBorrador(notaId, original));
    });

    // Enter -> guardar; Escape -> cancelar
    $(document).on('keydown', '.input-borrador', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('.edit-borrador-wrap').find('.btn-save-borrador').click();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            $(this).closest('.edit-borrador-wrap').find('.btn-cancel-borrador').click();
        }
    });

    // El wrap está dentro de una nota-hija; evita que un click dentro del wrap colapse el grupo padre.
    $(document).on('click', '.edit-borrador-wrap, .input-borrador, .texto-borrador', function (e) {
        e.stopPropagation();
    });

    // =====================================================
    // MODAL AGREGAR ADJUNTOS
    // =====================================================

    // Open modal and load data
    $(document).on('click', '.btn-agregar-adjuntos', function (e) {
        e.preventDefault();
        let notaId = $(this).data('id');
        let tipoRama = $(this).data('tipo-rama');

        // Set hidden values
        $('#adjNotaId').val(notaId);
        $('#adjTipoRama').val(tipoRama);

        // Show label
        $('#labelTipoRama').text(tipoRama === 'MKT' ? 'Marketing' : 'Fiscalización');

        // Show correct fields based on tipo_rama
        $('#adjCamposMkt, #adjCamposFisc').hide();
        if (tipoRama === 'MKT') {
            $('#adjCamposMkt').show();
        } else {
            $('#adjCamposFisc').show();
        }

        // Clear ALL previous state - re-enable everything first
        $('#frmAgregarAdjuntos input').prop('disabled', false);
        $('#frmAgregarAdjuntos')[0].reset();
        $('#frmAgregarAdjuntos input[type="file"]').val('');
        $('#frmAgregarAdjuntos .btn-quitar-archivo').remove();
        $('#adjuntosActuales').html('');
        $('#timelineAdjuntos').html('');

        // Load history timeline
        $('#timelineAdjuntos').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando historial...</p>');

        $.get('/notas-unificadas/historial-adjuntos/' + notaId, function (res) {

            // Display current attachments
            if (res.adjuntos) {
                let adjHtml = '';
                let tipoRamaLocal = tipoRama;

                // Common attachments
                if (res.adjuntos.solicitud && res.adjuntos.solicitud.existe) {
                    adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-file"></i> Solicitud: ' + res.adjuntos.solicitud.nombre + '</span>';
                }

                if (tipoRamaLocal === 'MKT') {
                    if (res.adjuntos.diseno && res.adjuntos.diseno.existe) {
                        adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-image"></i> Diseño: ' + res.adjuntos.diseno.nombre + '</span>';
                    }
                    if (res.adjuntos.bases && res.adjuntos.bases.existe) {
                        adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-file-text"></i> Bases: ' + res.adjuntos.bases.nombre + '</span>';
                    }
                } else {
                    if (res.adjuntos.varios && res.adjuntos.varios.existe) {
                        adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-archive"></i> Varios: ' + res.adjuntos.varios.nombre + '</span>';
                    }
                }

                if (res.adjuntos.informe && res.adjuntos.informe.existe) {
                    adjHtml += '<span class="label label-warning" style="margin-right: 5px;"><i class="fa fa-clipboard"></i> Informe: ' + res.adjuntos.informe.nombre + '</span>';
                }

                if (adjHtml) {
                    $('#adjuntosActuales').html('<strong><i class="fa fa-check-circle text-success"></i> Archivos ya cargados:</strong><br>' + adjHtml);
                } else {
                    $('#adjuntosActuales').html('<span class="text-muted"><i class="fa fa-info-circle"></i> No hay archivos cargados aún.</span>');
                }
            }

            // Display timeline
            if (res.success && res.historial && res.historial.length > 0) {
                let html = '<ul style="list-style: none; padding-left: 0;">';
                res.historial.forEach(function (item) {
                    let icon = item.accion.includes('AGREGADO') ? 'fa-plus-circle text-success' : 'fa-refresh text-warning';
                    html += '<li style="padding: 8px 0; border-bottom: 1px solid #eee;">';
                    html += '<i class="fa ' + icon + '"></i> ';
                    html += '<strong>' + item.fecha + '</strong> - ';
                    html += item.usuario + ': ';
                    html += '<em>' + item.detalle + '</em>';
                    html += '</li>';
                });
                html += '</ul>';
                $('#timelineAdjuntos').html(html);
            } else {
                $('#timelineAdjuntos').html('<p class="text-muted text-center">No hay historial de adjuntos.</p>');
            }
        }).fail(function () {
            $('#timelineAdjuntos').html('<p class="text-danger text-center">Error al cargar historial.</p>');
        });

        // Open modal
        $('#modalAgregarAdjuntos').modal('show');
    });

    // Submit attachments
    $(document).on('click', '#btnGuardarAdjuntos', function () {
        let notaId = $('#adjNotaId').val();
        let tipoRama = $('#adjTipoRama').val();

        // Disable inputs in hidden sections to prevent duplicate names
        if (tipoRama === 'MKT') {
            $('#adjCamposFisc input').prop('disabled', true);
            $('#adjCamposMkt input').prop('disabled', false);
        } else {
            $('#adjCamposMkt input').prop('disabled', true);
            $('#adjCamposFisc input').prop('disabled', false);
        }

        let formData = new FormData($('#frmAgregarAdjuntos')[0]);

        // Ensure CSRF token is in FormData
        formData.set('_token', $('input[name="_token"]').first().val());

        // Check if any file selected from VISIBLE inputs only
        let hasFiles = false;
        $('#frmAgregarAdjuntos input[type="file"]:enabled').each(function () {
            if ($(this)[0].files.length > 0) hasFiles = true;
        });

        if (!hasFiles) {
            notificacion('warning', 'Por favor seleccione al menos un archivo.');
            return;
        }

        console.log('DEBUG: Uploading to nota_id=', notaId);
        console.log('DEBUG: FormData entries:');
        for (let [key, value] of formData.entries()) {
            console.log('  ', key, ':', value instanceof File ? value.name : value);
        }

        let btn = $(this);
        btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');

        $.ajax({
            url: '/notas-unificadas/agregar-adjuntos/' + notaId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                console.log('DEBUG: Upload response:', res);
                btn.attr('disabled', false).html('<i class="fa fa-upload"></i> Subir Adjuntos');
                // Re-enable all inputs
                $('#frmAgregarAdjuntos input').prop('disabled', false);
                if (res.success) {
                    notificacion('success', res.msg);
                    $('#modalAgregarAdjuntos').modal('hide');
                    // Refresh table
                    if (window.refreshTable) window.refreshTable();
                } else {
                    notificacion('error', res.msg || 'Error al subir adjuntos');
                }
            },
            error: function (err) {
                btn.attr('disabled', false).html('<i class="fa fa-upload"></i> Subir Adjuntos');
                // Re-enable all inputs
                $('#frmAgregarAdjuntos input').prop('disabled', false);
                notificacion('error', 'Error al subir adjuntos: ' + (err.responseJSON?.msg || 'Error desconocido'));
            }
        });
    });

    // =====================================================
    // ! MODAL DETALLE/EDITAR TRÁMITE
    // =====================================================

    // Abrir modal de detalle desde grupo
    $(document).on('click', '.btn-ver-detalle-grupo', function (e) {
        e.preventDefault();
        e.stopPropagation();
        let grupoId = $(this).data('grupo-id');
        abrirModalDetalleGrupo(grupoId);
    });

    // Abrir modal de detalle desde nota individual
    $(document).on('click', '.btn-ver-detalle-nota', function (e) {
        e.preventDefault();
        e.stopPropagation();
        let notaId = $(this).data('nota-id');
        let tipoRama = $(this).data('tipo-rama');
        abrirModalDetalleNota(notaId, tipoRama);
    });

    // Función para abrir modal con datos de grupo
    function abrirModalDetalleGrupo(grupoId) {
        // Reset modal
        $('#grupoInfoPanel, #grupoResumenPanel, #grupoAprobacionPanel, #grupoRelacionPanel').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        $('#mktContenido, #fiscContenido').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');

        // Show all tabs
        $('#tabGrupoLi, #tabMktLi, #tabFiscLi').show();
        $('#detalleTabs a[href="#tabGrupo"]').tab('show');

        $('#modalDetalleTramite').modal('show');

        $.get('/notas-unificadas/detalle-grupo/' + grupoId, function (res) {
            if (res.success) {
                // Header
                $('#detalleHeaderTitulo').text(res.grupo.titulo || 'Trámite #' + res.grupo.id);
                $('#detalleHeaderMeta').html(
                    '<i class="fa fa-building"></i> ' + (res.grupo.casino || 'N/A') +
                    ' &nbsp;|&nbsp; <i class="fa fa-calendar"></i> ' + (res.grupo.created_at || 'N/A')
                );

                // Tab Grupo - Info
                $('#grupoInfoPanel').html(
                    '<h5 style="color:#333; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:5px;">Información del Grupo</h5>' +
                    '<table class="table table-condensed" style="margin:0">' +
                    '<tr><td><strong>ID:</strong></td><td>' + res.grupo.id + '</td></tr>' +
                    '<tr><td><strong>Tipo Solicitud:</strong></td><td>' + (res.grupo.tipo_solicitud || 'N/A') + '</td></tr>' +
                    '<tr><td><strong>Título:</strong></td><td>' + (res.grupo.titulo || 'Sin título') + '</td></tr>' +
                    '<tr><td><strong>Casino:</strong></td><td>' + (res.grupo.casino || 'N/A') + '</td></tr>' +
                    '<tr><td><strong>Creado:</strong></td><td>' + (res.grupo.created_at || 'N/A') + '</td></tr>' +
                    '</table>'
                );

                // Tab Grupo - Resumen
                let resumenHtml = '<h5 style="color:#333; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:5px;">Resumen de Notas</h5>';
                resumenHtml += '<ul style="list-style:none; padding-left:0; margin:0">';
                if (res.mkt) {
                    resumenHtml += '<li style="padding:8px; background:#dbeafe; border-radius:6px; margin-bottom:8px">' +
                        '<i class="fa fa-bullhorn text-primary"></i> <strong style="color:#1e3a8a;">Marketing:</strong> ' +
                        '<span class="label" style="' + getEstadoStyle(res.mkt.estado) + '">Estado: ' + res.mkt.estado + '</span>' +
                        '</li>';
                }
                if (res.fisc && !window.OCULTA_FISC) {
                    resumenHtml += '<li style="padding:8px; background:#d1fae5; border-radius:6px">' +
                        '<i class="fa fa-gavel text-success"></i> <strong style="color:#064e3b;">Fiscalización:</strong> ' +
                        '<span class="label" style="' + getEstadoStyle(res.fisc.estado) + '">Estado: ' + res.fisc.estado + '</span>' +
                        '</li>';
                }
                resumenHtml += '</ul>';
                $('#grupoResumenPanel').html(resumenHtml);

                // Notas Relacionadas
                renderRelaciones(res.grupo_padre, res.grupos_hijos || [], res.grupo.id);

                // Trackear ramas del grupo
                currentGrupoRamas = { mkt: !!res.mkt, fisc: !!res.fisc };

                // Notas de Aprobación
                renderNotasAprobacion(res.notas_aprobacion || [], res.grupo.id);

                // Tab MKT
                if (res.mkt) {
                    $('#mktContenido').html(renderizarNotaDetalle(res.mkt, '#3b82f6'));
                    _scrollPanelsToBottom('#mktContenido');
                    $('#tabMktLi').show();
                } else {
                    $('#mktContenido').html('<p class="text-muted text-center">No hay nota de Marketing asociada.</p>');
                    $('#tabMktLi').hide();
                }

                // Tab FISC (oculto para funcionarios y juego_responsable)
                if (res.fisc && !window.OCULTA_FISC) {
                    $('#fiscContenido').html(renderizarNotaDetalle(res.fisc, '#10b981'));
                    _scrollPanelsToBottom('#fiscContenido');
                    $('#tabFiscLi').show();
                } else {
                    $('#fiscContenido').html('');
                    $('#tabFiscLi').hide();
                }
            } else {
                notificacion('error', res.msg || 'Error al cargar detalle');
            }
        }).fail(function () {
            notificacion('error', 'Error de conexión al cargar detalle');
        });
    }

    // Función para abrir modal con datos de nota individual
    function abrirModalDetalleNota(notaId, tipoRama) {
        // Funcionarios y juego_responsable no pueden ver notas FISC
        if (window.OCULTA_FISC && tipoRama === 'FISC') {
            notificacion('warning', 'No tiene permisos para ver notas de Fiscalización');
            return;
        }

        $('#grupoInfoPanel, #grupoResumenPanel, #grupoAprobacionPanel, #grupoRelacionPanel').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
        $('#mktContenido, #fiscContenido').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');

        // Hide grupo tab, show only relevant tab
        $('#tabGrupoLi').hide();

        if (tipoRama === 'MKT') {
            $('#tabMktLi').show();
            $('#tabFiscLi').hide();
            $('#detalleTabs a[href="#tabMkt"]').tab('show');
        } else {
            $('#tabMktLi').hide();
            $('#tabFiscLi').show();
            $('#detalleTabs a[href="#tabFisc"]').tab('show');
        }

        $('#modalDetalleTramite').modal('show');

        $.get('/notas-unificadas/detalle-nota/' + notaId, function (res) {
            if (res.success) {
                let nota = res.nota;

                // Header
                $('#detalleHeaderTitulo').text('Nota #' + (nota.nro_nota || nota.id));
                $('#detalleHeaderMeta').html(
                    '<i class="fa fa-building"></i> ' + (nota.casino || 'N/A') +
                    ' &nbsp;|&nbsp; <i class="fa fa-tag"></i> ' + (nota.tipo_rama === 'MKT' ? 'Marketing' : 'Fiscalización') +
                    ' &nbsp;|&nbsp; <i class="fa fa-calendar"></i> ' + (nota.created_at || 'N/A')
                );

                // Change header color based on tipo_rama
                if (nota.tipo_rama === 'MKT') {
                    $('#modalDetalleHeader').css('background', 'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)');
                } else {
                    $('#modalDetalleHeader').css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)');
                }

                // Render nota content
                let color = nota.tipo_rama === 'MKT' ? '#3b82f6' : '#10b981';
                let contenidoHtml = renderizarNotaDetalle(nota, color);

                if (nota.tipo_rama === 'MKT') {
                    $('#mktContenido').html(contenidoHtml);
                    _scrollPanelsToBottom('#mktContenido');
                } else {
                    $('#fiscContenido').html(contenidoHtml);
                    _scrollPanelsToBottom('#fiscContenido');
                }
            } else {
                notificacion('error', res.msg || 'Error al cargar nota');
            }
        }).fail(function () {
            notificacion('error', 'Error de conexión');
        });
    }

    // ==================== NOTAS DE APROBACIÓN ====================

    var currentGrupoIdAprobacion = null;
    var currentGrupoRamas = { mkt: false, fisc: false };

    // Formatea un número de documento a 4 dígitos con ceros (ej: "4" -> "0004").
    function padNumeroAprobacion(n) {
        var s = ('' + (n == null ? '' : n)).replace(/\D/g, '');
        if (s === '') return ('' + (n == null ? '' : n));
        s = s.replace(/^0+/, '') || '0';
        while (s.length < 4) s = '0' + s;
        return s;
    }

    // Autocompleta el próximo número correlativo (por tipo + año). Solo al crear, no al editar.
    function autocompletarNumeroAprobacion() {
        var tipo = $('#aprobacionTipoDocumento').val();
        var anio = $.trim($('#aprobacionAnioDoc').val());
        if (!tipo || !anio) return;
        var $num = $('#aprobacionNumeroDoc');
        $num.attr('placeholder', 'Calculando…');
        $.get('/notas-unificadas/nota-aprobacion/proximo-numero', { tipo_documento: tipo, anio_documento: anio })
            .done(function (res) {
                if (res && res.success) {
                    $num.val(res.numero).attr('placeholder', res.numero);
                } else {
                    $num.attr('placeholder', 'Ej: 0001');
                    console.warn('proximo-numero sin éxito:', res);
                }
            })
            .fail(function (xhr) {
                $num.attr('placeholder', 'Ej: 0001');
                console.warn('Autocompletar número de aprobación falló:', xhr.status, xhr.responseText);
                if (typeof notificacion === 'function') {
                    notificacion('error', 'No se pudo calcular el próximo número (HTTP ' + xhr.status + ')');
                }
            });
    }

    function renderNotasAprobacion(notas, grupoId) {
        currentGrupoIdAprobacion = grupoId;
        var panel = $('#grupoAprobacionPanel');

        if (!notas || notas.length === 0) {
            panel.html('<h5 style="color:#333; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:5px;">Notas de Aprobación</h5>' +
                '<p class="text-muted text-center">No hay notas de aprobación cargadas.</p>');
            return;
        }

        var html = '<h5 style="color:#333; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:5px;">Notas de Aprobación</h5>';
        html += '<ul style="list-style:none; padding-left:0; margin:0">';
        notas.forEach(function (na) {
            var ramaColor = na.tipo_rama === 'MKT' ? '#3b82f6' : '#10b981';
            var ramaLabel = na.tipo_rama === 'MKT' ? 'Marketing' : 'Fiscalización';
            var ramaIcon = na.tipo_rama === 'MKT' ? 'fa-bullhorn' : 'fa-gavel';
            // Tipo documento badge: usa el mismo color que la rama
            var tipoDocLabel = '';
            if (na.tipo_documento) {
                var tdText = na.tipo_documento === 'NOTA' ? 'Nota' : 'Disposición';
                var numDoc = na.numero_documento ? ' N° ' + padNumeroAprobacion(na.numero_documento) : '';
                var anioDoc = na.anio_documento ? '/' + na.anio_documento : '';
                tipoDocLabel = ' <span class="label" style="background:' + ramaColor + '; color:white;">' + tdText + numDoc + anioDoc + '</span>';
            }

            html += '<li style="padding:8px 10px; border-bottom:1px solid #f3f4f6; display:flex; align-items:flex-start; gap:10px;">' +
                '<div style="flex:1 1 auto; min-width:0;">' +
                '<div style="margin-bottom:4px;">' +
                '<i class="fa ' + ramaIcon + '" style="color:' + ramaColor + '"></i> ' +
                '<span class="label" style="background:' + ramaColor + '; color:white;">' + ramaLabel + '</span>' +
                tipoDocLabel +
                '<small class="text-muted" style="margin-left:8px;">' + (na.created_at || '') + '</small>' +
                '</div>' +
                '<div style="word-break:break-word; overflow-wrap:anywhere;"><strong>' + na.nombre_original + '</strong></div>' +
                '</div>' +
                '<div style="flex:0 0 auto; white-space:nowrap;">' +
                '<a href="/notas-unificadas/nota-aprobacion/visualizar/' + na.id + '" target="_blank" class="btn btn-xs btn-info" title="Ver"><i class="fa fa-eye"></i></a> ' +
                '<a href="/notas-unificadas/nota-aprobacion/descargar/' + na.id + '" class="btn btn-xs btn-default" title="Descargar"><i class="fa fa-download"></i></a> ' +
                '<button class="btn btn-xs btn-warning btn-editar-nota-aprobacion" title="Editar" ' +
                'data-id="' + na.id + '" data-rama="' + (na.tipo_rama || '') + '" data-tipo="' + (na.tipo_documento || '') + '" ' +
                'data-numero="' + (na.numero_documento || '') + '" data-anio="' + (na.anio_documento || '') + '"><i class="fa fa-edit"></i></button> ' +
                (window.PUEDE_ELIMINAR ? '<button class="btn btn-xs btn-danger btn-eliminar-nota-aprobacion" data-id="' + na.id + '" title="Eliminar"><i class="fa fa-trash"></i></button>' : '') +
                '</div>' +
                '</li>';
        });
        html += '</ul>';
        panel.html(html);
    }

    // Selección de rama con botonera tipo card (estilo wizard)
    $(document).on('click', '.btn-rama-aprobacion', function () {
        $('.btn-rama-aprobacion').css({ border: '2px solid transparent', background: 'white' });
        var rama = $(this).data('rama');
        var borderColor = rama === 'MKT' ? '#3b82f6' : '#10b981';
        var bgColor = rama === 'MKT' ? '#eff6ff' : '#ecfdf5';
        $(this).css({ border: '2px solid ' + borderColor, background: bgColor });
        $('#aprobacionTipoRama').val(rama);
        // Reset tipo documento
        $('.btn-tipo-documento').css({ border: '2px solid transparent', background: 'white' });
        $('#aprobacionTipoDocumento').val('');
        $('#aprobacionNumeroDoc').val('').attr('placeholder', 'Elegí Nota o Disposición ↑');
        $('#aprobacionDatosWrap').slideDown(200);
    });

    // Selección de tipo documento (Nota / Disposición)
    $(document).on('click', '.btn-tipo-documento', function () {
        $('.btn-tipo-documento').css({ border: '2px solid transparent', background: 'white' });
        var tipo = $(this).data('tipo');
        var borderColor = tipo === 'NOTA' ? '#d97706' : '#4f46e5';
        var bgColor = tipo === 'NOTA' ? '#fffbeb' : '#eef2ff';
        $(this).css({ border: '2px solid ' + borderColor, background: bgColor });
        $('#aprobacionTipoDocumento').val(tipo);
        // Al crear (no editar): autocompletar el próximo número correlativo, editable
        if (!$('#aprobacionEditId').val()) {
            autocompletarNumeroAprobacion();
        }
    });

    // Si cambian el año al crear, recalcular el próximo número correlativo
    $(document).on('change', '#aprobacionAnioDoc', function () {
        if (!$('#aprobacionEditId').val() && $('#aprobacionTipoDocumento').val()) {
            autocompletarNumeroAprobacion();
        }
    });

    // Botón "Agregar" nota de aprobación — cerrar modal detalle primero
    $(document).on('click', '.btn-agregar-nota-aprobacion', function () {
        if (!currentGrupoIdAprobacion) {
            notificacion('error', 'No se pudo determinar el grupo');
            return;
        }
        var grupoId = currentGrupoIdAprobacion;
        // Cerrar modal de detalle y abrir el de aprobación cuando termine de cerrarse
        $('#modalDetalleTramite').modal('hide');
        $('#modalDetalleTramite').one('hidden.bs.modal', function () {
            $('#frmNotaAprobacion')[0].reset();
            $('#aprobacionGrupoId').val(grupoId);
            $('#aprobacionTipoRama').val('');
            $('#aprobacionTipoDocumento').val('');
            // Modo CREAR: limpiar id de edición y restaurar título, botón y carga de archivo
            $('#aprobacionEditId').val('');
            $('#tituloModalNotaAprobacion').html('<i class="fa fa-check-circle"></i> Agregar Nota de Aprobación');
            $('#btnGuardarNotaAprobacion').html('<i class="fa fa-upload"></i> Subir');
            $('#aprobacionArchivoWrap').show();
            // Reset botoneras visuales
            $('.btn-rama-aprobacion').css({ border: '2px solid transparent', background: 'white' });
            $('.btn-tipo-documento').css({ border: '2px solid transparent', background: 'white' });
            $('#aprobacionDatosWrap').hide();
            // Restaurar año actual
            $('#aprobacionAnioDoc').val(new Date().getFullYear());
            $('#modalNotaAprobacion').modal('show');

            // Preseleccionar rama si el grupo tiene solo MKT o solo FISC
            var soloMkt = currentGrupoRamas.mkt && !currentGrupoRamas.fisc;
            var soloFisc = !currentGrupoRamas.mkt && currentGrupoRamas.fisc;
            if (soloMkt || soloFisc) {
                var rama = soloMkt ? 'MKT' : 'FISC';
                $('.btn-rama-aprobacion[data-rama="' + rama + '"]').trigger('click');
            }
        });
    });

    // Botón "Editar" nota de aprobación — reutiliza el modal en modo edición
    $(document).on('click', '.btn-editar-nota-aprobacion', function () {
        var $b = $(this);
        var data = {
            id: $b.data('id'),
            rama: ('' + ($b.data('rama') || '')),
            tipo: ('' + ($b.data('tipo') || '')),
            numero: ('' + ($b.data('numero') || '')),
            anio: ('' + ($b.data('anio') || ''))
        };
        var grupoId = currentGrupoIdAprobacion;
        $('#modalDetalleTramite').modal('hide');
        $('#modalDetalleTramite').one('hidden.bs.modal', function () {
            $('#frmNotaAprobacion')[0].reset();
            $('#aprobacionGrupoId').val(grupoId);
            // Modo EDITAR: id, título, botón y ocultar carga de archivo (solo se editan los datos)
            $('#aprobacionEditId').val(data.id);
            $('#tituloModalNotaAprobacion').html('<i class="fa fa-edit"></i> Editar Nota de Aprobación');
            $('#btnGuardarNotaAprobacion').html('<i class="fa fa-save"></i> Guardar cambios');
            $('#aprobacionArchivoWrap').hide();
            // Reset botoneras visuales y mostrar datos
            $('.btn-rama-aprobacion').css({ border: '2px solid transparent', background: 'white' });
            $('.btn-tipo-documento').css({ border: '2px solid transparent', background: 'white' });
            $('#aprobacionDatosWrap').hide();
            $('#modalNotaAprobacion').modal('show');
            // Preseleccionar rama y tipo según el registro (en modo edición no autocompleta el número)
            if (data.rama) $('.btn-rama-aprobacion[data-rama="' + data.rama + '"]').trigger('click');
            if (data.tipo) $('.btn-tipo-documento[data-tipo="' + data.tipo + '"]').trigger('click');
            // Cargar número y año DESPUÉS de los triggers (el handler de rama limpia el número)
            $('#aprobacionNumeroDoc').val(padNumeroAprobacion(data.numero));
            $('#aprobacionAnioDoc').val(data.anio);
        });
    });

    // Al cerrar modal de aprobación, reabrir el de detalle
    $('#modalNotaAprobacion').on('hidden.bs.modal', function () {
        if (currentGrupoIdAprobacion) {
            abrirModalDetalleGrupo(currentGrupoIdAprobacion);
        }
    });

    // Subir nota de aprobación
    $('#btnGuardarNotaAprobacion').on('click', function () {
        var form = $('#frmNotaAprobacion')[0];
        var formData = new FormData(form);
        var btn = $(this);

        if (!$('#aprobacionTipoRama').val()) {
            notificacion('error', 'Seleccione una rama (Marketing o Fiscalización)');
            return;
        }
        if (!$('#aprobacionTipoDocumento').val()) {
            notificacion('error', 'Seleccione el tipo de documento (Nota o Disposición)');
            return;
        }
        if (!$.trim($('#aprobacionNumeroDoc').val())) {
            notificacion('error', 'Ingrese el número de documento');
            $('#aprobacionNumeroDoc').focus();
            return;
        }
        if (!$.trim($('#aprobacionAnioDoc').val())) {
            notificacion('error', 'Ingrese el año del documento');
            $('#aprobacionAnioDoc').focus();
            return;
        }
        var editId = $('#aprobacionEditId').val();
        if (!editId && !$('#inputAprobacionArchivos').val()) {
            notificacion('error', 'Seleccione al menos un archivo');
            return;
        }

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + (editId ? 'Guardando...' : 'Subiendo...'));

        $.ajax({
            url: editId ? '/notas-unificadas/nota-aprobacion/editar/' + editId : '/notas-unificadas/nota-aprobacion/subir',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    notificacion('exito', res.msg);
                    $('#modalNotaAprobacion').modal('hide');
                    // Recargar panel de aprobación y tabla principal
                    recargarNotasAprobacion(currentGrupoIdAprobacion);
                    if (typeof ajaxLoadTable === 'function') ajaxLoadTable();
                    else if (typeof window.refreshTable === 'function') window.refreshTable();
                } else {
                    notificacion('error', res.msg || 'Error al subir');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.msg : 'Error de conexión';
                notificacion('error', msg);
            },
            complete: function () {
                btn.prop('disabled', false).html(editId ? '<i class="fa fa-save"></i> Guardar cambios' : '<i class="fa fa-upload"></i> Subir');
            }
        });
    });

    // Eliminar nota de aprobación
    $(document).on('click', '.btn-eliminar-nota-aprobacion', function () {
        var id = $(this).data('id');
        confirmar('¿Eliminar esta nota de aprobación?', function () {
            $.ajax({
                url: '/notas-unificadas/nota-aprobacion/eliminar/' + id,
                method: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() },
                success: function (res) {
                    if (res.success) {
                        notificacion('exito', 'Nota de aprobación eliminada');
                        recargarNotasAprobacion(currentGrupoIdAprobacion);
                        if (typeof window.refreshTable === 'function') window.refreshTable();
                    } else {
                        notificacion('error', res.msg || 'Error al eliminar');
                    }
                },
                error: function () {
                    notificacion('error', 'Error de conexión');
                }
            });
        }, { titulo: 'Eliminar nota de aprobación' });
    });

    // Recargar solo el panel de notas de aprobación
    function recargarNotasAprobacion(grupoId) {
        $.get('/notas-unificadas/detalle-grupo/' + grupoId, function (res) {
            if (res.success) {
                renderNotasAprobacion(res.notas_aprobacion || [], grupoId);
            }
        });
    }

    // ==================== FIN NOTAS DE APROBACIÓN ====================

    // Renderizar contenido de una nota
    function renderizarNotaDetalle(nota, color) {
        let template = $('#templateNotaDetalle').html();
        if (!template) {
            console.error('templateNotaDetalle not found');
            return '<p class="text-danger">Error: template no encontrado</p>';
        }

        // Safe replace helper (avoids $ special chars in replacement strings)
        function sr(str, placeholder, value) {
            return str.split(placeholder).join(value);
        }

        // Replace placeholders
        var adjuntosHtml = renderAdjuntos(nota.documentos, nota.id, nota.tipo_rama);
        var activosHtml = renderActivos(nota.activos);
        var comentariosHtml = renderComentarios(nota.movimientos);
        var historialHtml = renderHistorial(nota.movimientos);

        let html = template;
        html = sr(html, '{{id}}', nota.id);
        html = sr(html, '{{color}}', color);
        html = sr(html, '{{tipo_rama}}', nota.tipo_rama || '');
        html = sr(html, '{{nro_nota}}', nota.nro_nota || 'N/A');
        html = sr(html, '{{anio}}', nota.anio || '');
        html = sr(html, '{{casino}}', nota.casino || 'N/A');
        html = sr(html, '{{tipo_solicitud}}', nota.tipo_solicitud || 'N/A');
        html = sr(html, '{{tipo_evento}}', nota.tipo_evento || 'N/A');
        html = sr(html, '{{id_tipo_evento}}', nota.id_tipo_evento || '');
        html = sr(html, '{{categoria}}', nota.categoria || 'N/A');
        html = sr(html, '{{id_categoria}}', nota.id_categoria || '');
        html = sr(html, '{{descripcion}}', nota.descripcion || 'Sin descripción');
        html = sr(html, '{{fecha_inicio}}', nota.fecha_inicio || 'N/A');
        html = sr(html, '{{fecha_fin}}', nota.fecha_fin || 'N/A');
        html = sr(html, '{{fecha_pretendida_aprobacion}}', nota.fecha_pretendida_aprobacion || 'N/A');
        html = sr(html, '{{fecha_propuesta_realizacion}}', nota.fecha_propuesta_realizacion || 'N/A');
        html = sr(html, '{{compartir_administrador}}', nota.compartir_administrador ? '1' : '0');
        html = sr(html, '{{compartir_administrador_label}}', nota.compartir_administrador ? '<span style="color:#5cb85c;"><i class="fa fa-check-circle"></i> Sí</span>' : '<span style="color:#999;"><i class="fa fa-times-circle"></i> No</span>');
        html = sr(html, '{{estado}}', nota.estado || 'INGRESADO');
        html = sr(html, '{{estadoStyle}}', getEstadoStyle(nota.estado));
        html = sr(html, '{{created_at}}', nota.created_at || 'N/A');
        html = sr(html, '{{id_casino}}', nota.id_casino || '');
        html = sr(html, '{{id_plataforma}}', nota.id_plataforma || '');
        html = sr(html, '{{adjuntosHtml}}', adjuntosHtml);
        html = sr(html, '{{activosHtml}}', activosHtml);
        html = sr(html, '{{activosCount}}', (nota.activos ? nota.activos.length : 0));
        html = sr(html, '{{comentariosHtml}}', comentariosHtml);
        html = sr(html, '{{historialHtml}}', historialHtml);

        // Panel de activos: visible en FISC siempre; en MKT solo si la nota involucra juegos
        var mostrarActivos = (nota.tipo_rama !== 'MKT') || (nota.involucra_juegos == 1);
        var $tmp = $('<div>').html(html);
        if (!mostrarActivos) {
            $tmp.find('.panel-activos-wrap').remove();
        } else if (nota.id_plataforma) {
            // Plataforma: el activo asociado solo puede ser Juego Online
            $tmp.find('.det-sel-tipo-activo').html('<option value="JUEGO_ONLINE">Juego Online</option>');
            $tmp.find('.activos-titulo').text('Juegos Asociados');
        }
        // Campos exclusivos de MKT que FISC no usa
        if (nota.tipo_rama !== 'MKT') {
            $tmp.find('.row-fecha-pretendida').remove();
            $tmp.find('.row-compartir-admin').remove();
            $tmp.find('.row-categoria').remove();
        } else {
            // En MKT 'Tipo Evento' está borrado lógicamente; FISC sigue mostrando su Tipo Evento Técnico.
            $tmp.find('.row-tipo-evento').remove();
            // 'Fecha propuesta de realización' es exclusiva de FISC.
            $tmp.find('.row-fecha-propuesta').remove();
        }
        html = $tmp.html();

        // Editar datos de nota: lo puede el staff admin SIEMPRE; el concesionario SOLO si la nota está en CARGA INICIAL.
        var puedeEditarEstaNota = (window.NIVEL_ESTADO === 'admin') ||
            (window.ES_CONCESIONARIO && nota.estado === window.ESTADO_CARGA_INICIAL);
        if (!puedeEditarEstaNota) {
            var $tmp3 = $('<div>').html(html);
            $tmp3.find('.btn-editar-nota').remove();
            html = $tmp3.html();
        }

        // No-admin (funcionario y regular): ocultar historial
        if (window.NIVEL_ESTADO !== 'admin') {
            var $tmp5 = $('<div>').html(html);
            $tmp5.find('.timeline-movimientos').closest('.panel').remove();
            html = $tmp5.html();
        }

        // FUNCIONARIO: ocultar botón adjuntar
        if (window.NIVEL_ESTADO === 'funcionario') {
            var $tmp4 = $('<div>').html(html);
            $tmp4.find('.btn-agregar-adj-modal').remove();
            html = $tmp4.html();
        }

        return html;
    }

    // Helper para estilo inline de estado
    // Color del badge segun la columna `color` de la BD (via OPCIONES_ESTADO). Mismo criterio que NotaEstado::cssPorColor.
    function cssPorColorEstado(color) {
        switch (color) {
            case 'danger': return 'background:#dc3545;color:#fff;';
            case 'success': return 'background:#28a745;color:#fff;';
            case 'warning':
            case 'warning-white': return 'background:#f0ad4e;color:#fff;';
            case 'warning-black': return 'background:#f0ad4e;color:#000;';
            case 'info': return 'background:#5bc0de;color:#fff;';
            default: return 'background:#5bc0de;color:#fff;';
        }
    }
    function getEstadoStyle(estado) {
        if (!estado) return 'background:#5bc0de;color:#fff;';
        var color = 'default';
        (window.OPCIONES_ESTADO || []).forEach(function (e) { if (e.descripcion === estado) color = e.color; });
        return cssPorColorEstado(color);
    }
    // Compat: mantiene nombre viejo para no romper nada
    function getEstadoClass(estado) {
        return 'default';
    }

    // Renderizar adjuntos
    function escAdj(s) {
        if (s === undefined || s === null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Render anidado: Tipo -> Documentos -> (última versión + acceso a versiones). Incluye "Anexos".
    function renderAdjuntos(documentos, notaId, tipoRama) {
        documentos = documentos || {};

        // Cada tipo lleva su propio color para que el encabezado sea bien distintivo.
        var tipos = (tipoRama === 'MKT')
            ? [['solicitud', 'Solicitud', 'fa-file-pdf-o', '#3b82f6'], ['diseno', 'Diseño', 'fa-image', '#8b5cf6'], ['bases', 'Bases', 'fa-file-text-o', '#0ea5e9'], ['informe', 'Informe', 'fa-clipboard', '#f59e0b']]
            : [['solicitud', 'Solicitud', 'fa-file-pdf-o', '#3b82f6'], ['varios', 'Archivos Varios', 'fa-archive', '#10b981'], ['informe', 'Informe', 'fa-clipboard', '#f59e0b']];
        tipos.push(['anexo', 'Anexos', 'fa-paperclip', '#64748b']);

        var html = '';
        tipos.forEach(function (t) {
            var key = t[0], label = t[1], icon = t[2], color = t[3];
            var docs = documentos[key] || [];
            var esAnexo = (key === 'anexo');

            // Cada tipo es su propio panel, con encabezado de color (¡!important para ganarle a la regla global de .panel-heading!)
            html += '<div class="panel panel-default" style="border-radius:8px; margin-bottom:10px; border:1px solid #e2e8f0;">';
            html += '<div class="panel-heading" style="display:flex !important; align-items:center; padding:9px 12px; background:' + color + ' !important; color:#fff !important; border-radius:8px 8px 0 0; border-bottom:none;">' +
                '<i class="fa ' + icon + '" style="margin-right:8px; color:#fff !important; font-size:14px;"></i>' +
                '<strong style="flex:1; font-size:13px; color:#fff !important; letter-spacing:.3px; text-transform:uppercase;">' + label + ' <span style="opacity:.85; font-weight:400; text-transform:none;">(' + docs.length + ')</span></strong>' +
                '<button class="btn btn-xs btn-add-documento" data-nota="' + notaId + '" data-tipo="' + key + '" title="Agregar ' + (esAnexo ? 'anexo' : 'documento') + '" style="background:rgba(255,255,255,0.92) !important; color:' + color + ' !important; border:none; border-radius:5px; font-weight:600; padding:3px 9px;"><i class="fa fa-plus"></i> ' + (esAnexo ? 'Anexo' : 'Doc') + '</button>' +
                '</div>';
            html += '<div class="panel-body" style="padding:8px;">';
            if (docs.length === 0) {
                html += '<div style="padding:4px 6px; color:#9ca3af; font-size:12px; text-align:center;"><em>' + (esAnexo ? 'Sin anexos' : 'No cargado') + '</em></div>';
            } else {
                docs.forEach(function (doc) { html += renderDocumentoRow(doc, key, notaId); });
            }
            html += '</div></div>';
        });

        return html;
    }

    function renderDocumentoRow(doc, tipo, notaId) {
        var ult = doc.ultima;
        var nombreArch = ult ? (ult.nombre_original || ult.path || '') : '';
        var esPdf = ult && /\.pdf$/i.test(nombreArch);
        var verUrl = ult ? '/notas-unificadas/visualizar-version/' + ult.version_id : '#';

        // Etiqueta principal = nombre del archivo (no "Documento N")
        var primary = ult ? escAdj(ult.nombre_original || nombreArch) : '<em>sin archivo</em>';
        var sub = ult ? ('v' + ult.version + (doc.cant_versiones > 1 ? ' · ' + doc.cant_versiones + ' versiones' : '')) : '';

        var h = '<div class="doc-item" data-doc="' + doc.id + '" style="background:#fff; border:1px solid #e2e8f0; border-radius:6px; padding:6px 8px; margin-bottom:6px;">';
        h += '<div style="display:flex; align-items:center;">';
        h += '<div style="flex:1; min-width:0;">' +
            '<div style="font-weight:600; font-size:12px; color:#334155; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + escAdj(nombreArch) + '">' + primary + '</div>' +
            (sub ? '<div style="font-size:11px; color:#94a3b8;">' + sub + '</div>' : '') +
            '</div>';
        // Botones compactos
        var bs = 'padding:2px 7px; font-size:11px; line-height:1.4;';
        h += '<div style="flex-shrink:0; display:flex; gap:3px; margin-left:6px;">';
        if (ult) h += '<a href="' + verUrl + '" target="_blank" class="btn btn-xs btn-info" title="Ver" style="' + bs + '"><i class="fa fa-eye"></i></a>';
        if (esPdf && ult) h += '<button class="btn btn-xs btn-warning btn-anotar-doc" data-nota="' + notaId + '" data-tipo="' + tipo + '" data-version="' + ult.version_id + '" title="Anotar PDF" style="' + bs + '"><i class="fa fa-edit"></i></button>';
        h += '<button class="btn btn-xs btn-default btn-nueva-version-doc" data-doc="' + doc.id + '" title="Subir nueva versión" style="' + bs + '"><i class="fa fa-upload"></i></button>';
        if (doc.cant_versiones > 1) h += '<button class="btn btn-xs btn-default btn-ver-versiones-doc" data-doc="' + doc.id + '" title="Ver versiones" style="' + bs + '"><i class="fa fa-history"></i></button>';
        if (window.PUEDE_ELIMINAR) h += '<button class="btn btn-xs btn-danger btn-eliminar-doc" data-doc="' + doc.id + '" title="Eliminar documento" style="' + bs + '"><i class="fa fa-trash"></i></button>';
        h += '</div></div>';
        h += '<div class="doc-versiones" data-doc="' + doc.id + '" style="display:none; margin-top:6px; border-top:1px solid #e2e8f0; padding-top:6px;"></div>';
        h += '</div>'; // cierra .doc-item (sin esto, el siguiente tipo quedaba anidado adentro)
        return h;
    }

    // Re-renderiza el panel de adjuntos a partir de la estructura documentos devuelta por el backend.
    function refrescarAdjuntos($scope, documentos) {
        var $content = $scope.closest('.nota-detalle-content');
        var notaId = $content.data('nota-id');
        var rama = $content.data('tipo-rama');
        $content.find('.adjuntos-lista').html(renderAdjuntos(documentos, notaId, rama));
    }

    // Subida genérica vía input file temporal (documento nuevo o nueva versión).
    function _subirArchivoVia(url, extra, $scope) {
        var $inp = $('<input type="file" style="display:none">');
        $('body').append($inp);
        $inp.on('change', function () {
            var file = this.files[0];
            if (!file) { $inp.remove(); return; }
            var fd = new FormData();
            fd.append('archivo', file);
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));
            for (var k in extra) { if (extra.hasOwnProperty(k) && extra[k] != null) fd.append(k, extra[k]); }
            notificacion('info', 'Subiendo archivo…');
            $.ajax({
                url: url, type: 'POST', data: fd, processData: false, contentType: false,
                success: function (res) {
                    if (res && res.success) {
                        notificacion('success', 'Archivo subido');
                        if ($scope) refrescarAdjuntos($scope, res.documentos);
                    } else {
                        notificacion('error', (res && res.msg) || 'Error al subir');
                    }
                },
                error: function (xhr) {
                    notificacion('error', (xhr.responseJSON && xhr.responseJSON.msg) || 'Error al subir (HTTP ' + xhr.status + ')');
                },
                complete: function () { $inp.remove(); }
            });
        });
        $inp.trigger('click');
    }

    // Agregar documento (o anexo). El nombre del documento = nombre del archivo (lo pone el backend).
    $(document).on('click', '.btn-add-documento', function () {
        var notaId = $(this).data('nota');
        var tipo = $(this).data('tipo');
        _subirArchivoVia('/notas-unificadas/documentos/' + notaId + '/' + tipo, {}, $(this));
    });

    // Subir nueva versión a un documento existente
    $(document).on('click', '.btn-nueva-version-doc', function () {
        var docId = $(this).data('doc');
        _subirArchivoVia('/notas-unificadas/documento/' + docId + '/version', {}, $(this));
    });

    // Eliminar documento completo (todas sus versiones)
    $(document).on('click', '.btn-eliminar-doc', function () {
        var docId = $(this).data('doc');
        var $scope = $(this);
        confirmar('Se eliminará este documento y TODAS sus versiones. ¿Continuar?', function () {
            $.ajax({
                url: '/notas-unificadas/documento/' + docId, type: 'DELETE',
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                success: function (res) {
                    if (res && res.success) { notificacion('success', 'Documento eliminado'); refrescarAdjuntos($scope, res.documentos); }
                    else notificacion('error', (res && res.msg) || 'Error');
                },
                error: function () { notificacion('error', 'Error de conexión'); }
            });
        }, { titulo: 'Eliminar documento', textoBoton: 'Eliminar' });
    });

    // Ver versiones de un documento (toggle inline)
    $(document).on('click', '.btn-ver-versiones-doc', function () {
        var docId = $(this).data('doc');
        var $cont = $(this).closest('.nota-detalle-content').find('.doc-versiones[data-doc="' + docId + '"]');
        if ($cont.is(':visible')) { $cont.slideUp(150); return; }
        $cont.html('<div class="text-muted" style="font-size:11px;"><i class="fa fa-spinner fa-spin"></i> Cargando…</div>').slideDown(150);
        $.get('/notas-unificadas/documento/' + docId + '/versiones', function (res) {
            if (!res || !res.success) { $cont.html('<span class="text-danger" style="font-size:11px;">Error al cargar versiones</span>'); return; }
            var h = '';
            res.versiones.forEach(function (v) {
                h += '<div style="display:flex; align-items:center; font-size:11px; padding:2px 0;">' +
                    '<span style="flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">v' + v.version + ' · ' + escAdj(v.nombre_original) + ' <span style="opacity:.6;">' + (v.created_at || '') + '</span></span>' +
                    '<a href="/notas-unificadas/visualizar-version/' + v.version_id + '" target="_blank" class="btn btn-xs btn-info" title="Ver"><i class="fa fa-eye"></i></a>' +
                    '</div>';
            });
            $cont.html(h || '<em class="text-muted" style="font-size:11px;">Sin versiones</em>');
        });
    });

    // Anotar el documento (abre el editor PDF en su última versión)
    $(document).on('click', '.btn-anotar-doc', function () {
        var notaId = $(this).data('nota');
        var tipo = $(this).data('tipo');
        var versionId = $(this).data('version');
        if (typeof abrirEditorPdf === 'function') {
            abrirEditorPdf(notaId, tipo, versionId);
        } else {
            notificacion('error', 'Editor de anotaciones no disponible');
        }
    });

    // Renderizar activos como tabla: Tipo · Nombre · ID · Estado · % Dev · Acción.
    // El backend devuelve nombre / id_display / estado / porcentaje_devolucion uniformes
    // para los 4 tipos (MTM, MESA, BINGO, JUEGO_ONLINE).
    function renderActivos(activos) {
        if (!activos || activos.length === 0) return '<p class="text-muted text-center" style="padding:10px"><i class="fa fa-info-circle"></i> No hay activos asociados a esta nota</p>';

        var tipoMeta = {
            'MTM':          { icon: 'fa-desktop', bg: '#8b5cf6' },
            'MESA':         { icon: 'fa-table',   bg: '#28a745' },
            'BINGO':        { icon: 'fa-th',      bg: '#e67e22' },
            'JUEGO_ONLINE': { icon: 'fa-gamepad', bg: '#0ea5e9' },
            'ISLA':         { icon: 'fa-server',  bg: '#17a2b8' }
        };

        function estadoBadge(estado) {
            // white-space:nowrap + display:inline-block: el badge no rompe a 'Activ\na' cuando la columna es angosta.
            if (estado === 'Activa')   return '<span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; white-space:nowrap; display:inline-block;">Activa</span>';
            if (estado === 'Inactiva') return '<span style="background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; white-space:nowrap; display:inline-block;">Inactiva</span>';
            return '<span style="color:#9ca3af;">—</span>';
        }

        // Etiqueta corta para el badge de tipo (evita que 'JUEGO_ONLINE' desborde su columna)
        var tipoLabel = { 'MTM': 'MTM', 'MESA': 'MESA', 'BINGO': 'BINGO', 'JUEGO_ONLINE': 'ONLINE', 'ISLA': 'ISLA' };

        // table-layout:fixed + colgroup: la tabla nunca supera el ancho del panel,
        // así la columna de eliminar siempre queda visible (sin scroll y sin que el overflow:hidden del modal la recorte).
        var html =
            '<table class="table table-condensed" style="margin:0; font-size:12px; width:100%; table-layout:fixed;">' +
                '<colgroup>' +
                    '<col style="width:58px;">' +
                    '<col>' +
                    '<col style="width:80px;">' +
                    '<col style="width:42px;">' +
                    '<col style="width:70px;">' +
                    '<col style="width:44px;">' +
                    (window.PUEDE_ELIMINAR ? '<col style="width:34px;">' : '') +
                '</colgroup>' +
                '<thead><tr style="background:#f8fafc;">' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 6px;">Tipo</th>' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 6px;">Nombre</th>' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 4px;">ID</th>' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 4px; text-align:center;">Isla</th>' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 4px;">Estado</th>' +
                    '<th style="font-size:11px; color:#64748b; padding:6px 4px;">% Dev</th>' +
                    (window.PUEDE_ELIMINAR ? '<th style="padding:6px 4px;"></th>' : '') +
                '</tr></thead>' +
                '<tbody>';

        activos.forEach(function (act, idx) {
            var tipo = act.tipo_activo || 'ISLA';
            var meta = tipoMeta[tipo] || { icon: 'fa-cube', bg: '#64748b' };
            var bgRow = idx % 2 === 0 ? '#fafafa' : '#fff';
            var pdev = (act.porcentaje_devolucion !== undefined && act.porcentaje_devolucion !== null && act.porcentaje_devolucion !== '—' && act.porcentaje_devolucion !== '')
                ? act.porcentaje_devolucion + '%'
                : '<span style="color:#9ca3af;">—</span>';

            var nombre = act.nombre || '—';
            // Nombre con max-width + ellipsis: nombres largos ('DA VINCI DIAMONDS') no se cortan en dos líneas
            // y dejan ancho para que ID/Estado no se rompan. El tooltip muestra el nombre completo.
            var nombreCell = '<td style="padding:6px 8px; max-width:120px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12px;" title="' + nombre.replace(/"/g, '&quot;') + '">' + nombre + '</td>';

            // Isla: solo aplica a MTM (las máquinas pertenecen a una isla). El resto muestra '—'.
            var islaCell = (tipo === 'MTM' && act.nro_isla !== undefined && act.nro_isla !== null && act.nro_isla !== '')
                ? '<b>' + act.nro_isla + '</b>'
                : '<span style="color:#9ca3af;">—</span>';

            html += '<tr data-activo-id="' + act.id + '" style="background:' + bgRow + ';">' +
                '<td style="padding:6px 6px; white-space:nowrap; overflow:hidden;"><span style="background:' + meta.bg + '; color:#fff; font-size:10px; font-weight:600; padding:2px 6px; border-radius:3px;"><i class="fa ' + meta.icon + '"></i> ' + (tipoLabel[tipo] || tipo) + '</span></td>' +
                nombreCell +
                '<td style="padding:6px 4px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + (act.id_display || act.id_activo || '') + '"><b>' + (act.id_display || act.id_activo || '—') + '</b></td>' +
                '<td style="padding:6px 4px; white-space:nowrap; overflow:hidden; text-align:center;">' + islaCell + '</td>' +
                '<td style="padding:6px 4px; white-space:nowrap; overflow:hidden;">' + estadoBadge(act.estado) + '</td>' +
                '<td style="padding:6px 4px; white-space:nowrap; overflow:hidden;">' + pdev + '</td>' +
                (window.PUEDE_ELIMINAR ? '<td style="padding:4px 2px; white-space:nowrap; text-align:center; position:relative;">' +
                    '<button class="btn btn-xs btn-ask-remove-activo" data-activo-id="' + act.id + '" title="Eliminar" style="padding:1px 5px; font-size:10px; line-height:1.2; background:#e2e8f0; color:#64748b; border:1px solid #cbd5e1; transition:all 0.15s;" onmouseover="this.style.background=\'#d9534f\';this.style.color=\'#fff\';this.style.borderColor=\'#d43f3a\';" onmouseout="this.style.background=\'#e2e8f0\';this.style.color=\'#64748b\';this.style.borderColor=\'#cbd5e1\';"><i class="fa fa-trash"></i></button>' +
                    // Confirmación flotante: position:absolute anclada a la derecha para que los 2 botones (✓/✗) no se recorten por el ancho fijo de la columna ni el overflow:hidden del modal.
                    '<span class="confirm-remove-activo" style="display:none; position:absolute; top:50%; right:4px; transform:translateY(-50%); background:#fff; padding:3px 5px; border-radius:6px; box-shadow:0 1px 6px rgba(0,0,0,0.22); white-space:nowrap; z-index:10;">' +
                        '<button class="btn btn-xs btn-success btn-confirm-remove-activo" data-activo-id="' + act.id + '" title="Confirmar" style="padding:1px 6px; font-size:11px;"><i class="fa fa-check"></i></button> ' +
                        '<button class="btn btn-xs btn-default btn-cancel-remove-activo" title="Cancelar" style="padding:1px 6px; font-size:11px;"><i class="fa fa-times"></i></button>' +
                    '</span>' +
                '</td>' : '') +
                '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }

    // Mantiene el contador del título del panel sincronizado al agregar/eliminar activos.
    function actualizarContadorActivos(count) {
        $('.panel-activos-wrap .activos-contador').text(count);
    }

    // Exportar los activos (máquinas/juegos) de la nota a CSV / Excel
    $(document).on('click', '.btn-export-activos', function () {
        var id = $(this).data('id');
        var formato = $(this).data('formato') || 'xlsx';
        if (!id) {
            notificacion('error', 'No se pudo determinar la nota');
            return;
        }
        window.location = '/notas-unificadas/activos/exportar/' + id + '?formato=' + formato;
    });

    // Renderizar comentarios (solo movimientos tipo COMENTARIO)
    function _avatarHtml(userImagen, size) {
        size = size || 32;
        if (userImagen) {
            return '<img src="data:image/jpeg;base64,' + userImagen + '" style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; object-fit:cover; flex-shrink:0;">';
        }
        return '<div style="width:' + size + 'px; height:' + size + 'px; border-radius:50%; background:#bdc3c7; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fa fa-user" style="color:white; font-size:' + (size * 0.45) + 'px;"></i></div>';
    }

    function renderComentarios(movimientos) {
        if (!movimientos || movimientos.length === 0) return '<p class="text-muted text-center" style="padding:20px">No hay comentarios aún</p>';

        let comentarios = movimientos.filter(function (m) { return m.accion === 'COMENTARIO'; });
        if (comentarios.length === 0) return '<p class="text-muted text-center" style="padding:20px">No hay comentarios aún</p>';

        let html = '';
        comentarios.forEach(function (c) {
            var puedeEliminarComentario = window.PUEDE_ELIMINAR || (c.id_usuario == window.CURRENT_USER_ID);
            html += '<div class="comentario-item" data-mov-id="' + c.id + '" style="display:flex; align-items:flex-start; gap:8px; padding:8px; background:#fce7f3; border-radius:8px; margin-bottom:6px; position:relative;">' +
                _avatarHtml(c.user_imagen, 30) +
                '<div style="flex:1; min-width:0;">' +
                    '<strong style="font-size:12px;">' + c.usuario + '</strong> <small class="text-muted">(' + c.fecha + ')</small>' +
                    (puedeEliminarComentario ? '<button class="btn btn-xs btn-danger btn-borrar-comentario" data-mov-id="' + c.id + '" style="padding:1px 5px; font-size:10px; float:right; margin-top:-2px;" title="Eliminar"><i class="fa fa-trash"></i></button>' : '') +
                    '<div style="font-size:13px; margin-top:2px; color:#1f2937;">' + c.comentario + '</div>' +
                '</div>' +
                '</div>';
        });
        return html;
    }

    // Renderizar historial (orden cronológico: más antiguo arriba, más reciente abajo)
    function renderHistorial(movimientos) {
        if (!movimientos || movimientos.length === 0) return '<p class="text-muted text-center">Sin historial</p>';

        // Invertir para orden cronológico (el backend viene desc)
        var items = movimientos.slice().reverse();

        let html = '';
        items.forEach(function (m) {
            // Orden: las acciones más específicas deben evaluarse ANTES que las genéricas
            // (p.ej. NOTA_APROBACION antes de ELIMIN; GRUPO_PADRE antes de cualquier match).
            let iconClass = 'fa-circle text-muted';
            if (m.accion.includes('INGRES')) iconClass = 'fa-plus-circle text-success';
            else if (m.accion.includes('MODIFIC')) iconClass = 'fa-exchange-alt text-info';
            else if (m.accion.includes('EDIT')) iconClass = 'fa-pencil-alt text-info';
            else if (m.accion.includes('NOTA_APROBACION')) iconClass = 'fa-certificate text-warning';
            else if (m.accion.includes('GRUPO_PADRE_ASIGNADO')) iconClass = 'fa-link text-info';
            else if (m.accion.includes('GRUPO_PADRE_QUITADO')) iconClass = 'fa-unlink text-warning';
            else if (m.accion.includes('ADJUNTO')) iconClass = 'fa-paperclip text-warning';
            else if (m.accion.includes('COMENT')) iconClass = 'fa-comment text-primary';
            else if (m.accion.includes('BORRADOR')) iconClass = 'fa-sticky-note text-info';
            else if (m.accion.includes('ACTIVO')) iconClass = 'fa-desktop text-info';
            else if (m.accion.includes('ELIMIN')) iconClass = 'fa-trash text-danger';

            html += '<div style="padding:6px 0; border-bottom:1px solid #f3f4f6">' +
                '<i class="fa ' + iconClass + '"></i> ' +
                '<strong>' + m.fecha + '</strong> - ' + m.usuario + '<br>' +
                '<small>' + m.accion + (m.comentario ? ': ' + m.comentario : '') + '</small>' +
                '</div>';
        });
        return html;
    }

    // Scroll comentarios y historial al fondo (más reciente abajo)
    function _scrollPanelsToBottom(containerSelector) {
        function doScroll() {
            $(containerSelector).find('.comentarios-lista').each(function () {
                this.scrollTop = this.scrollHeight;
            });
            $(containerSelector).find('.timeline-movimientos').each(function () {
                var $panel = $(this).closest('.panel-body');
                if ($panel.length) $panel[0].scrollTop = $panel[0].scrollHeight;
            });
        }
        // Ejecutar ahora y también cuando el modal sea visible
        setTimeout(doScroll, 100);
        $('#modalDetalleTramite').one('shown.bs.modal', doScroll);
    }

    // Enviar comentario
    $(document).on('click', '.btn-enviar-comentario', function () {
        let notaId = $(this).data('id');
        let $input = $('.input-comentario[data-id="' + notaId + '"]');
        let comentario = $input.val().trim();

        if (!comentario) {
            notificacion('warning', 'Escriba un comentario');
            return;
        }

        let $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            url: '/notas-unificadas/comentario/' + notaId,
            type: 'POST',
            data: { _token: $('input[name="_token"]').first().val(), comentario: comentario },
            success: function (res) {
                $btn.prop('disabled', false);
                if (res.success) {
                    $input.val('');
                    // Agregar comentario al final de la lista y scrollear abajo
                    let $lista = $('.comentarios-lista[data-id="' + notaId + '"]');
                    // Quitar placeholder si existe
                    $lista.find('.text-muted.text-center').remove();
                    var m = res.movimiento;
                    $lista.append(
                        '<div class="comentario-item" data-mov-id="' + m.id + '" style="display:flex; align-items:flex-start; gap:8px; padding:8px; background:#fce7f3; border-radius:8px; margin-bottom:6px; position:relative;">' +
                        _avatarHtml(m.user_imagen, 30) +
                        '<div style="flex:1; min-width:0;">' +
                            '<strong style="font-size:12px;">' + m.usuario + '</strong> <small class="text-muted">(' + m.fecha + ')</small>' +
                            '<button class="btn btn-xs btn-danger btn-borrar-comentario" data-mov-id="' + m.id + '" style="padding:1px 5px; font-size:10px; float:right; margin-top:-2px;" title="Eliminar"><i class="fa fa-trash"></i></button>' +
                            '<div style="font-size:13px; margin-top:2px; color:#1f2937;">' + m.comentario + '</div>' +
                        '</div></div>'
                    );
                    $lista[0].scrollTop = $lista[0].scrollHeight;
                    notificacion('success', 'Comentario agregado');
                } else {
                    notificacion('error', res.msg || 'Error');
                }
            },
            error: function () {
                $btn.prop('disabled', false);
                notificacion('error', 'Error de conexión');
            }
        });
    });

    // Enter para enviar comentario
    $(document).on('keypress', '.input-comentario', function (e) {
        if (e.which === 13) {
            $(this).closest('.input-group').find('.btn-enviar-comentario').click();
        }
    });

    // Borrar comentario
    $(document).on('click', '.btn-borrar-comentario', function () {
        var btn = $(this);
        var movId = btn.data('mov-id');
        var item = btn.closest('.comentario-item');

        if (btn.hasClass('confirming')) {
            // Segundo click: borrar
            btn.prop('disabled', true);
            $.ajax({
                url: '/notas-unificadas/comentario/' + movId,
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').first().val() },
                success: function (res) {
                    if (res.success) {
                        item.fadeOut(200, function () { item.remove(); });
                        notificacion('success', 'Comentario eliminado');
                    } else {
                        notificacion('error', res.msg || 'Error');
                        btn.prop('disabled', false);
                    }
                },
                error: function () {
                    notificacion('error', 'Error de conexión');
                    btn.prop('disabled', false);
                }
            });
        } else {
            // Primer click: confirmar
            btn.addClass('confirming').html('<i class="fa fa-check"></i> Confirmar');
            setTimeout(function () {
                if (btn.hasClass('confirming')) {
                    btn.removeClass('confirming').html('<i class="fa fa-trash"></i>');
                }
            }, 3000);
        }
    });

    // Eliminar adjunto
    $(document).on('click', '.btn-eliminar-adjunto', function () {
        var notaId = $(this).data('id');
        var campo = $(this).data('campo');
        var $contenedor = $(this).closest('.nota-detalle-content');

        confirmar('Se eliminarán el archivo y todas sus versiones anteriores. ¿Desea continuar?', function () {
            $.ajax({
                url: '/notas-unificadas/eliminar-adjunto/' + notaId + '/' + campo,
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').first().val() },
                success: function (res) {
                    if (res.success) {
                        notificacion('success', 'Adjunto y versiones eliminados');
                        // Refrescar adjuntos e historial
                        $.get('/notas-unificadas/detalle-nota/' + notaId, function (detRes) {
                            if (detRes.success) {
                                var nota = detRes.nota;
                                $contenedor.find('.adjuntos-lista').html(renderAdjuntos(nota.documentos, nota.id, nota.tipo_rama));
                                $contenedor.find('.timeline-movimientos').html(renderHistorial(nota.movimientos));
                            }
                        });
                    } else {
                        notificacion('error', res.msg || 'Error');
                    }
                },
                error: function () {
                    notificacion('error', 'Error de conexión');
                }
            });
        }, { titulo: 'Eliminar adjunto', textoBoton: 'Eliminar todo' });
    });

    // === ACTIVOS EN DETALLE ===

    // Lista temporal de activos pendientes (no guardados aún)
    var activosPendientes = [];

    function limpiarFormActivos(form) {
        activosPendientes = [];
        form.find('.det-inp-activo').val('');
        form.find('.det-hid-activo').val('');
        form.find('.det-pendientes-lista').empty();
        form.find('.det-pendientes-actions').hide();
        form.find('.det-resultados-busqueda').hide().empty();
    }

    function actualizarContadorPendientes(form) {
        var n = activosPendientes.length;
        var actions = form.find('.det-pendientes-actions');
        if (n > 0) {
            actions.show();
            form.find('.det-pendientes-count').text(n + (n === 1 ? ' activo pendiente' : ' activos pendientes'));
        } else {
            actions.hide();
        }
    }

    // Toggle form de agregar activo
    $(document).on('click', '.btn-toggle-add-activo', function (e) {
        e.stopPropagation();
        var panel = $(this).closest('.panel-activos-wrap');
        var form = panel.find('.activos-add-form');
        if (form.is(':visible')) {
            limpiarFormActivos(form);
            form.slideUp(200);
        } else {
            form.slideDown(200);
        }
    });

    // Cambiar placeholder según tipo
    $(document).on('change', '.det-sel-tipo-activo', function () {
        var form = $(this).closest('.activos-add-form');
        var inp = form.find('.det-inp-activo');
        var tipo = $(this).val();
        if (tipo === 'MTM') inp.attr('placeholder', 'Nro admin de la máquina...');
        else if (tipo === 'ISLA') inp.attr('placeholder', 'Nro de isla...');
        else if (tipo === 'MESA') inp.attr('placeholder', 'Nro de mesa...');
        else if (tipo === 'BINGO') inp.attr('placeholder', 'Nro de bingo...');
        inp.val('');
        form.find('.det-hid-activo').val('');
        form.find('.det-resultados-busqueda').hide().empty();
    });

    // Búsqueda de activos en detalle (con debounce)
    var detActivoTimeout = null;
    $(document).on('keyup', '.det-inp-activo', function () {
        var input = $(this);
        var val = input.val().trim();
        var form = input.closest('.activos-add-form');
        var tipo = form.find('.det-sel-tipo-activo').val();
        var idCasino = form.data('casino-id');
        var idPlataforma = form.data('plataforma-id');
        var resultsDiv = form.find('.det-resultados-busqueda');

        form.find('.det-hid-activo').val('');

        if (val.length < 1) { resultsDiv.hide().empty(); return; }

        var params = { q: val, tipo: tipo };
        if (idPlataforma) {
            params.id_plataforma = idPlataforma;
        } else {
            params.id_casino = idCasino;
        }

        clearTimeout(detActivoTimeout);
        detActivoTimeout = setTimeout(function () {
            $.get('/notas-unificadas/buscar-activos', params, function (data) {
                resultsDiv.empty();
                if (data.length === 0) {
                    resultsDiv.append('<div class="list-group-item text-muted" style="padding:8px 12px;">Sin resultados</div>');
                } else {
                    data.forEach(function (item) {
                        var info = item.info ? '<br><small class="text-muted">' + item.info + '</small>' : '';
                        resultsDiv.append('<a class="list-group-item det-result-item" href="#" data-id="' + item.id + '" data-text="' + item.text + '" style="padding:8px 12px;">' + item.text + info + '</a>');
                    });
                }
                // Posicionar dropdown debajo del input
                var inputWrap = input.closest('.det-input-wrap');
                var rect = inputWrap[0].getBoundingClientRect();
                resultsDiv.css({
                    top: rect.bottom + 'px',
                    left: rect.left + 'px',
                    width: rect.width + 'px'
                });
                resultsDiv.show();
            });
        }, 300);
    });

    // Seleccionar resultado de búsqueda
    $(document).on('click', '.det-result-item', function (e) {
        e.preventDefault();
        var form = $(this).closest('.activos-add-form');
        form.find('.det-inp-activo').val($(this).data('text'));
        form.find('.det-hid-activo').val($(this).data('id'));
        form.find('.det-resultados-busqueda').hide();
    });

    // Cerrar resultados al hacer click fuera
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.det-inp-activo, .det-resultados-busqueda').length) {
            $('.det-resultados-busqueda').hide();
        }
    });

    // Agregar activo a lista PENDIENTE (no guarda aún)
    // Agrega un activo YA RESUELTO (id canónico) a la lista pendiente. Devuelve false si era duplicado.
    function agregarPendiente(form, tipo, id, texto) {
        for (var i = 0; i < activosPendientes.length; i++) {
            if (activosPendientes[i] && activosPendientes[i].tipo === tipo && activosPendientes[i].id == id) return false;
        }
        activosPendientes.push({ tipo: tipo, id: id, texto: texto });
        var icon = 'fa-gamepad', color = '#17a2b8';
        if (tipo === 'MTM') { icon = 'fa-desktop'; color = '#8b5cf6'; }
        else if (tipo === 'MESA') { icon = 'fa-table'; color = '#28a745'; }
        else if (tipo === 'ISLA') { icon = 'fa-sitemap'; color = '#f59e0b'; }
        else if (tipo === 'BINGO') { icon = 'fa-th'; color = '#e67e22'; }
        var idx = activosPendientes.length - 1;
        form.find('.det-pendientes-lista').append(
            '<div class="det-pendiente-row" data-idx="' + idx + '" style="display:flex; align-items:center; padding:6px 8px; background:#fefce8; border:1px dashed #d4a017; border-radius:4px; margin-bottom:4px;">' +
                '<i class="fa ' + icon + '" style="color:' + color + '; margin-right:8px;"></i>' +
                '<span style="background:' + color + '; color:#fff; font-size:10px; font-weight:600; padding:2px 7px; border-radius:3px; margin-right:8px;">' + tipo + '</span>' +
                '<span style="flex:1; font-size:12px;">' + escAdj(texto) + '</span>' +
                '<button class="btn btn-xs btn-default det-btn-quitar-pendiente" data-idx="' + idx + '"><i class="fa fa-times"></i></button>' +
            '</div>'
        );
        actualizarContadorPendientes(form);
        return true;
    }

    // Scope (casino/plataforma) del form para el resolver.
    function scopeActivos(form) {
        return { id_casino: form.data('casino-id') || '', id_plataforma: form.data('plataforma-id') || '' };
    }

    $(document).on('click', '.det-btn-agregar-activo', function () {
        var form = $(this).closest('.activos-add-form');
        var tipo = form.find('.det-sel-tipo-activo').val();
        var idSel = form.find('.det-hid-activo').val();
        var texto = $.trim(form.find('.det-inp-activo').val());

        // Si eligió del desplegable, el id ya es canónico → agregar directo.
        if (idSel) {
            if (agregarPendiente(form, tipo, idSel, texto || idSel)) {
                form.find('.det-inp-activo').val(''); form.find('.det-hid-activo').val('');
            } else { notificacion('warning', 'Ya está en la lista pendiente.'); }
            return;
        }
        if (!texto) { notificacion('warning', 'Escribí o seleccioná un activo.'); return; }

        // No eligió: RESOLVER el valor tipeado (id o código) contra los datos reales. Nunca se guarda texto crudo.
        var sc = scopeActivos(form);
        $.post('/notas-unificadas/resolver-activos', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            tipo: tipo, id_casino: sc.id_casino, id_plataforma: sc.id_plataforma, valores: [texto]
        }, function (res) {
            if (res.resueltos && res.resueltos.length) {
                var r = res.resueltos[0];
                agregarPendiente(form, tipo, r.id, r.nombre);
                form.find('.det-inp-activo').val(''); form.find('.det-hid-activo').val('');
            } else if (res.ambiguos && res.ambiguos.length) {
                notificacion('warning', '"' + texto + '" coincide con varios; elegilo del buscador.');
            } else {
                notificacion('error', '"' + texto + '" no corresponde a ningún activo de este casino/plataforma.');
            }
        }).fail(function () { notificacion('error', 'Error al resolver el activo.'); });
    });

    // Carga masiva: mostrar/ocultar el textarea
    $(document).on('click', '.det-toggle-masiva', function () {
        $(this).closest('.activos-add-form').find('.det-masiva-wrap').slideToggle(150);
    });

    // Carga masiva: resolver lista pegada y agregar los resueltos
    $(document).on('click', '.det-btn-resolver-masiva', function () {
        var form = $(this).closest('.activos-add-form');
        var tipo = form.find('.det-sel-tipo-activo').val();
        var raw = form.find('.det-masiva-text').val() || '';
        var valores = raw.split(/[\r\n,;]+/).map(function (s) { return s.trim(); }).filter(function (s) { return s.length; });
        if (!valores.length) { notificacion('warning', 'Pegá al menos un ID o código.'); return; }

        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Resolviendo...');
        var sc = scopeActivos(form);
        $.post('/notas-unificadas/resolver-activos', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            tipo: tipo, id_casino: sc.id_casino, id_plataforma: sc.id_plataforma, valores: valores
        }, function (res) {
            $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> Resolver y agregar');
            var agregados = 0, dup = 0;
            (res.resueltos || []).forEach(function (r) { if (agregarPendiente(form, tipo, r.id, r.nombre)) agregados++; else dup++; });
            var rep = '<div style="color:#166534;"><b>' + agregados + '</b> agregados' + (dup ? ' · ' + dup + ' ya estaban' : '') + '</div>';
            if (res.no_encontrados && res.no_encontrados.length) {
                rep += '<div style="color:#991b1b; margin-top:4px;"><b>No encontrados (' + res.no_encontrados.length + '):</b> ' + res.no_encontrados.map(escAdj).join(', ') + '</div>';
            }
            if (res.ambiguos && res.ambiguos.length) {
                rep += '<div style="color:#92400e; margin-top:4px;"><b>Ambiguos (' + res.ambiguos.length + ') — elegilos del buscador:</b> ' + res.ambiguos.map(function (a) { return escAdj(a.valor); }).join(', ') + '</div>';
            }
            form.find('.det-masiva-reporte').html(rep);
            // Dejar en el textarea solo los problemáticos (no encontrados + ambiguos)
            var problem = (res.no_encontrados || []).concat((res.ambiguos || []).map(function (a) { return a.valor; }));
            form.find('.det-masiva-text').val(problem.join('\n'));
        }).fail(function () {
            $btn.prop('disabled', false).html('<i class="fa fa-magic"></i> Resolver y agregar');
            notificacion('error', 'Error al resolver la lista.');
        });
    });

    // Quitar un pendiente de la lista
    $(document).on('click', '.det-btn-quitar-pendiente', function () {
        var idx = $(this).data('idx');
        activosPendientes[idx] = null; // marcar como removido
        $(this).closest('.det-pendiente-row').fadeOut(150, function () { $(this).remove(); });
        var form = $(this).closest('.activos-add-form');
        // Recontar solo los no-null
        var count = 0;
        for (var i = 0; i < activosPendientes.length; i++) { if (activosPendientes[i]) count++; }
        if (count === 0) {
            form.find('.det-pendientes-actions').hide();
        } else {
            form.find('.det-pendientes-count').text(count + (count === 1 ? ' activo pendiente' : ' activos pendientes'));
        }
    });

    // CONFIRMAR: guardar todos los pendientes
    $(document).on('click', '.det-btn-confirmar-activos', function () {
        var form = $(this).closest('.activos-add-form');
        var notaId = form.data('nota-id');
        var btn = $(this);

        // Filtrar nulls
        var toSave = [];
        for (var i = 0; i < activosPendientes.length; i++) {
            if (activosPendientes[i]) toSave.push(activosPendientes[i]);
        }
        if (toSave.length === 0) { notificacion('warning', 'No hay activos pendientes.'); return; }

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.post('/notas-unificadas/activos/' + notaId, {
            _token: $('input[name="_token"]').first().val(),
            activos: toSave
        }, function (res) {
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Confirmar');
            if (res.success) {
                notificacion('success', toSave.length + ' activo(s) guardado(s).');
                var lista = form.closest('.panel-body').find('.activos-lista-detalle');
                lista.html(renderActivos(res.activos));
                actualizarContadorActivos((res.activos || []).length);
                limpiarFormActivos(form);
                form.slideUp(200);
            } else {
                notificacion('error', res.msg || 'Error al guardar.');
            }
        }).fail(function () {
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Confirmar');
            notificacion('error', 'Error de conexión.');
        });
    });

    // CANCELAR: descartar pendientes y cerrar form
    $(document).on('click', '.det-btn-cancelar-activos', function () {
        var form = $(this).closest('.activos-add-form');
        limpiarFormActivos(form);
        form.slideUp(200);
    });

    // --- ELIMINAR ACTIVOS EXISTENTES (individual con confirm/cancel inline) ---

    // Click en trash muestra confirm/cancel
    $(document).on('click', '.btn-ask-remove-activo', function () {
        $(this).hide();
        $(this).siblings('.confirm-remove-activo').show();
    });

    // Cancelar eliminación
    $(document).on('click', '.btn-cancel-remove-activo', function () {
        var wrap = $(this).closest('.confirm-remove-activo');
        wrap.hide();
        wrap.siblings('.btn-ask-remove-activo').show();
    });

    // Confirmar eliminación
    $(document).on('click', '.btn-confirm-remove-activo', function () {
        var activoId = $(this).data('activo-id');
        // El render de activos ahora es <table>; antes era <li>. Buscamos el contenedor por clase
        // (.activos-lista-detalle es el wrap del template) para no depender de la etiqueta.
        var fila = $(this).closest('tr, li');
        var lista = $(this).closest('.activos-lista-detalle');

        $.ajax({
            url: '/notas-unificadas/activo/' + activoId,
            type: 'DELETE',
            data: { _token: $('input[name="_token"]').first().val() },
            success: function (res) {
                if (res.success) {
                    fila.fadeOut(200, function () {
                        fila.remove();
                        lista.html(renderActivos(res.activos || []));
                        actualizarContadorActivos((res.activos || []).length);
                    });
                    notificacion('success', 'Activo eliminado.');
                } else {
                    notificacion('error', res.msg || 'Error');
                }
            },
            error: function () { notificacion('error', 'Error de conexión.'); }
        });
    });

    // Abrir modal agregar adjuntos desde dentro del modal detalle
    $(document).on('click', '.btn-agregar-adj-modal', function () {
        let notaId = $(this).data('id');
        let tipoRama = $(this).data('tipo-rama');

        // Close detail modal, open adjuntos modal
        $('#modalDetalleTramite').modal('hide');

        setTimeout(function () {
            // Trigger the existing adjuntos modal logic
            $('#adjNotaId').val(notaId);
            $('#adjTipoRama').val(tipoRama);
            $('#labelTipoRama').text(tipoRama === 'MKT' ? 'Marketing' : 'Fiscalización');

            $('#adjCamposMkt, #adjCamposFisc').hide();
            if (tipoRama === 'MKT') {
                $('#adjCamposMkt').show();
            } else {
                $('#adjCamposFisc').show();
            }

            // Clear ALL previous state - re-enable everything first
            $('#frmAgregarAdjuntos input').prop('disabled', false);
            $('#frmAgregarAdjuntos')[0].reset();
            $('#frmAgregarAdjuntos input[type="file"]').val('');
            $('#frmAgregarAdjuntos .btn-quitar-archivo').remove();
            $('#adjuntosActuales').html('');
            $('#timelineAdjuntos').html('');
            $('#timelineAdjuntos').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando historial...</p>');

            $.get('/notas-unificadas/historial-adjuntos/' + notaId, function (res) {
                // Same logic as before for displaying
                if (res.adjuntos) {
                    let adjHtml = '';
                    if (res.adjuntos.solicitud && res.adjuntos.solicitud.existe) {
                        adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-file"></i> Solicitud: ' + res.adjuntos.solicitud.nombre + '</span>';
                    }
                    if (tipoRama === 'MKT') {
                        if (res.adjuntos.diseno && res.adjuntos.diseno.existe) adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-image"></i> Diseño: ' + res.adjuntos.diseno.nombre + '</span>';
                        if (res.adjuntos.bases && res.adjuntos.bases.existe) adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-file-text"></i> Bases: ' + res.adjuntos.bases.nombre + '</span>';
                    } else {
                        if (res.adjuntos.varios && res.adjuntos.varios.existe) adjHtml += '<span class="label label-success" style="margin-right: 5px;"><i class="fa fa-archive"></i> Varios: ' + res.adjuntos.varios.nombre + '</span>';
                    }
                    if (res.adjuntos.informe && res.adjuntos.informe.existe) adjHtml += '<span class="label label-warning" style="margin-right: 5px;"><i class="fa fa-clipboard"></i> Informe: ' + res.adjuntos.informe.nombre + '</span>';

                    if (adjHtml) {
                        $('#adjuntosActuales').html('<strong><i class="fa fa-check-circle text-success"></i> Archivos ya cargados:</strong><br>' + adjHtml);
                    } else {
                        $('#adjuntosActuales').html('<span class="text-muted"><i class="fa fa-info-circle"></i> No hay archivos cargados aún.</span>');
                    }
                }
                if (res.historial && res.historial.length > 0) {
                    let html = '<ul style="list-style: none; padding-left: 0;">';
                    res.historial.forEach(function (item) {
                        let icon = item.accion.includes('AGREGADO') ? 'fa-plus-circle text-success' : 'fa-refresh text-warning';
                        html += '<li style="padding: 8px 0; border-bottom: 1px solid #eee;"><i class="fa ' + icon + '"></i> <strong>' + item.fecha + '</strong> - ' + item.usuario + ': <em>' + item.detalle + '</em></li>';
                    });
                    html += '</ul>';
                    $('#timelineAdjuntos').html(html);
                } else {
                    $('#timelineAdjuntos').html('<p class="text-muted text-center">No hay historial de adjuntos.</p>');
                }
            });

            $('#modalAgregarAdjuntos').modal('show');
        }, 300);
    });

    // ========================================
    // EDITAR NOTA - INLINE EDITING
    // ========================================

    // Click en botón Editar
    $(document).on('click', '.btn-editar-nota', function () {
        let notaId = $(this).data('id');
        let $panel = $(this).closest('.panel');

        // Toggle edit mode
        if ($(this).hasClass('editing')) {
            // Cancel edit
            cancelarEdicion($panel, notaId);
            $(this).removeClass('editing').html('<i class="fa fa-pencil"></i> Editar');
        } else {
            // Enable edit mode
            habilitarEdicion($panel, notaId);
            $(this).addClass('editing').html('<i class="fa fa-times"></i> Cancelar');
        }
    });

    // Habilitar edición inline
    function habilitarEdicion($panel, notaId) {
        var tipoRama = $panel.closest('.nota-detalle-content').data('tipo-rama') || 'MKT';

        $panel.find('.editable').each(function () {
            let $span = $(this);
            let field = $span.data('field');
            let currentValue = $span.text().trim();
            if (currentValue === 'N/A') currentValue = '';

            let inputHtml = '';
            if (field === 'descripcion') {
                inputHtml = '<textarea class="form-control edit-input" data-field="' + field + '" rows="2" style="font-size: 13px;">' + currentValue + '</textarea>';
            } else if (field === 'fecha_inicio' || field === 'fecha_fin' || field === 'fecha_pretendida_aprobacion' || field === 'fecha_propuesta_realizacion') {
                inputHtml = '<input type="date" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" style="font-size: 13px;">';
            } else if (field === 'anio') {
                inputHtml = '<input type="number" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" min="2000" max="2100" step="1" style="font-size: 13px; width: 80px; display: inline-block;">';
            } else if (field === 'nro_nota_ing') {
                inputHtml = '<input type="text" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" style="font-size: 13px; width: 120px; display: inline-block;">';
            } else if (field === 'id_tipo_evento') {
                var opciones = window.OPCIONES_TIPO_EVENTO[tipoRama] || [];
                var selectedId = $span.data('value') || '';
                var esPlataforma = !!$panel.closest('.nota-detalle-content').data('id-plataforma');
                inputHtml = '<select class="form-control edit-input" data-field="' + field + '" style="font-size: 13px;"><option value="">-- Seleccione --</option>';
                opciones.forEach(function(o) {
                    var ctx = o.contexto || 'todos';
                    if (ctx === 'fisico' && esPlataforma) return;
                    if (ctx === 'plataforma' && !esPlataforma) return;
                    inputHtml += '<option value="' + o.id + '"' + (o.id == selectedId ? ' selected' : '') + '>' + o.nombre + '</option>';
                });
                inputHtml += '</select>';
            } else if (field === 'id_categoria') {
                var opciones = window.OPCIONES_CATEGORIA[tipoRama] || [];
                var selectedId = $span.data('value') || '';
                inputHtml = '<select class="form-control edit-input" data-field="' + field + '" style="font-size: 13px;"><option value="">-- Seleccione --</option>';
                opciones.forEach(function(o) { inputHtml += '<option value="' + o.id + '"' + (o.id == selectedId ? ' selected' : '') + '>' + o.nombre + '</option>'; });
                inputHtml += '</select>';
            } else if (field === 'compartir_administrador') {
                var checked = ($span.data('value') == 1) ? ' checked' : '';
                inputHtml = '<label style="display:inline-flex; align-items:center; gap:6px; font-weight:normal; cursor:pointer; margin:0;"><input type="checkbox" class="edit-input edit-input-check" data-field="' + field + '"' + checked + ' style="width:16px; height:16px;"> <span style="font-size:13px;">Sí</span></label>';
            } else if (field === 'estado') {
                var estadoActual = $span.data('value') || '';
                var allOpciones = window.OPCIONES_ESTADO || [];
                var trans = window.TRANSICIONES_ESTADO || {};
                var permitidos = [];
                if (window.NIVEL_ESTADO === 'admin') {
                    permitidos = allOpciones.map(function(e) { return e.descripcion; });
                } else {
                    var mapa = trans[window.NIVEL_ESTADO] || {};
                    permitidos = mapa[estadoActual] || [];
                }
                // "APROBADO - NOTA/DISPOSICION" solo Superusuario o Despacho.
                if (!window.PUEDE_APROBAR_NOTA) {
                    permitidos = permitidos.filter(function (e) { return e !== window.ESTADO_APROBADO_NOTA; });
                }
                inputHtml = '<select class="form-control edit-input" data-field="' + field + '" style="font-size: 13px;">';
                allOpciones.forEach(function(e) {
                    var enabled = (permitidos.indexOf(e.descripcion) !== -1) || (e.descripcion === estadoActual);
                    inputHtml += '<option value="' + e.descripcion + '"' + (e.descripcion === estadoActual ? ' selected' : '') + (!enabled ? ' disabled style="color:#ccc;"' : '') + '>' + e.descripcion + '</option>';
                });
                inputHtml += '</select>';
            } else {
                inputHtml = '<input type="text" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" style="font-size: 13px;">';
            }

            $span.data('original-value', currentValue).hide().after(inputHtml);
        });

        // Add save button
        $panel.find('.panel-body').append(
            '<div class="edit-actions" style="margin-top: 15px; text-align: right;">' +
            '<button class="btn btn-success btn-sm btn-guardar-edicion" data-id="' + notaId + '">' +
            '<i class="fa fa-save"></i> Guardar' +
            '</button>' +
            '</div>'
        );
    }

    // Cancelar edición
    function cancelarEdicion($panel, notaId) {
        $panel.find('.edit-input-check').closest('label').remove();
        $panel.find('.edit-input').remove();
        $panel.find('.edit-actions').remove();
        $panel.find('.editable').each(function () {
            $(this).show();
        });
    }

    // Guardar cambios
    $(document).on('click', '.btn-guardar-edicion', function () {
        let notaId = $(this).data('id');
        let $panel = $(this).closest('.panel');
        let $btn = $(this);

        let datos = {};
        $panel.find('.edit-input').each(function () {
            let field = $(this).data('field');
            let value = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $(this).val();
            datos[field] = value;
        });

        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

        $.ajax({
            url: '/notas-unificadas/update-nota/' + notaId,
            type: 'PUT',
            data: $.extend({ _token: $('input[name="_token"]').first().val() }, datos),
            success: function (res) {
                if (res.success) {
                    // Update display values
                    $panel.find('.edit-input').each(function () {
                        var $input = $(this);
                        var field = $input.data('field');
                        var $editable = $input.is(':checkbox') ? $input.closest('label').prev('.editable') : $input.prev('.editable');
                        var displayValue;
                        if (field === 'compartir_administrador') {
                            var val = $input.is(':checked') ? 1 : 0;
                            $editable.data('value', val);
                            $editable.html(val ? '<span style="color:#5cb85c;"><i class="fa fa-check-circle"></i> Sí</span>' : '<span style="color:#999;"><i class="fa fa-times-circle"></i> No</span>').show();
                            $input.closest('label').remove();
                            return;
                        } else if (field === 'estado') {
                            var estadoVal = $input.val();
                            $editable.data('value', estadoVal);
                            $editable.html('<span class="label" style="' + getEstadoStyle(estadoVal) + '">' + estadoVal + '</span>').show();
                        } else if ($input.is('select')) {
                            displayValue = $input.find('option:selected').text() || 'N/A';
                            $editable.data('value', $input.val());
                            $editable.text(displayValue).show();
                        } else {
                            displayValue = $input.val() || 'N/A';
                            $editable.text(displayValue).show();
                        }
                        $input.remove();
                    });
                    $panel.find('.edit-actions').remove();
                    $panel.find('.btn-editar-nota').removeClass('editing').html('<i class="fa fa-pencil"></i> Editar');
                    notificacion('success', 'Nota actualizada correctamente');

                    // Si el cambio tocó campos compartidos con el grupo (nro_nota,
                    // anio, titulo), refrescar la tabla detrás del modal para que
                    // la fila del grupo muestre los nuevos valores sin recargar.
                    if (res.grupo_actualizado && typeof window.refreshTable === 'function') {
                        window.refreshTable();
                    }
                } else {
                    notificacion('error', res.msg || 'Error al guardar');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                }
            },
            error: function (xhr) {
                // Mostrar el mensaje específico del backend (422 de validación,
                // 403 de permiso, choque de nro_nota+anio con otro grupo, etc.).
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) || 'Error de conexión';
                notificacion('error', msg);
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
            }
        });
    });

    // ========================================
    // COMPLEMENTACIÓN DE TRÁMITE (Workflow)
    // ========================================

    // Click en botón Agregar MKT / FISC
    $(document).on('click', '.btn-complementar-grupo', function () {
        let grupoId = $(this).data('grupo-id');
        let rama = $(this).data('rama');
        complementarGrupo(grupoId, rama);
    });

    function complementarGrupo(grupoId, rama) {
        console.log('COMPLEMENTAR INICIO: Grupo ' + grupoId + ', Rama ' + rama);

        let $btn = $('.btn-complementar-grupo[data-grupo-id="' + grupoId + '"][data-rama="' + rama + '"]');
        let originalIcon = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        $.get('/notas-unificadas/detalle-grupo/' + grupoId, function (res) {
            console.log('COMPLEMENTAR DATA:', res);
            $btn.html(originalIcon).prop('disabled', false);

            if (!res.success) {
                notificacion('error', 'Error al obtener datos del grupo: ' + res.msg);
                return;
            }

            let g = res.grupo;

            // 1. Resetear y abrir Modal
            resetWizard();
            $('#modalNuevaNota').modal('show');
            $('#modalNuevaNota .modal-title').text('Complementar Trámite #' + g.nro_nota + ' - Agregar ' + rama);

            // 2. Pre-llenar hidden inputs
            $('#idGrupoExistente').val(g.id);

            // 3. Forzar selección de tipo (pero no mostrar cards)
            if (rama === 'MKT') {
                selectTaskType('MARKETING');
            } else {
                selectTaskType('FISCALIZACION');
            }

            // 4. Llenar campos
            $('#inpNroNota').val(g.nro_nota).prop('readonly', true);
            $('#inpAnio').val(g.anio).prop('disabled', true);

            // Seleccionar casino o plataforma según corresponda
            if (g.id_plataforma) {
                // Buscar la option de plataforma por data-es-plataforma y valor
                $('#selCasino option').each(function() {
                    if ($(this).data('es-plataforma') == '1' && $(this).val() == g.id_plataforma) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
                $('#hidCasinoId').val('');
                $('#hidPlataformaId').val(g.id_plataforma);
            } else {
                $('#selCasino').val(g.id_casino);
                $('#hidCasinoId').val(g.id_casino);
                $('#hidPlataformaId').val('');
            }
            $('#selCasino').trigger('change').prop('disabled', true);

            $('#inpTitulo').val(g.titulo).prop('readonly', true);

            if (rama === 'MKT') {
                // MKT siempre usa PUBLICIDAD
                $('#selTipoSolicitud').val('PUBLICIDAD').trigger('change');
            } else if (g.tipo_solicitud) {
                $('#selTipoSolicitud').val(g.tipo_solicitud).trigger('change').prop('disabled', true);
            }
            if (g.fecha_inicio_evento) $('#inpFechaInicio').val(g.fecha_inicio_evento);
            if (g.fecha_fin_evento) $('#inpFechaFin').val(g.fecha_fin_evento);

            // 5. Forzar navegación directa al Paso 1 (sin animaciones)
            console.log('FORCE NAV TO STEP 1');
            $('#step0Content').hide();
            $('#step1Content').show();
            currentStep = 1;
            updateStepperUI(1);
            updateButtonsUI(1);

        }).fail(function () {
            console.error('COMPLEMENTAR FAIL');
            $btn.html(originalIcon).prop('disabled', false);
            notificacion('error', 'Error de conexión');
        });
    }

    // =========================================================================
    // RELACIÓN ENTRE NOTAS (PADRE / HIJOS)
    // =========================================================================

    // --- Búsqueda de nota padre en Wizard (paso 2) ---
    var _buscarPadreTimer = null;
    $(document).on('keyup', '#inpBuscarNotaPadre', function () {
        var q = $(this).val().trim();
        if (q.length < 2) { $('#resultadosBusquedaPadre').hide().empty(); return; }
        clearTimeout(_buscarPadreTimer);
        _buscarPadreTimer = setTimeout(function () {
            $.get('/notas-unificadas/buscar-grupos', { q: q }, function (data) {
                var $res = $('#resultadosBusquedaPadre').empty();
                if (!data || data.length === 0) {
                    $res.html('<div class="list-group-item text-muted text-center">Sin resultados</div>').show();
                    return;
                }
                data.forEach(function (g) {
                    var ramas = (g.ramas || []).map(function (r) {
                        return r === 'MKT' ? '<span class="label label-primary" style="font-size:9px">MKT</span>' : '<span class="label label-success" style="font-size:9px">FISC</span>';
                    }).join(' ');
                    $res.append(
                        '<a href="#" class="list-group-item resultado-nota-padre" data-id="' + g.id + '" data-nro="' + g.nro_nota + '" data-anio="' + g.anio + '" data-titulo="' + (g.titulo || '') + '" data-casino="' + (g.casino || '') + '" style="padding:8px 12px;">' +
                        '<strong>' + g.nro_nota + '-' + g.anio + '</strong> ' + ramas +
                        '<br><small class="text-muted">' + (g.titulo || 'Sin título') + ' | ' + (g.casino || '') + '</small>' +
                        '</a>'
                    );
                });
                $res.show();
            });
        }, 300);
    });

    // Seleccionar nota padre desde búsqueda del wizard
    $(document).on('click', '.resultado-nota-padre', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        var nro = $(this).data('nro');
        var anio = $(this).data('anio');
        var titulo = $(this).data('titulo');
        var casino = $(this).data('casino');
        seleccionarNotaPadreWizard(id, nro + '-' + anio, titulo, casino);
    });

    function seleccionarNotaPadreWizard(id, nroAnio, titulo, casino) {
        $('#hidIdGrupoPadre').val(id);
        $('#lblNotaPadreNro').text(nroAnio);
        $('#lblNotaPadreTitulo').text(titulo || 'Sin título');
        $('#lblNotaPadreCasino').text(casino || '');
        $('#notaPadreSeleccionada').show();
        $('#buscadorNotaPadre').hide();
        $('#resultadosBusquedaPadre').hide().empty();
        $('#inpBuscarNotaPadre').val('');
    }

    // Quitar nota padre en wizard
    $(document).on('click', '#btnQuitarNotaPadreWizard', function () {
        $('#hidIdGrupoPadre').val('');
        $('#notaPadreSeleccionada').hide();
        $('#buscadorNotaPadre').show();
    });

    // Cerrar resultados al hacer click fuera
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#buscadorNotaPadre').length) {
            $('#resultadosBusquedaPadre').hide();
        }
        if (!$(e.target).closest('#buscadorVincularDetalle').length) {
            $('#resultadosVincularDetalle').hide();
        }
    });

    // --- Panel de relaciones en el Detalle ---
    function renderRelaciones(grupoPadre, gruposHijos, grupoId) {
        var html = '';

        // Nota padre
        if (grupoPadre) {
            html += '<div style="margin-bottom:10px;">' +
                '<small style="color:#9ca3af; text-transform:uppercase; font-weight:600; font-size:10px;">Nota Origen</small>' +
                '<div style="display:flex; align-items:center; gap:10px; background:#ede9fe; padding:10px 14px; border-radius:8px; border:1px solid #c4b5fd; margin-top:4px; cursor:pointer;" class="link-grupo-detalle" data-id="' + grupoPadre.id + '">' +
                '<i class="fa fa-level-up" style="color:#7c3aed; font-size:16px;"></i>' +
                '<div style="flex:1;">' +
                '<strong style="color:#5b21b6;">' + grupoPadre.nro_nota + '-' + grupoPadre.anio + '</strong>' +
                '<span style="color:#6b7280; margin-left:8px;">' + (grupoPadre.titulo || '') + '</span>' +
                '<small class="text-muted" style="margin-left:8px;">' + (grupoPadre.casino || '') + '</small>' +
                '</div>' +
                '<button type="button" class="btn btn-xs btn-danger btn-quitar-relacion-padre" data-grupo="' + grupoId + '" title="Desvincular"><i class="fa fa-times"></i></button>' +
                '</div></div>';
        }

        // Notas hijas
        if (gruposHijos && gruposHijos.length > 0) {
            html += '<div style="margin-bottom:6px;">' +
                '<small style="color:#9ca3af; text-transform:uppercase; font-weight:600; font-size:10px;">Notas Derivadas (' + gruposHijos.length + ')</small></div>';
            gruposHijos.forEach(function (gh) {
                html += '<div style="display:flex; align-items:center; gap:10px; background:#dbeafe; padding:8px 14px; border-radius:8px; border:1px solid #93c5fd; margin-bottom:6px; cursor:pointer;" class="link-grupo-detalle" data-id="' + gh.id + '">' +
                    '<i class="fa fa-level-down" style="color:#2563eb; font-size:14px;"></i>' +
                    '<div style="flex:1;">' +
                    '<strong style="color:#1e3a8a;">' + gh.nro_nota + '-' + gh.anio + '</strong>' +
                    '<span style="color:#6b7280; margin-left:8px;">' + (gh.titulo || '') + '</span>' +
                    '<small class="text-muted" style="margin-left:8px;">' + (gh.casino || '') + '</small>' +
                    '</div>' +
                    '<i class="fa fa-chevron-right" style="color:#93c5fd;"></i>' +
                    '</div>';
            });
        }

        if (!grupoPadre && (!gruposHijos || gruposHijos.length === 0)) {
            html = '<p class="text-muted text-center" style="padding:8px; margin:0;"><i class="fa fa-info-circle"></i> Sin notas relacionadas</p>';
        }

        $('#grupoRelacionPanel').html(html);
    }

    // Click en nota relacionada → navegar al detalle de ese grupo
    $(document).on('click', '.link-grupo-detalle', function (e) {
        if ($(e.target).closest('.btn-quitar-relacion-padre').length) return; // no navegar si se hizo click en X
        var id = $(this).data('id');
        abrirModalDetalleGrupo(id);
    });

    // Desvincular padre desde el detalle
    $(document).on('click', '.btn-quitar-relacion-padre', function (e) {
        e.stopPropagation();
        var grupoId = $(this).data('grupo');
        confirmar('¿Desvincular esta nota de su nota origen?', function () {
        $.post('/notas-unificadas/quitar-grupo-padre', {
            id_grupo: grupoId,
            _token: $('input[name="_token"]').val()
        }, function (res) {
            if (res.success) {
                notificacion('success', 'Relación eliminada');
                abrirModalDetalleGrupo(grupoId);
            } else {
                notificacion('error', res.msg || 'Error');
            }
        });
        }, { titulo: 'Desvincular nota', color: '#f0ad4e', textoBoton: 'Desvincular' });
    });

    // --- Vincular nota desde el detalle (botón "Vincular") ---
    var _currentGrupoIdVincular = null;

    $(document).on('click', '.btn-vincular-nota', function () {
        _currentGrupoIdVincular = currentGrupoIdAprobacion; // reuse the tracked grupo ID
        var $panel = $('#grupoRelacionPanel');
        // Check if search box already open
        if ($panel.find('#buscadorVincularDetalle').length) {
            $panel.find('#buscadorVincularDetalle').remove();
            $panel.css('overflow', '').closest('.panel').css('overflow', '');
            return;
        }
        $panel.css('overflow', 'visible').closest('.panel').css('overflow', 'visible');
        $panel.append(
            '<div id="buscadorVincularDetalle" style="margin-top:10px; position:relative;">' +
            '<input type="text" class="form-control" id="inpVincularDetalle" placeholder="Buscar nota por número o título..." autocomplete="off">' +
            '<div id="resultadosVincularDetalle" class="list-group" style="position:absolute; top:100%; left:0; right:0; z-index:99999; max-height:220px; overflow-y:auto; display:none; box-shadow:0 10px 20px rgba(0,0,0,0.15); background:#fff;"></div>' +
            '</div>'
        );
        $('#inpVincularDetalle').focus();
    });

    var _vincularDetalleTimer = null;
    $(document).on('keyup', '#inpVincularDetalle', function () {
        var q = $(this).val().trim();
        if (q.length < 2) { $('#resultadosVincularDetalle').hide().empty(); return; }
        clearTimeout(_vincularDetalleTimer);
        _vincularDetalleTimer = setTimeout(function () {
            $.get('/notas-unificadas/buscar-grupos', { q: q }, function (data) {
                var $res = $('#resultadosVincularDetalle').empty();
                if (!data || data.length === 0) {
                    $res.html('<div class="list-group-item text-muted text-center">Sin resultados</div>').show();
                    return;
                }
                data.forEach(function (g) {
                    // No mostrar el grupo actual
                    if (g.id == _currentGrupoIdVincular) return;
                    var ramas = (g.ramas || []).map(function (r) {
                        return r === 'MKT' ? '<span class="label label-primary" style="font-size:9px">MKT</span>' : '<span class="label label-success" style="font-size:9px">FISC</span>';
                    }).join(' ');
                    $res.append(
                        '<a href="#" class="list-group-item resultado-vincular-detalle" data-id="' + g.id + '" style="padding:8px 12px;">' +
                        '<strong>' + g.nro_nota + '-' + g.anio + '</strong> ' + ramas +
                        '<br><small class="text-muted">' + (g.titulo || 'Sin título') + ' | ' + (g.casino || '') + '</small>' +
                        '</a>'
                    );
                });
                $res.show();
            });
        }, 300);
    });

    // Seleccionar nota padre desde el detalle
    $(document).on('click', '.resultado-vincular-detalle', function (e) {
        e.preventDefault();
        var padreId = $(this).data('id');
        var grupoId = _currentGrupoIdVincular;
        if (!grupoId) { notificacion('error', 'Error: grupo no identificado'); return; }
        $.post('/notas-unificadas/asignar-grupo-padre', {
            id_grupo: grupoId,
            id_grupo_padre: padreId,
            _token: $('input[name="_token"]').val()
        }).done(function (res) {
            if (res.success) {
                notificacion('success', 'Nota vinculada correctamente');
                abrirModalDetalleGrupo(grupoId);
            } else {
                notificacion('error', res.msg || 'Error al vincular');
            }
        }).fail(function (xhr) {
            var msg = 'Error de conexión';
            if (xhr.responseJSON && xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
            notificacion('error', msg);
        });
    });

    // Fix Bootstrap 3: al cerrar un modal apilado, restaurar modal-open y el padding
    // en el body para que el modal de atrás mantenga el scroll habilitado.
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal.in').length > 0) {
            $('body').addClass('modal-open');
        }
    });

});
