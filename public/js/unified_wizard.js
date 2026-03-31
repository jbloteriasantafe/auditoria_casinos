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
    function actualizarTiposActivo() {
        let select = $('#selTipoActivo');
        select.empty();

        var casinoId = parseInt($('#selCasino').val()) || 0;
        if (casinoId >= (window.PLATAFORMA_ID_OFFSET || 100)) {
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

    // --- EVENT HANDLERS STEP 1 ---

    $('#selCasino').change(function () {
        actualizarTiposActivo();
        filtrarTipoEventoFISC();
    });

    function filtrarTipoEventoFISC() {
        var casinoId = parseInt($('#selCasino').val()) || 0;
        var esPlataforma = casinoId >= (window.PLATAFORMA_ID_OFFSET || 100);
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
        } else if ($('#selTipoTarea').val() === 'FISCALIZACION') {
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

        if (tarea === 'MARKETING') {
            $('.section-marketing').show();
            $('.section-fiscalizacion').hide();

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

            // Mostrar activos (salvo que sea Alta)
            $('#secActivosAsociados').slideDown();
        }
        updateProgressBar();
    }

    $('#selTipoTarea').change(function () {
        updateSectionVisibility();
    });

    $('#selTipoSolicitud').on('change input', function () {
        if ($('#selTipoTarea').val() !== 'MARKETING') return;
        // MKT siempre es PUBLICIDAD — ocultar activos
        $('#secActivosAsociados').slideUp();
        activos = [];
        $('#tablaActivos tbody').empty();
        updateProgressBar();
    });

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
        let id_casino = $('#selCasino').val();

        if (val.length < 1) {
            $('#resultadosBusqueda').hide();
            return;
        }

        timeout = setTimeout(function () {
            $.get('/notas-unificadas/buscar-activos', { q: val, tipo: tipo, id_casino: id_casino }, function (data) {
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
    function updateTableHeaders(tipo, sampleData) {
        let thead = $('#tablaActivos thead tr');
        thead.empty();
        thead.append('<th>Tipo</th>');
        if (sampleData) {
            Object.keys(sampleData).forEach(k => {
                thead.append(`<th>${k}</th>`);
            });
        } else {
            thead.append('<th>Descripción</th>');
        }
        thead.append('<th>Acción</th>');
    }

    function agregarFila(tipo, id, texto, data) {
        if (activos.some(a => a.id == id && a.tipo == tipo)) {
            return false;
        }

        if ($('#tablaActivos tbody tr').length === 0) {
            updateTableHeaders(tipo, data);
        }

        activos.push({ tipo: tipo, id: id });

        let row = `<tr><td>${tipo}</td>`;
        if (Object.keys(data).length > 0) {
            Object.values(data).forEach(v => {
                row += `<td>${v}</td>`;
            });
        } else {
            row += `<td>${texto}</td>`;
        }
        row += `<td><button class='btn btn-xs btn-danger btn-borrar-activo'>X</button></td></tr>`;

        $('#tablaActivos tbody').append(row);
        return true;
    }

    $('#btnAgregarActivo').click(function () {
        let tipo = $('#selTipoActivo').val();
        let texto = $('#inpIdActivo').val();
        let id = $('#hidIdActivo').val();
        let data = $('#inpIdActivo').data('selected-data') || {};

        if (!id && texto.length > 0) id = texto;

        if (id) {
            if (tipo == 'ISLA') {
                mostrarCargando(true);
                $.get('/notas-unificadas/obtener-activos-isla/' + id, function (mtms) {
                    mostrarCargando(false);
                    if (mtms.length > 0) {
                        let addedCount = 0;
                        mtms.forEach(function (m) {
                            if (agregarFila('MTM', m.id, m.text, m.data)) {
                                addedCount++;
                            }
                        });
                        notificacion('success', 'Se agregaron ' + addedCount + ' máquinas.');
                    } else {
                        notificacion('error', 'La isla no tiene máquinas activas.');
                    }
                }).fail(function () { mostrarCargando(false); notificacion('error', 'Error al obtener isla'); });
            } else if (tipo == 'BINGO') {
                let data = { 'Descripción': 'Aplica a todas las sesiones / actividad general de Bingo' };
                agregarFila(tipo, id, texto, data);
                $('#inpIdActivo').val('');
            } else {
                if (!agregarFila(tipo, id, texto, data)) {
                    notificacion('warning', 'Este activo ya está en la lista.');
                } else {
                    $('#inpIdActivo').val('');
                    $('#inpIdActivo').data('selected-data', null);
                    $('#hidIdActivo').val('');
                }
            }
        }
    });

    $('#tablaActivos').on('click', '.btn-borrar-activo', function () {
        // Note: For full robustness, remove from 'activos' array logic needs to be exact by index or id/type match.
        // For prototype we just visual remove, array sync might be needed if re-adding.
        // Simple sync:
        let tr = $(this).closest('tr');
        // This is weak, but sufficient for Mvp if we just clear array on Reset. 
        // Better:
        // activos = activos.filter(...) - requires storing ID in TR.
        tr.remove();
        // TODO: Sync array properly if strict validation needed.
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
            id_casino: $('select[name="id_casino"]').val(),
            tipo_tarea: tipoTarea,
            tipo_solicitud: $('#selTipoSolicitud').val(),
            id_tipo_evento: isMkt ? $('#selTipoEventoMKT').val() : $('#selTipoEventoFISC').val(),
            id_categoria: isMkt ? $('#selCategoriaMKT').val() : $('#selCategoriaFISC').val(),
            fecha_inicio_evento: $('input[name="fecha_inicio_evento"]').val(),
            fecha_fin_evento: $('input[name="fecha_fin_evento"]').val(),
            fecha_referencia: $('input[name="fecha_referencia"]').val() || '',
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

        let nextStep = currentStep + 1;
        goToStep(nextStep);
    };

    function uploadAdjuntos(callback) {
        let formData = new FormData();
        formData.append('_token', $('input[name="_token"]').val());

        // Retrieve IDs injected by crearNotaBorrador
        let id_nota_mkt = $('input[name="id_nota_mkt"]').val();
        let id_nota_fisc = $('input[name="id_nota_fisc"]').val();

        console.log('UPLOAD DEBUG: id_nota_mkt=', id_nota_mkt, ', id_nota_fisc=', id_nota_fisc);

        formData.append('id_nota_mkt', id_nota_mkt);
        formData.append('id_nota_fisc', id_nota_fisc);

        // ===========================================
        // ADJUNTOS MKT (Marketing)
        // ===========================================
        let solicitudMkt = $('#adjuntoSolicitud')[0]?.files[0];
        if (solicitudMkt) formData.append('adjuntoSolicitud', solicitudMkt);

        let diseno = $('#adjuntoDisenio')[0]?.files[0];
        if (diseno) formData.append('adjuntoDisenio', diseno);

        let bases = $('#adjuntoBases')[0]?.files[0];
        if (bases) formData.append('adjuntoBases', bases);

        let informeMkt = $('#adjuntoInformeMkt')[0]?.files[0];
        if (informeMkt) formData.append('adjuntoInformeMkt', informeMkt);

        // ===========================================
        // ADJUNTOS FISC (Fiscalización)
        // ===========================================
        let solicitudFisc = $('#adjuntoSolicitudFisc')[0]?.files[0];
        if (solicitudFisc) formData.append('adjuntoSolicitudFisc', solicitudFisc);

        let varios = $('#adjuntoVarios')[0]?.files[0];
        if (varios) formData.append('adjuntoVarios', varios);

        let informeFisc = $('#adjuntoInformeFisc')[0]?.files[0];
        if (informeFisc) formData.append('adjuntoInformeFisc', informeFisc);

        // UX: Show loading on button
        let btn = $('.btn-wizard-next');
        let originalText = btn.text();
        btn.attr('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');

        $.ajax({
            url: '/notas-unificadas/guardar-adjuntos',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                console.log("Upload success", res);
                btn.attr('disabled', false).text(originalText);
                callback();
            },
            error: function (err) {
                console.error("Upload error", err);
                notificacion('error', 'Error al subir los adjuntos: ' + (err.responseJSON?.msg || 'Error desconocido'));
                btn.attr('disabled', false).text(originalText);
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
            id_casino: $('select[name="id_casino"]').val(),
            tipo_tarea: $('#selTipoTarea').val(),
            tipo_solicitud: $('#selTipoSolicitud').val(),

            // New Inputs for Split MKT/FISC
            id_tipo_evento_mkt: $('select[name="id_tipo_evento_mkt"]').val(),
            id_categoria_mkt: $('select[name="id_categoria_mkt"]').val(),
            id_tipo_evento_fisc: $('select[name="id_tipo_evento_fisc"]').val(),

            fecha_pretendida_aprobacion: $('#inpFechaPretendida').val() || '',
            fecha_inicio_evento: $('#inpFechaInicio').val(),
            fecha_fin_evento: $('#inpFechaFin').val(),
            fecha_referencia: $('input[name="fecha_referencia"]').val() || '',
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
        id_casino: '',
        rama: '',
        estado: '',
        fecha_desde: '',
        fecha_hasta: '',
        page: 1,
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

    // 2. Filters (all selects + date inputs with class .filtro-tabla)
    $(document).on('change', '.filtro-tabla', function () {
        gridState.id_casino   = $('#selFiltroCasino').val();
        gridState.rama        = $('#selFiltroRama').val();
        gridState.estado      = $('#selFiltroEstado').val();
        gridState.fecha_desde = $('#inpFechaDesde').val();
        gridState.fecha_hasta = $('#inpFechaHasta').val();
        gridState.page = 1;
        ajaxLoadTable();
    });

    // 3. Sorting
    $(document).on('click', '.sortable', function () {
        let field = $(this).data('sort');
        if (gridState.sort_by === field) {
            gridState.order = (gridState.order === 'desc') ? 'asc' : 'desc';
        } else {
            gridState.sort_by = field;
            gridState.order = 'desc'; // Default new sort
        }
        ajaxLoadTable();
    });

    // 4. Pagination
    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        let url = $(this).attr('href');
        if (url) {
            let page = url.split('page=')[1];
            gridState.page = page;
            ajaxLoadTable();
        }
    });

    // Core Load Function
    function ajaxLoadTable() {
        $('#divTablaNotas').css('opacity', '0.5'); // Skelethon effect

        $.get('/notas-unificadas', gridState, function (html) {
            $('#divTablaNotas').html(html).css('opacity', '1');

            // Re-render sort icons
            $('.sortable').find('i').removeClass('fa-sort-asc fa-sort-desc').addClass('fa-sort');
            let activeHeader = $(`.sortable[data-sort="${gridState.sort_by}"]`);
            let icon = (gridState.order === 'asc') ? 'fa-sort-asc' : 'fa-sort-desc';
            activeHeader.find('i').removeClass('fa-sort').addClass(icon);
        }).fail(function () {
            notificacion('error', 'Error al cargar listado.');
            $('#divTablaNotas').css('opacity', '1');
        });
    }

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
            td.append('<span class="label label-info" style="margin-right:2px;">' + estados[i] + '</span>');
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

        // Determinar opciones permitidas según rol (desde OPCIONES_ESTADO y TRANSICIONES_ESTADO)
        var opciones = [];
        var allEstados = (window.OPCIONES_ESTADO || []).map(function(e) { return e.descripcion; });
        var trans = window.TRANSICIONES_ESTADO || {};

        if (window.NIVEL_ESTADO === 'admin') {
            opciones = allEstados;
        } else {
            var nivel = window.NIVEL_ESTADO || 'regular';
            var mapa = trans[nivel] || {};
            if (!mapa[current]) { notificacion('warning', 'No puede cambiar este estado'); return; }
            opciones = mapa[current];
        }

        span.addClass('editing');

        let wrapper = $('<span class="estado-edit-wrap" style="white-space:nowrap;"></span>');
        let select = $('<select class="form-control input-sm" style="width:auto; display:inline-block; padding: 2px; font-size: 11px;"></select>');
        opciones.forEach(function(op) { select.append('<option value="' + op + '">' + op + '</option>'); });
        select.val(opciones[0]);
        let btnConfirm = $('<button class="btn btn-success btn-xs" style="margin-left:3px;" title="Confirmar"><i class="fa fa-check"></i></button>');
        let btnCancel = $('<button class="btn btn-danger btn-xs" style="margin-left:2px;" title="Cancelar"><i class="fa fa-times"></i></button>');
        wrapper.append(select).append(btnConfirm).append(btnCancel);
        span.replaceWith(wrapper);
        select.focus();

        function restoreBadge(text) {
            let color = getBalanceColor(text);
            let newSpan = $('<span class="label label-' + color + ' estado-badge" data-id="' + id + '" data-toggle="popover" data-trigger="hover">' + text + '</span>');
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
            gridState.per_page = 50;
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
            gridState.per_page = 10;
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

        if (filter !== 'reset') {
            $(this).removeClass('btn-default').addClass('btn-primary');
        }

        // Limpiar todo primero
        gridState.q = '';
        gridState.quick_filter = '';
        gridState.page = 1;
        $('#inpBusqueda').val('');

        if (filter === 'hoy') {
            gridState.quick_filter = 'hoy';
        } else if (filter === 'proximos') {
            gridState.quick_filter = 'proximos';
        } else if (filter === 'por_vencer') {
            gridState.quick_filter = 'por_vencer';
        } else if (filter === 'reset') {
            // Reset completo de todos los filtros
            gridState.id_casino = '';
            gridState.rama = '';
            gridState.estado = '';
            gridState.fecha_desde = '';
            gridState.fecha_hasta = '';
            gridState.quick_filter = '';
            $('#selFiltroCasino').val('');
            $('#selFiltroRama').val('');
            $('#selFiltroEstado').val('');
            $('#inpFechaDesde').val('');
            $('#inpFechaHasta').val('');
        }

        ajaxLoadTable();
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
        $('#modalNuevaNota').modal('hide');
        notificacion('success', 'Trámite finalizado exitosamente.');
        setTimeout(() => location.reload(), 1000);
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
        let tipoTarea = $('#selTipoTarea').val();
        let tipoSolicitud = $('#selTipoSolicitud').val();
        let titulo = $('#inpTitulo').val();
        let anio = $('#inpAnio').val();
        let casino = $('#selCasino option:selected').text();
        let nro_nota = $('#inpNroNota').val();

        let html = `
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default" style="border:none; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <div class="panel-body">
                            <h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px;">Trámite</h5>
                            <p style="font-size:16px; font-weight:600; color:#475569;">${tipoTarea || '-'} / ${tipoSolicitud || '-'}</p>
                            
                            <h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px; margin-top:15px;">Nota Referencia</h5>
                            <p style="font-size:16px; font-weight:bold; color:#3b82f6;">${nro_nota || 'S/N'}-${anio || '-'}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                     <div class="panel panel-default" style="border:none; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <div class="panel-body">
                            <h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px;">Casino</h5>
                            <p style="font-size:16px; font-weight:600; color:#475569;">${casino || '-'}</p>
                            
                            <h5 style="color:#94a3b8; font-weight:bold; text-transform:uppercase; font-size:11px; margin-top:15px;">Título</h5>
                            <p style="font-size:15px; color:#64748b;">${titulo || 'Sin Título'}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add Specifics
        let inicio = $('#inpFechaInicio').val();
        let fin = $('#inpFechaFin').val();
        if (inicio || fin) {
            html += `
                <div class="alert alert-info" style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                    <strong><i class="fa fa-calendar"></i> Fechas:</strong>
                    <span style="font-size:13px; margin-left:10px;">Del <b>${inicio || '—'}</b> al <b>${fin || '—'}</b></span>
                </div>
             `;
        }

        $('#step4Content').html('<h4 class="text-center" style="margin-bottom:20px; font-weight:700; color:#475569;">Resumen de la Solicitud</h4>' + html + '<div class="alert alert-success text-center" style="margin-top:-5px; margin-bottom:20px; padding:8px;"><i class="fa fa-info-circle"></i> Verifique que todos los datos sean correctos antes de confirmar.</div>');
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

        // Clear previous files
        $('#frmAgregarAdjuntos input[type="file"]').val('');

        // Load history timeline
        $('#timelineAdjuntos').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando historial...</p>');

        $.get('/notas-unificadas/historial-adjuntos/' + notaId, function (res) {
            console.log('DEBUG historial-adjuntos response:', res);
            console.log('DEBUG adjuntos:', res.adjuntos);

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
                if (res.success) {
                    notificacion('success', res.msg);
                    $('#modalAgregarAdjuntos').modal('hide');
                    // Refresh table
                    if (window.refreshTable) window.refreshTable();
                    else location.reload();
                } else {
                    notificacion('error', res.msg || 'Error al subir adjuntos');
                }
            },
            error: function (err) {
                btn.attr('disabled', false).html('<i class="fa fa-upload"></i> Subir Adjuntos');
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
                        '<span class="label label-' + getEstadoClass(res.mkt.estado) + '">Estado: ' + res.mkt.estado + '</span>' +
                        '</li>';
                }
                if (res.fisc && window.NIVEL_ESTADO !== 'funcionario') {
                    resumenHtml += '<li style="padding:8px; background:#d1fae5; border-radius:6px">' +
                        '<i class="fa fa-gavel text-success"></i> <strong style="color:#064e3b;">Fiscalización:</strong> ' +
                        '<span class="label label-' + getEstadoClass(res.fisc.estado) + '">Estado: ' + res.fisc.estado + '</span>' +
                        '</li>';
                }
                resumenHtml += '</ul>';
                $('#grupoResumenPanel').html(resumenHtml);

                // Notas Relacionadas
                renderRelaciones(res.grupo_padre, res.grupos_hijos || [], res.grupo.id);

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

                // Tab FISC (oculto para funcionarios)
                if (res.fisc && window.NIVEL_ESTADO !== 'funcionario') {
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
        // Funcionario no puede ver notas FISC
        if (window.NIVEL_ESTADO === 'funcionario' && tipoRama === 'FISC') {
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

            html += '<li style="padding:8px 10px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between;">' +
                '<div>' +
                '<i class="fa ' + ramaIcon + '" style="color:' + ramaColor + '"></i> ' +
                '<span class="label" style="background:' + ramaColor + '; color:white;">' + ramaLabel + '</span> ' +
                '<strong>' + na.nombre_original + '</strong>' +
                '<small class="text-muted" style="margin-left:8px;">' + (na.created_at || '') + '</small>' +
                '</div>' +
                '<div>' +
                '<a href="/notas-unificadas/nota-aprobacion/visualizar/' + na.id + '" target="_blank" class="btn btn-xs btn-info" title="Ver"><i class="fa fa-eye"></i></a> ' +
                '<a href="/notas-unificadas/nota-aprobacion/descargar/' + na.id + '" class="btn btn-xs btn-default" title="Descargar"><i class="fa fa-download"></i></a> ' +
                (window.PUEDE_ELIMINAR ? '<button class="btn btn-xs btn-danger btn-eliminar-nota-aprobacion" data-id="' + na.id + '" title="Eliminar"><i class="fa fa-trash"></i></button>' : '') +
                '</div>' +
                '</li>';
        });
        html += '</ul>';
        panel.html(html);
    }

    // Selección de rama con botonera tipo card
    $(document).on('click', '.btn-rama-aprobacion', function () {
        // Deseleccionar todos
        $('.btn-rama-aprobacion').css({ border: '2px solid #e5e7eb', background: '#f8fafc' });
        // Marcar seleccionado
        var rama = $(this).data('rama');
        var borderColor = rama === 'MKT' ? '#3b82f6' : '#10b981';
        var bgColor = rama === 'MKT' ? '#eff6ff' : '#ecfdf5';
        $(this).css({ border: '2px solid ' + borderColor, background: bgColor });
        $('#aprobacionTipoRama').val(rama);
        // Mostrar input de archivos
        $('#aprobacionArchivoWrap').slideDown(200);
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
            // Reset botonera visual
            $('.btn-rama-aprobacion').css({ border: '2px solid #e5e7eb', background: '#f8fafc' });
            $('#aprobacionArchivoWrap').hide();
            $('#modalNotaAprobacion').modal('show');
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
        if (!$('#inputAprobacionArchivos').val()) {
            notificacion('error', 'Seleccione al menos un archivo');
            return;
        }

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Subiendo...');

        $.ajax({
            url: '/notas-unificadas/nota-aprobacion/subir',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success) {
                    notificacion('exito', res.msg);
                    $('#modalNotaAprobacion').modal('hide');
                    // Recargar panel de aprobación
                    recargarNotasAprobacion(currentGrupoIdAprobacion);
                } else {
                    notificacion('error', res.msg || 'Error al subir');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.msg : 'Error de conexión';
                notificacion('error', msg);
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Subir');
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
        var adjuntosHtml = renderAdjuntos(nota.adjuntos, nota.id, nota.tipo_rama);
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
        html = sr(html, '{{fecha_referencia}}', nota.fecha_referencia || 'N/A');
        html = sr(html, '{{estado}}', nota.estado || 'INGRESADO');
        html = sr(html, '{{estadoClass}}', getEstadoClass(nota.estado));
        html = sr(html, '{{created_at}}', nota.created_at || 'N/A');
        html = sr(html, '{{id_casino}}', nota.id_casino || '');
        html = sr(html, '{{adjuntosHtml}}', adjuntosHtml);
        html = sr(html, '{{activosHtml}}', activosHtml);
        html = sr(html, '{{comentariosHtml}}', comentariosHtml);
        html = sr(html, '{{historialHtml}}', historialHtml);

        // Si MKT, ocultar panel de activos y fecha referencia
        if (nota.tipo_rama === 'MKT') {
            var $tmp = $('<div>').html(html);
            $tmp.find('.panel-activos-wrap').remove();
            $tmp.find('.row-fecha-referencia').remove();
            html = $tmp.html();
        }
        // Si FISC, ocultar fecha pretendida y categoría
        if (nota.tipo_rama !== 'MKT') {
            var $tmp2 = $('<div>').html(html);
            $tmp2.find('.row-fecha-pretendida').remove();
            $tmp2.find('.row-categoria').remove();
            html = $tmp2.html();
        }

        // Solo admin puede editar datos de nota
        if (window.NIVEL_ESTADO !== 'admin') {
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

    // Helper para clase de estado (desde DB via OPCIONES_ESTADO)
    function getEstadoClass(estado) {
        if (!estado) return 'default';
        var opciones = window.OPCIONES_ESTADO || [];
        for (var i = 0; i < opciones.length; i++) {
            if (opciones[i].descripcion === estado) return opciones[i].color;
        }
        return 'default';
    }

    // Renderizar adjuntos
    function renderAdjuntos(adjuntos, notaId, tipoRama) {
        if (!adjuntos) return '<p class="text-muted">No hay adjuntos</p>';

        let campos = tipoRama === 'MKT'
            ? [['solicitud', 'Solicitud', 'fa-file-pdf-o'], ['diseno', 'Diseño', ''], ['bases', 'Bases', 'fa-file-text-o'], ['informe', 'Informe', '']]
            : [['solicitud', 'Solicitud', 'fa-file-pdf-o'], ['varios', 'Archivos Varios', 'fa-archive'], ['informe', 'Informe', 'fa-clipboard']];

        let html = '';
        campos.forEach(function (c) {
            let key = c[0], label = c[1], icon = c[2];
            let adj = adjuntos[key];

            if (adj && adj.existe) {
                html += '<div style="display:flex; align-items:center; padding:8px; background:#d1fae5; border-radius:6px; margin-bottom:6px">' +
                    '<i class="fa ' + icon + ' text-success" style="margin-right:8px; flex-shrink: 0;"></i>' +
                    '<span style="flex:1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 10px;" title="' + adj.nombre + '">' + label + ': <strong>' + adj.nombre + '</strong></span>' +
                    '<div style="flex-shrink: 0; display: flex; gap: 5px;">' +
                        // FIX: Usar ruta controlada para forzar inline en vez de download
                        '<a href="/notas-unificadas/visualizar/' + notaId + '/' + key + '" target="_blank" class="btn btn-xs btn-info" style="margin-right: 5px;" title="Ver"><i class="fa fa-eye"></i></a> ' +
                        (window.PUEDE_ELIMINAR ? '<button class="btn btn-xs btn-danger btn-eliminar-adjunto" data-id="' + notaId + '" data-campo="path_' + key + '" title="Eliminar"><i class="fa fa-trash"></i></button>' : '') +
                    '</div>' +
                    '</div>';
            } else {
                html += '<div style="display:flex; align-items:center; padding:8px; background:#fee2e2; border-radius:6px; margin-bottom:6px">' +
                    '<i class="fa ' + icon + ' text-muted" style="margin-right:8px"></i>' +
                    '<span style="flex:1; color:#9ca3af">' + label + ': <em>No cargado</em></span>' +
                    '</div>';
            }
        });

        return html;
    }

    // Renderizar activos como lista/tabla
    function renderActivos(activos) {
        if (!activos || activos.length === 0) return '<p class="text-muted text-center" style="padding:10px"><i class="fa fa-info-circle"></i> No hay activos asociados a esta nota</p>';

        let html = '<ul style="list-style:none; padding:0; margin:0;">';
        activos.forEach(function (act, idx) {
            let tipoLabel = act.tipo_activo || 'ISLA';
            let icon = 'fa-gamepad';
            let badgeBg = '#17a2b8';
            if (tipoLabel === 'MTM') { icon = 'fa-desktop'; badgeBg = '#8b5cf6'; }
            else if (tipoLabel === 'MESA') { icon = 'fa-table'; badgeBg = '#28a745'; }
            else if (tipoLabel === 'BINGO') { icon = 'fa-th'; badgeBg = '#e67e22'; }

            let bgRow = idx % 2 === 0 ? '#fafafa' : '#fff';
            let nro = act.nro_admin || act.id_activo || 'N/A';

            let detalle = [];
            if (act.marca) detalle.push(act.marca);
            if (act.nro_isla) detalle.push('Isla ' + act.nro_isla);
            let detalleStr = detalle.length > 0 ? ' <small style="color:#9ca3af">' + detalle.join(' | ') + '</small>' : '';

            html += '<li data-activo-id="' + act.id + '" style="position:relative; padding:7px 50px 7px 10px; border-bottom:1px solid #f0f0f0; background:' + bgRow + ';">' +
                '<span style="background:' + badgeBg + '; color:#fff; font-size:9px; font-weight:600; padding:2px 6px; border-radius:3px; margin-right:6px;"><i class="fa ' + icon + '"></i> ' + tipoLabel + '</span>' +
                '<b style="font-size:13px;">' + nro + '</b>' + detalleStr +
                (window.PUEDE_ELIMINAR ? '<span style="position:absolute; right:8px; top:50%; transform:translateY(-50%);">' +
                    '<button class="btn btn-xs btn-danger btn-ask-remove-activo" data-activo-id="' + act.id + '" title="Eliminar" style="padding:2px 7px; font-size:11px;"><i class="fa fa-trash"></i></button>' +
                    '<span class="confirm-remove-activo" style="display:none;">' +
                        '<button class="btn btn-xs btn-success btn-confirm-remove-activo" data-activo-id="' + act.id + '" title="Confirmar" style="padding:2px 7px; font-size:11px;"><i class="fa fa-check"></i></button> ' +
                        '<button class="btn btn-xs btn-default btn-cancel-remove-activo" title="Cancelar" style="padding:2px 7px; font-size:11px;"><i class="fa fa-times"></i></button>' +
                    '</span>' +
                '</span>' : '') +
                '</li>';
        });
        html += '</ul>';
        return html;
    }

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
            let iconClass = 'fa-circle text-muted';
            if (m.accion.includes('INGRES')) iconClass = 'fa-plus-circle text-success';
            else if (m.accion.includes('MODIFIC')) iconClass = 'fa-exchange-alt text-info';
            else if (m.accion.includes('EDIT')) iconClass = 'fa-pencil-alt text-info';
            else if (m.accion.includes('ADJUNTO')) iconClass = 'fa-paperclip text-warning';
            else if (m.accion.includes('COMENT')) iconClass = 'fa-comment text-primary';
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
                                $contenedor.find('.adjuntos-lista').html(renderAdjuntos(nota.adjuntos, nota.id, nota.tipo_rama));
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
        var resultsDiv = form.find('.det-resultados-busqueda');

        form.find('.det-hid-activo').val('');

        if (val.length < 1) { resultsDiv.hide().empty(); return; }

        clearTimeout(detActivoTimeout);
        detActivoTimeout = setTimeout(function () {
            $.get('/notas-unificadas/buscar-activos', { q: val, tipo: tipo, id_casino: idCasino }, function (data) {
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
    $(document).on('click', '.det-btn-agregar-activo', function () {
        var form = $(this).closest('.activos-add-form');
        var tipo = form.find('.det-sel-tipo-activo').val();
        var id = form.find('.det-hid-activo').val() || form.find('.det-inp-activo').val();
        var texto = form.find('.det-inp-activo').val();

        if (!id) { notificacion('warning', 'Busque y seleccione un activo.'); return; }

        // Evitar duplicados en pendientes
        for (var i = 0; i < activosPendientes.length; i++) {
            if (activosPendientes[i].tipo === tipo && activosPendientes[i].id == id) {
                notificacion('warning', 'Ya está en la lista pendiente.');
                return;
            }
        }

        activosPendientes.push({ tipo: tipo, id: id, texto: texto });

        // Render fila pendiente
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
                '<span style="flex:1; font-size:12px;">' + texto + '</span>' +
                '<button class="btn btn-xs btn-default det-btn-quitar-pendiente" data-idx="' + idx + '"><i class="fa fa-times"></i></button>' +
            '</div>'
        );

        form.find('.det-inp-activo').val('');
        form.find('.det-hid-activo').val('');
        actualizarContadorPendientes(form);
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
        var li = $(this).closest('li');
        var lista = li.closest('.activos-lista-detalle');

        $.ajax({
            url: '/notas-unificadas/activo/' + activoId,
            type: 'DELETE',
            data: { _token: $('input[name="_token"]').first().val() },
            success: function (res) {
                if (res.success) {
                    li.fadeOut(200, function () {
                        li.remove();
                        lista.html(renderActivos(res.activos || []));
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

            $('#frmAgregarAdjuntos input[type="file"]').val('');
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
            } else if (field === 'fecha_inicio' || field === 'fecha_fin' || field === 'fecha_pretendida_aprobacion') {
                inputHtml = '<input type="date" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" style="font-size: 13px;">';
            } else if (field === 'id_tipo_evento') {
                var opciones = window.OPCIONES_TIPO_EVENTO[tipoRama] || [];
                var selectedId = $span.data('value') || '';
                var idCasino = parseInt($panel.closest('.nota-detalle-content').data('id-casino')) || 0;
                var esPlataforma = idCasino >= (window.PLATAFORMA_ID_OFFSET || 100);
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
            } else if (field === 'estado') {
                var estadoActual = $span.data('value') || '';
                var opciones = window.OPCIONES_ESTADO || [];
                inputHtml = '<select class="form-control edit-input" data-field="' + field + '" style="font-size: 13px;">';
                opciones.forEach(function(e) { inputHtml += '<option value="' + e.descripcion + '"' + (e.descripcion === estadoActual ? ' selected' : '') + '>' + e.descripcion + '</option>'; });
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
            let value = $(this).val();
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
                        var $editable = $input.prev('.editable');
                        var displayValue;
                        if (field === 'estado') {
                            var estadoVal = $input.val();
                            $editable.data('value', estadoVal);
                            $editable.html('<span class="label label-' + getEstadoClass(estadoVal) + '">' + estadoVal + '</span>').show();
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
                } else {
                    notificacion('error', res.msg || 'Error al guardar');
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Guardar');
                }
            },
            error: function (xhr) {
                notificacion('error', 'Error de conexión');
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
            $('#selCasino').val(g.id_casino).trigger('change').prop('disabled', true);
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

});
