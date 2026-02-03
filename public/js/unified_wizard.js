$(document).ready(function () {
    let activos = [];

    // --- INIT & HELPERS ---
    function actualizarTiposActivo() {
        let option = $('#selCasino option:selected');
        let nombre = (option.data('nombre') || '').toUpperCase();
        let select = $('#selTipoActivo');
        select.empty();

        if (nombre.includes('ONLINE') || nombre.includes('BPLAY') || nombre.includes('CITYCENTER')) {
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

            // Always show assets for Fiscalizacion? Assuming yes
            $('#secActivosAsociados').slideDown();
        }
        updateProgressBar();
    }

    $('#selTipoTarea').change(function () {
        updateSectionVisibility();
    });

    $('#selTipoSolicitud').on('change input', function () {
        // Only relevant if visible
        if ($('#selTipoTarea').val() !== 'MARKETING') return;

        let val = $(this).val();
        if (val === 'PUBLICIDAD') {
            $('#secActivosAsociados').slideUp();
            $('#helpTipoSolicitud').html('<i class="fa fa-info-circle"></i> Solo se activará el circuito de Marketing.');
            activos = [];
            $('#tablaActivos tbody').empty();
        } else {
            $('#secActivosAsociados').slideDown();
            $('#helpTipoSolicitud').html('<i class="fa fa-info-circle"></i> Se habilitará la carga de activos.');
        }
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

        let formData = {
            nro_nota: $('input[name="nro_nota"]').val(),
            anio: $('input[name="anio"]').val(),
            titulo: $('input[name="titulo"]').val(),
            id_casino: $('select[name="id_casino"]').val(),
            tipo_tarea: $('#selTipoTarea').val(),
            tipo_solicitud: $('#selTipoSolicitud').val(),
            id_tipo_evento: $('#selTipoEventoLegacy').val(),
            id_categoria: $('#selCategoriaLegacy').val(),
            fecha_inicio_evento: $('#inpFechaInicioEvento').val(),
            fecha_fin_evento: $('#inpFechaFinEvento').val(),
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
    $('[data-toggle="tooltip"]').tooltip();

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
        if (step === 2) $('#step2Content').fadeIn();

        if (step === 3) {
            // STEP 3: ATTACHMENTS (Create Draft)
            // Only trigger creation if we are coming from step 2
            if (currentStep === 2) {
                crearNotaBorrador(() => {
                    // Show correct attachment sections based on task type
                    let tipoTarea = $('#selTipoTarea').val(); // MARKETING or FISCALIZACION
                    let tipoSolicitud = $('#selTipoSolicitud').val(); // PUBLICIDAD or EVENTO
                    let isComplementing = $('#idGrupoExistente').val() !== '';

                    console.log('STEP 3: tipoTarea=', tipoTarea, ', tipoSolicitud=', tipoSolicitud, ', isComplementing=', isComplementing);

                    // Reset all sections
                    $('.section-marketing').hide();
                    $('.section-fiscalizacion').hide();
                    $('.section-informe').hide();

                    // Show sections based on task type
                    if (isComplementing) {
                        // STRICT MODE: If complementing, ONLY show the specific branch being added
                        if (tipoTarea === 'MARKETING') {
                            $('.section-marketing').show();
                        } else if (tipoTarea === 'FISCALIZACION') {
                            $('.section-fiscalizacion').show();
                        }
                    } else {
                        // NORMAL FLOW: Evento activates both
                        if (tipoTarea === 'MARKETING') {
                            $('.section-marketing').show();
                            if (tipoSolicitud === 'EVENTO') {
                                // EVENTO crea MKT + FISC, mostrar ambas secciones
                                $('.section-fiscalizacion').show();
                            }
                        } else if (tipoTarea === 'FISCALIZACION') {
                            $('.section-fiscalizacion').show();
                        }
                    }

                    $('#step3Content').fadeIn();
                    currentStep = 3;
                    updateStepperUI(3);
                    updateButtonsUI(3);
                });
                return; // Wait for callback
            } else {
                $('#step3Content').fadeIn();
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

        // Hide all steps, show Step 0
        $('#step1Content, #step2Content, #step3Content, #step4Content').hide();
        $('#step0Content').show();

        // Clear form
        $('#frmNuevaNota')[0].reset();

        // Clear hidden inputs
        $('#idGrupoExistente').val('');
        $('#hidIdActivo').val('');
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
            id_categoria_fisc: $('select[name="id_categoria_fisc"]').val(),

            fecha_inicio_evento: $('#inpFechaInicio').val(),
            fecha_fin_evento: $('#inpFechaFin').val(),
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
        });
    }

    // --- UI HELPERS ---

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

    function mostrarCargando(show) {
        // Implementar un overlay global si se desea, por ahora no bloqueante
    }
    // --- ADVANCED LIST LOGIC (Hyper-Modern) ---

    // State
    let gridState = {
        q: '',
        id_casino: '',
        tipo: '',
        page: 1,
        sort_by: 'id',
        order: 'desc'
    };

    // 1. Debounced Search
    let searchTimeout;
    $('#inpBusqueda').on('input', function () {
        clearTimeout(searchTimeout);
        gridState.q = $(this).val();
        gridState.page = 1; // Reset to page 1
        searchTimeout = setTimeout(ajaxLoadTable, 300);
    });

    // 2. Filters
    $('#selFiltroCasino, #selFiltroTipo').change(function () {
        gridState.id_casino = $('#selFiltroCasino').val();
        gridState.tipo = $('#selFiltroTipo').val();
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

    // --- DRAWER LOGIC ---
    $(document).on('click', '.btn-ver-nota', function () {
        let id = $(this).data('id');
        openDrawer(id);
    });

    $('#btnCloseDrawer, #drawer-backdrop').click(function () {
        closeDrawer();
    });

    function openDrawer(id) {
        $('#drawer-backdrop').fadeIn();
        $('#drawer-right').css('right', '0');

        // Load Details via AJAX
        $('#drawer-content').html('<h4 class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando detalles...</h4>');

        $.get('/notas-unificadas/' + id, function (res) {
            $('#drawer-content').html(res);
        }).fail(function () {
            $('#drawer-content').html('<p class="text-danger">Error al cargar detalle.</p>');
        });
    }

    function closeDrawer() {
        $('#drawer-right').css('right', '-500px');
        $('#drawer-backdrop').fadeOut();
    }
    // --- INLINE EDITING ---
    $(document).on('click', '.estado-badge', function (e) {
        e.stopPropagation(); // Prevent row click
        let span = $(this);
        let current = span.text().trim();
        let id = span.data('id');

        // Prevent double click
        if (span.hasClass('editing')) return;
        span.addClass('editing');

        let select = $('<select class="form-control input-sm" style="width:110px; display:inline-block; padding: 2px;"><option value="PENDIENTE">PENDIENTE</option><option value="INICIO">INICIO</option><option value="NOTIFICADO">NOTIFICADO</option><option value="RESP. CASINO">RESP. CASINO</option><option value="FINALIZADO">FINALIZADO</option></select>');
        select.val(current);

        span.replaceWith(select);
        select.focus();

        function saveAndClose() {
            let newVal = select.val();
            let color = getBalanceColor(newVal);
            let newSpan = $(`<span class="label label-${color} estado-badge" data-id="${id}" data-toggle="popover" data-trigger="hover">${newVal}</span>`);
            select.replaceWith(newSpan);

            if (newVal !== current) {
                // AJAX Update
                $.post('/notas-unificadas/quick-update', {
                    _token: $('input[name="_token"]').val(),
                    id: id,
                    field: 'estado',
                    value: newVal
                }, function (res) {
                    notificacion('success', 'Estado actualizado.');
                }).fail(function () {
                    notificacion('error', 'Error al actualizar.');
                    newSpan.text(current); // Revert visual
                });
            }
        }

        select.on('blur', saveAndClose);
        select.on('keydown', function (e) {
            if (e.key === 'Enter') { $(this).blur(); }
        });
    });

    function getBalanceColor(status) {
        if (status === 'FINALIZADO') return 'success';
        if (status === 'PENDIENTE') return 'warning';
        if (status === 'INICIO') return 'primary';
        return 'default';
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

        if (!confirm('¿Está seguro de eliminar las ' + ids.length + ' notas seleccionadas?')) return;

        $.ajax({
            url: '/notas-unificadas/eliminar-masivo',
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(), // Ensure this exists on main page too
                ids: ids
            },
            success: function (res) {
                notificacion('success', res.mensaje || 'Notas eliminadas');
                // Reload table
                ajaxLoadTable();
                $('#bulkToolbar').fadeOut();
                $('#checkAll').prop('checked', false);
            },
            error: function (err) {
                notificacion('error', 'Error al eliminar notas.');
            }
        });
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
            if (!confirm('¿Eliminar esta nota?')) return;
            $.ajax({
                url: '/notas-unificadas/eliminar/' + id,
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').val() },
                success: function () { ajaxLoadTable(); notificacion('success', 'Eliminado'); }
            });
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

        // Reset logic
        gridState.q = '';
        $('#inpBusqueda').val('');
        gridState.page = 1;

        if (filter === 'pendientes') {
            gridState.q = 'PENDIENTE';
            $('#inpBusqueda').val('PENDIENTE');
        } else if (filter === 'hoy') {
            gridState.quick_filter = 'hoy';
        } else if (filter === 'eventos_activos') {
            gridState.tipo = 'EVENTO';
            $('#selFiltroTipo').val('EVENTO');
            gridState.q = 'INICIO'; // Assume status INICIO
        } else if (filter === 'reset') {
            gridState.id_casino = '';
            $('#selFiltroCasino').val('');
            gridState.tipo = '';
            $('#selFiltroTipo').val('');
            gridState.quick_filter = '';
        }

        ajaxLoadTable();
    });

    // --- TIMELINE TOOLTIP ---
    // Initialize standard popovers
    $('[data-toggle="popover"]').popover();

    // Hover logic for 'estado-badge' to clear old and fetch new
    // We delegate using 'body' because badges are dynamic
    $('body').popover({
        selector: '.estado-badge',
        trigger: 'hover',
        html: true,
        placement: 'bottom',
        content: function () {
            let id = $(this).data('id');
            // If we already have content, return it
            if ($(this).data('cached-content')) {
                return $(this).data('cached-content');
            }

            // Otherwise show spinner and fetch
            let $el = $(this);
            $.get('/movimientos/' + id, function (res) {
                let html = '<ul style="padding-left:15px; margin-bottom:0;">';
                res.forEach(m => {
                    html += `<li><small><b>${m.fecha}</b>: ${m.estado}</small></li>`;
                });
                html += '</ul>';

                $el.data('cached-content', html);

                // If still hovering, update the popover
                let popover = $el.data('bs.popover');
                if (popover && popover.tip().hasClass('in')) {
                    popover.options.content = html;
                    popover.setContent();
                    popover.$tip.addClass(popover.options.placement);
                }
            });
            return '<i class="fa fa-spinner fa-spin"></i> Cargando historial...';
        }
    });

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

    // --- PRESENCE SYSTEM ---
    function updatePresence() {
        // Send heartbeat
        let token = $('input[name="_token"]').val();
        if (!token) return; // Prevent 500 if token missing

        $.post('/notas-unificadas/presence/heartbeat', {
            _token: token,
            ubicacion: 'Listado Notas'
        });

        // Get list
        $.get('/notas-unificadas/presence/list', function (users) {
            let html = '';
            users.forEach(u => {
                // Generate initials
                let initials = u.user_name.substring(0, 2).toUpperCase();
                let color = '#' + (Math.random() * 0xFFFFFF << 0).toString(16); // Random color for now or hash username

                html += `
                    <div title="${u.user_name}" style="
                        display:inline-flex; 
                        align-items:center; 
                        justify-content:center; 
                        width:35px; height:35px; 
                        border-radius:50%; 
                        background:${color}; 
                        color:white; 
                        font-weight:bold; 
                        margin-right:-10px; 
                        border: 2px solid white;
                        cursor:help;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                        font-size: 12px;
                    ">
                        ${initials}
                    </div>
                `;
            });

            // Container for avatars (Create if not exists)
            if ($('#presenceContainer').length === 0) {
                $('.tituloSeccion').parent().append('<div id="presenceContainer" style="float:right; padding-right:20px; display:flex;"></div>');
            }
            $('#presenceContainer').html(html);
        });
    }

    // Poll every 15 seconds
    setInterval(updatePresence, 15000);
    setTimeout(updatePresence, 2000); // Initial call

    // --- PHASE 4: AUTO-SAVE REMOVED AS REQUESTED ---
    /*
    const STORAGE_KEY = 'draft_nueva_nota';
    ... (Disabled)
    */

    // --- FINALIZAR ---
    window.wizardFinish = function () {
        // Just close and refresh, as the note was already "saved" in Step 2/3 (Create Draft)
        // Ideally we should update status to 'FINALIZADO' or similar if needed.
        // For now, reload to show in dashboard.
        $('#modalNuevaNota').modal('hide');
        notificacion('success', 'Trámite finalizado exitosamente.');
        setTimeout(() => location.reload(), 1000);
    };

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
        if (tipoTarea == 'FISCALIZACION' || tipoSolicitud == 'EVENTO') {
            let inicio = $('#inpFechaInicio').val();
            let fin = $('#inpFechaFin').val();
            let tipoEvento = $('#selTipoEventoLegacy option:selected').text();

            html += `
                <div class="alert alert-info" style="background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1;">
                    <strong><i class="fa fa-calendar"></i> Evento:</strong> ${tipoEvento}<br>
                    <span style="font-size:13px; margin-left:20px;">Del <b>${inicio}</b> al <b>${fin}</b></span>
                </div>
             `;
        }

        $('#step4Content').html('<h4 class="text-center" style="margin-bottom:20px; font-weight:700; color:#475569;">Resumen de la Solicitud</h4>' + html + '<div class="alert alert-success text-center"><i class="fa fa-info-circle"></i> Verifique que todos los datos sean correctos antes de confirmar.</div>');
    }

    // =====================================================
    // DELETE HANDLERS
    // =====================================================

    // Single Delete Button (Action Column)
    $(document).on('click', '.btn-borrar-nota', function (e) {
        e.preventDefault();
        let id = $(this).data('id');
        let row = $(this).closest('tr');

        if (!confirm('¿Está seguro de eliminar esta nota? Esta acción no se puede deshacer.')) {
            return;
        }

        $.ajax({
            url: '/notas-unificadas/eliminar/' + id,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
            success: function (res) {
                if (res.success) {
                    notificacion('success', 'Nota eliminada correctamente');
                    row.fadeOut(300, function () { $(this).remove(); });
                } else {
                    notificacion('error', res.msg || 'Error al eliminar');
                }
            },
            error: function (err) {
                notificacion('error', 'Error al eliminar la nota');
                console.error(err);
            }
        });
    });

    // Context Menu Delete Action
    $(document).on('click', '.ctx-action[data-action="eliminar"]', function (e) {
        e.preventDefault();
        let id = $(this).closest('#custom-context-menu').data('target-id');
        let row = $('tr[data-id="' + id + '"]');

        $('#custom-context-menu').hide();

        if (!id || !confirm('¿Está seguro de eliminar esta nota?')) {
            return;
        }

        $.ajax({
            url: '/notas-unificadas/eliminar/' + id,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
            success: function (res) {
                if (res.success) {
                    notificacion('success', 'Nota eliminada');
                    row.fadeOut(300, function () { $(this).remove(); });
                } else {
                    notificacion('error', res.msg || 'Error al eliminar');
                }
            },
            error: function (err) {
                notificacion('error', 'Error al eliminar');
            }
        });
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

        if (!confirm('¿Está seguro de eliminar ' + ids.length + ' nota(s)? Esta acción no se puede deshacer.')) {
            return;
        }

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
        let grupoRow = $(this).closest('tr');
        let childRows = $('tr.nota-hija[data-parent-grupo="' + grupoId + '"]');

        if (!confirm('¿Está seguro de eliminar este grupo y TODAS sus notas asociadas? Esta acción no se puede deshacer.')) {
            return;
        }

        $.ajax({
            url: '/notas-unificadas/eliminar-grupo/' + grupoId,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() },
            success: function (res) {
                if (res.success) {
                    notificacion('success', 'Grupo eliminado correctamente');
                    childRows.fadeOut(200);
                    grupoRow.fadeOut(300, function () {
                        childRows.remove();
                        $(this).remove();
                    });
                } else {
                    notificacion('error', res.msg || 'Error al eliminar');
                }
            },
            error: function (err) {
                notificacion('error', 'Error al eliminar el grupo');
            }
        });
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
        $('#grupoInfoPanel, #grupoResumenPanel').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
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
                    ' &nbsp;|&nbsp; <i class="fa fa-tag"></i> ' + (res.grupo.tipo_tarea || 'N/A') +
                    ' &nbsp;|&nbsp; <i class="fa fa-calendar"></i> ' + (res.grupo.created_at || 'N/A')
                );

                // Tab Grupo - Info
                $('#grupoInfoPanel').html(
                    '<h5 style="color:#333; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:5px;">Información del Grupo</h5>' +
                    '<table class="table table-condensed" style="margin:0">' +
                    '<tr><td><strong>ID:</strong></td><td>' + res.grupo.id + '</td></tr>' +
                    '<tr><td><strong>Tipo:</strong></td><td>' + (res.grupo.tipo_tarea || 'N/A') + '</td></tr>' +
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
                if (res.fisc) {
                    resumenHtml += '<li style="padding:8px; background:#d1fae5; border-radius:6px">' +
                        '<i class="fa fa-gavel text-success"></i> <strong style="color:#064e3b;">Fiscalización:</strong> ' +
                        '<span class="label label-' + getEstadoClass(res.fisc.estado) + '">Estado: ' + res.fisc.estado + '</span>' +
                        '</li>';
                }
                resumenHtml += '</ul>';
                $('#grupoResumenPanel').html(resumenHtml);

                // Tab MKT
                if (res.mkt) {
                    $('#mktContenido').html(renderizarNotaDetalle(res.mkt, '#3b82f6'));
                    $('#tabMktLi').show();
                } else {
                    $('#mktContenido').html('<p class="text-muted text-center">No hay nota de Marketing asociada.</p>');
                    $('#tabMktLi').hide();
                }

                // Tab FISC
                if (res.fisc) {
                    $('#fiscContenido').html(renderizarNotaDetalle(res.fisc, '#10b981'));
                    $('#tabFiscLi').show();
                } else {
                    $('#fiscContenido').html('<p class="text-muted text-center">No hay nota de Fiscalización asociada.</p>');
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
        $('#grupoInfoPanel, #grupoResumenPanel').html('<p class="text-muted text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</p>');
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
                } else {
                    $('#fiscContenido').html(contenidoHtml);
                }
            } else {
                notificacion('error', res.msg || 'Error al cargar nota');
            }
        }).fail(function () {
            notificacion('error', 'Error de conexión');
        });
    }

    // Renderizar contenido de una nota
    function renderizarNotaDetalle(nota, color) {
        let template = $('#templateNotaDetalle').html();

        // Replace placeholders
        let html = template
            .replace(/{{id}}/g, nota.id)
            .replace(/{{color}}/g, color)
            .replace(/{{tipo_rama}}/g, nota.tipo_rama)
            .replace(/{{nro_nota}}/g, nota.nro_nota || 'N/A')
            .replace(/{{tipo_solicitud}}/g, nota.tipo_solicitud || 'N/A')
            .replace(/{{descripcion}}/g, nota.descripcion || 'Sin descripción')
            .replace(/{{fecha_inicio}}/g, nota.fecha_inicio || 'N/A')
            .replace(/{{fecha_fin}}/g, nota.fecha_fin || 'N/A')
            .replace(/{{estado}}/g, nota.estado || 'INGRESADO')
            .replace(/{{estadoClass}}/g, getEstadoClass(nota.estado))
            .replace(/{{adjuntosHtml}}/g, renderAdjuntos(nota.adjuntos, nota.id, nota.tipo_rama))
            .replace(/{{activosHtml}}/g, renderActivos(nota.activos))
            .replace(/{{comentariosHtml}}/g, renderComentarios(nota.movimientos))
            .replace(/{{historialHtml}}/g, renderHistorial(nota.movimientos));

        return html;
    }

    // Helper para clase de estado
    function getEstadoClass(estado) {
        if (!estado) return 'default';
        estado = estado.toUpperCase();
        if (estado.includes('APROB') || estado.includes('COMPLET')) return 'success';
        if (estado.includes('RECHAZ') || estado.includes('CANCEL')) return 'danger';
        if (estado.includes('PROCESO') || estado.includes('REVIS')) return 'warning';
        if (estado.includes('INGRES')) return 'info';
        return 'default';
    }

    // Renderizar adjuntos
    function renderAdjuntos(adjuntos, notaId, tipoRama) {
        if (!adjuntos) return '<p class="text-muted">No hay adjuntos</p>';

        let campos = tipoRama === 'MKT'
            ? [['solicitud', 'Solicitud', 'fa-file-pdf-o'], ['diseno', 'Diseño', 'fa-image'], ['bases', 'Bases', 'fa-file-text-o'], ['informe', 'Informe', 'fa-clipboard']]
            : [['solicitud', 'Solicitud', 'fa-file-pdf-o'], ['varios', 'Archivos Varios', 'fa-archive'], ['informe', 'Informe', 'fa-clipboard']];

        let html = '';
        campos.forEach(function (c) {
            let key = c[0], label = c[1], icon = c[2];
            let adj = adjuntos[key];

            if (adj && adj.existe) {
                html += '<div style="display:flex; align-items:center; padding:8px; background:#d1fae5; border-radius:6px; margin-bottom:6px">' +
                    '<i class="fa ' + icon + ' text-success" style="margin-right:8px"></i>' +
                    '<span style="flex:1">' + label + ': <strong>' + adj.nombre + '</strong></span>' +
                    '<a href="/storage/' + adj.path + '" target="_blank" class="btn btn-xs btn-info" title="Ver"><i class="fa fa-eye"></i></a> ' +
                    '<button class="btn btn-xs btn-danger btn-eliminar-adjunto" data-id="' + notaId + '" data-campo="path_' + key + '" title="Eliminar"><i class="fa fa-trash"></i></button>' +
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

    // Renderizar activos
    function renderActivos(activos) {
        if (!activos || activos.length === 0) return '<p class="text-muted text-center" style="padding:10px"><i class="fa fa-info-circle"></i> No hay activos asociados a esta nota</p>';

        let html = '<ul style="list-style:none; padding:0; margin:0">';
        activos.forEach(function (act) {
            let tipoLabel = act.tipo_activo || 'ISLA';
            let tipoClass = tipoLabel === 'MESA' ? 'label-success' : 'label-info';
            html += '<li style="padding:8px 10px; border-bottom:1px solid #eee; display:flex; align-items:center">' +
                '<i class="fa fa-gamepad" style="margin-right:8px; color:#8b5cf6"></i> ' +
                '<span class="label ' + tipoClass + '" style="margin-right:8px">' + tipoLabel + '</span> ' +
                '<strong>' + (act.id_activo || 'N/A') + '</strong>' +
                '</li>';
        });
        html += '</ul>';
        return html;
    }

    // Renderizar comentarios (solo movimientos tipo COMENTARIO)
    function renderComentarios(movimientos) {
        if (!movimientos || movimientos.length === 0) return '<p class="text-muted text-center" style="padding:20px">No hay comentarios aún</p>';

        let comentarios = movimientos.filter(function (m) { return m.accion === 'COMENTARIO'; });
        if (comentarios.length === 0) return '<p class="text-muted text-center" style="padding:20px">No hay comentarios aún</p>';

        let html = '';
        comentarios.forEach(function (c) {
            html += '<div style="padding:8px; background:#fce7f3; border-radius:6px; margin-bottom:6px">' +
                '<strong>' + c.usuario + '</strong> <small class="text-muted">(' + c.fecha + ')</small><br>' +
                '<span>' + c.comentario + '</span>' +
                '</div>';
        });
        return html;
    }

    // Renderizar historial
    function renderHistorial(movimientos) {
        if (!movimientos || movimientos.length === 0) return '<p class="text-muted text-center">Sin historial</p>';

        let html = '';
        movimientos.forEach(function (m) {
            let iconClass = 'fa-circle text-muted';
            if (m.accion.includes('INGRES')) iconClass = 'fa-plus-circle text-success';
            else if (m.accion.includes('EDIT')) iconClass = 'fa-pencil text-info';
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
                    // Add comment to list
                    let $lista = $('.comentarios-lista[data-id="' + notaId + '"]');
                    $lista.prepend(
                        '<div style="padding:8px; background:#fce7f3; border-radius:6px; margin-bottom:6px">' +
                        '<strong>' + res.movimiento.usuario + '</strong> <small class="text-muted">(' + res.movimiento.fecha + ')</small><br>' +
                        '<span>' + res.movimiento.comentario + '</span>' +
                        '</div>'
                    );
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

    // Eliminar adjunto
    $(document).on('click', '.btn-eliminar-adjunto', function () {
        let notaId = $(this).data('id');
        let campo = $(this).data('campo');
        let $row = $(this).closest('div');

        if (!confirm('¿Está seguro de eliminar este adjunto?')) return;

        $.ajax({
            url: '/notas-unificadas/eliminar-adjunto/' + notaId + '/' + campo,
            type: 'DELETE',
            data: { _token: $('input[name="_token"]').first().val() },
            success: function (res) {
                if (res.success) {
                    $row.css('background', '#fee2e2').find('strong').html('<em>Eliminado</em>');
                    $row.find('.btn-eliminar-adjunto, .btn-info').remove();
                    notificacion('success', 'Adjunto eliminado');
                } else {
                    notificacion('error', res.msg || 'Error');
                }
            },
            error: function () {
                notificacion('error', 'Error de conexión');
            }
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
        $panel.find('.editable').each(function () {
            let $span = $(this);
            let field = $span.data('field');
            let currentValue = $span.text().trim();
            if (currentValue === 'N/A') currentValue = '';

            let inputHtml = '';
            if (field === 'descripcion') {
                inputHtml = '<textarea class="form-control edit-input" data-field="' + field + '" rows="2" style="font-size: 13px;">' + currentValue + '</textarea>';
            } else if (field === 'fecha_inicio' || field === 'fecha_fin') {
                inputHtml = '<input type="date" class="form-control edit-input" data-field="' + field + '" value="' + currentValue + '" style="font-size: 13px;">';
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
                        let field = $(this).data('field');
                        let value = $(this).val() || 'N/A';
                        $(this).prev('.editable').text(value).show();
                        $(this).remove();
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

            if (g.tipo_solicitud) {
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

});
